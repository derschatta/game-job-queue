<?php
require_once CLASS_PATH . '/game/actions/AbstractAction.class.php';
require_once CLASS_PATH . '/game/events/Events.class.php';

/**
 * class for creating an building from the building queue
 * uses run to start the action
 *
 * @author Fabian von Derschatta <fabian@space-frontier.net>
 * @package game
 * @subpackage actions
 */
class ResearchQueueAction extends AbstractAction {
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
    public function __construct($db) {
        $this->db = Registry::getModule("db");
    }
    
    /**
     * method for starting the action
     *
     * @access public
     * @param mix $event
     *            - event id or event object with loaded event
     */
    public function run($event, $process_hash) {
        // initialize the event object
        // $this->_initEvent($event);
        
        // here all actions will take place
        // $table = $this->event->getValue('table');
        // $eventid = $this->event->getValue('id');
        // $execdate = $this->event->getValue('execdate');
        
        // $events = new Events();
        $table = $event['table'];
        $eventid = $event['id'];
        $execdate = $event['execdate'];
        
        // if($events->doHandshake($eventid, $process_hash)) {
        
        // only for testing: insert into test table
        $dbQuery = "INSERT INTO events_test_" . $table . " VALUES (" . $eventid . ", " . $execdate . ", " . time() . ")";
        // execute sql query
        $this->db->_query($dbQuery, false);
        
        // }
    }
}
?>