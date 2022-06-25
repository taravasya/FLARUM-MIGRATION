<?php
set_time_limit(0);
ini_set('memory_limit', -1);
ini_set("log_errors", 1);
ini_set("error_log", "vBulletin_to_Flarum_error.log");

//-----------------------------------------------------------------------------
// vBulletin and Flarum database information (must be same database server)
// Update the following settings to reflect your environment
//
$servername         = "localhost";   // Your database server
$username           = "root_taravasya";   // Your database server username
$password           = "6978321";   // Your database server password
$vbulletinDbName    = "wedframe_db";   // Your vBulletin database name
$vbulletinDbPrefix  = "";   // Your vBulletin database table prefix
$flarumDbName       = "flarum_db";   // Your Flarum database name
$flarumDbPrefix     = "flrm_";   // Your Flarum database table prefix

include __DIR__ . '/../../vendor/autoload.php';
include_once 'flarumbundle.php'; //!!! here custom bundle for parsing vb posts content @taravasya

$parser = FlarumBundle::getParser();
$parser->disableTag('SPOILER');
$parser->disableTag('QUOTE');
$parser->disablePlugin('Emoji');
$parser->disablePlugin('Emoticons');
?>