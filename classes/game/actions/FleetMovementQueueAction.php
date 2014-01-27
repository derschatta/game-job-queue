<?php
require_once CLASS_PATH . '/game/actions/AbstractAction.php';
require_once CLASS_PATH . '/game/events/Events.php';
require_once CLASS_PATH . '/game/fleets/FleetPeer.php';
require_once CLASS_PATH . '/game/fleets/FleetAction.php';

/**
 * class for creating an building from the building queue
 * uses run to start the action
 *
 * @author Fabian Derschatta <fabian@derschatta.de>
 * @package game
 * @subpackage actions
 */
class FleetMovementQueueAction extends AbstractAction {
    /**
     * database object
     *
     * @var object $db
     */
    public $db;
    
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
     * @access public
     * @param mix $event
     *            - event id or event object with loaded event
     */
    public function run($event) {
        // initiate new event object
        $events = new Events();
        // create new object with factory
        $queue = QueueFactory::create("FleetMovement");
        // get building queue entry
        $movement_queue = $queue->get($event['event_type_id']);
        // get fleet of current movement
        $fleet = FleetPeer::load($movement_queue['fleet_id']);
        // if it's an attack
        if ($movement_queue['fleet_movement_type_id'] == 1) {
            // create new action for fleet
            $action = new FleetAction($fleet);
            // 1. run attack
            // a) calculate result of attack
            // b) subtract lost units from base / fleet of participated players
            // c) write into attack_log to show result to player
            $result = $action->attackSector($fleet->getSector($fleet->getTargetMapId()));
            // if everything was right
            if ($result) {
                // get home base
                $base = $fleet->getHomeBase();
                // source is current target
                $source_map_id = $movement_queue['target_map_id'];
                // target is map_id of base of fleet
                $target_map_id = $base->getValue('map_id');
                $fleet->setSourceMapId($source_map_id);
                $fleet->setTargetMapId($target_map_id);
                // 2. send rest of fleet back home => create new queue and event entry
                $fleet->move(3);
            }
            // if it's a reinforcement
        } elseif ($movement_queue['fleet_movement_type_id'] == 2) {
            // create new fleet action
            $action = new FleetAction($fleet);
            // reinforce
            $result = $action->reinforceSector($fleet->getSector($fleet->getTargetMapId()));
            // if its a welcome home thingy
        } elseif ($movement_queue['fleet_movement_type_id'] == 3) {
            // create new fleet action
            $action = new FleetAction($fleet);
            // welcome back! Yeay!
            $result = $action->welcomeBack();
        }
        // cleanup
        // $queue->delete($event['event_type_id']);
        // $events->delete($event['id']);
        
        if ($event['execdate'] != time()) {
            $log = Registry::getModule('Log');
            $log->log("Time's not right: " . $event['execdate'] . " -> " . time() . " => " . serialize($event) . "\n");
        }
    }
}
?>