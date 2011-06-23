<?php
/**
 * Simple text file based storage (if you don't have database access)
 *
 * @package AirjoStats
 * @author joruss
 */


require_once(dirname(__FILE__)."/Reader.php");

/**
 * Text file reader class
 *
 */
class Airio_Stats_Txtfile extends Airio_Stats_Reader {

protected $fh;

    /**
     * Create and set defaults for the reader
     *
     * @param string Directory where put text files
     *        if not set (or null) it'll use AIRIO_STATS_FILE_DIR constant
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
        // Stored data strings can be compressed.
        // As a de/compressor can be used any (even user defined) function.
        // if any of defined functions doesn't exist it'll fall back to
        // plain text which may render unusable data in case just one will fail
        // or their routines arent compatibile with each other.
        //
        // !!!Important!!!
        // Make sure that base64 encoded compressed data is in fact smaller
        // than plain text. The more servers you own, the better chances are
        // for you to save some space with that.
        //
        // A quick example - raw data vs compressed one:
        // - serialized 4 element array:
        //  a:4:{i:0;s:3:"aaa";i:1;s:3:"bbb";i:2;s:3:"ccc";i:3;s:3:"ddd";}
        //  S7QysarOtDKwLrYytlJKTExUss60MoTwkpKSQDwjCC85ORnEM4bwUlJSlKxrAQ==
        //
        //  2 bytes more than original (BAD)
        //
        // - serialized 5 element array:
        //  a:5:{i:0;s:3:"aaa";i:1;s:3:"bbb";i:2;s:3:"ccc";i:3;s:3:"ddd";i:4;s:3:"eee";}
        //  S7QytarOtDKwLrYytlJKTExUss60MoTwkpKSQDwjCC85ORnEM4bwUlJSQDwTCC81NVXJuhYA
        //
        //  4 bytes less than original (GOOD)
        //
        $this->setConfig('compressor','gzdeflate');
        $this->setConfig('decompressor','gzinflate');
        // by default we'll smooth 20 minutes (to keep things clean)
        $this->setConfig('smooth',20);
    }

    /**
     * Connect (ie. open todays file for writing if we're not in 'readonly' mode)
     * @access public
     * @return boolean (if file exists)
     *         it'll return true on any readonly request
     */
    function connect() {
        if ($this->getConfig('readonly') == false) {
            return ($this->fh = @fopen($this->getConfig('filesdir')."/stats".date('Ymd').".txt",'a+'));
        } else {
        return true;
        }
    }

    /**
     * Save data to file.
     *
     * Execute compressor first and escape compressed data with base64 encoding.
     *
     * @access public
     * @return undefined
     */

    function insert($data) {
        $stats = Array();
        if (!$this->fh) {
            $this->setConfig('readonly',false);
            $this->connect();
        }
        foreach ($data as $k => $v) {
            $stats[substr($k,0,1)][substr($k,1)] = intval($v);
        }
        if (empty($stats)) { $this->showError("empty request"); }


        $data = time()."\t".serialize($stats);
        if (function_exists($this->config['compressor'])) {
            $data = base64_encode($this->config['compressor']($data));
        }
        fputs($this->fh,$data."\r\n");
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
        $this->fh = @fopen($this->getConfig('filesdir')."/stats".$date.".txt","r");
        // gather data first and group them within 1 minute periods
        while ($line = fgets($this->fh)) {
            if (function_exists($this->config['decompressor'])) {
                $line = base64_decode($line);
                $line = $this->config['decompressor']($line);
            }
            $line = explode("\t",$line);
            $time = floor($line[0]/60)*60;
            $line = unserialize($line[1]);
            if ($type == null) {
                $data[$time] = $line;
            } else {
                if ( (!isset($data[$time])) || (!is_array($data[$time])) ) {
                    $data[$time] = Array();
                }
                if (isset($line[$type])) {
                    $data[$time] = array_merge($data[$time],$line[$type]);
                }
            }
        }
        // if it's single chart data, average values
        if ($type != null) {
            $data = $this->average($data);
        }
    }

    /**
     * Close file handle
     *
     * @access public
     * @return undefined
     */
    function disconnect() {
        @fclose($this->fh);
    }

} // Airio_Stats_Txtfile()