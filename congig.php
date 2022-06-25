<?php
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

//Parse some custom bbcodes or smiles? Enable or dissable it if don't needed
$_convertCustomBBCodesToXML = true;
$_convertCustomSmiliesToXML = true;
$_convertInternalURLs = true;
// Set limits for testing purposes 
$threads_limit  = 500000; // Limit threads in results to not run through all posts in db
$threads_limit_ids = [false, 2, 5, 10]; // set to true first value and fill the list the ids of only those threads that will be included in the import. Other threads will be ignored.

//Some locales used in function "formatText" for convert internal URLs
$GLOBALS['post_not_found'] = '#Пост не найден#';
$GLOBALS['is_thread'] = 'Тема';
$GLOBALS['is_post'] = 'Пост';
?>