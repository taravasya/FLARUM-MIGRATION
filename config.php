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
$flarumDbPrefix     = "";   // Your Flarum database table prefix

//Parse custom data Enable or dissable it if don't needed:
//bbcodes: QUOTE, SPOILER, ATTACH, HIDE, SHOWTOGROUPS, MENTION
$_convertCustomBBCodesToXML = true;
//smiles:  Fot this you need to have installed and properly configured flarum plugin - Flamoji. Please learn how to add custom smiles-packs to it. And then you need to change text array values: $vb_smiles_text and $flarum_smiles_text in function convertCustomSmiliesToXML() in functions.php file 
$_convertCustomSmiliesToXML = true;
//Internal vB URLs is showthread, member, forumdisplay etc. This function adds link text to vB standard paths (if it was missing) so that the link looks formatted [url=www.site/showthread=500]Thread №500[/url]
$_convertInternalURLs = true;
//Use custom Flarum plugins: Old Passwords and Birthday, for import: hashed passwords and birthdays from vB 
$_useCustomPlugins = true;

// Set limits for testing purposes 
$threads_limit  = 500000; // Limit the number threads in results to not run through all posts from vB db
$threads_limit_ids = array(false, 1, 5, 120, 2539); // Set to true first value and fill the list with ids of only those threads that will be included in the import. Other threads will be ignored.

//Some locales used in function "formatText" for convert internal URLs
$GLOBALS['post_not_found'] = '[!Пост не найден!]';
$GLOBALS['is_thread'] = 'Тема';
$GLOBALS['is_post'] = 'Пост';
?>