<?php
require_once CLASS_PATH . '/lib/daemon/Daemon.class.php';
require_once CLASS_PATH . '/game/events/Events.class.php';
require_once CLASS_PATH . '/game/actions/ActionFactory.class.php';

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
class EventHandler extends Daemon {
    
    /**
     * event object
     * 
     * @var object
     */
    protected $events;
    
    /**
     * path and filename of logfile
     * 
     * @var string
     */
    protected $logFile;
    
    /**
     * socket
     */
    protected $socket;
    
    /**
     * constructor of class, initiates the daemon
     */
    public function __construct() {
        $this->pidFileLocation = "/tmp/EventHandler.pid";
        $this->logFile = "/tmp/EventHandler.log";
        
        // calls constructor of inherited class
        parent::__construct();
        
        // if not existing create logfile
        $fp = fopen($this->logFile, 'a');
        fclose($fp);
        
        // make logfile writeable
        chmod($this->logFile, 0777);
    }
    
    /**
     * write log message into logfile, method overwritten
     *
     * @access private
     * @param string $msg
     *            - message for logfile
     * @param string $status
     *            - print out on screen of log into file
     */
    protected function logMessage($msg, $status = DLOG_NOTICE) {
        
        // if log message should be printed out
        if ($status & DLOG_TO_CONSOLE) {
            print $msg . "\n";
        }
        
        // open logfile for appending
        $fp = fopen($this->logFile, 'a');
        // write log message into logfile
        fwrite($fp, date("Y/m/d H:i:s ") . $msg . "\n");
        // close log file handle
        fclose($fp);
    }
    
    /**
     * do the task this daemon is written for, method overwritten
     *
     * @access private
     */
    protected function doTask() {
        static $i;
        
        // get config object from registry
        $config = Registry::getModule("config");
        
        // fetch current events from the database
        $result = $this->events->fetch();
        
        // get hash value of current process
        $process_hash = $this->getProcessHash();
        
        // if there are records in the recordset
        if ($result) {
            
            // for each record
            while (! $result->EOF) {
                
                // assign result of current row to array
                $row = $result->fields;
                
                // if the current event could be locked
                if ($this->events->lock($row['id'], $process_hash)) {
                    
                    // check if the current event is owned by the current process
                    if ($this->events->doHandshake($row['id'], $process_hash)) {
                        
                        // open connection
                        $this->socket->connect($config->get('action_server_host'), $config->get('action_server_port'));
                        // send data including linebreak
                        $this->socket->write($row['id']);
                        // close connection
                        $this->socket->disconnect();
                    }
                }
                
                // move on to next record in recordset
                $result->MoveNext();
            }
            
            // close recordset and free memory
            $result->Close();
        }
        
        $i ++;
    }
    
    /**
     * start the daemon, method overwritten
     *
     * @access public
     */
    public function start() {
        // if the daemon is already running
        if ($this->_isDaemonRunning()) {
            // stop the current process
            $this->stop();
        }
        
        // call start method of inherited class
        if (parent::start()) {
            // get registry object, singleton
            $registry = Registry::setup();
            // get db object from registry
            $db = $registry->get("db");
            
            // force new db connect
            $db->NConnect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
            
            // register db object again
            $registry->register("db", $db);
            
            // get events object
            $this->events = new Events();
            
            // create new socket
            $this->socket = new Net_Socket();
            
            /**
             *
             * @todo output just for debugging, remove later
             */
            echo $this->_pid;
            
            // start infinite loop
            while ($this->_isRunning) {
                // do the task the daemon is written for
                $this->doTask();
            }
        }
    }
}
?>
