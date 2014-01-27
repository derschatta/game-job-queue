<?php

/**
 * factory for initiating new action objects.
 * you just have to pass
 * the type of the action you want to load and the factory automatically
 * tries to initiate the appropriate object for it
 *
 * @package game
 * @subpackage actions
 * @author Fabian Derschatta
 * @copyright 2008 space-frontier
 * @version SVN: $Id:$
 */
class ActionFactory {
    
    /**
     * contructor, don't call directly
     *
     * @access private
     */
    private function __construct() {
        die("direct call of constructor prohibitet");
    }
    
    /**
     * tries to initiate the object
     *
     * @param string $type
     *            - type of the action object
     * @return object - object of the appropriate class
     */
    public function create($type) {
        // create class name
        $class = $type . "Action";
        
        // set file path of class
        $file = CLASS_PATH . "/game/actions/" . $class . ".php";
        
        // if the file exists
        if (file_exists($file)) {
            // try to include the file
            require_once $file;
            // create new object
            $object = new $class();
            // return the object
            return $object;
        } else {
            // stop and throw error message
            die("File {$type}Action.php wasn't found");
        }
    }
}
?>