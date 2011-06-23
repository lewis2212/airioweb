<?php


// I strongly advise to change this path to something you only know
// if you're not happy with strangers reading your stats


// select storage engine you'd like to utilise

/* Sqlite
 *
define('AIRIO_STATS_DIR',dirname(__FILE__).'/../');
require_once("Airio/Stats/Sqlite.php");
$r = new Airio_Stats_Sqlite();
 /*
 */

/* Mysql
 *
 *
require_once("Airio/Stats/Mysql.php");
define('AIRIO_STATS_DIR','username=root;password=;dbname=test;table=data');
$r = new Airio_Stats_Mysql();
 /*
 */

/* Textfile
 * By default it'll use gzip compression for storage
 * unless you disable it (set to '', see below)
 *
 */
define('AIRIO_STATS_DIR',dirname(__FILE__).'/../data/');
require_once("Airio/Stats/Txtfile.php");
$r = new Airio_Stats_Txtfile();

// specific configuration values for textfile storage
$r->setConfig('compressor','');
$r->setConfig('decompressor','');
 /*
 */
