<?php
require_once CLASS_PATH . '/game/events/logs/EventLog.class.php';

/**
 * class for logging building buildings
 *
 * @package    game
 * @subpackage events
 * @author	   Fabian von Derschatta
 */
class BuildingEventLog extends EventLog {

	public function __construct($base_id) {
		parent::__construct($base_id, Events::TYPE_BUILDING);
	}

	/**
	 * log this event
	 *
	 * @param integer $slot_id     the id of the slot
	 * @param integer $building_id the id of the building
	 * @param integer $level       the new level
	 *
	 * @return boolean
	 */
	public function log($slot_id, $building_id, $level) {
		if (!($log_id = parent::log())) {
			return false;
		}
		$db = Registry :: getModule("db");
		$query = "INSERT INTO event_log_buildings (
					  event_log_id,
			          slot_id,
					  building_id,
				      level
				  ) VALUES (
					  ".$log_id.",
					  ".$slot_id.",
					  ".$building_id.",
					  ".$level."
				  )";
		$result = $db->query($query);
		if($db->Affected_Rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

}
?>