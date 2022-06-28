<?php
//-----------------------------------------------------------------------------
// vBulletin database information
// Update the following settings to reflect your environment
//
include_once 'config.php';
include_once 'functions.php';

$vbulletinDbConnection = new mysqli($servername, $username, $password, $vbulletinDbName);

if ($vbulletinDbConnection->connect_error) {

   consoleOut("Connection to vBulletin database failed: ".$vbulletinDbConnection->connect_error."\n");
   die("Script stopped");

} else {

    consoleOut("Connection to vBulletin database successful"."\n");
    if(!$vbulletinDbConnection->set_charset("utf8")) {

       consoleOut("Error loading character set utf8: ".$vbulletinDbConnection->error."\n");
      exit();
       
    } else {

      consoleOut("Current character set: ".$vbulletinDbConnection->character_set_name()."\n");

   }

}

$result = $vbulletinDbConnection->query("SELECT `attachmentid`, `filedata`, `filesize`, `attachment`.`dateline`, `filename`, `extension`, `contentid` FROM `attachment` JOIN `filedata` ON `filedata`.`filedataid` = `attachment`.`filedataid` GROUP BY `attachmentid`;");

while ($row = $result->fetch_assoc()){
   $fp = '../assets/files/vbattachments/';
   $fn = $row["attachmentid"].'_'.$row["dateline"].'_'.$row["filesize"].'_'.$row["contentid"].'.'.$row["extension"];
   $fd = fopen($fp.$fn, 'wb');
   $message = saveBLOB($fp, $fn, $row["filedata"]) ? '[v] saved: '.$fn : '[!] error'.$fn;
   consoleOut($message);
}
?>