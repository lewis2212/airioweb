<?php
/**
 * Simple sqlite storage
 * (if you don't have proper database access, but sqlite extension enabled)
 *
 * @package AirjoStats
 * @author joruss
 */


require_once(dirname(__FILE__)."/Reader.php");

/**
 * Sqlite reader class
 *
 */
class Airio_Stats_Sqlite extends Airio_Stats_Reader {

protected $fh;

    /**
     * Create and set defaults for the reader
     *
     * @param string Directory where put files
     *        if not set (or null) it'll use AIRIO_STATS_DIR constant
     * @param boolean set readonly access
     * @access public
     * @return undefined
     */
    function __construct($where = null ,$readonly = false) {
        if ($where == null) {
            if (!defined('AIRIO_STATS_DIR')) {
                // we'll keep files next to the class
                // since we don't know what to do...
                define('AIRIO_STATS_DIR',dirname(__FILE__));
            }

            $this->setConfig('filesdir',AIRIO_STATS_DIR);
        } else {
            $this->setConfig('filesdir',$where);
        }
        $this->setConfig('readonly',$readonly);
        $this->setConfig('database','airio_stats');
    }

    /**
     * Connect to database
     * @access public
     * @return boolean weather operation succeeded
     */
    function connect() {
        $dbfile = $this->getConfig('filesdir').$this->getConfig('database').'.sqlite';
//        $dbfile =str_replace("/","\\",$dbfile);
        if (!file_exists($dbfile)) {
            $create = "
                CREATE TABLE data(
                    time INTEGER(4) NOT NULL,
                    server INTEGER(1) NOT NULL,
                    C INTEGER(1) DEFAULT 0,
                    P INTEGER(1) DEFAULT 0,
                    B INTEGER(1) DEFAULT 0,
                    K INTEGER(1) DEFAULT 0,
                    L INTEGER(1) DEFAULT 0,
                    X INTEGER(2) DEFAULT 0,
                    T INTEGER(2) DEFAULT 0
                )
            ";
        }
        $this->db = new SQLiteDatabase($dbfile,0666,$errormsg);
        if ( (!$errormsg) && (isset($create)) ) {
            $this->db->query($create,SQLITE_ASSOC,$errormsg);
        }
        if ($errormsg) {
            $this->showError($errormsg);
        }
    }

    /**
     * Save data to file.
     *
     * @access public
     * @return undefined
     */

    function insert($data) {
        $stats = Array();
        $now = time();
        if (!$this->db) {
            $this->connect();
        }
        foreach ($data as $k => $v) {
            $stats[substr($k,1)][substr($k,0,1)] = intval($v);
        }
        if (empty($stats)) { $this->showError("empty request"); }

        foreach ($stats as $k => $v) {
            if ($k == intval($k)) {
                $values = "VALUES($now,$k,".
                   intval($v['C']).",".
                   intval($v['P']).",".
                   intval($v['B']).",".
                   intval($v['K']).",".
                   intval($v['L']).",".
                   intval($v['X']).",".
                   intval($v['T']).")";
                $this->db->query("INSERT INTO data $values");
            }
        }
    }

    /**
     * Retrieve day-long data
     *
     * @param string Date in yyyymmdd format
     * @param &array variable to store data
     * @param string which data to retrieve
     *        (Check Reader.php for more info)
     * @access public
     * @return undefined
     */
    function getDay($date,&$data,$type = null) {
        if (preg_match('/[0-9]{8}/',$date) == 0) {
            $this->showError('request error - bad date');
        }
        if (!$this->db) {
            $this->connect();
        }

        $time = mktime(0,0,0,substr($date,4,2),substr($date,6,2),substr($date,0,4));

        if ($type == null) {
            $q = "SELECT * FROM data where time - $time BETWEEN 0 AND 86400";
            $q = $this->db->query($q,SQLITE_ASSOC);
            $res = $q->fetchAll();
            $data = Array();
            foreach ($res as $v) {
                $time = floor($v['time']/60)*60;
                $data[$time][$v['server']] = $v[$type];
            }
        } else {
            if (preg_match('/[A-Z]/',$type) == 0) {
                $this->showError('bad chart selected');
            }
            $fields = "time, server, $type";
            $q = "SELECT $fields FROM data where time - $time BETWEEN 0 AND 86400";
            $q .= " ORDER BY time ASC";
            $q = $this->db->query($q,SQLITE_ASSOC);
            $res = $q->fetchAll();
            $data = Array();
            foreach ($res as $v) {
                $time = floor($v['time']/60)*60;
                $data[$time][$v['server']] = $v[$type];
            }

            // if it's single chart data, average values
            $data = $this->average($data);
        }

    }

    /**
     * Dummmy disconnect for compatibility reasons
     *
     */
    function disconnect() {
    }

} // Airio_Stats_Sqlite()