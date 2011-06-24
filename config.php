<?php

// We'll add library folder to include path first to get this going.
// If you stored it somewhere else, you need to modify it accordingly.
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.'./library');

require_once("Airio/Stats/Image.php");
$config = Array();

// Get what chart types are available for displaying.
$config['chart_types'] = Airio_Stats_Image::getAvailableCharts();

// Define your server names and their chart colors.
// It's taken straight from default settings, but we'll need it for the page
$config['servers'] = array(
'Server #01' => array(0xff,0x00,0x00), // red
'Server #02' => array(0x00,0xff,0x00), // green
'Server #03' => array(0x00,0x00,0xff), // blue
'Server #04' => array(0x00,0xff,0xff), // cyan
'Server #05' => array(0xff,0x00,0xff), // magenta
'Server #06' => array(0x80,0x00,0x00), // darkred
//'Server #07' => array(0x00,0x80,0x00), // darkgreen
//'Server #08' => array(0x00,0x80,0x80), // navy blue
//'Server #09' => array(0x40,0x40,0x40), // dark grey
//'Server #10' => array(0x40,0x00,0x40), // violet
//'Server #11' => array(0x80,0x80,0x80), // grey
//'Server #12' => array(0xff,0x60,0x00), // orange
);

// Define chart descriptions
$config['charts'] = Array(
"C" => 'Connections',
"P" => 'Players',
"B" => 'Bans',
"K" => 'Kicks',
"L" => 'Admins',
"T" => 'Tracks',
"X" => 'TestChart',
);

// Define charts available to anyone.
$config['guestcharts'] = Array('C','P','B','K','T');

// Define password for accessing 'non-guest' charts.
// Default password `admin_pass` is disabled. You HAVE TO change it.
$config['adminpass'] = 'admin_pass';

// Page title and header
$config['pagetitle'] = 'Sample Airio LFS Server stats page';
$config['pagehead'] = 'Server stats page';