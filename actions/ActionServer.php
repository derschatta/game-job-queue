#!/usr/bin/php5
<?php

/**
 * The action server listens on port 9090.
 * It uses the ActionServerHandler
 * for executing actions send to this server.
 *
 * @package actions
 * @author Fabian von Derschatta
 * @copyright 2008 space-frontier
 * @version SVN: $Id:$
 * @see PEAR:Net_Server, ActionServerHandler
 */
require_once 'Log.php';
$pear_log = Log::factory('file', dirname(__FILE__) . '/ActionServer.log', 'ActionServer', array()); // PEAR_LOG_NOTICE

$registry = Registry::setup();
$registry->register('Log', $pear_log);

$log = Registry::getModule('Log');
$log->log('ActionServer started!');

// required ActionServerHandler
require CLASS_PATH . '/game/actions/ActionServerHandler.php';

// pear class used for this server
require 'Net/Server.php';

// get config object from registry
$config = Registry::getModule('config');

// create a new server which will fork new processes
$server = Net_Server::create('fork', $config->get('action_server_host'), $config->get('action_server_port'));

// create new handler object
$handler = new ActionServerHandler();

// connect handler as callback object to server
$server->setCallbackObject($handler);

// start server
$server->start();
?>