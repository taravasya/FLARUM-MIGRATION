<?php
include_once 'config.php';
include_once 'functions.php';
include_once 'database.php';

echo "\nImport Forums=>Tags subscriptions";
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
}

echo "\nImport Thread=>Discussions subscriptions";
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
}
?>