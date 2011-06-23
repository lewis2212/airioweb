<?php
/**
 * Here we'll save data supplied by airio server
 *
 * You should (I mean you have to) rename this file for security reasons
 * (You really don't want someone to flood your stats with rubbish, do you?)
 *
 */
error_reporting(E_PARSE);

if (basename(__FILE__) == 'savestats.php') {
    echo 'You have to rename this file to something less obvious. ';
    echo md5(time().sha1($_SERVER['REQUEST_TIME'])).'.php is a good start';
    die();
}

// Alternatively you can define access key to keep unathorised users away
// to do so, uncomment next line and set acces key there

//define('AIRIO_STATS_ACCESSKEY','put your custom accesskey here');

if (defined('AIRIO_STATS_ACCESSKEY')) {
    if (AIRIO_STATS_ACCESSKEY == 'put your custom accesskey here') {
        die('<h1 style="margin:0">Misconfigured.</h1>You have to change password to get this to work.');
    };
    if ($_REQUEST['AK'] != AIRIO_STATS_ACCESSKEY) {
        die('<h1 style="margin:0">Unauthorised</h1>You need to supply access key to use this page.');

    }
}
// We'll get rid of accesskey variable here,
// it won't be of any use anywhere else
unset($_REQUEST['AK']);

// Use UTC time, so everybody knows when's what
date_default_timezone_set('UTC');

// get reader interface
require dirname(__FILE__)."/readercfg.php";

$r->connect();
$r->insert($_REQUEST);
$r->disconnect();

echo "OK";