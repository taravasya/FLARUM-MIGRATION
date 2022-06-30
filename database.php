<?php
//-----------------------------------------------------------------------------
//
// Establish connection to the vBulletin database
//
$vbulletinDbConnection = new mysqli($servername, $username, $password, $vbulletinDbName);

if ($vbulletinDbConnection->connect_error) {

   consoleOut("Connection to vBulletin database failed: ".$vbulletinDbConnection->connect_error);
   die("Script stopped");

} else {

    consoleOut("Connection to vBulletin database successful");
    if(!$vbulletinDbConnection->set_charset("utf8")) {

       consoleOut("Error loading character set utf8: ".$vbulletinDbConnection->error);
      exit();

    } else {

      consoleOut("Current character set: ".$vbulletinDbConnection->character_set_name());

   }

}

//-----------------------------------------------------------------------------
//
// Establish connection to the Flarum database
//
$flarumDbConnection = new mysqli($servername, $username, $password, $flarumDbName);

if ($flarumDbConnection->connect_error) {

   consoleOut("Connection to Flarum database failed: ".$flarumDbConnection->connect_error);
   die("Script stopped");

} else {

    consoleOut("Connection to Flarum database successful");
    if(!$flarumDbConnection->set_charset("utf8")) {

      consoleOut("Error loading character set utf8: ".$flarumDbConnection->error);
      exit();

    } else {

      consoleOut("Current character set: ".$flarumDbConnection->character_set_name());

   }

}

//-----------------------------------------------------------------------------
//
// Disable foreing keys check
//
$flarumDbConnection->query("SET FOREIGN_KEY_CHECKS=0");
consoleOut("Foreign key checks disabled in Flarum database");
?>