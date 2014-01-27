#!/usr/bin/php
<?php
require_once CLASS_PATH . "/game/daemon/EventHandler.daemon.php";
require_once CLASS_PATH . "/lib/db/adodbSimple/adodb.inc.php";

$eventhandler = new EventHandler();

$eventhandler->start();

?>
