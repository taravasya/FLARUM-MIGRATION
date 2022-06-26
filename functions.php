<?php
//=============================================================================
// FUNCTIONS

// ---------------------------------------------------------------------------
/**
 * Replaces all special chars and blanks with dashes and sets to lower case.
 *
 * @param  string    $text    Year of the day to count for
 * @return string    Slugified string
 */
function slugify($text) {
   $cyr = array(
      'ж',  'ч',  'щ',   'ш',  'ю',  'я', 'ы', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'э', 'ф', 'х', 'ц', 'ъ', 'ь',
      'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'Я', 'Ы', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Э', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь');
   $lat = array(
      'zh', 'ch', 'csh', 'sh', 'yu', 'ya', 'i', 'a', 'b', 'v', 'g', 'd', 'e', 'e','z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'e', 'f', 'h', 'c', '', '',
      'ZH', 'CH', 'CSH', 'SH', 'YU', 'YA', 'I', 'A', 'B', 'V', 'G', 'D', 'E', 'E','Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'E', 'F', 'H', 'c', '', '');
   $text = str_replace($cyr, $lat, $text);
   $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
   $text = trim($text, '-');
   $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
   $text = strtolower($text);
   $text = preg_replace('~[^-\w]+~', '', $text);
   if (empty($text))
     return 'n-a';

   return $text;
}

// ---------------------------------------------------------------------------
/**
 * Creates a random hex color code
 *
 * @return string    Hex color code
 */
function rand_color() {
   /*$colors_pallet = array('#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffc107', '#ff9800', '#ff5722', '#795548', '#9e9e9e', '#607d8b');
   return $colors_pallet[random_int(0,17)];*/
   $h = random_int(0,360);
   $s = random_int(40,80);
   $l = random_int(35,50); 
   return convertHSL($h, $s, $l, true);
}

function convertHSL($h, $s, $l, $toHex=true){
    $h /= 360;
    $s /=100;
    $l /=100;

    $r = $l;
    $g = $l;
    $b = $l;
    $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
    if ($v > 0){
          $m;
          $sv;
          $sextant;
          $fract;
          $vsf;
          $mid1;
          $mid2;

          $m = $l + $l - $v;
          $sv = ($v - $m ) / $v;
          $h *= 6.0;
          $sextant = floor($h);
          $fract = $h - $sextant;
          $vsf = $v * $sv * $fract;
          $mid1 = $m + $vsf;
          $mid2 = $v - $vsf;

          switch ($sextant)
          {
                case 0:
                      $r = $v;
                      $g = $mid1;
                      $b = $m;
                      break;
                case 1:
                      $r = $mid2;
                      $g = $v;
                      $b = $m;
                      break;
                case 2:
                      $r = $m;
                      $g = $v;
                      $b = $mid1;
                      break;
                case 3:
                      $r = $m;
                      $g = $mid2;
                      $b = $v;
                      break;
                case 4:
                      $r = $mid1;
                      $g = $m;
                      $b = $v;
                      break;
                case 5:
                      $r = $v;
                      $g = $m;
                      $b = $mid2;
                      break;
          }
    }
    $r = round($r * 255, 0);
    $g = round($g * 255, 0);
    $b = round($b * 255, 0);

    if ($toHex) {
        $r = ($r < 15)? '0' . dechex($r) : dechex($r);
        $g = ($g < 15)? '0' . dechex($g) : dechex($g);
        $b = ($b < 15)? '0' . dechex($b) : dechex($b);
        return "#$r$g$b";
    } else {
        return "rgb($r, $g, $b)";    
      }
}

// ---------------------------------------------------------------------------
/**
 * Escapes MySQL query strings.
 *
 * @return string    Hex color code
 */
function mysql_escape_mimic($inp) {

    if (is_array($inp)) {
      
      return array_map(__METHOD__, $inp);

   }

    if (!empty($inp) && is_string($inp)) {

      return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
      
   }
   
   return $inp;
   
}

// ---------------------------------------------------------------------------
/**
 * Formats vBulletin's text to Flarum's text format.
 * 
 * @param  object    $connection    Database connection
 * @param  string    $text          Text to convert
 * @return string    Converted text
 */
function formatText($connection, $text, $discussionid, $postnumber, $postid) {
   global $mentionsArray;
   global $quotesArray;
   global $postsNumbersArray;   
   global $_convertCustomBBCodesToXML;   
   global $_convertCustomSmiliesToXML;
   global $_convertInternalURLs;

   $text = html_entity_decode($text);
   if ($_convertInternalURLs) {
	   //replace direct urls to profiles
	   $text = preg_replace('#https?:\/\/wedframe\.ru\/member\.php\?u=(\d+)#', '/u/$1', $text);

	   //replace urls to posts between tags [url]URL[/url]
	   $text = preg_replace_callback(
	      '#\[url](https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+)\[\/url]#Ui',
	      function ($m) use ($postsNumbersArray) {
	         $postnumber = isset($postsNumbersArray[$m[5]]) ? $postsNumbersArray[$m[5]] : null;
	         return isset($postnumber) ? '[URL="/d/'.$m[3].'/'.$postnumber.'&pid'.$m[5].'"]Пост № '.$m[5].'[/URL]' : '[URL="/d/'.$m[3].'"]'.$GLOBALS['post_not_found'].'[/URL]';
	      },
	      $text
	   );


	   //replace urls to posts inside tags [url="URL"]
	   $text = preg_replace_callback(
	      '#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+?)(&page=\d+)?&p=(\d+?).*post\d+"?]#Ui',
	      function ($m) use ($postsNumbersArray) {
	         $postnumber = isset($postsNumbersArray[$m[4]]) ? $postsNumbersArray[$m[4]] : null;
	         return isset($postnumber) ? '[URL="/d/'.$m[2].'/'.$postnumber.'&pid'.$m[4].'"]' : '[URL="/d/'.$m[2].'"]';
	      },
	      $text
	   );

	   //replace urls to threads between tags [url]URL[/url]
	   $text = preg_replace('#\[URL]https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+?)\[\/URL]#i', '[URL="/d/$2"]'.$GLOBALS['is_thread'].' №$2[/URL]', $text);

	   //replace urls to threads inside tag [url="URL"]
	   $text = preg_replace('#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+?)"?]#i', '[URL="/d/$2"]', $text);

	   //replace text urls to posts between tags [url=url]URL[/url]
	   $text = preg_replace_callback(
	      '#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+#i',
	      function ($m) use ($postsNumbersArray) {
	         return isset($postsNumbersArray[$m[4]]) ? $GLOBALS['is_post'].' №'.$m[4] : $GLOBALS['post_not_found'];
	      },
	      $text
	   );

	   //replace text urls to threads between tags [url=url]URL[/url]
	   $text = preg_replace('#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)#i', $GLOBALS['is_thread'].' №$2', $text);

   }
   //clear whitespaces what placed next to square brackets between tags: [code] <<<MY TEXT[/code] (due to a typo or negligence). 
   $text = preg_replace('#(\[\S+])([[:blank:]]+?)(.+)([[:blank:]]+)?(\[\/\S+])#U', '$1$3$5', $text);

   $text = textFormatterParse($text);
   //echo(PHP_EOL.'[#####FORMAT] : '.$text.PHP_EOL);

   if ($_convertCustomBBCodesToXML) $text = convertCustomBBCodesToXML($text, $discussionid, $postnumber, $postid);
   //echo(PHP_EOL.'[#####CUSTOM] : '.$text.PHP_EOL); 

   if ($_convertCustomSmiliesToXML) $text = convertCustomSmiliesToXML($text);

   return $connection->real_escape_string($text);
   
}
// ---------------------------------------------------------------------------
/**
 * Convert bbcodes to XML templates with s9e\TextFormatter
 *
 * @param  string    $bbcode  Source text from posts
 * @return string    Converted $bbcode
 */
function textFormatterParse($text) {
   global $parser;

   //Adapte for formatter
   $text = preg_replace('#\[VIDEO=(.*?)]((.)*?)\[\/VIDEO]#is', '[MEDIA]$2[/MEDIA]', $text);
   $text = preg_replace('#\[ADDSHARE](.+)src="((https?:)?(.*?))"(.+)\[\/ADDSHARE]#is', '[MEDIA]https:$4[/MEDIA]', $text);
   $text = preg_replace('#\[SIZE=1]#is', '[SIZE=10]', $text);
   $text = preg_replace('#\[SIZE=2]#is', '[SIZE=13]', $text);
   $text = preg_replace('#\[SIZE=3]#is', '[SIZE=16]', $text);
   $text = preg_replace('#\[SIZE=4]#is', '[SIZE=20]', $text);
   $text = preg_replace('#\[SIZE=5]#is', '[SIZE=25]', $text);
   $text = preg_replace('#\[SIZE=6]#is', '[SIZE=30]', $text);
   $text = preg_replace('#\[SIZE=7]#is', '[SIZE=35]', $text);
   return $parser->parse($text);
}

// ---------------------------------------------------------------------------
/**
 * Strips BB codes from a string. Used to create Flarum tags.
 *
 * @param  string    $text_to_search    Text to convert
 * @return string    Converted text
 */
function stripBBCode($text_to_search) {

   $pattern = '|[[\/\!]*?[^\[\]]*?]|si';
   $replace = '';
   return preg_replace($pattern, $replace, $text_to_search);

}

// ---------------------------------------------------------------------------
/**
 * Converts BBcode to XML.
 *
 * @param  string    $bbcode    Text to convert
 * @return string    Converted text
 */
function convertCustomBBCodesToXML($bbcode, $discussionid, $postnumber, $postid) {
   global $attachmentsArray;
   global $mentionsArray;
   global $quotesArray;
   $bbcode = preg_replace_callback('#\[ATTACH(=CONFIG)?](\d{1,10})\[\/ATTACH]#', function ($m) use ($attachmentsArray) {
      return isset($attachmentsArray[$m[2]]) ? '<UPL-IMAGE-PREVIEW url="'.$attachmentsArray[$m[2]]['path'].'">[upl-image-preview url='.$attachmentsArray[$m[2]]['path'].']</UPL-IMAGE-PREVIEW>' : $m[0];
   }, $bbcode);
   $bbcode = preg_replace('#\[SPOILER](.*?)\[\/SPOILER]#is', '<DETAILS title="⏵ Подробнее"><s>[details="Подробнее"]</s><p>$1</p><e>[/details]</e></DETAILS>', $bbcode);
   $bbcode = preg_replace('#\[SPOILER=(.*?)](.*?)\[\/SPOILER]#is', '<DETAILS title="⏵ $1"><s>[details="$1"]</s><p>$2</p><e>[/details]</e></DETAILS>', $bbcode);
   $bbcode = preg_replace('#\[(HIDE(.*?)|SHOWTOGROUPS(.*?))]((.)*?)\[(\/HIDE(.*?)|\/SHOWTOGROUPS(.*?))]#is', '<p>[LOGIN]$4[/LOGIN]</p>', $bbcode);
   $bbcode = preg_replace_callback(
      '#\[MENTION=(\d+)](.+?)\[\/MENTION]#is',
      function($m) use (&$mentionsArray, $postid) {
         $m[1] = isset($m[1]) ? $m[1] : '';
         $m[2] = isset($m[2]) ? $m[2] : '';
         array_push($mentionsArray, [$postid, $m[1]]);
         return '<USERMENTION displayname="'.$m[2].'" id="'.$m[1].'">@'.$m[2].'</USERMENTION>';
      },
      $bbcode
   );
   $bbcode = preg_replace_callback(
      '#\[QUOTE(=\"?((.+?);(\d+))\"?)]((.)*?)\[\/QUOTE]#is',
      function($m) use (&$quotesArray, $discussionid, $postid, $postnumber) {
         $m[1] = isset($m[1]) ? $m[1] : '';
         $m[2] = isset($m[2]) ? $m[2] : '';
         $m[3] = isset($m[3]) ? $m[3] : '';
         $m[4] = isset($m[4]) ? $m[4] : '';
         $m[5] = isset($m[5]) ? $m[5] : '';
         array_push($quotesArray, [$postid, $m[4]]);
         return '<QUOTE><i>&gt; </i><p><POSTMENTION discussionid="'.$discussionid.'" displayname="'.$m[3].'" id="'.$m[4].'" number="'.$postnumber.'">@"'.$m[3].'"#p'.$m[4].'</POSTMENTION>'.$m[5].'</p></QUOTE>';
      },
      $bbcode
   );
   $bbcode = preg_replace('#\[QUOTE="?([^;]+?)"?]((.)+)\[\/QUOTE]#is', '<QUOTE><i>&gt; </i>@$1</br><p>$2</p></QUOTE>', $bbcode);
   $bbcode = preg_replace('#\[QUOTE]((.)*?)\[\/QUOTE]#is', '<QUOTE><i>&gt; </i><p>$1</p></QUOTE>', $bbcode);
   // Posts with this bbcodes need to be 'richtext' too
   if (preg_match('#<QUOTE|<USERMENTION|<DETAILS|<UPL-IMAGE-PREVIEW#i', $bbcode) == 1) $bbcode = preg_replace('#<t>(.*)<\/t>#is', '<r>$1</r>', $bbcode);
   return $bbcode;   
}
// ---------------------------------------------------------------------------
/**
 * Parse custom smiles to XML.
 *
 * @param  string    $postText    Text to change
 * @return string    Converted text
 */
function convertCustomSmiliesToXML($postText) {
   $vb_smiles_text = [' :\'( ', ' :-* ',' O_o ',' :[ ',' :D ',' 8*) ',' :P ',' ;) ',' :( ',' :) ','O:-)',' :-X ',' :-| ',' :-\\ ',' ::) '];
   $fl_smiles_text = [' :Smile_Ak: ', ' :Smile_Aj: ',' :Smile_Ai: ',' :Smile_Ah: ',' :Smile_Ag: ',' :Smile_Af: ',' :Smile_Ae: ',' :Smile_Ad: ',' :Smile_Ac: ',' :Smile_Ab: ',' :Smile_Aa: ',' :Smile_Al: ',' :Smile_An: ',' :Smile_Ao: ', ' :Smile_Ap: '];
   $postText = str_replace($vb_smiles_text, $fl_smiles_text, $postText);
   $smiles_text = '#(:Smile_Ak:|:Smile_Aj:|:Smile_Ai:|:Smile_Ah:|:Smile_Ag:|:Smile_Af:|:Smile_Ae:|:Smile_Ad:|:Smile_Ac:|:Smile_Ab:|:Smile_Aa:|:Smile_Al:|:smile_am:|:Smile_An:|:Smile_Ao:|:Smile_Ap:|:smile_aq:|:smile_ar:|:smile_as:|:smile_at:|:smile_au:|:smile_av:|:smile_aw:|:smile_ax:|:smile_ay:|:smile_az:|:smile_ba:|:smile_bb:|:smile_bc:|:smile_bd:|:smile_be:|:smile_bf:|:smile_bg:|:smile_bh:|:smile_bi:|:smile_bj:|:smile_bk:|:smile_bl:|:smile_bm:|:smile_bn:|:smile_bo:|:smile_bp:|:smile_bq:|:smile_br:|:smile_bs:|:smile_bt:|:smile_bu:|:smile_bv:|:smile_bw:|:smile_bx:|:smile_by:|:smile_bz:|:smile_ca:|:smile_cb:|:smile_cd:|:smile_ce:|:smile_cf:|:smile_cg:|:smile_ch:|:smile_ci:|:smile_cj:|:smile_ck:|:smile_cl:|:smile_cm:|:smile_cn:|:smile_co:|:smile_cp:|:smile_cq:|:smile_cr:|:smile_cs:|:smile_ct:|:smile_cu:|:smile_cv:|:smile_cw:|:smile_cx:|:smile_cy:|:smile_cz:|:smile_da:|:smile_db:|:smile_dc:|:smile_dd:|:smile_de:|:smile_df:|:smile_dg:|:smile_dh:|:smile_di:|:smile_dj:|:smile_dk:|:smile_dl:|:smile_dm:|:smile_dn:|:smile_do:|:smile_dp:|:smile_dr:|:smile_ds:|:smile_dt:|:smile_du:|:smile_dv:|:smile_gamer:|:smile_preved:|:smile_wacko:|:smile_you:|:smile_mail:|:smile_blyaa:|:smile_yazik:|:smile_ft:|:smile_rd:)#';
   $postText = preg_replace($smiles_text, '<E>$1</E>', $postText);
   return $postText; 
}

// ---------------------------------------------------------------------------
/**
 * Puts information out to the console.
 *
 * @param  string    $consoleText    Text to put out
 * @param  bool      $timeStamp      Whether ot not to show a timestamp
 */
function consoleOut($consoleText, $timeStamp=true) {

   $time_stamp = Date('Y-m-d H:i:s');
   $startStr = "\n";

   if ($timeStamp) {
      
      $startStr .= $time_stamp.": ";

   }
   $endStr = "";
   
   echo $startStr.$consoleText.$endStr;

}

// ---------------------------------------------------------------------------
/**
 * Gets the Flarum user ID for a given username.
 *
 * @param  string    $username    Username to look for
 * @return int       User ID
 */
function getFlarumUserId($db, $prefix, $username) {

   $userid = 0;
   $query = "SELECT id FROM ${prefix}users WHERE username = '".$username."';";
   $resUser = $db->query($query);

   if ($resUser === false) {
         
      $userid = 0;

   } else {

      $userCount = $resUser->num_rows;
      if ($userCount == 1) {

         $row = $resUser->fetch_assoc();
         $userid = $row['id'];

      } else {

         $userid = 0;

      }

   }

   return $userid;

}

function limitUse () {
   global $threads_limit;
   $limitUse = 0;
   /*$handle = fopen ("php://stdin","r");
   $line = fgets($handle);
   if(trim($line) != 'y'){
       echo "ABORTING!\n";
       exit;
   }
   fclose($handle);
   echo "\n";
   echo "Continuing...\n";*/
   if ($limitUse > $threads_limit) {

      return false;
   }
   $limitUse++;
   return true;
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
?>