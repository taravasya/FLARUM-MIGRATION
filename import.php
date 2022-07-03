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

set_time_limit(0);
ini_set('memory_limit', -1);
ini_set("log_errors", 1);
ini_set("error_log", "vBulletin_to_Flarum_error.log");

include_once 'config.php';
include_once 'functions.php';

// TEXT FORMATTER SETTINGS
include __DIR__ . '/../../vendor/autoload.php';
include_once 'flarumbundle.php'; //!!! here custom bundle for parsing vb posts content @taravasya
$parser = FlarumBundle::getParser();


$step = -1;
consoleOut("\n===============================================================================",false,true);
consoleOut("vBulletin to Flarum Migration Script                                     v".$script_version,false,true);
$step++;
include_once 'database.php';
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

//-----------------------------------------------------------------------------
//
// Group Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {

    $result = $vbulletinDbConnection->query("SELECT usergroupid, title, usertitle FROM ${vbulletinDbPrefix}usergroup");
    $totalGroups = $result->num_rows;

    if ($totalGroups) {
        consoleOut("Migrating ".$totalGroups." groups:",true, true);
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
                $res = insertupdateSQL($flarumDbConnection, $query, true);
                $res = $flarumDbConnection->query($query);
            }
        }
        consoleOut("Done",true,true);
    } else {
        consoleOut("No vBulletin groups found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// User Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {
    $result = $vbulletinDbConnection->query("SELECT userid, usergroupid, membergroupids, from_unixtime(joindate) as user_joindate, from_unixtime(lastvisit) as user_lastvisit, username, password, salt, email, birthday_search FROM ${vbulletinDbPrefix}user");
    $totalUsers = $result->num_rows;
    if ($totalUsers) {
        consoleOut("Migrating ".$totalUsers." users:",true,true);
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
                $birthday = $row['birthday_search'];
                if ($_useCustomPlugins) {
                    if ($birthday != '0000-00-00') {
                        $query = "INSERT INTO ".$flarumDbPrefix."users (id, username, email, password, joined_at, last_seen_at, is_email_confirmed, migratetoflarum_old_password, birthday, showDobDate, showDobYear) VALUES ('$id', '$username', '$email', '$password', '$joined_at', '$last_seen_at', 1, '$oldpassword', '$birthday', 1, 1)";
                    } else {
                        $query = "INSERT INTO ".$flarumDbPrefix."users (id, username, email, password, joined_at, last_seen_at, is_email_confirmed, migratetoflarum_old_password,  showDobDate, showDobYear) VALUES ('$id', '$username', '$email', '$password', '$joined_at', '$last_seen_at', 1, '$oldpassword', 1, 1)";
                    }
                } else {
                    $query = "INSERT INTO ".$flarumDbPrefix."users (id, username, email, password, joined_at, last_seen_at, is_email_confirmed) VALUES ('$id', '$username', '$email', '$password', '$joined_at', '$last_seen_at', 1)";
                }
                $res = insertupdateSQL($flarumDbConnection, $query);
                if ($res)  {
                    $ids = array();
                    array_push($ids, $row['usergroupid']);
                    if ($row['membergroupids'] != '') $ids = array_merge($ids, explode(',', $row['membergroupids']));

                    //
                    // Pick the appropriate usergroup.
                    // We are matching the first 7 default groups in vBulletin to the 4 default Flarum groups.
                    // Group IDs > 7 are just added as is.
                    //
                    foreach ($ids as $usergroupid) {
                        switch ($usergroupid) {
                            // | VBULLETIN                              |  FLARUM      |
                            // | 1 = Unregistered / Not Logged In       |  2 = Guests  |
                            // | 3 = Users awaiting email confirmation  |  2 = Guests  |
                            // | 4 = (COPPA) Users Awaiting Moderation  |  2 = Guests  |
                            case 1:
                            case 3:
                            case 4:
                            $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '2')";
                            $res = insertupdateSQL($flarumDbConnection, $query);
                            break;

                            // | VBULLETIN                              |  FLARUM      |
                            // | 2 = Registered users                   |  3 = Members |
                            case 2:
                            case 33:
                            $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '3')";
                            $res = insertupdateSQL($flarumDbConnection, $query);
                            break;

                            // | VBULLETIN                              |  FLARUM      |
                            // | 5 = Super Moderators                   |  4 = Mods    |
                            // | 7 = Moderators                         |  4 = Mods    |
                            case 5:
                            case 7:
                            $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '4')";
                            $res = insertupdateSQL($flarumDbConnection, $query);
                            break;

                            // | VBULLETIN                              |  FLARUM      |
                            // | 6 = Administrators                     |  1 = Admins  |
                            case 6:
                            $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '1')";
                            $res = insertupdateSQL($flarumDbConnection, $query);
                            break;

                            default:
                            $query = "INSERT INTO ".$flarumDbPrefix."group_user (user_id, group_id) VALUES ( '$id', '".$usergroupid."')";
                            $res = insertupdateSQL($flarumDbConnection, $query);
                            break;
                        }
                    }
                    echo(".");
                }
            } else {
                $usersIgnored++;
            }
        }
        consoleOut($i-$usersIgnored." out of ".$totalUsers." total users migrated",true,true);
    } else {
        consoleOut("No vBulletin users found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Forums => Tags Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {

    $result = $vbulletinDbConnection->query("SELECT forumid, title_clean, description_clean, from_unixtime(lastpost) as forum_lastpost, lastposter, threadcount, parentid FROM ${vbulletinDbPrefix}forum");
    $totalCategories = $result->num_rows;

    if ($totalCategories) {
        consoleOut("Migrating ".$totalCategories." categories:",true,true);
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
            $res = insertupdateSQL($flarumDbConnection, $query, true);

            if($res === false || $res == false) {
                consoleOut("Tag ID ".$id." might already exist. Trying to update record...",true,true);
                $slug = mysql_escape_mimic(slugify($row["title_clean"])).'1';
                $queryupdate = "INSERT INTO ".$flarumDbPrefix."tags (id, name, description, slug, color, position, parent_id, last_posted_at, last_posted_user_id, discussion_count) VALUES ( '$id', '$name', '$description', '$slug', '$color', '$position', $parent_id, '$last_posted_at', ".$last_posted_user_id.", ".$discussion_count.");";
                $res = insertupdateSQL($flarumDbConnection, $queryupdate, true);
            }
            $i++;
        }
        consoleOut($totalCategories." forums migrated.",true,true);
    } else {
        consoleOut("No vBulletin forums found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Thread/Posts => Discussions/Posts Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

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
        $postsNumbersArray[$row['postid']][0] = $curPostNumber;
        $postsNumbersArray[$row['postid']][1] = $curThread;
        $curPostNumber++;
    }
    // Create an array for all attachments with size and path, to insert them in posts inlined
    $attachmentsQuery = $vbulletinDbConnection->query("SELECT `attachmentid`, `filesize`, `attachment`.`dateline`, from_unixtime(`attachment`.`dateline`) AS `datetime`, `filename`, `extension`, `contentid` FROM `attachment` JOIN `filedata` ON `filedata`.`filedataid` = `attachment`.`filedataid` GROUP BY `attachmentid` ORDER BY `attachmentid` ASC;");
    while ($row = $attachmentsQuery->fetch_assoc()) {
        $fp = '/assets/files/vbattachments/';
        $fn = $row["attachmentid"].'_'.$row["dateline"].'_'.$row["filesize"].'_'.$row["contentid"].'.'.$row["extension"];
        $attachmentsArray[$row["attachmentid"]]['path'] = $fp.$fn;
        $attachmentsArray[$row["attachmentid"]]['size'] = $row["filesize"];
        $attachmentsArray[$row["attachmentid"]]['datetime'] = $row["datetime"];
    }
    $threadQuery = $vbulletinDbConnection->query("SELECT threadid, postuserid, forumid, title, from_unixtime(lastpost) as thread_lastpost, lastposter FROM ${vbulletinDbPrefix}thread ORDER BY threadid DESC;");
    $threadCount = $threadQuery->num_rows;

    if ($threadCount) {
        consoleOut("Migrating ".$threadCount." topics:",true,true);
        $insertSQL = "INSERT INTO ".$flarumDbPrefix."posts (id, discussion_id, number, created_at, user_id, type, content) VALUES";
        $curThreadCount = 0;
        $threadtotal = $threadQuery->num_rows;
        //
        // Loop through all threads
        //
        while ($thread = $threadQuery->fetch_assoc()) {
            if (limitUse()) {
                $newInsert = true;
                $curThreadCount++;
                $participantsArr = [];
                $lastPosterID = 0;
                $threadid = (int)$thread['threadid'];
                $query = "SELECT * FROM ${vbulletinDbPrefix}post WHERE threadid = {$threadid} ORDER BY dateline ASC;";
                $postsQuery = $vbulletinDbConnection->query($query);
                $postCount = $postsQuery->num_rows;

                if ($threads_limit_ids[0] && !in_array($threadid, $threads_limit_ids, true)) continue;
                if ($postCount) {
                    consoleOut("Migrating ".$postCount." posts for thread ID ".$thread["threadid"]." (".$curThreadCount." of ".$threadCount."):",true);
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
                        $insertSQL.= "('".$post['postid']."','".$thread['threadid']."','".$curPost."','".$postDate."','".$posterID."','comment','".$postText."'),";
                        if (strlen($insertSQL) * 8 > 1024000) {
                            $insertSQL = substr($insertSQL, 0, -1);
                            $res = insertupdateSQL($flarumDbConnection, $insertSQL, true);
                            $insertSQL = "INSERT INTO ".$flarumDbPrefix."posts (id, discussion_id, number, created_at, user_id, type, content) VALUES";
                        }
                        echo(".");
                    }
                    //$insertSQL = substr($insertSQL, 0, -1);
                    //$res = insertupdateSQL($flarumDbConnection, $insertSQL, true);
                } else {
                    consoleOut("Thread ".$thread['threadid']." has zero posts.",true,true);
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
                $res = insertupdateSQL($flarumDbConnection, $query);
                //
                // Check for parent forums
                //
                $isChildForum = $thread["forumid"];
                $query = "INSERT INTO ".$flarumDbPrefix."discussion_tag (discussion_id, tag_id) VALUES";
                $hasParent = false;
                while ($isChildForum) {
                    $parentForum = $vbulletinDbConnection->query("SELECT parentid FROM ${vbulletinDbPrefix}forum WHERE forumid = ".$isChildForum);
                    $result = $parentForum->fetch_assoc();

                    if ($result['parentid'] > 0) {
                        $hasParent = true;
                        $threadid = $thread["threadid"];
                        $parentid = $result['parentid'];
                        $query .= "('".$threadid."','".$parentid."'),";
                        $isChildForum = $result['parentid'];
                    } else {
                        $isChildForum = false;
                    }
                }
                if ($hasParent) {
                    $query = substr($query, 0, -1);
                    $res = insertupdateSQL($flarumDbConnection, $query);
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

                $res = insertupdateSQL($flarumDbConnection, $query);
            }
            if (strlen($insertSQL) * 8 > 1024000) {
                $insertSQL = substr($insertSQL, 0, -1);
                $res = insertupdateSQL($flarumDbConnection, $insertSQL, true);
                $insertSQL = "INSERT INTO ".$flarumDbPrefix."posts (id, discussion_id, number, created_at, user_id, type, content) VALUES";
                $newInsert = false;
            }
        }
        if ($newInsert) {
            $insertSQL = substr($insertSQL, 0, -1);
            $res = insertupdateSQL($flarumDbConnection, $insertSQL, true);
        }

        $query = "INSERT IGNORE INTO ".$flarumDbPrefix."post_mentions_post (post_id, mentions_post_id) VALUES ";
        foreach($quotesArray as $data) {
            $query .= " ('".implode("', '", $data)."'),";
        }
        $query = substr($query, 0, -1);
        $res = insertupdateSQL($flarumDbConnection, $query);

        $query = "INSERT IGNORE INTO ".$flarumDbPrefix."post_mentions_user (post_id, mentions_user_id) VALUES ";
        foreach($mentionsArray as $data) {
            $query .= " ('".implode("', '", $data)."'),";
        }
        $query = substr($query, 0, -1);
        $res = insertupdateSQL($flarumDbConnection, $query);
        consoleOut("Done",true,true);
    } else {
        consoleOut("No threads",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Avatars Migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {
    $result = $vbulletinDbConnection->query("SELECT `userid`, `filename` FROM `customavatar` ORDER BY `userid` ASC;");
    while ($row = $result->fetch_assoc()) {
        $ext = preg_replace('#.+?(\w{3,4})$#', '$1', $row["filename"]);
        $fn = $vbulletinDbConnection->real_escape_string($row["userid"].'.'.$ext);
        $query = "UPDATE ".$flarumDbPrefix."users SET avatar_url = '$fn' WHERE ".$flarumDbPrefix."users.id = ".$row["userid"].";";
        $res = insertupdateSQL($flarumDbConnection, $query, true);
    }
    consoleOut("Done",true,true);
} else {
    consoleOut("Step disabled in script",true,true);
}


//-----------------------------------------------------------------------------
//
// User/Discussions record creation
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {

    $threadQuery = $vbulletinDbConnection->query("SELECT threadid, postuserid FROM ${vbulletinDbPrefix}thread;");
    $threadCount = $threadQuery->num_rows;
    if ($threadCount) {
        consoleOut("Creating ".$threadCount." discussion_user entries:",true,true);
        $query = "INSERT INTO ".$flarumDbPrefix."discussion_user (user_id, discussion_id) VALUES";
        //
        // Loop through all threads
        //
        while($thread = $threadQuery->fetch_assoc()) {
            $query .= "('".$thread["postuserid"]."','".$thread["threadid"]."'),";
            echo (".");
        }
        $query = substr($query, 0, -1);
        $res = insertupdateSQL($flarumDbConnection, $query, true);
        consoleOut("Done",true,true);
    } else {
        consoleOut("No vBulletin threads found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}


//-----------------------------------------------------------------------------
//
// User discussion and comment count creation
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {
    $result = $flarumDbConnection->query("SELECT id FROM ".$flarumDbPrefix."users");
    if ($result->num_rows > 0) {
        $total = $result->num_rows;
        consoleOut("Updating ".$total." user records:",true,true);
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
                $res = insertupdateSQL($flarumDbConnection, $query, true);
            }
        }
        consoleOut("Done",true,true);
    } else {
        consoleOut("Flarum user table is empty",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Tag sort
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);
if ($steps[$step]['enabled']) {
    $result = $flarumDbConnection->query("SELECT * FROM ".$flarumDbPrefix."tags ORDER BY slug ASC");
    if ($result->num_rows > 0) {
        consoleOut("Updating ".$result->num_rows." tag records:",true,true);
        $position = 0;
        while ($row = $result->fetch_assoc()) {
            $query = "UPDATE ".$flarumDbPrefix."tags SET position = $position WHERE id = ".$row['id'].";";
            $res = insertupdateSQL($flarumDbConnection, $query, true);
            $position++;
        }
        consoleOut("Done",true,true);
    } else {
        consoleOut("No Flarum tags found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Disscussions subscription
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false,true);

if ($steps[$step]['enabled']) {
    $subscribedisscussions = $flarumDbConnection->query("SELECT `user_id`, `discussion_id` FROM ${flarumDbPrefix}discussion_user ORDER BY `user_id` ASC;");
    $subscribedisscussions_array = array();
    if ($subscribedisscussions->num_rows) {
        while ($row = $subscribedisscussions->fetch_assoc()) {
            if (isset($subscribedisscussions_array[$row['user_id']])) {
               array_push($subscribedisscussions_array[$row['user_id']], $row['discussion_id']);
            } else {
               $subscribedisscussions_array[$row['user_id']] = array($row['discussion_id']);
            }
        }
    }

    $subscribethread = $vbulletinDbConnection->query("SELECT `userid`, `threadid` FROM ${vbulletinDbPrefix}subscribethread ORDER BY `userid` ASC;");
    $subscribethread_array = array();
    consoleOut("Migrating ".$subscribethread->num_rows." disscussions subscription:",true,true);
    if ($subscribethread->num_rows) {
        while ($row = $subscribethread->fetch_assoc()){
           if (isset($subscribethread_array[$row['userid']])) {
              array_push($subscribethread_array[$row['userid']], $row['threadid']);
           } else {
              $subscribethread_array[$row['userid']] = array($row['threadid']);
           }
        }

        $sqlInsert = "INSERT INTO ".$flarumDbPrefix."discussion_user (user_id, discussion_id, subscription) VALUES";
        $sqlUpdate ='';
        foreach ($subscribethread_array as $user => $discussion) {
            if (!array_key_exists($user, $subscribedisscussions_array)) {
                foreach ($discussion as $discussion => $value) {
                    $sqlInsert .= "('".$user."','".$value."','follow'),";
                }
            } else {
                foreach ($discussion as $discussion => $value) {
                    if (array_search($value, $subscribedisscussions_array[$user]) === false) {
                        $sqlInsert .= "('".$user."','".$value."','follow'),";
                    } else {
                        $sqlUpdate = $flarumDbConnection->query("UPDATE ".$flarumDbPrefix."discussion_user SET subscription = 'follow' WHERE user_id = '$user' AND discussion_id = '$value';");
                        $res = $flarumDbConnection->query($sqlUpdate);
                    }
                }
            }
            echo '.';
        }
        $sqlInsert = substr($sqlInsert, 0, -1);
        $res = $flarumDbConnection->query($sqlInsert);
        consoleOut("Done",true,true);
    } else {
        consoleOut("No vB threads subscription found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Tags subsciption
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

if ($steps[$step]['enabled']) {
    $subscribetags = $flarumDbConnection->query("SELECT `user_id`, `tag_id` FROM ${flarumDbPrefix}tag_user ORDER BY `user_id` ASC;");
    $subscribetags_array = array();
    if ($subscribetags->num_rows) {
        while ($row = $subscribetags->fetch_assoc()) {
            if (isset($subscribetags_array[$row['user_id']])) {
               array_push($subscribetags_array[$row['user_id']], $row['tag_id']);
            } else {
               $subscribetags_array[$row['user_id']] = array($row['tag_id']);
            }
        }
    }

    $subscribeforum = $vbulletinDbConnection->query("SELECT `userid`, `forumid` FROM ${vbulletinDbPrefix}subscribeforum ORDER BY `userid` ASC;");
    $subscribeforum_array = array();
    if ($subscribeforum->num_rows) {
        consoleOut("Migrating ".$subscribethread->num_rows." tags subscription:",true,true);
        while ($row = $subscribeforum->fetch_assoc()) {
           if (isset($subscribeforum_array[$row['userid']])) {
              array_push($subscribeforum_array[$row['userid']], $row['forumid']);
           } else {
              $subscribeforum_array[$row['userid']] = array($row['forumid']);
           }
        }

        $sqlInsert = "INSERT INTO ".$flarumDbPrefix."tag_user (user_id, tag_id, subscription) VALUES";
        $sqlUpdate ='';
        foreach ($subscribeforum_array as $user => $tag) {
            if (!array_key_exists($user, $subscribetags_array)) {
                foreach ($tag as $tagid => $value) {
                    $sqlInsert .= "('".$user."','".$value."','follow'),";
                }
            } else {
                foreach ($tag as $tagid => $value) {
                    if (array_search($value, $subscribetags_array[$user]) === false) {
                        $sqlInsert .= "('".$user."','".$value."','follow'),";
                    } else {
                        $sqlUpdate = $flarumDbConnection->query("UPDATE ".$flarumDbPrefix."tag_user SET subscription = 'follow' WHERE user_id = '$user' AND tag_id = '$value';");
                        $res = $flarumDbConnection->query($sqlUpdate);
                    }
                }
            }
            echo '.';
        }
        $sqlInsert = substr($sqlInsert, 0, -1);
        $res = $flarumDbConnection->query($sqlInsert);
        consoleOut("Done",true,true);
    } else {
        consoleOut("No vB forums subscription found",true,true);
    }
} else {
    consoleOut("Step disabled in script",true,true);
}

//-----------------------------------------------------------------------------
//
// Reputation => Likes migration
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);
if ($steps[$step]['enabled']) {
    $reprecords = $vbulletinDbConnection->query("SELECT postid, reputation, whoadded, FROM_UNIXTIME(dateline) AS 'timestamp' FROM ${vbulletinDbPrefix}reputation WHERE reputation > 0 ORDER BY postid ASC;");
    $reprecords_array = array();
    if ($reprecords->num_rows) {
        consoleOut("Migrating ".$reprecords->num_rows." reputation records");
        $sqlInsert = "INSERT IGNORE INTO ".$flarumDbPrefix."post_likes (post_id, user_id, created_at) VALUES";
        while ($row = $reprecords->fetch_assoc()) {
            $sqlInsert .= "('".$row['postid']."','".$row['whoadded']."','".$row['timestamp']."'),";
            echo (".");
        }
        $sqlInsert = substr($sqlInsert, 0, -1);
        $res = insertupdateSQL($flarumDbConnection, $sqlInsert);
        if ($res) {
            consoleOut("Done!");
        }
    } else {
        consoleOut("No vB reputation records found");
    }
} else {
    consoleOut("Step disabled in script",true,true);
}
//-----------------------------------------------------------------------------
//
// Wrapping it up
//
$step++;
consoleOut("\n-------------------------------------------------------------------------------",false,true);
consoleOut("STEP ".$step.": ".strtoupper($steps[$step]['title'])."\n",false);

$vbulletinDbConnection->close();
$flarumDbConnection->close();

consoleOut("Done");
consoleOut("\nCheck your Flarum forum to see how it worked.",false);
consoleOut("\n===============================================================================",false);
?>