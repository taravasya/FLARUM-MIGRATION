<?php
/**
 * vBulletin to Flarum Migration Script
 * Based on a phpBB migration script by robrotheram from discuss.flarum.org
 *
 * @author George Lewe <george@lewe.com>
 * @since 1.0.0
 * 
 * Modified by: taravasya
 * Modified by:
 */
$script_version = "1.0.1";
$post_limit = 0;

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set("log_errors", 1);
ini_set("error_log", "vBulletin_to_Flarum_error.log");

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

//-----------------------------------------------------------------------------
//
// USE FORMATTER
include __DIR__ . '/../../vendor/autoload.php';
include_once 'flarumbundle.php';

$parser = FlarumBundle::getParser();
$parser->disableTag('SPOILER');
$parser->disableTag('QUOTE');
$parser->disablePlugin('Emoji');
$parser->disablePlugin('Emoticons');
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
   array ( 'title' => 'Closing database connections',                'enabled' => true ),  // You cannot disable this step
);
$step = -1;

consoleOut("\n===============================================================================",false);
consoleOut("vBulletin to Flarum Migration Script                                     v".$script_version,false);

//-----------------------------------------------------------------------------
//
// Establish connection to the vBulletin database
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

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

//-----------------------------------------------------------------------------
//
// Group Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $result = $vbulletinDbConnection->query("SELECT usergroupid, title, usertitle FROM ${vbulletinDbPrefix}usergroup");
   $totalGroups = $result->num_rows;
   
   if ($totalGroups) {

      consoleOut("Migrating ".$totalGroups." groups:");


      while ($row = $result->fetch_assoc()) {

         $id = $row['usergroupid'];

         //
         // Flarum has four default groups already with ID 1 - 4.
         // We must not overwrite them but keep the corresponding vBulletin
         // group IDs in mind for the user migration later.
         //
         // | VBULLETIN                              |  FLARUM      |
         // | 1 = Unregistered / Not Logged In       |  2 = Guests  |
         // | 2 = Registered users                   |  3 = Members |
         // | 3 = Users awaiting email confirmation  |  3 = Members |
         // | 4 = (COPPA) Users Awaiting Moderation  |  3 = Members |
         //
         // Groups 5 to 7 in vBulletin can be matched with existing groups
         // in Flarum
         //
         // | VBULLETIN                              |  FLARUM      |
         // | 5 = Super Moderators                   |  4 = Mods    |
         // | 6 = Administrators                     |  1 = Admins  |
         // | 7 = Moderators                         |  4 = Mods    |
         //
         // Thus, we need to migrate vBulletin groups with ID > 7.
         //
         if ($id > 7) {

            $name_singular = $row["usertitle"];
            $name_plural = $row["title"];
            $color = rand_color();
            $query = "INSERT INTO ".$flarumDbPrefix."groups (id, name_singular, name_plural, color) VALUES ( '$id', '$name_singular', '$name_plural', '$color')";
            $res = $flarumDbConnection->query($query);
            
            if ($res === false) {
               
               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);

            } else {

               echo(".");

            }

         }
         
      }
      
      consoleOut("Done");
      
   } else {

      consoleOut("No vBulletin groups found");
      
   }
      
} else {

   consoleOut("Step disabled in script");
   
}

//-----------------------------------------------------------------------------
//
// User Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $result = $vbulletinDbConnection->query("SELECT userid, usergroupid, from_unixtime(joindate) as user_joindate, from_unixtime(lastvisit) as user_lastvisit, username, password, salt, email FROM ${vbulletinDbPrefix}user");
   $totalUsers = $result->num_rows;
   
   if ($totalUsers) {

      consoleOut("Migrating ".$totalUsers." users:");
      $i = 0;
      $usersIgnored = 0;
      while ($row = $result->fetch_assoc()) {

         $i++;
         if ($row["email"] != NULL) {

            //
            // Add to the user to Flarum
            //
            $id = $row['userid'];
            $username = $vbulletinDbConnection->real_escape_string($row["username"]);
            $email = $row['email'];
            $password = sha1(md5(time()));
            $joined_at = $row['user_joindate'];
            $last_seen_at = $row['user_lastvisit'];
            $oldpassword = $vbulletinDbConnection->real_escape_string('{"type":"md5-double","password":"'.$row["password"].'","salt-after":"'.$row["salt"].'"}');
            $query = "INSERT INTO ".$flarumDbPrefix."users (id, username, email, password, joined_at, last_seen_at, is_email_confirmed, migratetoflarum_old_password) VALUES ( '$id', '$username', '$email', '$password', '$joined_at', '$last_seen_at', 1, '$oldpassword')";
            $res = $flarumDbConnection->query($query);
            
            if ($res === false) {
               
               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);

            } else {

               //
               // Pick the appropriate usergroup.
               // We are matching the first 7 default groups in vBulletin to the 4 default Flarum groups.
               // Group IDs > 7 are just added as is.
               //
               switch ($row['usergroupid']) {

                  // | VBULLETIN                              |  FLARUM      |
                  // | 1 = Unregistered / Not Logged In       |  2 = Guests  |
                  // | 3 = Users awaiting email confirmation  |  2 = Guests  |
                  // | 4 = (COPPA) Users Awaiting Moderation  |  2 = Guests  |
                  case 1:
                  case 3:
                  case 4:
                     $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '2')";
                     $res = $flarumDbConnection->query($query);
                     break;
         
                  // | VBULLETIN                              |  FLARUM      |
                  // | 2 = Registered users                   |  3 = Members |
                  case 2:
                     $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '3')";
                     $res = $flarumDbConnection->query($query);
                     break;

                  // | VBULLETIN                              |  FLARUM      |
                  // | 5 = Super Moderators                   |  4 = Mods    |
                  // | 7 = Moderators                         |  4 = Mods    |
                  case 5:
                  case 7:
                     $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '4')";
                     $res = $flarumDbConnection->query($query);
                     break;

                  // | VBULLETIN                              |  FLARUM      |
                  // | 6 = Administrators                     |  1 = Admins  |
                  case 6:
                     $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '1')";
                     $res = $flarumDbConnection->query($query);
                     break;
   
                  default:
                     $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '".$row['usergroupid']."')";
                     $res = $flarumDbConnection->query($query);
                     break;

               }

               echo(".");

            }
            
         } else {

            $usersIgnored++;
            
         }
         
      }
      
      consoleOut($i-$usersIgnored." out of ".$totalUsers." total users migrated");
      
   } else {

      consoleOut("No vBulletin users found");
      
   }
      
} else {

   consoleOut("Step disabled in script");
   
}

//-----------------------------------------------------------------------------
//
// Forums => Tags Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $result = $vbulletinDbConnection->query("SELECT forumid, title_clean, description_clean, from_unixtime(lastpost) as forum_lastpost, lastposter, threadcount, parentid FROM ${vbulletinDbPrefix}forum");
   $totalCategories = $result->num_rows;
   
   if ($totalCategories) {

      consoleOut("Migrating ".$totalCategories." categories:");
      $i = 1;
      while ($row = $result->fetch_assoc()) {
         $parent_id = $row["parentid"] > 0 ? $row["parentid"] : "NULL";
         $id = $row["forumid"];
         $name = mysql_escape_mimic($row["title_clean"]);
         $slug = mysql_escape_mimic(slugify($row["title_clean"]));
         $description = mysql_escape_mimic(strip_tags(stripBBCode(html_entity_decode($row["description_clean"]))));
         $color = rand_color();
         $position = $i;
         $last_posted_at = $row['forum_lastpost'];
         $last_posted_user_id = getFlarumUserId($flarumDbConnection, $flarumDbPrefix, $row['lastposter']);
         $discussion_count = $row['threadcount'];

         $query = "INSERT INTO ".$flarumDbPrefix."tags (id, name, description, slug, color, position, parent_id, last_posted_at, last_posted_user_id, discussion_count) VALUES ( '$id', '$name', '$description', '$slug', '$color', '$position', $parent_id, '$last_posted_at', ".$last_posted_user_id.", ".$discussion_count.");";
         $res = $flarumDbConnection->query($query);
         
         if($res === false) {

            consoleOut("Tag ID ".$id." might already exist. Trying to update record...");
            $queryupdate = "UPDATE ".$flarumDbPrefix."tags SET name = '$name', description = '$description', slug = '$slug', last_posted_at = '$last_posted_at', last_posted_user_id = $last_posted_user_id, discussion_count = $discussion_count WHERE id = '$id' ;";
            $res = $flarumDbConnection->query($queryupdate);
            
            if ($res === false) {
               
               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);

            } else {

               echo(".");

            }
         
         }
         
         $i++;
         
      }
      
      consoleOut($totalCategories." forums migrated.");
      
   } else {

      consoleOut("No vBulletin forums found");
      
   }
   
} else {

   consoleOut("Step disabled in script");
   
}

//-----------------------------------------------------------------------------
//
// Thread/Posts => Discussions/Posts Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {
   $attachmentsArray = array();
   $mentionsArray = array();
   $quotesArray = array();
   $postsNumbersArray = array();
   $curPostNumber = 1;
   $curThread = 0;
   // Create an array for all posts, to save the determined Flarum posts.number to link to
   $postsNumbersQuery = $vbulletinDbConnection->query("SELECT threadid, postid FROM ${vbulletinDbPrefix}post ORDER BY threadid, dateline ASC;");
   while ($row = $postsNumbersQuery->fetch_assoc()) {
         if ($curThread != $row['threadid']) {
            $curThread = $row['threadid'];
            $curPostNumber = 1;
         }
         $postsNumbersArray[$row['postid']] = $curPostNumber;
         $curPostNumber++;
   }
   // Create an array for all attachments with size and path, to insert them in posts inlined
   $attachmentsQuery = $vbulletinDbConnection->query("SELECT `attachmentid`, `filesize`, `attachment`.`dateline`, from_unixtime(`attachment`.`dateline`) AS `datetime`, `filename`, `extension`, `contentid` FROM `filedata` JOIN `attachment` ON `filedata`.`filedataid` = `attachment`.`filedataid` GROUP BY `filedata`.`filedataid` ORDER BY `attachmentid` ASC;");
   while ($row = $attachmentsQuery->fetch_assoc()) {
      $fp = '/assets/files/vb/attachments/';
      $fn = $row["attachmentid"].'_'.$row["dateline"].'_'.$row["filesize"].'_'.$row["contentid"].'.'.$row["extension"];
      $attachmentsArray[$row["attachmentid"]]['path'] = $fp.$fn;
      $attachmentsArray[$row["attachmentid"]]['size'] = $row["filesize"];
      $attachmentsArray[$row["attachmentid"]]['datetime'] = $row["datetime"];
   }

   $threadQuery = $vbulletinDbConnection->query("SELECT threadid, postuserid, forumid, title, from_unixtime(lastpost) as thread_lastpost, lastposter FROM ${vbulletinDbPrefix}thread ORDER BY threadid DESC;");
   $threadCount = $threadQuery->num_rows;

   if ($threadCount) {

      consoleOut("Migrating ".$threadCount." topics:");
      $curThreadCount = 0;
      $threadtotal = $threadQuery->num_rows;
      //
      // Loop through all threads
      //
      while($thread = $threadQuery->fetch_assoc()) {
         if (limitUse()) {
            $curThreadCount++;
            $participantsArr = [];
            $lastPosterID = 0;
            $threadid = $thread['threadid'];
            $query = "SELECT * FROM ${vbulletinDbPrefix}post WHERE threadid = {$threadid} ORDER BY postid ASC;";
            $postsQuery = $vbulletinDbConnection->query($query);
            $postCount = $postsQuery->num_rows;

            if ($postCount) {
               consoleOut("\n#######################################################################################################",false);
               consoleOut("Migrating ".$postCount." posts for thread ID ".$thread["threadid"]." (".$curThreadCount." of ".$threadCount."):");
               $curPost = 0;
               
               //
               // Loop through all posts of the current thread
               //
               while ($post = $postsQuery->fetch_assoc()) {

                  $curPost++;

                  $date = new DateTime();
                  $date->setTimestamp($post["dateline"]);
                  $postDate = $date->format('Y-m-d H:i:s');
                  $postText = formatText($vbulletinDbConnection, $post['pagetext'], $thread['threadid'], $curPost, $post['postid']);
                  $posterID = $post['userid'];
                  // Add to the array only if unique
                  if (!in_array($posterID, $participantsArr)) $participantsArr[] = $posterID;

                  if ($curPost == $postCount) {

                     // It's the last post in the discussion. Save the last poster id.
                     $lastPosterID = $posterID;
                     
                  }

                  $query = "INSERT INTO ".$flarumDbPrefix."posts (
                     id,
                     discussion_id,
                     number,
                     created_at,
                     user_id,
                     type,
                     content
                  )
                  VALUES (
                     ".$post['postid'].",
                     ".$thread['threadid'].",
                     ".$curPost.",
                     '".$postDate."',
                     ".$posterID.",
                     'comment',
                     '".$postText."'
                  );";

                  $res = $flarumDbConnection->query($query);
                  
                  if ($res === false) {

                     consoleOut("SQL error");
                     consoleOut($query,false);
                     consoleOut($flarumDbConnection->error."\n",false);
                     
                  } else {

                     echo(".");

                  }                            
               }                                 
            } else {

               consoleOut("Thread ".$thread['threadid']." has zero posts.");
               
            }

            //
            // Convert thread to Flarum format
            // This needs to be done at the end because we need to get the post count first
            //
            $discussionDate = $thread["thread_lastpost"];
            $threadTitle = $vbulletinDbConnection->real_escape_string(html_entity_decode($thread["title"]));

            //
            // Link Discussion/Topic to a Tag/Category
            //
            $threadid = $thread["threadid"];
            $forumid = $thread["forumid"];

            $query = "INSERT INTO ".$flarumDbPrefix."discussion_tag (discussion_id, tag_id) VALUES( '$threadid', '$forumid')";
            $res = $flarumDbConnection->query($query);
            
            if ($res === false) {

               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);
               
            }

            //
            // Check for parent forums
            //
            $parentForum = $vbulletinDbConnection->query("SELECT parentid FROM ${vbulletinDbPrefix}forum WHERE forumid = ".$thread["forumid"]);
            $result = $parentForum->fetch_assoc();
            
            if ($result['parentid'] > 0) {

               $threadid = $thread["threadid"];
               $parentid = $result['parentid'];
               $query = "INSERT INTO ".$flarumDbPrefix."discussion_tag (discussion_id, tag_id) VALUES( '$threadid', '$parentid')";
               $res = $flarumDbConnection->query($query);
               
               if($res === false) {

                  consoleOut("SQL error");
                  consoleOut($query,false);
                  consoleOut($flarumDbConnection->error."\n",false);
                  
               }
               
            }
            
            if ($lastPosterID == 0) {
               
               //
               // Just to make sure it displays an actual username if the topic doesn't have posts? Not sure about this.
               // Try to find the last poster's ID in Flarum (requires vBulletin users already imported)
               //
               $lastPosterID = getFlarumUserId($flarumDbConnection, $flarumDbPrefix, strtolower($thread['lastposter']));

            }

            $slug = mysql_escape_mimic(slugify($threadTitle));
            $count = count($participantsArr);
            $poster = $thread["postuserid"];
            $query = "INSERT INTO ".$flarumDbPrefix."discussions (
               id,
               title,
               slug,
               created_at,
               comment_count,
               participant_count,
               first_post_id,
               last_post_id,
               user_id,
               last_posted_user_id,
               last_posted_at
            ) VALUES (
               '$threadid',
               '$threadTitle',
               '$slug',
               '$discussionDate',
               '$postCount',
               '$count',
               1,
               1,
               '$poster',
               '$lastPosterID',
               '$discussionDate'
            )";

            $res = $flarumDbConnection->query($query);
            
            if ($res === false) {

               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);
               
            }
         }
      }

      $query = "INSERT IGNORE INTO ".$flarumDbPrefix."post_mentions_post (post_id, mentions_post_id) VALUES ";
      foreach($quotesArray as $data) {
         $query .= " ('".implode("', '", $data)."'),";
      }
      $query = substr($query, 0, -1);
      $res = $flarumDbConnection->query($query);
      if ($res === false) {
         consoleOut("SQL error");
         consoleOut($query,false);
         consoleOut($flarumDbConnection->error."\n",false);                  
      } 
      
      $query = "INSERT IGNORE INTO ".$flarumDbPrefix."post_mentions_user (post_id, mentions_user_id) VALUES ";
      foreach($mentionsArray as $data) {
         $query .= " ('".implode("', '", $data)."'),";
      }
      $query = substr($query, 0, -1);
      $res = $flarumDbConnection->query($query);
      if ($res === false) {
         consoleOut("SQL error");
         consoleOut($query,false);
         consoleOut($flarumDbConnection->error."\n",false);                  
      }

      consoleOut("Done");
      
   } else {

      consoleOut("No threads");
      
   }
   
} else {

   consoleOut("Step disabled in script");
   
}


//-----------------------------------------------------------------------------
//
// Avatars Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {
   $result = $vbulletinDbConnection->query("SELECT `userid`, `filename` FROM `customavatar` ORDER BY `userid` ASC;");
   while ($row = $result->fetch_assoc()) {
      $ext = preg_replace('#.+?(\w{3,4})$#', '$1', $row["filename"]);
      $fn = $vbulletinDbConnection->real_escape_string($row["userid"].'.'.$ext);
      $query = "UPDATE ".$flarumDbPrefix."users SET avatar_url = '$fn' WHERE flrm_users.id = ".$row["userid"].";";
      $res = $flarumDbConnection->query($query);
      if ($res === false) {
         consoleOut("SQL error");
         consoleOut($query,false);
         consoleOut($flarumDbConnection->error."\n",false);                  
      }
   }
consoleOut("Done");   
} else {

   consoleOut("Step disabled in script");
   
}


//-----------------------------------------------------------------------------
//
// User/Discussions record creation
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $threadQuery = $vbulletinDbConnection->query("SELECT threadid, postuserid FROM ${vbulletinDbPrefix}thread;");
   $threadCount = $threadQuery->num_rows;

   if ($threadCount) {

      consoleOut("Creating ".$threadCount." discussion_user entries:");

      //
      // Loop through all threads
      //
      while($thread = $threadQuery->fetch_assoc()) {

         $userID = $thread["postuserid"];
         $threadID = $thread["threadid"];
         $query = "INSERT INTO ".$flarumDbPrefix."discussion_user (user_id, discussion_id) VALUES ( $userID, $threadID)";
         $res = $flarumDbConnection->query($query);
         
         if($res === false) {

            consoleOut("SQL error");
            consoleOut($query,false);
            consoleOut($flarumDbConnection->error."\n",false);
            
         } else {

            echo(".");

         }

      }
      
      consoleOut("Done");
      
   } else {

      consoleOut("No vBulletin threads found");
      
   }

} else {

   consoleOut("Step disabled in script");
   
}


//-----------------------------------------------------------------------------
//
// User discussion and comment count creation
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $result = $flarumDbConnection->query("SELECT id FROM ".$flarumDbPrefix."users");

   if ($result->num_rows > 0) {

      $total = $result->num_rows;
      consoleOut("Updating ".$total." user records:");
      $i = 1;
      
      while ($row = $result->fetch_assoc()) {

         $userID = $row["id"];
         $res = $flarumDbConnection->query("SELECT * FROM ".$flarumDbPrefix."discussion_user WHERE user_id = '$userID' ");
         $numTopics =  $res->num_rows;

         $res1 = $flarumDbConnection->query("SELECT * FROM ".$flarumDbPrefix."posts WHERE user_id = '$userID' ");
         $numPosts =  $res1->num_rows;

         if($res === false OR $res1 === false) {

            consoleOut("SQL error");
            consoleOut($query,false);
            consoleOut($flarumDbConnection->error."\n",false);
            
         } else {

            $query = "UPDATE ".$flarumDbPrefix."users SET discussion_count = '$numTopics',  comment_count = '$numPosts' WHERE id = '$userID' ";
            $res = $flarumDbConnection->query($query);

            if($res === false) {

               consoleOut("SQL error");
               consoleOut($query,false);
               consoleOut($flarumDbConnection->error."\n",false);
               
            } else {
   
               echo(".");
   
            }
   
         }
   
      }
      
      consoleOut("Done");

   } else {

      consoleOut("Flarum user table is empty");
      
   }

} else {

   consoleOut("Step disabled in script");
   
}

//-----------------------------------------------------------------------------
//
// Tag sort
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {

   $result = $flarumDbConnection->query("SELECT * FROM ".$flarumDbPrefix."tags ORDER BY slug ASC");

   if ($result->num_rows > 0) {

      consoleOut("Updating ".$result->num_rows." tag records:");
      $position = 0;
      
      while ($row = $result->fetch_assoc()) {

         $query = "UPDATE ".$flarumDbPrefix."tags SET position = $position WHERE id = ".$row['id'].";";
         $res = $flarumDbConnection->query($query);

         if($res === false) {

            consoleOut("SQL error");
            consoleOut($query,false);
            consoleOut($flarumDbConnection->error."\n",false);
            
         } else {

            echo(".");

         }

         $position++;

      }

      consoleOut("Done");
      
   } else {

      consoleOut("No Flarum tags found");
      
   }
   
} else {

   consoleOut("Step disabled in script");
   
}

//-----------------------------------------------------------------------------
//
// Wrapping it up
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

$vbulletinDbConnection->close();
$flarumDbConnection->close();

consoleOut("Done");
consoleOut("\nCheck your Flarum forum to see how it worked.",false);
consoleOut("\n===============================================================================",false);




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

   $text = html_entity_decode($text);

   //replace direct urls to profiles
   $text = preg_replace('#https?:\/\/wedframe\.ru\/member\.php\?u=(\d+)#', '/u/$1', $text);

   //replace urls to posts between tags [url]URL[/url]
   $text = preg_replace_callback(
      '#\[url](https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+)\[\/url]#i',
      function ($m) use ($postsNumbersArray) {
         $postnumber = isset($postsNumbersArray[$m[5]]) ? $postsNumbersArray[$m[5]] : null;
         return isset($postnumber) ? '[URL="/d/'.$m[3].'/'.$postnumber.'&pid'.$m[5].'"]Пост № '.$m[5].'[/URL]' : '[URL="/d/'.$m[3].'"]#Пост не найден#[/URL]';
      },
      $text
   );


   //replace urls to posts inside tags [url="URL"]
   $text = preg_replace_callback(
      '#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+"?]#i',
      function ($m) use ($postsNumbersArray) {
         $postnumber = isset($postsNumbersArray[$m[4]]) ? $postsNumbersArray[$m[4]] : null;
         return isset($postnumber) ? '[URL="/d/'.$m[2].'/'.$postnumber.'&pid'.$m[4].'"]' : '[URL="/d/'.$m[2].'"]';
      },
      $text
   );

   //replace urls to threads between tags [url]URL[/url]
   $text = preg_replace('#\[URL]https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)\[\/URL]#i', '[URL="/d/$2"]Тема №$2[/URL]', $text);

   //replace urls to threads inside tag [url="URL"]
   $text = preg_replace('#\[URL="?https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)"?]#i', '[URL="/d/$2"]', $text);

   //replace text urls to posts between tags [url=url]URL[/url]
   $text = preg_replace_callback(
      '#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)(&page=\d+)?&p=(\d+).*post\d+#i',
      function ($m) use ($postsNumbersArray) {
         return isset($postsNumbersArray[$m[4]]) ? 'Пост №'.$m[4] : '#Пост не найден#';
      },
      $text
   );

   //replace text urls to threads between tags [url=url]URL[/url]
   $text = preg_replace('#https?:\/\/(www.)?wedframe\.ru\/showthread\.php\?t=(\d+)#i', 'Тема №$2', $text);

   //clear whitespaces what placed next to square brackets between tags: [code] <<<MY TEXT[/code] (due to a typo or negligence). 
   $text = preg_replace('#(\[\S+])([[:blank:]]+?)(.+)([[:blank:]]+)?(\[\/\S+])#U', '$1$3$5', $text);

   $text = textFormatterParse($text);
   //echo(PHP_EOL.'[#####FORMAT] : '.$text.PHP_EOL);

   $text = convertCustomBBCodesToXML($text, $discussionid, $postnumber, $postid);
   //echo(PHP_EOL.'[#####CUSTOM] : '.$text.PHP_EOL); 

   $text = convertCustomSmiliesToXML($text);

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
   $text = preg_replace('#\[VIDEO=(.*?)]((.)*?)\[\/VIDEO]#is', '[media]$2[/media]', $text);
   $text = preg_replace('#\[ADDSHARE](.+)src="((https?:)?(.*?))"(.+)\[\/ADDSHARE]#is', '[media]https:$4[/media]', $text);
   $text = preg_replace('#\[SIZE=1](.+?)\[\/SIZE]#is', '[SIZE=10]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=2](.+?)\[\/SIZE]#is', '[SIZE=13]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=3](.+?)\[\/SIZE]#is', '[SIZE=16]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=4](.+?)\[\/SIZE]#is', '[SIZE=20]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=5](.+?)\[\/SIZE]#is', '[SIZE=25]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=6](.+?)\[\/SIZE]#is', '[SIZE=30]$1[/SIZE]', $text);
   $text = preg_replace('#\[SIZE=7](.+?)\[\/SIZE]#is', '[SIZE=35]$1[/SIZE]', $text);
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
   $bbcode = preg_replace('#\[SPOILER](.*?)\[\/SPOILER]#is', '<DETAILS title="ПОДРОБНЕЕ +++"><s>[details="ПОДРОБНЕЕ +++"]</s><p>$1</p><e>[/details]</e></DETAILS>', $bbcode);
   $bbcode = preg_replace('#\[SPOILER=(.*?)](.*?)\[\/SPOILER]#is', '<DETAILS title="$1"><s>[details="$1"]</s><p>$2</p><e>[/details]</e></DETAILS>', $bbcode);
   $bbcode = preg_replace('#\[(HIDE(.*?)|SHOWTOGROUPS(.*?))]((.)*?)\[(\/HIDE(.*?)|\/SHOWTOGROUPS(.*?))]#is', '<p>[REPLY]$4[/REPLY]</p>', $bbcode);
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
   global $post_limit;
   /*$handle = fopen ("php://stdin","r");
   $line = fgets($handle);
   if(trim($line) != 'y'){
       echo "ABORTING!\n";
       exit;
   }
   fclose($handle);
   echo "\n";
   echo "Continuing...\n";*/
   if ($post_limit > 6000) {

      return false;
   }
   $post_limit++;
   return true;
}
?>