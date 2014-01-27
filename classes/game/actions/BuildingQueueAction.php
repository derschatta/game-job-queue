<?php
require_once CLASS_PATH . '/game/actions/AbstractAction.php';
require_once CLASS_PATH . '/game/events/Events.php';
require_once CLASS_PATH . '/game/queues/QueueFactory.php';

require_once CLASS_PATH . '/game/base/Base.php';
require_once CLASS_PATH . '/game/base/Slot.php';

/**
 * class for creating an building from the building queue
 * uses run to start the action
 *
 * @author Fabian von Derschatta <fabian@derschatta.de>
 * @package game
 * @subpackage actions
 */
class BuildingQueueAction extends AbstractAction {
    /**
     * database object
     *
     * @var object $db
     */
    private $db;
    
    /**
     * constructor of class
     */
    public function __construct() {
        // get db object from registry
        $this->db = Registry::getModule("db");
    }
    
    /**
     * method for starting the action
     *
     * @access public
     * @param array $event
     *            - array with all event values
     */
    public function run($event) {
        $table = $event['table'];
        $eventid = $event['id'];
        $execdate = $event['execdate'];
        $event_type_id = $event['event_type_id'];
        
        // initiate new event object
        $events = new Events();
        
        // create new object with factory
        $queue = QueueFactory::create("Building");
        // get building queue entry
        $building_queue = $queue->get($event_type_id);
        
        // if there's an building_queue entry
        if (is_array($building_queue)) {
            
            // init objects
            $base = new Base($building_queue['base_id']);
            $slot = new Slot($base, $building_queue['slot_id']);
            
            // try to upgrade the building
            $upgraded = $slot->upgradeBuilding();
            
            // if upgrade was successful
            if ($upgraded) {
                // delete event entry
                $events->delete($eventid);
                // delete building queue entry
                $queue->delete($event_type_id);
            }
            
            if ($event['execdate'] != time()) {
                $log = Registry::getModule('Log');
                $log->log("Time's not right: " . $event['execdate'] . " -> " . time() . " => " . serialize($event) . "\n");
            }
        }
    }
}
?>