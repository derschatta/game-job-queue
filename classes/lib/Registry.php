<?php

/**
 * class for storing objects in it for easier later use
 *
 * @author fabian von derschatta <fabian@derschatta.de>
 *        
 */
class Registry {
    
    /**
     * array which holds the registered modules
     *
     * @var array
     * @access private
     */
    private $registered_modules = array();
    
    /**
     * create and/or get the singleton instance of Registry
     * if used no new object is created, always the existing is used
     *
     * @return object $registry - registry object
     * @access public
     */
    public function setup() {
        // create static var
        static $registry;
        
        // if there's no active registry object
        if (! isset($registry)) {
            // create a new object
            $registry = new Registry();
        }
        
        return $registry;
    }
    
    /**
     * static method to fetch a registered module
     *
     * Example:
     * <code>
     * $config = Registry::getModule("Config");
     * </code>
     *
     * @param string $module_name            
     * @return object $module
     * @static
     *
     * @access public
     */
    public static function getModule($module_name) {
        // get singleton instance of Registry
        $registry = Registry::setup();
        
        // fetch module and return it
        return $registry->get($module_name);
    }
    
    /**
     * fetch registered module from Registry
     *
     * @param string $module_name            
     * @return object or null
     * @access public
     */
    public function get($module_name) {
        // if module is registered
        if ($this->isRegistered($module_name)) {
            // return registered module
            return $this->registered_modules[$module_name];
        } else {
            return null;
        }
    }
    
    /**
     * register a module (object) in Registry
     *
     * @param string $module_name            
     * @param object $instance            
     * @access public
     */
    public function register($module_name, $instance) {
        
        // if the instance
        if (is_object($instance)) {
            
            // if module is already registered
            if ($this->isRegistered($module_name)) {
                // delete the current module first
                unset($this->registered_modules[$module_name]);
            }
            
            // store in array
            $this->registered_modules[$module_name] = $instance;
        }
    }
    
    /**
     * check if module is registered in class
     *
     * @param string $module_name            
     * @return boolean
     * @access private
     */
    private function isRegistered($module_name) {
        // if module_name exists in array
        if (array_key_exists($module_name, $this->registered_modules)) {
            // if value is an object
            if (is_object($this->registered_modules[$module_name])) {
                return true;
            }
        }
        return false;
    }
}

?>
