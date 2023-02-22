<?php
/******************************************************************************
* POST SYNDICATION SCRIPT by chAos
* Modified by Crawlerbasher
*
* A very basic script that pulls threads with the first post from the database
* and puts them into an array form so you can use them as you like.
*
* Now works with phpBB3.3.10
*
* For use with phpBB3, freely distributable
*
******************************************************************************/

/** Notes:
*
* - Attachments haven't been handled properly.
* - Starts a forum session as Guest user, taking all the default values for time, bbcode style (from theme), etc
* - While viewing this page, users will appear to be viewing the Forum Index on viewonline.php.  
*   This can't be helped without modifying other code which is beyond this
*
*/


//////////////////////////////////////
//

define('FORUM_ID', 1);                    // Forum ID to get data from
define('POST_LIMIT', 10);                  // How many to get
define('PHPBB_ROOT_PATH', './forum/');   // Path to phpBB (including trailing /)

define('PRINT_TO_SCREEN', true);         

         // If set to true, it will print the posts out
         // If set to false it will create an array $news[] with all the following info
         //
         //   'topic_id'         eg. 119
         //   
         //   'topic_time'      eg. 06 June, 07 (uses board default)
         //   
         //   'username'         eg. chAos
         //   'topic_title'      eg. "News Post"
         //   
         //   'post_text'         eg. just the text (formatted w/ smilies, bbcode, etc)

//
//////////////////////////////////////

define('IN_PHPBB', true);
$phpbb_root_path = PHPBB_ROOT_PATH;
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Start session management
$user->session_begin(false);
$auth->acl($user->data);

// Grab user preferences
$user->setup();

$query = 
"SELECT u.user_id, u.username, t.topic_title, t.topic_poster, t.forum_id, t.topic_id, t.topic_time, t.topic_first_post_id, p.poster_id, p.topic_id, p.post_id, p.post_text, p.bbcode_bitfield, p.bbcode_uid 
FROM ".USERS_TABLE." u, ".TOPICS_TABLE." t, ".POSTS_TABLE." p 
WHERE u.user_id = t.topic_poster 
AND u.user_id = p.poster_id 
AND t.topic_id = p.topic_id 
AND p.post_id = t.topic_first_post_id 
AND t.forum_id = ".FORUM_ID." 
ORDER BY t.topic_time DESC";

$result = $db->sql_query_limit($query, POST_LIMIT);
$posts = array();
$news = array();
$bbcode_bitfield = '';
$message = '';
$poster_id = 0;

while ($r = $db->sql_fetchrow($result))
{
   $posts[] = array(
         'topic_id' => $r['topic_id'],
         'topic_time' => $r['topic_time'], 
         'username' => $r['username'], 
         'topic_title' => $r['topic_title'], 
         'post_text' => $r['post_text'],
         'bbcode_uid' => $r['bbcode_uid'],
         'bbcode_bitfield' => $r['bbcode_bitfield'],
         );
   $bbcode_bitfield = $bbcode_bitfield | base64_decode($r['bbcode_bitfield']);
}


// Instantiate BBCode
if ($bbcode_bitfield !== '')
{
   $bbcode = new bbcode(base64_encode($bbcode_bitfield));
}

// Output the posts
foreach($posts as $m)
{

   
   $message = $m['post_text'];
   if($m['bbcode_bitfield'])
   {
      $bbcode->bbcode_second_pass($message, $m['bbcode_uid'], $m['bbcode_bitfield']);
   }
         
   $message = smiley_text($message);

   
   if( PRINT_TO_SCREEN )
   {
      /* Output is in the following format
       *
       * <h3>Thread Title</h3>
       ^ <h4 class="postinfo">date // 5 comments // poster</h4>
       * <p>First post test</p>
       * 
       */
      echo "\n\n<h3>{$m['topic_title']}</h3>";
      echo "\n<h4 class=\"postinfo\">".$user->format_date($m['topic_time'])."</h4>";
      echo "\n<p>{$message}</p>";
   }
   else
   {
      $news[] = array(
            'topic_id' => $m['topic_id'], // eg: 119
            
            'topic_time' => $user->format_date($m['topic_time']), // eg: 06 June, 07 (uses board default)
            'topic_replies' => $m['topic_replies'], // eg: 26
            
            'username' => $m['username'], // eg: chAos
            'topic_title' => $m['topic_title'], // eg: "News Post"
            
            'post_text' => $message, // just the text         
            );
   }
   
   unset($message,$poster_id);
}
?>
