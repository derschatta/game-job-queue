<?php
session_start();
error_reporting(E_ALL);

define('HOME_PATH', '/var/www/game');
define('CLASS_PATH', HOME_PATH.'/classes');

require_once CLASS_PATH.'/lib/Registry.class.php';
require_once CLASS_PATH.'/game/utils/Config.class.php';

require_once CLASS_PATH.'/lib/db/adodbSimple/adodb.inc.php';
require CLASS_PATH.'/lib/db/config.inc.php';

# report all errors
ini_set("display_errors", "1"); # but do not echo the errors
//define('ADODB_ERROR_LOG_TYPE',3);
//define('ADODB_ERROR_LOG_DEST', '/var/log/game_mysql_errors.log');
require_once CLASS_PATH.'/lib/db/adodbSimple/adodb-errorhandler.inc.php';

$db = ADONewConnection('mysqlt');
$db->Connect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

//$db->debug = true;

$registry = Registry::setup();
$registry->register("db", $db);

$config = new Config();
$registry->register("config", $config);

require_once CLASS_PATH.'/game/utils/TimeUtils.class.php';
require_once CLASS_PATH.'/game/utils/MathsUtils.class.php';

?>