<?php
require_once CLASS_PATH . '/game/actions/AbstractAction.php';
require_once CLASS_PATH . '/game/events/Events.php';
require_once CLASS_PATH . '/game/queues/QueueFactory.php';
require_once CLASS_PATH . '/game/base/Base.php';
require_once CLASS_PATH . '/game/fleets/Unit.php';

/**
 * class for creating an building from the building queue
 * uses run to start the action
 *
 * @author Fabian von Derschatta <fabian@space-frontier.net>
 * @package game
 * @subpackage actions
 */
class UnitQueueAction extends AbstractAction {
    /**
     * database object
     *
     * @var object $db
     */
    private $db;
    
    /**
     * constructor of class
     *
     * @param object $db
     *            - database object
     */
    public function __construct() {
        $this->db = Registry::getModule("db");
    }
    
    /**
     * method for starting the action
     *
     * @param mix $event
     *            - event id or event object with loaded event
     */
    public function run($event) {
        
        // initiate new event object
        $events = new Events();
        
        // create new object with factory
        $queue = QueueFactory::create("Unit");
        
        // get building queue entry
        $unit_queue = $queue->get($event['event_type_id']);
        
        // if there's an building_queue entry
        if (is_array($unit_queue)) {
            
            // get the build time for this unit
            // $unit_build_time = Unit :: getTrainTime($unit_queue['unit_id']);
            
            // alternative: get difference between event exec_time and init_time
            $unit_build_time = $event['execdate'] - $event['initdate'];
            
            // build unit
            if (Unit::build($unit_queue['unit_id'], $unit_queue['base_id'])) {
                
                // modify the unit_queue
                $queue->buildUnits($event['event_type_id'], $unit_build_time);
                
                // if theres still more than two units to build
                if ($unit_queue['amount'] > 1) {
                    
                    // data for the new event
                    $new_event_data['initdate'] = $event['initdate'];
                    $new_event_data['execdate'] = $event['execdate'] + $unit_build_time;
                    $new_event_data['event_type'] = $event['event_type'];
                    $new_event_data['event_type_id'] = $event['event_type_id'];
                    
                    // create new event entry with new exectime
                    $events->create($new_event_data);
                } else {
                    
                    // delete unit_queue entry
                    $queue->delete($event['event_type_id']);
                }
                
                // delete old event entry
                $events->delete($event['id']);
                
                if ($event['execdate'] != time()) {
                    $log = Registry::getModule('Log');
                    $log->log("Time's not right: " . $event['execdate'] . " -> " . time() . " => " . serialize($event) . "\n");
                }
            }
        }
    }
}
?>