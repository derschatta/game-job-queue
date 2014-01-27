#!/usr/bin/php -q
<?php
// Include Class
error_reporting(E_ALL);
require_once "System/Daemon.php";

require_once CLASS_PATH . "/game/daemon/EventHandler.daemon2.php";
require_once CLASS_PATH . "/lib/db/adodbSimple/adodb.inc.php";

require_once 'Log.php';
$pear_log = Log::factory('file', dirname(__FILE__).'/EventHandler.log', 'EventHandler', array(), PEAR_LOG_NOTICE);  //PEAR_LOG_NOTICE

$options = array(
    "appName" 				=> "EventHandler",
    "appDir" 				=> dirname(__FILE__),
    "appDescription" 		=> "Get upcoming events and push them to the ActionServer",
    "authorName" 			=> "Fabian von Derschatta",
    "authorEmail" 			=> "fabian@vonderschatta.de",
    "sysMaxExecutionTime" 	=> "0",
    "sysMaxInputTime" 		=> "0",
    "sysMemoryLimit"		=> "1024M",
	"usePEARLogInstance"	=> $pear_log
);

System_Daemon::setOptions($options);

// Spawn Deamon!
System_Daemon::start();

System_Daemon::log(System_Daemon::LOG_INFO, "Daemon: '".
    System_Daemon::getOption("appName").
    "' spawned! This will be written to ".
    System_Daemon::getOption("logLocation"));

$eventhandler = new EventHandler($pear_log);
$eventhandler->start();

System_Daemon::stop();

?>