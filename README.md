# vBulletin 4 to Flarum

<img src="https://photomir.dn.ua/pastenupload/2022.06/25ntov.png" width="300">

---

![Image](https://user-images.githubusercontent.com/7197473/175793569-e4999013-dc75-4f12-a2ff-62fc23ff47f6.png)

!!!!Project is copy and edit from here:   
https://github.com/glewe/vbulletin-to-flarum

!!!Its not ready to use!!!

Script to migrate a vBulletin board to a Flarum board

Based on a phpBB migration script by robrotheram from discuss.flarum.org

Author:     George Lewe

License:    MIT

Discussion: https://discuss.flarum.org/d/25961-vbulletin-to-flarum-migration-script

## Requirements and Conditions
* The current script only supports vBulletin 4.2.
* The script assumes that both databases, vBulletin and Flarum, are on the same database server.
* The script assumes that attachments and customavatars are stored in vB database. If you attachments stored in file system, then at this moment, script will not work for you. Maybe later I check this out
* You CAN use pre-configured here bbcode parser, bearing in mind that SPOILER, QUOTE, Emoji and Emoticons codes are disabled in my configuration, and parsed with my code. Or you can configure your bundle for [s9e/TextFormatter](https://s9etextformatter.readthedocs.io/Bundles/Your_own_bundle/) by editing configurator.php as it discribed in above link to generate your own bundle and replace with it pre-configured **flarumbundle.php**.
* In Flarum needs to be already installed and enabled next plugins:

   * ! [Old Passwords](https://github.com/migratetoflarum/old-passwords.git), which add to table USERS a column **migratetoflarum_old_password** used in this script for import users with their hashed vB password
   * ! [Birthdays](https://github.com/datlechin/flarum-birthdays), for use birthdays  
     ( For plugins **Old Passwords** and **Birthdays** need DB data and prepaired db tables, so if you dont disable _$_useCustomPlugins_ in **config.php** you MUST to have installed and enabled this plugins in Flarum. Otherwise script will be failed )

   * [FoF Upload](https://github.com/FriendsOfFlarum/upload.git), for place vB post-body inline attachments to posts content in Flarum
   * [FoF Formatting](https://github.com/FriendsOfFlarum/formatting.git), for use media embedded data (converted bbcode MEDIA)
   * [FoF BBCode Details](https://github.com/FriendsOfFlarum/bbcode-details.git), for use converted bbcode SPOILER
   * [BBCode Hide Content](https://github.com/datlechin/flarum-bbcode-hide-content.git), for use converted bbcode HIDE  
     ( This plugins, can be not installed, but in this case imported text will not look like in vB. SPOILER, MEDIA, HIDE and SMILES will be look as plain text )

   * [Flamoji](https://github.com/the-turk/flarum-flamoji) to easy add custom text symbols (like :this_custom_smile:) for your emoticons  
     ( This plugin is not necesary, and it here just as suggestion, because it can help for easy transfer old good gif-ed vB smiles to Flarum)   

     ( If you dont want to use this additional flarum plugins, you can disable the import of the corresponding data in the settings that are in the **config.php** )  

## What the script import.php does
### Step 1: Group Migration
It will create all non-default vBulletin groups in Flarum. The first seven vBulletin groups will be skipped since they can be matched to one of the four default Flarum groups:

| VBULLETIN                              | FLARUM      |
| -------------------------------------- |-------------|
| 1 = Unregistered / Not Logged In       | 2 = Guests  |
| 2 = Registered users                   | 3 = Members |
| 3 = Users awaiting email confirmation  | 3 = Members |
| 4 = (COPPA) Users Awaiting Moderation  | 3 = Members |
| 5 = Super Moderators                   | 4 = Mods    |
| 6 = Administrators                     | 1 = Admins  |
| 7 = Moderators                         | 4 = Mods    |

### Step 2: User Migration
It will create all vBulletin users in Flarum with fields
* username
* email
* is_email_confirmed = 1
* joined_at
* last_seen_at
(next is optional)
* migratetoflarum_old_password      //need **Old Passwords** plugin
* birthday                          //need **Birthdays** plugin
* showDobDate                       //need **Birthdays** plugin
* showDobYear                       //need **Birthdays** plugin

It will then create the group_user tabel entries with the appropriate group IDs from step 1.

### Step 3: Forums => Tags Migration
It will read the vBulleting forum records and create corresponding tag records in Flarum with fields:
* id
* name
* description
* slug
* color
* position
* last_posted_at
* last_posted_user_id
* discussion_count

### Step 4: Thread/Posts => Discussions/Posts Migration
It will read the vBulletin Threads and their Posts and create the appropriate Flarum Discussions and Posts.

Discussion fields:
* id
* title
* slug
* created_at
* comment_count
* participant_count
* first_post_id
* last_post_id
* user_id
* last_posted_user_id
* last_posted_at

Post fields:
* id
* discussion_id
* created_at
* user_id
* type
* content

The posts content will be parsed to XML(what Flarum used to store posts content in DB) with s9e/TextFormatter what integrated in Flarum.
Inline image attachments [ATTACH]XXXID[/ATTACH] will be replaced with the <UPL-IMAGE-PREVIEW></UPL-IMAGE-PREVIEW> tags.

The posts will then be linked to the appropriate discussions.

### Step 5: User/Discussions record creation
It will link discussions with users that have contributed to them.

### Step 6: Avatars import to db
It will place to flarum table **users** paths to their avatars

### Step 6: User discussion and comment count creation
It will count discussions and comments for each user and save them accordingly.

### Step 7: Tag Sort
It will sort the tags created in step 3 in alphabetical order (since there is no feature yet in Flarum to configure their display order).

## What the script does not do
* No private messages
* No subscriptions (maybe add later)
* Something else... ???

---

# Instructions

1. Install a local web server with a MySQL database server (e.g. XAMPP)
2. Create a local copy of your vBulletin board database (export from production).
3. Install a fresh Flarum forum using the same database server.
4. Export the fresh Flarum database into a file with option "Drop if exists" and disabled foreign key check (for later re-import if you want to run the migration again with your own customizations).
5. Edit config.php and change options and the database settings to your local environment.
6. Place all .php files from this repo to folder **import** in site root, and create all other this folders:
```
flarum
└───public
	└──import
	|  └─[here all files from this repo]
	└──assets
	   └──avatars
	   └──files
	      └──vbattachments
```
7. Run attachments.php
8. Run avatars.php

(Point 7-9 will save BLOBs from vBulletin DB to file system to the folders what must be created at point 5. It is recommended to check the integrity of saved files)

10. Run import.php. It will output information to the console telling you what is going on and some errors what occurred.

### Starting a new attempt
If something went wrong and you want to start over:
1. Re-import the Flarum database
2. Recreate the Flarum database with the same name.
3. Import the fresh Flarum database that you exported after the Flarum installation.
4. Make the changes to the script that you desire (e.g. skipping certain sections)
4. Run the script again

I hope this helps.
Enjoy.

George

## Contributions Welcome
Thanks to [all who have contributed](https://github.com/glewe/vbulletin_to_flarum/graphs/contributors)
