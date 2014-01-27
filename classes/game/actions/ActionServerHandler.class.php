<?php

/**
 * The ActionServerHander defines all actions used by the ActionServer,
 * start, connect, receive, close, etc.
 *
 * @package    game
 * @subpackage actions
 * @author     Fabian von Derschatta
 * @copyright  2008 space-frontier
 * @version    SVN: $Id:$
 * @see        PEAR:Net_Server_Handler, Net_Server
 */

// PEAR Handler classes
require 'Net/Server/Handler.php';

// required classes
require_once CLASS_PATH . '/game/actions/ActionFactory.class.php';
require_once CLASS_PATH . '/game/events/Events.class.php';

/**
 * Handler for the Action server. It implements the functionality of the server
 *
 * @author 		Fabian von Derschatta <fabian@space-frontier.net>
 * @package 	game
 * @subpackage 	actions
 */
class ActionServerHandler extends Net_Server_Handler
{
    /**
     * event object
     *
     * @var 	object
     * @access 	public
     */
    public $event;

	/**
	 * memcache object
	 *
	 * @var object
	 */
    private $_memcache;

	/**
	 * all allowed hosts
	 *
	 * @var array
	 */
	private $_allowedHosts = array('127.0.0.1');

	/**
	 * the user name
	 *
	 * @var string
	 */
	private $_username = 'test';

	/**
	 * the password
	 *
	 * @var string
	 */
	private $_password = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';

	/**
	 * the process hash
	 *
	 * @var string
	 */
	private $_processhash;

	/**
	 * is client authenticated
	 *
	 * @var boolean
	 */
	private $_authenticated = array(0, 0);

    /**
     * defines what happens if the clients sends data to the server
     *
     * @access   public
     * @param    integer $clientId
     * @param    string  $data
     */
    public function onReceiveData($clientId = 0, $data = "")
    {
    	$data = trim($data);

    	// not yet authenticated?
		if (in_array(0, $this->_authenticated)) {
			if (!$this->_authenticate($data)) {
				$this->_server->sendData('Authentication failed!');
				// close connection to the client
        		$this->_server->closeConnection();
			}
			return;
		}

		if (empty($this->_processhash)) {
			if (!is_numeric($data) && strlen($data) == 32) {
				$this->_processhash = $data;
				return false;
			} else {
				$this->_server->sendData('Expected Hash!');
				// close connection to the client
    			$this->_server->closeConnection();
			}
		}

        // assume that the data is the event id
        $eventid = (int) $data;

        // if there's an event id
        if ($eventid != "") {

			// if not found in cache
			if (!($event_values = $this->_memcache->get($eventid))) {
    	        // load current event into object
	            if (!$this->event->load($eventid, $this->_processhash)) {
	            	// close connection to the client
            		$this->_server->closeConnection();
    	        }
				// get value type
	            $type = $this->event->getValue('type');
	            // get all values into an array
	            $event_values = $this->event->getValues();
			} else {
				if ($event_values['process_hash'] != $this->_processhash) {
	            	// close connection to the client
            		$this->_server->closeConnection();
				}
				// use cache value
				$type = $event_values['type'];
			}

            // create the action object of the current type
            $action = ActionFactory :: create($type);
            // run the action
            $action->run($event_values);

        }
		// free memcache
		$this->_memcache->delete($eventid);

        // if the client is still connected
        if ($this->_server->isConnected($clientId)) {
            // close connection to the client
            $this->_server->closeConnection();
        }
    }

    /**
     * if the server starts
     *
     * @access public
     */
    public function onStart()
    {
        // do nothing special
    }

    /**
     * if a clients connects to the server
     *
     * @access	public
     * @param 	int 	$clientid
     */
    public function onConnect($clientId = 0)
    {
		$client_info = $this->_server->getClientInfo();

		if (!in_array($client_info['host'], $this->_allowedHosts)) {
			// close connection to the client
            $this->_server->closeConnection();
		} else {
			$this->_server->sendData('Please authenticate!');
		}

        // get registry object, singleton
        $registry = Registry :: setup();
        // get db object from registry
        $db = $registry->get("db");

        // connect to the db, force new connection
        $db->NConnect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

        // register newly connected db object again
        $registry->register("db", $db);

		$this->_memcache = new Memcache();
		$this->_memcache->connect('127.0.0.1', 11211);

        // create new events object for later use
        $this->event = new Events();
    }

    /**
     * if the connection of a client closes
     *
     * @access 	public
     * @param	int		$clientId
     */
    public function onClose($clientId = 0) {
        // get database object from registry
        $db = Registry :: getModule("db");
        // close database connection
        $db->Close();
    }

	/**
	 * try to authenticate session
	 *
	 * @param string $data
	 *
	 * @return boolean
	 */
	private function _authenticate($data) {
		$data = trim($data);
		if (!is_string($data)) {
			return false;
		}
		if ($this->_authenticated[0] === 0) {
			if ($data == $this->_username) {
				$this->_authenticated[0] = 1;
				return true;
			}
			$this->_authenticated[0] = 2;
			return false;
		} elseif ($this->_authenticated[1] === 0) {
			if ($this->_password == hash('sha256', $data)) {
				$this->_authenticated[1] = 1;
				return true;
			}
			$this->_authenticated[1] = 2;
			return false;
		}
		return true;
	}
}
?>