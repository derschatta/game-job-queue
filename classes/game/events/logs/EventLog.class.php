<?php
require_once CLASS_PATH . '/game/events/Events.class.php';

/**
 * abstract class for all EventLogs
 *
 * @package game
 * @subpackage events
 * @author Fabian von Derschatta
 */
abstract class EventLog {
    
    /**
     * the event type id, see the constants in the event class
     *
     * @var integer
     */
    private $event_type_id = 0;
    
    /**
     * the base id
     *
     * @var integer
     */
    private $base_id = 0;
    
    /**
     * an array of players involved in this event
     *
     * @var array
     */
    private $players = array();
    
    /**
     * array of resource amount and their ids
     *
     * @param
     *            array
     */
    private $resources = array();
    
    /**
     * init the log object
     *
     * @param integer $base_id
     *            the id of the base the event takes place
     * @param integer $event_type_id
     *            the type of the event log
     *            
     * @return EventLog
     */
    public function __construct($base_id, $event_type_id = 0) {
        $this->base_id = $base_id;
        $this->event_type_id = $event_type_id;
        $this->db = Registry::getModule("db");
    }
    
    /**
     * add a new player to log
     *
     * @param integer $player_id
     *            the id of the player
     *            
     * @return void
     */
    public function addPlayer($player_id) {
        if (! in_array($player_id, $this->players)) {
            $this->players[] = $player_id;
        }
    }
    
    /**
     * add resources stolen/transported in the action
     *
     * @param array $resources
     *            array of resources and their ids
     *            
     * @return void
     */
    public function addResources($resources) {
        $this->resources = $resources;
    }
    
    /**
     * log the main data
     *
     * @return boolean
     */
    public function log() {
        $query = "INSERT INTO event_log (
			          event_type_id,
					  base_id
				  ) VALUES (
					  " . $this->event_type_id . ",
					  " . $this->base_id . "
				  )";
        $result = $this->db->query($query);
        if ($this->db->Affected_Rows() > 0) {
            $event_log_id = $this->db->Insert_ID();
            $this->_logPlayers($event_log_id);
            $this->_logResources($event_log_id);
            return $event_log_id;
        } else {
            return false;
        }
    }
    
    /**
     * log all players
     *
     * @param integer $event_log_id
     *            the id of the new log entry
     *            
     * @return void
     */
    private function _logPlayers($event_log_id) {
        if (! empty($this->players)) {
            foreach ($this->players as $player_id) {
                if (is_numeric($player_id)) {
                    $query = "INSERT INTO event_log_players (
						          event_log_id,
								  player_id
							  ) VALUES (
								  " . $event_log_id . ",
								  " . $player_id . "
							  )";
                    $this->db->query($query);
                } else {
                    throw new RuntimeException('Wrong player id!');
                }
            }
        }
    }
    
    /**
     * log resources transported
     *
     * @param integer $event_log_id
     *            the id of the new log entry
     *            
     * @return void
     */
    private function _logResources($event_log_id) {
        if (is_array($this->resources) && ! empty($this->resources)) {
            foreach ($this->resources as $resource_id => $amount) {
                if (is_numeric($resource_id) && is_numeric($amount)) {
                    $query = "INSERT INTO event_log_resources (
						          event_log_id,
								  resource_id,
								  amount
							  ) VALUES (
								  " . $event_log_id . ",
								  " . $resource_id . ",
								  " . $amount . "
							  )";
                    $this->db->query($query);
                } else {
                    throw new RuntimeException('Wrong resource information!');
                }
            }
        }
    }
}
?>
