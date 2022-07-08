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
        'ZH', 'CH', 'CSH', 'SH', 'YU', 'YA', 'I', 'A', 'B', 'V', 'G', 'D', 'E', 'E','Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'E', 'F', 'H', 'c', '', ''
    );
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
    $s = random_int(55,70);
    $l = random_int(55,70);
    return convertHSL($h, $s, $l, true);
}

function convertHSL($h, $s, $l, $toHex=true) {
    $h /= 360;
    $s /=100;
    $l /=100;

    $r = $l;
    $g = $l;
    $b = $l;
    $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
    if ($v > 0) {
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

        switch ($sextant) {
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
    global $_removeUnwantedBBcodes;

    $text = html_entity_decode($text);
    if ($_convertInternalURLs) {
        //replace direct urls to profiles
        $text = preg_replace('#https?:\/\/wedframe\.ru\/member\.php\?u=(\d+)#', '/u/$1', $text);

        //replace urls to posts between tags [url]URL[/url]
        $text = preg_replace_callback(
            '#\[url](https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+)\[\/url]#i',
            function ($m) use ($postsNumbersArray) {
                $postnumber = isset($postsNumbersArray[$m[5]]) ? $postsNumbersArray[$m[5]][0] : null;
                return isset($postnumber) ? '[URL="/d/'.$m[3].'/'.$postnumber.'&pid'.$m[5].'"]Пост № '.$m[5].'[/URL]' : '[URL="/d/'.$m[3].'"]'.$GLOBALS['post_not_found'].'[/URL]';
            },
            $text
        );

        //replace urls to posts inside tags [url="URL"]
        $text = preg_replace_callback(
            '#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+?)(&page=\d+)?&p=(\d+).*post\d+"?]#i',
            function ($m) use ($postsNumbersArray) {
                $postnumber = isset($postsNumbersArray[$m[4]]) ? $postsNumbersArray[$m[4]][0] : null;
                return isset($postnumber) ? '[URL="/d/'.$m[2].'/'.$postnumber.'&pid'.$m[4].'"]' : '[URL="/d/'.$m[2].'"]';
            },
            $text
        );

        //replace urls to threads between tags [url]URL[/url]
        $text = preg_replace('#\[URL]https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=(\d+))?\[\/URL]#i', '[URL="/d/$2/${4}0"]'.$GLOBALS['is_thread'].' №$2[/URL]', $text);

        //replace urls to threads inside tag [url="URL"]
        $text = preg_replace('#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=(\d+))?"?]#i', '[URL="/d/$2/${4}0"]', $text);

        //replace text urls to posts between tags [url=url]URL[/url]
        $text = preg_replace_callback(
            '#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+#i',
            function ($m) use ($postsNumbersArray) {
                return isset($postsNumbersArray[$m[4]][0]) ? $GLOBALS['is_post'].' №'.$m[4] : $GLOBALS['post_not_found'];
            },
            $text
        );

        //replace text urls to threads between tags [url=url]URL[/url]
        $text = preg_replace('#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=(\d+))?#i', $GLOBALS['is_thread'].' №$2', $text);

    }
    //clear whitespaces what placed next to square brackets between tags: [code] <<<MY TEXT[/code] (due to a typo or negligence).
    $text = preg_replace('#(\[\S+])([[:blank:]]+?)(.+)([[:blank:]]+)?(\[\/\S+])#U', ' $1$3$5 ', $text);

    //remove bbcode tags that will be ignored with flarum or if they just dont need
    if ($_removeUnwantedBBcodes) $text = removeUnwantedBBcodes($text);
    $text = s9eTextFormatterParse($text);
    if ($_convertCustomBBCodesToXML) $text = convertCustomBBCodesToXML($text, $discussionid, $postnumber, $postid);
    if ($_convertCustomSmiliesToXML) $text = convertCustomSmiliesToXML($text);
    debugPosts ($postid, 'formatText', $text);
    return $connection->real_escape_string($text);
}

function removeUnwantedBBcodes ($text) {
    $UnwantedBcodes = array('[LEFT]', '[/LEFT]', '[RIGHT]', '[/RIGHT]', '[INDENT]', '[/INDENT]', '[HIGHLIGHT]', '[/HIGHLIGHT]', '[/FONT]', '[IGM]', '[/IGM]');
    $text = preg_replace('#\[FONT=.+]#iU', '', $text);
    return str_ireplace($UnwantedBcodes, '', $text);
}

// ---------------------------------------------------------------------------
/**
* Convert bbcodes to XML templates with s9e\TextFormatter
*
* @param  string    $bbcode  Source text from posts
* @return string    Converted $bbcode
*/
function s9eTextFormatterParse($text) {
    global $parser;
    $bbcode_tag_size = '';
    $bbcode_tag_h = '';
    //Adapte for s9etextformatter
    $text = preg_replace('#(\[VIDEO(=.*)?])|(\[\/VIDEO])#Ui', '', $text);
    $text = preg_replace('#\[ADDSHARE(.*?)?](.+?)src="((https?:)?(.+?))"(.+?)\[\/ADDSHARE]#i', 'https:$5'."\n", $text);
    $text = preg_replace_callback(
        '#(\[MENTION=\d+])(.*)(\[\/MENTION])#Ui',
        function($m) {
            return $m[1].preg_replace('#\[\w]|\[\/\w]#', '', $m[2]).$m[3];
        },
        $text
    );
    $text = preg_replace_callback(
        '#(\[size=(\d)])|(\[\/size])#Ui',
        function($m) use (&$bbcode_tag_size) {
            if ($m[1]) {
                $trans = array(1 => 10, 2 => 12, 3 => 15, 4 => 20, 5 => 25, 6 => 30, 7 => 40);
                $bbcode_tag_size = strtr($m[2], $trans);
                return '[SIZE='.$bbcode_tag_size.']';
            }
            if (isset($m[3]) && $m[3]) {
                return '[/SIZE]';
            }
        },
        $text
    );

    $text = preg_replace_callback(
        '#(\[H=?(\d)])|(\[\/H])#Ui',
        function($m) use (&$bbcode_tag_h) {
            if ($m[1]) {
                $bbcode_tag_h = $m[2];
                return '[H'.$bbcode_tag_h.']';
            }
            if (isset($m[3]) && $m[3]) {
                return '[/H'.$bbcode_tag_h.']';
            }
        },
        $text
    );
    $text = preg_replace('#\[noparse]#i', '[NOPARSE]', $text);
    $text = preg_replace('#\[\/noparse]#i', '[/NOPARSE]', $text);
    $text = preg_replace('#\[tt=#i', '[ACRONYM=', $text);
    $text = preg_replace('#\[\/tt]#i', '[/ACRONYM]', $text);
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
    global $postsNumbersArray;
    $bbcode = preg_replace_callback(
        '#\[ATTACH(=CONFIG)?](\d{1,10})\[\/ATTACH]#',
        function ($m) use ($attachmentsArray) {
            return isset($attachmentsArray[$m[2]]) ? '<UPL-IMAGE-PREVIEW url="'.$attachmentsArray[$m[2]]['path'].'">[upl-image-preview url='.$attachmentsArray[$m[2]]['path'].']</UPL-IMAGE-PREVIEW>' : $GLOBALS['attachment'].' №'.$m[2].' '.$GLOBALS['notfound'];
        },
        $bbcode
    );


    $bbcode = preg_replace_callback(
        '#(?>(\[SPOILER=?(.*)?])|(\[\/SPOILER]))#Ui',
        function($m) {
            if ($m[1]) {
                if (isset($m[2]) && $m[2]) {
                    $m[2] = str_replace(['=','"'], '', $m[2]);
                    return '<DETAILS title="⏵ '.$m[2].'"><s>[details="'.$m[2].'"]</s><p>';
                } else {
                    return '<DETAILS title="⏵ Подробнее"><s>[details="Подробнее"]</s><p>';
                }
            }
            if (isset($m[3])) {
                return '</p><e>[/details]</e></DETAILS>'."\n";
            }
        },
        $bbcode
    );

    $bbcode = preg_replace_callback(
        '#(?>(\[HIDE(.*)?])|(\[\/HIDE(.*)?]))#Ui',
        function($m) {
            if (isset($m[3])) {
                return '[/LOGIN]</p>';
            }
            if ($m[1]) {
                return '<p>[LOGIN]';
            }
        },
        $bbcode
    );

    $bbcode = preg_replace_callback(
        '#(?>(\[SHOWTOGROUPS=.+])|(\[\/SHOWTOGROUPS]))#Ui',
        function($m) {
            if (isset($m[2])) {
                return '[/LOGIN]</p>';
            }
            if ($m[1]) {
                return '<p>[LOGIN]';
            }
        },
        $bbcode
    );

    $bbcode = preg_replace_callback(
        '#\[MENTION=(\d+)](.+)\[\/MENTION]#Ui',
        function($m) use (&$mentionsArray, $postid) {
            array_push($mentionsArray, [$postid, $m[1]]);
            return '<USERMENTION displayname="'.$m[2].'" id="'.$m[1].'">@'.$m[2].'</USERMENTION>';
        },
        $bbcode
    );

    $bbcode = preg_replace_callback (
        '#(?>(\[QUOTE=?(.*)?])|(\[\/QUOTE]))#Ui',
        function($m) use (&$quotesArray, $discussionid, $postid, $postnumber, $postsNumbersArray) {
            if ($m[1]) {
                if (isset($m[2]) && $m[2]) {
                    if (strpos($m[2], ';') !== false) {
                        $qdata = explode(';', str_replace(['=','"'], '', $m[2]));
                        $qname = (isset($qdata[0]) && $qdata[0]) ? $qdata[0] : false;
                        $qpostid = (isset($qdata[1]) && $qdata[1]) ? $qdata[1] : false;
                        if (isset($postsNumbersArray[$qpostid][0]) && isset($postsNumbersArray[$qpostid][1]) && $qname && $qpostid) {
                            array_push($quotesArray, [$postid, $qpostid]);
                            return '<QUOTE><i>&gt; </i><p><POSTMENTION discussionid="'.$postsNumbersArray[$qpostid][1].'" displayname="'.$qname.'" id="'.$qpostid.'" number="'.$postsNumbersArray[$qpostid][0].'">@"'.$qname.'"#p'.$qpostid.'</POSTMENTION> ';
                        }
                        if ($qname) return '<QUOTE><i>&gt; </i><p>@'.$qname.', '.$GLOBALS['is_wrote'].': ';
                        return '<QUOTE><i>&gt; </i><p>';
                    } else {
                        $qname = str_replace(['=','"'], '', $m[2]);
                        return '<QUOTE><i>&gt; </i><p>@'.$qname.', '.$GLOBALS['is_wrote'].': ';
                    }
                } else {
                    return '<QUOTE><i>&gt; </i><p>';
                }
            }
            if (isset($m[3])) {
                return '</p></QUOTE>'."\n";
            }
        },
        $bbcode
    );

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
    $vb_smiles_text = [":'(", ':-*','O_o',':[',':D','8*)',':P',';)',':(',':)','O:-)',':-X',':-|',':-\\','::)'];
    $flarum_smiles_text = [' :Smile_Ak: ', ' :Smile_Aj: ',' :Smile_Ai: ',' :Smile_Ah: ',' :Smile_Ag: ',' :Smile_Af: ',' :Smile_Ae: ',' :Smile_Ad: ',' :Smile_Ac: ',' :Smile_Ab: ',' :Smile_Aa: ',' :Smile_Al: ',' :Smile_An: ',' :Smile_Ao: ', ' :Smile_Ap: '];
    $postText = str_replace($vb_smiles_text, $flarum_smiles_text, $postText);
    $smiles_text = '#(:Smile_Ak:|:Smile_Aj:|:Smile_Ai:|:Smile_Ah:|:Smile_Ag:|:Smile_Af:|:Smile_Ae:|:Smile_Ad:|:Smile_Ac:|:Smile_Ab:|:Smile_Aa:|:Smile_Al:|:smile_am:|:Smile_An:|:Smile_Ao:|:Smile_Ap:|:smile_aq:|:smile_ar:|:smile_as:|:smile_at:|:smile_au:|:smile_av:|:smile_aw:|:smile_ax:|:smile_ay:|:smile_az:|:smile_ba:|:smile_bb:|:smile_bc:|:smile_bd:|:smile_be:|:smile_bf:|:smile_bg:|:smile_bh:|:smile_bi:|:smile_bj:|:smile_bk:|:smile_bl:|:smile_bm:|:smile_bn:|:smile_bo:|:smile_bp:|:smile_bq:|:smile_br:|:smile_bs:|:smile_bt:|:smile_bu:|:smile_bv:|:smile_bw:|:smile_bx:|:smile_by:|:smile_bz:|:smile_ca:|:smile_cb:|:smile_cd:|:smile_ce:|:smile_cf:|:smile_cg:|:smile_ch:|:smile_ci:|:smile_cj:|:smile_ck:|:smile_cl:|:smile_cm:|:smile_cn:|:smile_co:|:smile_cp:|:smile_cq:|:smile_cr:|:smile_cs:|:smile_ct:|:smile_cu:|:smile_cv:|:smile_cw:|:smile_cx:|:smile_cy:|:smile_cz:|:smile_da:|:smile_db:|:smile_dc:|:smile_dd:|:smile_de:|:smile_df:|:smile_dg:|:smile_dh:|:smile_di:|:smile_dj:|:smile_dk:|:smile_dl:|:smile_dm:|:smile_dn:|:smile_do:|:smile_dp:|:smile_dr:|:smile_ds:|:smile_dt:|:smile_du:|:smile_dv:|:smile_gamer:|:smile_preved:|:smile_wacko:|:smile_you:|:smile_mail:|:smile_blyaa:|:smile_yazik:|:smile_ft:|:smile_rd:)#i';
    $postText = preg_replace($smiles_text, '<E>$1</E>', $postText);
    if (preg_match($smiles_text, $postText)) $postText = preg_replace('#<t>(.*)<\/t>#is', '<r>$1</r>', $postText);
    return $postText;
}

// ---------------------------------------------------------------------------
/**
* Puts information out to the console.
*
* @param  string    $consoleText    Text to put out
* @param  bool      $timeStamp      Whether ot not to show a timestamp
*/
function consoleOut($consoleText, $timeStamp=true, $putInDebug=false) {
    $time_stamp = Date('Y-m-d H:i:s');
    $startStr = "\n";

    if ($timeStamp) {
        $startStr .= $time_stamp.": ";
    }
    $endStr = "";
    echo $startStr.$consoleText.$endStr;
    if ($putInDebug) file_put_contents('debug.log', print_r($startStr.$consoleText.$endStr, true), FILE_APPEND);
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

function insertupdateSQL ($connection, $query, $withdot = false, $debug_only = false) {
    if (!$debug_only) {
        $res = $connection->query($query);
        if ($res === false) {
            consoleOut("SQL error");
            consoleOut($query,false);
            consoleOut($connection->error."\n",false,true);
            return false;
        } else {
            if ($withdot) echo(".");
            return true;
        }
    }
}

function saveBLOB($filepath, $filename, $content) {
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

function limitUse () {
    global $threads_limit;
    if ($threads_limit < 1) {
        return false;
    }
    $threads_limit--;
    return true;
}

function interactive () {
    echo "\nContinue? Type y/n: ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    if(trim($line) != 'y'){
        echo "ABORTING!\n";
        exit;
    }
    fclose($handle);
    echo "\n";
    echo "Continuing...\n";
}

function debugPosts ($postid, $mark, $text) {
    global $_debugPosts;
    if (in_array($postid, $_debugPosts)) file_put_contents('debugPosts.log', print_r("\n\n".' ################ '.$postid.'# '.$mark.' # '."\n\n".$text, true), FILE_APPEND);
}
?>