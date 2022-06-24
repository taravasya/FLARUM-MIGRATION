<?php
//-----------------------------------------------------------------------------
// vBulletin database information
// Update the following settings to reflect your environment
//
$servername         = "localhost";   // Your database server
$username           = "root_taravasya";   // Your database server username
$password           = "6978321";   // Your database server password
$vbulletinDbName    = "wedframe_db";   // Your vBulletin database name
$vbulletinDbPrefix  = "";   // Your vBulletin database table prefix

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

$result = $vbulletinDbConnection->query("SELECT `userid`, `filename`, `filedata`  FROM `customavatar` ORDER BY `userid` ASC;");

while ($row = $result->fetch_assoc()) {
   $ext = preg_replace('#.+?(\w{3,4})$#', '$1', $row["filename"]);
   $fp = 'vb/avatars/';
   $fn = $row["userid"].'.'.$ext;
   $fd = fopen($fp.$fn, 'wb');
   $message = saveBLOB($fp, $fn, $row["filedata"]) ? '[v] saved: '.$fn : '[!] error'.$fn;
   consoleOut($message);
}

function saveBLOB($filepath, $filename, $content)
{
  $File = $filepath.$filename;
  if ($Handle = fopen($File, 'w')) {
    $info = $content;
    if (fwrite($Handle, $info)) {
      return true;
    }
    fclose($Handle);
  }
  return false;
}

function consoleOut($consoleText, $timeStamp=true) {

   $time_stamp = Date('Y-m-d H:i:s');
   $startStr = "\n";

   if ($timeStamp) {
      
      $startStr .= $time_stamp.": ";

   }
   $endStr = "";
   
   echo $startStr.$consoleText.$endStr;

}
?>