<?php
//-----------------------------------------------------------------------------
// vBulletin database information
// Update the following settings to reflect your environment
//
include_once 'config.php';
include_once 'functions.php';
include_once 'database.php';


$result = $vbulletinDbConnection->query("SELECT `attachmentid`, `filedata`, `filesize`, `attachment`.`dateline`, `filename`, `extension`, `contentid` FROM `attachment` JOIN `filedata` ON `filedata`.`filedataid` = `attachment`.`filedataid` GROUP BY `attachmentid`;");

while ($row = $result->fetch_assoc()){
   $fp = '../assets/files/vbattachments/';
   $fn = $row["attachmentid"].'_'.$row["dateline"].'_'.$row["filesize"].'_'.$row["contentid"].'.'.$row["extension"];
   $fd = fopen($fp.$fn, 'wb');
   $message = saveBLOB($fp, $fn, $row["filedata"]) ? '[v] saved: '.$fn : '[!] error'.$fn;
   consoleOut($message);
}
?>