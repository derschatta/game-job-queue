<?php

/**
 * abstract class for all Actions, use as template
 *
 * @author Fabian Derschatta <fabian@derschatta.de>
 * @package game
 * @subpackage actions
 */
abstract class AbstractAction {
    
    /**
     * overwrite this function
     *
     * @access public
     * @abstract
     *
     */
    abstract protected function run($event);
    
    /**
     * initialize the event object
     * passing the object or only the eventid, both is possible
     *
     * @param mix $event
     *            - event object or eventid
     * @access private
     */
    public function _initEvent($event) {
        // if event is not an object but the eventid
        if (! is_object($event) && is_int($event)) {
            $eventid = $event;
            // create ne events object
            $event = new Events();
            // load event
            $event->load($eventid);
        }
        // store event object in class var
        $this->event = $event;
    }
}
?>