<?php
/**
 * Data reader interface
 *
 * @package AirjoStats
 * @author joruss
 */
require_once(dirname(__FILE__)."/Abstract.php");


/**
 * Data reader abstract class definition
 *
 * Holds essential routines to write compatibile data reader
 */
abstract class Airio_Stats_Reader extends Airio_Stats_Abstract {

    /**
     * Setting up the reader
     *
     * @param string should contain filename/dir/connection string etc...
     * @param boolean inform reader if we'll be just reading
     * @return undefined
     */
    abstract function __construct($where = null, $readonly = false);

    /**
     * Connecting to datasource
     *
     * @return undefined
     */
    abstract function connect();

    /**
     * Inserting data
     *
     * @param array AIRIO stats request to save
     * @return undefined
     */
    abstract function insert($data);

    /**
     * Retrieve day-long data
     *
     * @param string Date in yyyymmdd format
     * @param &array variable to store data
     * @param string which data to retrieve
     *      As for now (2011-06-16) valid params are:
     *          B for Bans
     *          C for Connections
     *          P for Players
     *          T for Tracks
     *          K for Kicks
     *          L for Admins
     *          X for TestData Chart
     *
     * @return undefined
     */
    abstract function getDay($date,&$data,$type = null);

    /**
     * Disconnect from data source.
     * This class calls overloaded disconnect() on destruction so there's
     * no need to call it, but it's always nice to clean behind yourself.
     */
    abstract function disconnect();

    /**
     * Average data within selected period
     *
     * @param &array data
     * @access private
     * @return array averaged data
     */
    function average(&$a) {
        $smooth = $this->getConfig('smooth');
        $sum = $this->getConfig('sum_values',false);
        // by default we'll do 5 min smoothing
        if (!$smooth) { $smooth = 5; }
        $int = $smooth * 60;
        $o = Array();
        $lts = 0; $t = Array();$cnt = 0;
        foreach($a as $time => $values) {
            $ts = floor($time/$int) * $int;
            if ($lts == 0) { $lts = $ts; }
            if ($ts != $lts) {
                foreach ($t as $k => $v) {
                    // don't 'average' just add values
                    if ($sum == false) {
                        $t[$k] = $t[$k] / $cnt;
                    }
                }
                $o[$ts] = $t;
                $t = array();
                $cnt = 0;
            }
            foreach ($values as $k => $v) {
                if (isset($t[$k])) {
                    $t[$k] += $v;
                } else {
                    $t[$k] = $v;
                }
            }
            $cnt++;
            $lts = $ts;
        }
        if ($cnt > 0) {
            foreach ($t as $k => $v) {
                // don't 'average' just add values
                if ($sum == false) {
                    $t[$k] = $t[$k] / $cnt;
                }
            }
            $o[$ts] = $t;
        }
        return $o;
    }



    /**
     * Cleanup the mess left by a lazy programmer
     */
    function __destruct() {
        try {
            $this->disconnect();
        } catch (exception $e) {
        }
    }

} // Airio_Stats_Reader()