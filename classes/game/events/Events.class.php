<?php

/**
 * class for handling all event actions
 * 
 * @author highmad
 * @package game
 * @subpackage events
 */
class Events {
    const TYPE_BUILDING = 1;
    const TYPE_UNIT = 2;
    const TYPE_RESEARCH = 3;
    const TYPE_MOVEMENT = 4;
    
    /**
     * database object
     * 
     * @var object
     */
    private $db;
    
    /**
     * values of the loaded events
     * 
     * @var array
     */
    public $event_values;
    
    /**
     * constructor of class
     */
    public function __construct() {
        // get db object from registry
        $this->db = Registry::getModule("db");
    }
    
    /**
     * fetch newest entries from event table
     *
     * @access public
     * @param array $data            
     * @return object Recordset
     */
    public function fetch() {
        // get all current events
        $dbQuery = "SELECT events.id, events.initdate, events.execdate, events.event_type, events.event_type_id, events_types.type, events_types.table, events.process_hash FROM events
					LEFT JOIN events_types ON events.event_type = events_types.id
					LEFT JOIN event_locks ON events.id = event_locks.event_id
        			WHERE events.execdate <= " . time() . "
        			AND events.status = 100
					#AND process_hash = ''
					AND event_locks.event_id IS NULL
        			ORDER BY events.execdate ASC
					LIMIT 20
					FOR UPDATE";
        // execute sql query and get Recordset
        return $this->db->_Execute($dbQuery);
    }
    
    /**
     * lock specific event for use by daemon with process_hash
     *
     * @access public
     * @param int $eventid
     *            - id of event to be locked
     * @param string $process_hash
     *            - hash value of current process for later identification
     * @return bool
     */
    public function lock($eventid, $process_hash) {
        // update event with hash value of current process, takeover ownership of event
        // $dbQuery = "UPDATE events SET status = 200, process_hash = '{$process_hash}' WHERE id = '$eventid' AND process_hash = ''";
        $dbQuery = "INSERT INTO event_locks (event_id, process_hash, timestamp) VALUES ($eventid, '$process_hash', NOW())";
        // execute sql query
        $this->db->_query($dbQuery, false);
        
        // if entry was updated
        if ($this->db->Affected_Rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * unlock specific event for use by daemon with process_hash
     *
     * @access public
     * @param int $eventid
     *            - id of event to be locked
     * @param string $process_hash
     *            - hash value of current process for later identification
     * @return bool
     */
    public function unlock($eventid) {
        // update event with hash value of current process, takeover ownership of event
        // $dbQuery = "UPDATE events SET status = 100, process_hash = '' WHERE id = '$eventid' AND process_hash = '{$process_hash}'";
        $dbQuery = "DELETE FROM event_locks WHERE event_id = '$eventid'";
        // execute sql query
        $this->db->_query($dbQuery, false);
        
        // if entry was updated
        if ($this->db->Affected_Rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * check if specific event is locked with current process_hash => check ownership
     *
     * @access public
     * @param int $eventid
     *            - id of event
     * @param string $process_hash
     *            - hash value of process to be checked
     * @return bool - if handshake was successful true, else false
     */
    public function checkLock($eventid) {
        // get event with hash value of current process, check ownership of event
        $dbQuery = "SELECT id FROM events WHERE id = '$eventid' AND process_hash = ''";
        
        // execute sql query and get RecordSet
        $rs = $this->db->_Execute($dbQuery);
        
        // if entry was found
        if ($rs->RecordCount() > 0) {
            // close recordset and free memory
            $rs->Close();
            return true;
        } else {
            // close recordset and free memory
            $rs->Close();
            return false;
        }
    }
    
    /**
     * return all event types in an hash array
     *
     * @access public
     * @return array $result - hash array with all event types
     */
    public function getEventTypes() {
        // get alls event types from table
        $dbQuery = "SELECT id, type, table FROM events_types";
        
        // execute sql query and get hash array
        $result = $this->db->GetAssoc($dbQuery);
        
        return $result;
    }
    
    /**
     * check if specific event is locked with current process_hash => check ownership
     *
     * @access public
     * @param int $eventid
     *            - id of event
     * @param string $process_hash
     *            - hash value of process to be checked
     * @return bool - if handshake was successful true, else false
     */
    public function doHandshake($eventid, $process_hash) {
        // get event with hash value of current process, check ownership of event
        $dbQuery = "SELECT event_id FROM event_locks WHERE event_id = '$eventid' AND process_hash = '$process_hash'";
        
        // execute sql query and get RecordSet
        $rs = $this->db->_Execute($dbQuery);
        
        // if entry was found
        if ($rs && $rs->RecordCount() > 0) {
            // close recordset and free memory
            $rs->Close();
            return true;
        } else {
            // close recordset and free memory
            if ($rs) {
                $rs->Close();
            }
            return false;
        }
    }
    
    /**
     * load all data of specified event from db and load it into object variable
     *
     * @access public
     * @param int $eventid
     *            id of event
     * @param int $processhash
     *            the hash of the event entry to load - optional
     *            
     * @return boolean if sql query was a success
     */
    public function load($eventid, $processhash = "") {
        // get all data of specific event
        $dbQuery = "SELECT events.id, events.initdate, events.execdate, events.event_type, events.event_type_id, events_types.type, events_types.table
        					FROM events
        					LEFT JOIN events_types ON events.event_type = events_types.id
        					WHERE events.id = " . $this->db->Quote($eventid);
        
        if ($processhash) {
            $dbQuery .= " AND events.process_hash = " . $this->db->Quote($processhash);
        }
        
        // get single row
        $result = $this->db->GetRow($dbQuery);
        
        // if there's a result
        if (! empty($result)) {
            // write result into class variable
            $this->event_values = $result;
            return true;
        }
        return false;
    }
    
    /**
     * return value of loaded event
     *
     * @access public
     * @param string $key
     *            - specific key of loaded event
     * @return string - value of loaded event and specific key
     */
    public function getValue($key) {
        return $this->event_values[$key];
    }
    
    /**
     * return all values of loaded event
     *
     * @access public
     * @return array - values of loaded event
     */
    public function getValues() {
        return $this->event_values;
    }
    
    /**
     * create new event entry in database
     *
     * @access public
     * @param array $data
     *            - array with all values which should be inserted into database
     * @return bool - true when successful
     */
    public function create($data) {
        
        // define default value for the initialdate
        if (! isset($data['initdate']) || empty($data['initdate'])) {
            $data['initdate'] = time();
        }
        
        // execdate has to be integer and a valid ix untimestamp
        if (! isset($data['execdate']) || empty($data['execdate'])) {
            return false;
        }
        
        // event_type must not be empty and has to be an integer value
        if (! isset($data['event_type']) || empty($data['event_type'])) {
            return false;
        }
        
        // event_type_id must not be empty and has to be an integer value
        if (! isset($data['event_type_id']) || empty($data['event_type_id'])) {
            return false;
        }
        
        // define default value for status
        if (! isset($data['status']) || empty($data['status'])) {
            $data['status'] = 100;
        }
        
        // define default value for active
        if (! isset($data['active']) || empty($data['active'])) {
            $data['active'] = 1;
        }
        
        // define default value for process_hash
        if (! isset($data['process_hash']) || empty($data['process_hash'])) {
            $data['process_hash'] = "";
        }
        
        // insert into the table events
        $sql_query = "INSERT INTO events (initdate, execdate, event_type, event_type_id, status, active, process_hash)
        						VALUES
        						('{$data['initdate']}', '{$data['execdate']}', '{$data['event_type']}', '{$data['event_type_id']}',
        						'{$data['status']}', '{$data['active']}', '{$data['process_hash']}')";
        // execute sql query
        $this->db->_query($sql_query, false);
        
        // if entry was created
        if ($this->db->Affected_Rows() > 0) {
            // return id of new entry
            return $this->db->Insert_ID();
        } else {
            return false;
        }
    }
    
    /**
     * deletes an event from the database
     *
     * @access public
     * @param int $eventid
     *            - id of event to be deleted
     * @return bool - true if deletion was succesful, false otherwise
     */
    public function delete($eventid) {
        
        // if eventid is not correct
        if (! isset($eventid) || empty($eventid)) {
            return false;
        }
        
        // delete specified entry
        $sql_query = "DELETE FROM events WHERE id = " . $this->db->Quote($eventid);
        // execute sql query
        $this->db->_query($sql_query, false);
        
        // if entry was found
        if ($this->db->Affected_Rows() > 0) {
            $this->unlock($eventid);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * activates an event
     *
     * @access public
     * @param int $eventid
     *            - id of event to be activated
     * @return bool
     */
    public function activate($eventid) {
        // if eventid is not correct
        if (! isset($eventid) || empty($eventid) || ! is_int($eventid)) {
            return false;
        }
        
        // set active to 1
        $sql_query = "UPDATE events SET active = 1 WHERE eventid = {$eventid}";
        // execute sql query
        $this->db->_query($sql_query, false);
        
        // if entry was found
        if ($this->db->Affected_Rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * deactivates an event
     *
     * @access public
     * @param int $eventid
     *            - id of event to be deactivated
     * @return bool
     */
    public function deactivate($eventid) {
        // if eventid is not correct
        if (! isset($eventid) || empty($eventid) || ! is_int($eventid)) {
            return false;
        }
        
        // set active to 0
        $sql_query = "UPDATE events SET active = 0 WHERE eventid = {$eventid}";
        // execute sql query
        $this->db->_query($sql_query, false);
        
        // if entry was found
        if ($this->db->Affected_Rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
}