<?php
require_once CLASS_PATH . '/game/events/logs/EventLog.php';
require_once CLASS_PATH . '/game/fleets/FleetPeer.php';
/**
 * class for logging building buildings
 *
 * @package game
 * @subpackage events
 * @author Fabian Derschatta
 */
class MovementEventLog extends EventLog {
    const PLAYER_ROLE_ATTACKER = 1;
    const PLAYER_ROLE_DEFENDER = 2;
    const PLAYER_ROLE_REINFORCER = 3;
    
    /**
     * an array of units that are moving to the destination
     * ...there will only be 1 attacker in the array
     *
     * @var array
     */
    private $attackers = array();
    
    /**
     * an array of players of units defending a destination
     *
     * @var array
     */
    private $defenders = array();
    public function __construct($base_id) {
        parent::__construct($base_id, Events::TYPE_MOVEMENT);
    }
    public function logAttack($source_base_id) {
        $movement_id = $this->_logMovement($source_base_id, FleetPeer::MOVEMENT_TYPE_ATTACK);
        $this->_logAttackers($movement_id);
        $this->_logDefenders($movement_id);
    }
    public function logReinforce($source_base_id) {
        $movement_id = $this->_logMovement($source_base_id, FleetPeer::MOVEMENT_TYPE_REINFORCE);
        $this->_logReinfocers($movement_id);
    }
    public function addAttackers($attackers) {
        $this->attackers = $attackers;
    }
    public function addDefenders($defenders) {
        $this->defenders = $defenders;
    }
    private function _logMovement($source_base_id, $unit_movement_type_id) {
        if (! ($log_id = parent::log())) {
            return false;
        }
        $db = Registry::getModule("db");
        $query = "INSERT INTO event_log_unit_movements (
					  event_log_id,
			          source_base_id,
				      unit_movement_type_id
				  ) VALUES (
					  $log_id,
					  $source_base_id,
					  $unit_movement_type_id
				  )";
        $result = $db->query($query);
        if ($db->Affected_Rows() > 0) {
            return $db->Insert_ID();
        } else {
            return false;
        }
    }
    private function _logAttackers($movement_id) {
        foreach ($this->attackers as $player_id => $units) {
            $this->_logPlayerUnits($movement_id, $player_id, $units, self::PLAYER_ROLE_ATTACKER);
        }
    }
    private function _logDefenders($movement_id) {
        foreach ($this->defenders as $player_id => $units) {
            $this->_logPlayerUnits($movement_id, $player_id, $units, self::PLAYER_ROLE_DEFENDER);
        }
    }
    private function _logReinfocers($movement_id) {
        foreach ($this->attackers as $player_id => $units) {
            $this->_logPlayerUnits($movement_id, $player_id, $units, self::PLAYER_ROLE_REINFORCER);
        }
    }
    private function _logPlayerUnits($movement_id, $player_id, $units, $role) {
        $db = Registry::getModule("db");
        $query = "INSERT INTO event_log_unit_movement_players (
					  event_log_unit_movement_id,
			          player_id,
				      player_role
				  ) VALUES (
					  $movement_id,
					  $player_id,
					  $role
				  )";
        $result = $db->query($query);
        if ($db->Affected_Rows() > 0) {
            $movement_player_id = $db->Insert_ID();
            foreach ($units as $unit) {
                $query = "INSERT INTO event_log_unit_movements_player_units (
							  event_log_unit_movement_player_id,
					          unit_id,
						      amount,
							  casualties
						  ) VALUES (
							  $movement_player_id,
							  " . $unit['id'] . ",
							  " . $unit['amount'] . ",
							  " . $unit['casualties'] . "
						  )";
                $result = $db->query($query);
            }
            return true;
        } else {
            return false;
        }
    }
}
?>