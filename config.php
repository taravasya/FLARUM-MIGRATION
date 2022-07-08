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
$flarumDbName       = "flarum_db2";   // Your Flarum database name
$flarumDbPrefix     = "";   // Your Flarum database table prefix

//Parse custom data. Enable or dissable it if you don't need it:
//parsed bbcodes: QUOTE, SPOILER, ATTACH, HIDE, SHOWTOGROUPS, MENTION
$_convertCustomBBCodesToXML = true;
//smiles:  Fot this you need to have installed and properly configured flarum plugin - Flamoji. Please learn how to add custom smiles-packs to it. And then you need to change text array values: $vb_smiles_text and $flarum_smiles_text in function convertCustomSmiliesToXML() in functions.php file
$_convertCustomSmiliesToXML = true;
//Internal vB URLs, like showthread, member, forumdisplay etc. This option add link text to vB standard urls (if it was missing) so that the links looks formatted [url=www.site/showthread=500]Thread №500[/url].
$_convertInternalURLs = true;
//Use custom Flarum plugins: Old Passwords and Birthday, for import: hashed passwords and birthdays from vB? You must have installed an enabled this plugins for use this option
$_useCustomPlugins = true;
//Remove bbcode tags from posts text (LEFT, RIGHT, INDENT, HIGHLIGHT, FONT)?. You can change this list in function removeUnwantedBcodes(). I remove them because they useless in flarum.
$_removeUnwantedBBcodes = true;

// Set limits for testing purposes
$threads_limit  = 500000; // Limit the number of threads in migration results limit posts amount result, if and while you debugging migration process
$threads_limit_ids = array(false, 1, 12, 5806); // Set to true first value and fill the list with ids of only those threads that will be included in the migration process. Other threads will be ignored.
$debugPosts = array(4754,4917,27711,34341,36838,38334,41679,42071,42313,43851,53357,63848,67070,74912,89041,89208); //Posts ids with content what will be write to debug.log file. Call function: postsDebug(postid, stage_to_distinguish,  post_content). Call this function inside functions: formatText, removeUnwantedBBcodes, s9eTextFormatterParse, convertCustomBBCodesToXML. By default this array not used anywhere. It will be use only if you add call for funtion postsDebug().
//Translation for words used in formatText() for convert internal URLs
$GLOBALS['post_not_found'] = '[!Пост не найден!]';
$GLOBALS['is_thread'] = 'Тема';
$GLOBALS['is_post'] = 'Пост';
$GLOBALS['is_wrote'] = 'Написал';
$GLOBALS['attachment'] = 'Вложение';
$GLOBALS['notfound'] = 'не найдено!';

//-----------------------------------------------------------------------------
//
// Migration steps
// Set 'enabled' to false if you want the script to skip that step
//
$steps = array (
   array ( 'title' => 'Opening database connections',                'enabled' => true ),  // You cannot disable this step
   array ( 'title' => 'Group migration',                             'enabled' => true ),
   array ( 'title' => 'User migration',                              'enabled' => true ),
   array ( 'title' => 'Forums => Tags migration',                    'enabled' => true ),
   array ( 'title' => 'Threads/Posts => Discussion/Posts migration', 'enabled' => true ),
   array ( 'title' => 'Avatars migration',                           'enabled' => true ),
   array ( 'title' => 'User/Discussions record creation',            'enabled' => true ),
   array ( 'title' => 'User discussion/comment count creation',      'enabled' => true ),
   array ( 'title' => 'Tag sort',                                    'enabled' => false ),
   array ( 'title' => 'Threads => Disscussions subscription',        'enabled' => true ),
   array ( 'title' => 'Forums => Tags subscription',                 'enabled' => true ),
   array ( 'title' => 'Reputation => Likes migration',               'enabled' => true ),
   array ( 'title' => 'Closing database connections',                'enabled' => true ),  // You cannot disable this step
);
?>