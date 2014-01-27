<?php
require_once CLASS_PATH . '/game/events/Events.php';
require_once CLASS_PATH . '/game/actions/ActionFactory.php';

require_once 'Net/Socket.php';

/**
 * class for handling the events table and redirect
 * the events to the specific event daemons
 *
 * @uthor Fabian von Derschatta <fabian@space-frontier.net>
 * 
 * @package game
 * @subpackage daemon
 * @see PEAR:Socket
 */
class EventHandler {
    
    /**
     * event object
     *
     * @var object
     */
    protected $events;
    
    /**
     * log object
     *
     * @var Log
     */
    protected $logger;
    
    /**
     * socket
     */
    protected $socket;
    
    /**
     * database object
     *
     * @var object
     */
    private $_db;
    
    /**
     * memcache object
     *
     * @var object
     */
    private $_memcache;
    
    /**
     * a unique process hash
     *
     * @var string
     */
    private $_process_hash;
    
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
    private $_password = 'test';
    
    /**
     * constructor of class, initiates the handler
     */
    public function __construct($log = null) {
        if ($log) {
            $this->logger = $log;
        }
        
        // get registry object, singleton
        $registry = Registry::setup();
        // get db object from registry
        $db = $registry->get("db");
        
        // force new db connect
        $db->NConnect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        
        $registry->register('db', $db);
        
        // get events object
        $this->events = new Events();
        
        // create new socket
        $this->socket = new Net_Socket();
        
        // $this->_createProcessHash();
        
        $this->_memcache = new Memcache();
        $this->_memcache->connect('127.0.0.1', 11211);
        
        $this->logger->notice('EventHandler initiated');
    }
    
    /**
     * start the daemon, method overwritten
     *
     * @access public
     */
    public function start() {
        // get db object from registry
        $db = Registry::getModule("db");
        
        static $i = 0;
        $child = 0;
        $max = 20;
        $maxseen = 0;
        $totseen = 0;
        
        $children = array(); // Create an array which will contain the PIDs of the children
        
        while (true) {
            
            // When children die, this gets rid of the zombies
            while (pcntl_wait($status, WNOHANG or WUNTRACED) > 0) {
                usleep(5000);
            }
            // We want to handle the death of the children, ie: getting them out of the $children array.
            while (list ($key, $val) = each($children)) {
                // This detects if the child is still running or not
                if (! posix_kill($val, 0)) {
                    unset($children[$key]);
                    $child = $child - 1;
                }
            }
            
            if ($child >= $max) {
                usleep(5000);
                continue;
            }
            
            // Reindex the array
            $children = array_values($children);
            
            $child ++;
            $totseen ++;
            if ($child > $maxseen) {
                $maxseen = $child;
            }
            
            // fork new children process
            $pid = pcntl_fork();
            if ($pid == - 1) {
                // Something went wrong (handle errors here)
                die("Could not fork!");
            } elseif ($pid == 0) {
                unset($children);
                $this->_createProcessHash();
                // do the task the daemon is written for
                $this->doTask();
                exit();
            } else {
                // force new connection to db for parent process
                // $db->NConnect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
                // Push the PID of the created child into $children
                $children[] = $pid;
                usleep(5000);
            }
            
            $i ++;
        }
    }
    
    /**
     * do the task this daemon is written for, method overwritten
     *
     * @access private
     */
    protected function doTask() {
        
        // get config object from registry
        $config = Registry::getModule("config");
        
        // get db object from registry
        $db = Registry::getModule("db");
        
        // if not connected any more
        // if (!$db->IsConnected()) {
        // reconnect
        $db->NConnect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        // $this->logger->notice('Warning: Lost connection to database. Reconnected!');
        // }
        
        // $db->StartTrans();
        
        // fetch current events from the database
        $result = $this->events->fetch();
        
        // get hash value of current process
        $process_hash = $this->getProcessHash();
        
        // if there are records in the recordset
        if ($result) {
            
            // fetch row into array
            while (! $result->EOF) {
                
                $row = $result->fields;
                
                // if the current event could be locked
                if ($this->events->lock($row['id'], $process_hash)) {
                    
                    $this->logger->notice('Event ' . $row['id'] . ' found, successfully locked with ' . $process_hash);
                    
                    // check if the current event is owned by the current process
                    if ($this->events->doHandshake($row['id'], $process_hash)) {
                        // store process hash in cache too
                        $row['process_hash'] = $process_hash;
                        
                        $this->logger->notice('Event ' . $row['id'] . ' ownership correct, send to ActionServer');
                        
                        $success = true;
                        $connection = "";
                        $written_id = "";
                        
                        $connection = $this->socket->connect($config->get('action_server_host'), $config->get('action_server_port'));
                        
                        // open connection
                        if (Pear::isError($connection)) {
                            $this->logger->error('Couldn\'t establish connection to the ActionServer!');
                            $success = false;
                        } else {
                            
                            // first authenticate
                            $this->socket->writeLine($this->_username);
                            $this->socket->writeLine($this->_password);
                            $this->socket->writeLine($process_hash);
                            
                            // cache the event
                            $this->_memcache->set($row['id'], $row, 900);
                            
                            $written_id = $this->socket->writeLine($row['id']);
                            
                            // send data including linebreak
                            if (Pear::isError($written_id)) {
                                $this->logger->error('Couldn\'t send event ' . $row['id'] . ' to ActionServer!');
                                $success = false;
                            }
                            // close connection
                            $this->socket->disconnect();
                        }
                        
                        if (! $success) {
                            $this->logger->notice('Unlock the event ' . $row['id']);
                            // unlock the event
                            $this->events->unlock($row['id'], $process_hash);
                        }
                    }
                }
                
                $result->MoveNext();
            }
            
            // close recordset and free memory
            $result->Close();
        }
        
        // $db->CompleteTrans();
        
        $db->Close();
    }
    
    /**
     * returns the current process hash
     *
     * @return string
     */
    protected function getProcessHash() {
        return $this->_process_hash;
    }
    
    /**
     * create a unique hash value for this process for later locks
     *
     * @return void
     */
    private function _createProcessHash() {
        $process_hash = md5(uniqid(microtime()));
        $this->_process_hash = $process_hash;
    }
}
?>
