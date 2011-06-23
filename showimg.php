<?php
error_reporting(E_PARSE);
session_start();

require(dirname(__FILE__)."/config.php");
require_once("Airio/Stats/Image.php");
require(dirname(__FILE__)."/reader.php");

$r->setConfig('readonly',true);

$smooth = @intval($_GET['a']);
if ($smooth > 0) {
    $r->setConfig('smooth',$smooth);
}

$img = new Airio_Stats_Image($r,$_GET['t']);

if ($_GET['c']) {
    if ($_SESSION['is_admin'] != 1) {
        if (!in_array($_GET['c'],$config['guestcharts'])) {
            $img->showError("This chart is not available for you.");
            die();
        }
    }
}




$r->setConfig('error_handler',$img);
$img->setConfig('server_names',array_keys($config['servers']));
$img->setConfig('server_colors',array_values($config['servers']));

@$img->display($_GET['d'],$_GET['c'],$_GET['s']);
