<?php
//-----------------------------------------------------------------------------
// vBulletin database information
// Update the following settings to reflect your environment
//
include_once 'config.php';
include_once 'functions.php';
include_once 'database.php';

$result = $vbulletinDbConnection->query("SELECT `userid`, `filename`, `filedata`  FROM `customavatar` ORDER BY `userid` ASC;");

while ($row = $result->fetch_assoc()) {
   $ext = preg_replace('#.+?(\w{3,4})$#', '$1', $row["filename"]);
   $fp = '../assets/avatars/';
   $fn = $row["userid"].'.'.$ext;
   $fd = fopen($fp.$fn, 'wb');
   $message = saveBLOB($fp, $fn, $row["filedata"]) ? '[v] saved: '.$fn : '[!] error'.$fn;
   consoleOut($message);
}
?>