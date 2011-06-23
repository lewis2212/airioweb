<?php
/**
 * Base class interface
 *
 * @package AirjoStats
 * @author joruss
 */

/**
 * Handling configuration data
 *
 */
class Airio_Stats_Abstract {
protected $config = Array();

    /**
     * Retrieve previously stored config variable
     *
     * @param string variable name
     * @return mixed variable
     */
    function getConfig($name,$default = null) {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        } else {
            return $default;
        }
    }

    /**
     * Store config variable
     *
     * @param string variable name (1)
     *        array name => value pairs (2)
     * @param mixed variable (1)
     *        null (2)
     *
     * @return undefined
     */
    function setConfig($data,$value = null) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->config[$k] = $v;
            }
        } else {
        $this->config[$data] = $value;
        }
    }

    /**
     * Error handler.
     *
     * If set config['error_handler'] it'll try to call
     * error handlers' showError method. Otherwise just display message.
     */
    function showError($msg) {
        $eh = $this->getConfig('error_handler');
        if (@method_exists($eh,'showError')) {
            $eh->showError($msg);
        } else {
            echo "ERROR: $msg";
            die();
        }
    }

} // Airio_Stats_Abstract()