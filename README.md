# vBulletin 4 to Flarum

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
* In Flarum need to be already installed and enabled plugin [Old Passwords](https://github.com/migratetoflarum/old-passwords.git), which add in table USERS a column **migratetoflarum_old_password** used in this script for import users with their vb password

## What the script does
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
* migratetoflarum_old_password = serialized data for Flarum **Old Passwords** plugin

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

The posts content will be parsed with s9e/TextFormatter integrated in Flarum. But some bbcodes will be prepaided with custom code, what finished text will be the most acceptable  
The posts will then be linked to the appropriate discussions.

### Step 5: User/Discussions record creation
It will link discussions with users that have contributed to them.

### Step 6: User discussion and comment count creation
It will count discussions and comments for each user and save them accordingly.

### Step 7: Tag Sort
It will sort the tags created in step 3 in alphabetical order (since there is no feature yet in Flarum to configure their display order).

## What the script does not do


## Instructions

1. Install a local web server with a MySQL database server (e.g. XAMPP)
2. Create a local copy of your vBulletin board database (export from production).
3. Install a fresh Flarum forum using the same database server.
4. Export the fresh Flarum database into a file with option "Drop if exists" and disabled foreign key check (for later re-import if you want to run the migration again with your own customizations).
5. Edit config.php and change the database settings to your local environment.
6. Place all .php files from this repo to folder **import** in site root, and create all other this folders:
```
flarum
└───public
	└──import
	|  └─[here all files from this repo]
	└──assets
	   └──avatars
	   └──smiles
	   └──files
	      └──vbattachments 
```
7. Run attachments.php
8. Run avatars.php
9. Run smiles.php
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
