<?php

/**
 * class for storing and handling global config vars
 *
 * usually stored in the registry for easier access
 */
class Config {
	
	/**
	 * database object
	 *
	 * @var object
	 */
	private $db;
	
	/**
	 * array which holds the config vars
	 *
	 * @var array
	 */
	private $config_vars = array();
	
	/**
	 * init object
	 */
	function __construct() {
		// get db object from registry
		$this->db = Registry::getModule("db");
		// init this object
		$this->init();
	}
	
	/**
	 * get a specific config var from this class
	 *
	 * @param string $key
	 * @return string $value
	 * @access public
	 */
	public function get($key) {
		// if the desired key exists
		if(array_key_exists($key, $this->config_vars)) {
			// return it
			return $this->config_vars[$key];
		}
		return null;
	}
	
	/**
	 * set a value only used by administration
	 *
	 * @param string $key
	 * @param string $value
	 * @access public
	 */
	public function set($key, $value) {
		// not used yet
	}
	
	/**
	 * load all config vars from database and store in class
	 *
	 * @access private
	 */
	private function init() {
		// fetch the config vars from the database
		$query = "SELECT config_key, config_value FROM config ORDER BY config_key";
		$result = $this->db->getAssoc($query);
		// if theres a result
		if(is_array($result)) {
			// store result in class var
			$this->config_vars = $result;
		}
	}
}

?>
