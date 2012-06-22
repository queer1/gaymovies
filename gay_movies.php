<?php

// v1.0 -- Initial release.
// v1.1 -- Add distinct edit/rate/browse modes.
// v1.2 -- Cleanup forms.
// v1.3 -- Modify traversal to go direct to edit, skipping browse
// v1.4 -- Make display mode menu same as edit mode menu
// v1.5 -- Fixed bugs and added special edit filters
// v2.0 -- Added support for combo ratings
// v2.1 -- Fix Combo rating to go from 1 to 10 (instead of 0 - 10) and
//         Show logged in user's rating along with combo when combo sort
// v2.2 -- Add ability to save/view suggestions
// v2.3 -- Add ability to save/view suggestions
// v2.4 -- Display 'new' icon and sort by created date.
// v2.5 -- Added support for facebook login -- currently disabled
// v2.6 -- Added initial support for multiple users; fixed lots of bugs
// v3.0 -- Fully support multiple users now. First version ready for beta test.
// v3.1 -- Cleaned up the grouping code somewhat.
// v3.2 -- Overhaul of UI
// v3.3 -- Added about menu option
// v3.4 -- Added FAQ menu option; fixed large icon display
// v3.5 -- Added better support for adding links when editing movies
// v3.6 -- Add like button and amazon buy buttons
// v3.7 -- Removed edit-mode menu; added welcome screen; update last login
// v3.8 -- Cleaned up formatting; added TOS
// v3.81 -- Fixed bug when trying to create new user
// v3.9 -- Fixed prev/next bug; add tips; re-added edit-mode menu
// v3.91 -- Fixed a few minor bugs with site traversal; added donate button
// v3.92 -- Made tips bar clickable
// v3.93 -- Add ability to save movie recommendations; aren't displayed yet.
// v4.0  -- Add recommendations!
// v4.01  -- Fixed some major traversal bugs with last version
// v4.02  -- Fixed more major traversal bugs
// v4.03  -- Added option to view all recommendations
// v4.04  -- full switchover to new domain; added meta data
// v4.1   -- Add beta version of 'Recent Activities'
// v4.11  -- Fiexed bug in displaying activity link to movie
// v4.12  -- Added abiity to auto-generate site map
// v4.13  -- Fix bug in displaying next movie buttons
// v4.14  -- Fix bug in editing movie from menu
// v4.15  -- Enhanced secondary sorting; added preliminary keyword support
// v4.16  -- Add Date to the Recent User Updates; handled no title case.
// v4.17  -- Modified recommended tab format to show past movies in left col.
// v4.18  -- Added 'share' button on recommended movie.
// v4.19  -- Fixed comment display anomalies
// v4.20  -- Show random comments; Improve formatting of comments
// v4.21  -- Updated activity info
// v4.22  -- Updated activity info
// v4.23  -- Updated activity info; restructured how we setup meta data
// v4.24  -- Display random comments even if not logged in
// v4.30  -- Added Flixster ratings and Scarecrow Video links
// v4.40  -- Added SIFF2011; fixed name of flixster (was flixter)
// v4.41  -- Changed way siff movies are displayed
// v4.42  -- Fixed hella php errors
// v4.43  -- Auto update of site image
// v4.44  -- Minor updates / cleanup
// v5.00  -- Updated Facebook login to be compatible with latest FB API
  $version = "v5.00";

  $php_self = $_SERVER['PHP_SELF'];

  $site_url = "http://thegaymovielist.com/";

  $site_title = "The Gay Movie List";

  $site_description  = <<<SITE_DESC
Your first stop when looking for a gay movie. 
See this week's recommendation; 
share your own movie ratings and comments with your Facebook friends; 
quickly find more information via IMDB and RottenTomato links.
SITE_DESC;

  $new_icon = "media/new_03.gif";
  $new_icon_width = 50;
  $new_icon_height = 50;

  $debug = 0;

  $no_help = true;

  // Init global variables to keep track of ranges of values
  // (used in calculating combo rating))
  $min_imdb    = 100;
  $max_imdb    =  -1;
  $min_durwood = 100;
  $max_durwood =  -1;
  $min_alex    = 100;
  $max_alex    =  -1;
  $ranges_calculated = false;


  // Set the icon and cover image heights
  $allow_facebook_login = true;
  define('FACEBOOK_APP_ID', '122327374503892');
  define('FACEBOOK_SECRET', '5a7c4b508c5530cda1f875d28bc558ba');
  define('ALEX_FB_UID', '572148635');
  define('DURWOOD_FB_UID', '1184847479');
  define('ALEX_UID', '3');
  define('DURWOOD_UID', '4');
  define('ALEX_USERNAME', 'Alexandre');
  define('DURWOOD_USERNAME', 'Durwood');

  require '/var/www/durwood/facebook-php-sdk/src/facebook.php';
  $facebook = new Facebook(array(
    'appId'  => FACEBOOK_APP_ID,
    'secret' => FACEBOOK_SECRET,
    'fileUpload' => false,
    'cookie' => true, // enable optional cookie support
  ));

  session_start();

  $fbUID = $facebook->getUser();
  if ($fbUID)
  {
    try
    {
      $me = $facebook->api('/me');
    }
    catch(FacebookApiException $e){
      error_log($e);
      $fbUID = null;
    }
  }

  // Handle session initialization
  if (isset($_REQUEST['test_mobile']))
    $_SESSION['is_mobile'] = true;
  if (isset($_REQUEST['reset']))
  {
    unset($_SESSION);
    unset($fbSession);
  }

  if (isset($_REQUEST['debug']))
    $debug = 1;

  // Handle MySQL connection
  $connection = mysql_connect("localhost", "durwood", "laika" );
  mysql_select_db("gay_movies", $connection);

  init_session();

  $site_image = $_SESSION['site_image'];

  // process session
  toggle_session();
  
  if (process_form($_REQUEST))
    return;

  display_doctype("Frameset");
?>

<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:fb="http://www.facebook.com/2008/fbml">

  <script type="text/javascript" src="gay_movies.js"></script>
  <script type="text/javascript" src="dropdowntabs.js"></script>
  <style type="text/css">
  @import "bluetabs.css";
  @import "my.css";
  @import "columns.css";
  </style>
    
<head>

<?php


print("<title>$site_title</title>\n");
display_meta_name("title", $site_title);
display_meta_name("description", $site_description);
if ($site_image != "")
  display_meta_link("image_src", "http://thegaymovielist.com/$site_image");
display_meta_name("keywords", "gay,movie,gay movie list,rate gay movies,gay movie recommendation,best gay movies,favorite gay movies");
display_meta_name("google-site-verification", 
                  "Bwe0qfR_J4YTS489d5PHJkrG1xpydEh1jop8uBHWcwo");
display_fbook_meta_data(isset($_REQUEST['movie_id']) ? $_REQUEST['movie_id'] : 0);

?>

</head>

<body>

<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId : '<?php echo $facebook->getAppId(); ?>',
      session : <?php echo json_encode($fbSession); ?>,
      status : true, // check login status
      cookie : true, // enable cookies to allow the server to access the session
      xfbml : true // parse XFBML
    });

    // whenever the user logs in, we refresh the page
    FB.Event.subscribe('auth.login', function() {
      window.location.reload();
    });
  };

  (function() {
    var e = document.createElement('script');
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    e.async = true;
    document.getElementById('fb-root').appendChild(e);
  }());
</script>

<div id="container">

<div id="anchor_top"></div>
<a href="#anchor_top" accesskey="t"></a>
<a href="/" accesskey="h"></a>

<?php

  // Set the icon and cover image heights
  $g_cover_height  = 400;
  if ($_SESSION['view_mode'] == 'compact')
    $g_icon_height = 100;
  elseif ($_SESSION['view_mode'] == 'large')
    $g_icon_height = 200;
  else
    $g_icon_height = 0;

  // Don't allow unauthorized editing or rating
  if (isset($_REQUEST['edit']) && (!allow_edit()))
    unset($_REQUEST['edit']);
  if (isset($_REQUEST['rate']) && (!allow_rating()))
    unset($_REQUEST['rate']);

  // Display debug info
  if ($debug)
  {
    display_request();
    display_session();
  }

  // toggle_session();
  // --- Main Loop ---
  if ( isset($_REQUEST['update_combo'] )) {
    update_all_combo_ratings();
    unset($_REQUEST['update_combo']);
  }
  if ( isset($_REQUEST['update_sitemap'] )) {
    update_sitemap();
    unset($_REQUEST['update_sitemap']);
  }
/*
  if ( isset($_REQUEST['friend'] )) {
    set_friend($_REQUEST['friend'], true);
    unset($_REQUEST['friend']);
  }
*/
  if (isset($_REQUEST['login']))
    return;
  elseif ( isset($_REQUEST['welcome'])
    || ($_SESSION['logged_in'] && $_SESSION['first_login']))
    display_welcome();
  elseif (isset($_REQUEST['confirm_delete']))
    confirm_delete($_REQUEST['movie_id']);
  elseif (isset($_REQUEST['movie_suggestion']))
    display_movie_suggestions();
  elseif (isset($_REQUEST['website_suggestion']))
    display_website_suggestions();
  elseif (isset($_REQUEST['show_privacy']))
    display_privacy_statement();    
  elseif (isset($_REQUEST['show_tos']))
    display_terms_of_service();    
  elseif (isset($_REQUEST['faq']))
    display_faq();
  elseif (isset($_REQUEST['about']))
    display_about();
  elseif ( (isset($_REQUEST['recommend_movie']))
        && (isset($_REQUEST['movie_id']))
        && allow_edit())
    recommend_movie($_REQUEST['movie_id']);
  elseif ( (isset($_REQUEST['edit']))
        && (!isset($_REQUEST['movie_id'])) )
    edit_movie();
  elseif ( isset($_REQUEST['recommended'] ))
  {
    if ( (isset($_REQUEST['rate'])) 
      && (isset($_REQUEST['movie_id'])) 
      && allow_rating())
    {
      $_SESSION['edit_mode'] = "rate";
      rate_movie($_REQUEST['movie_id']);
    }
    else
      display_movie_recommendation($_REQUEST['movie_id']);
  }
  elseif ( (   isset($_REQUEST['edit']) 
            || isset($_REQUEST['rate']) 
            || isset($_REQUEST['edit_mode']))
         && (isset($_REQUEST['movie_id'])) )
  {
    if ($_SESSION['edit_mode'] == "full_edit")
      edit_movie($_REQUEST['movie_id']);
    elseif ($_SESSION['edit_mode'] == "rate")
      rate_movie($_REQUEST['movie_id']);
    else
      display_movie_info($_REQUEST['movie_id']);
  }
  elseif (isset($_REQUEST['movie_id']))
      display_movie_info($_REQUEST['movie_id']);


  elseif (!isset($_REQUEST['login']) 
      || ($_SESSION['logged_in'] == true))  // don't display anything if login req
  {
    if ( $_SESSION['tab_status'] == 'recommendation')
      display_movie_recommendation();
    else
      display_movie_list();
  }

?>

<div class="push"></div>

</div>

<div class="footer">
<p>Copyright &#169 2011 Durwood Gafford. All Rights Reserved. --
<a href="javascript:TOSPopup('/privacy.html')">View Privacy Statement</a> --
<a href="javascript:TOSPopup('/tos.html')">View Terms of Service</a>
<?php
display_donate_button();
?>

</p>
</div>

</body>
</html>

<?php

function allow_admin()
{
  if ($_SESSION['logged_in'] && $_SESSION['allow_admin'])
    return true;
  else
    return false;
}
function allow_rating()
{
  if ($_SESSION['logged_in'] && $_SESSION['allow_rating'])
    return true;
  else
    return false;
}

function allow_edit()
{
  if ($_SESSION['logged_in'] && $_SESSION['allow_edit'])
    return true;
  else
    return false;
}

function display_movie_suggestions()
{
  global $php_self;

  print("<div class=\"left_column lefty\">\n");
  print "<form method=\"post\" action=\"$php_self\" name=\"movie_suggestion\">\n";
  print ("<table>\n");
  add_textbox("New Suggestion", "suggestion", "");
  print ("</table>\n");
  display_submit_button("save_movie_suggestion", "Save Suggestion");
  display_submit_button("cancel", "Return to List");
  print ("</form>\n");

  print "<pre\n>";
   echo @readfile("movie_suggestions.txt");
  print "</pre>\n";
  print("</div>\n");
}

function display_website_suggestions()
{
  global $php_self;

  print("<div class=\"left_column lefty\">\n");
  print "<form method=\"post\" action=\"$php_self\" name=\"website_suggestion\">\n";
  print ("<table>\n");
  add_textbox("New Suggestion", "suggestion");
  print ("</table>\n");
  display_submit_button("save_website_suggestion", "Save Suggestion");
  display_submit_button("cancel", "Return to List");
  print ("</form>\n");

  print "<pre\n>";
   echo @readfile("website_suggestions.txt");
  print "</pre>\n";
  print("</div>\n");
}

function display_welcome()
{
  global $php_self;

  print("<div class=\"left_column lefty\">\n");

  print "<pre\n>";
   echo @readfile("welcome.txt");
  print "</pre>\n";
  print "<form method=\"post\" action=\"$php_self\" name=\"welcome\">\n";
  display_submit_button("cancel", "Proceed to Website");
  print ("</form>\n");
  print("</div>\n");

  $_SESSION['first_login'] = false;
}

function display_privacy_statement()
{
  global $php_self;

  print("<div class=\"left_column lefty\">\n");
  if ($_REQUEST['from'] == "about")
    print "<form method=\"post\" action=\"$php_self?about\" name=\"privacy\">\n";
  else
    print "<form method=\"post\" action=\"$php_self\" name=\"privacy\">\n";
  display_submit_button("cancel", "Return to Previous Page");
  print ("</form>\n");

  print "<pre\n>";
   echo @readfile("privacy.txt");
  print "</pre>\n";
  print("</div>\n");
}

function display_terms_of_service()
{
  global $php_self;

  print("<div class=\"left_column lefty\">\n");
  if ($_REQUEST['from'] == "about")
    print "<form method=\"post\" action=\"$php_self?about\" name=\"tos\">\n";
  else
    print "<form method=\"post\" action=\"$php_self\" name=\"tos\">\n";
  display_submit_button("cancel", "Return to Previous Page");
  print ("</form>\n");

  print "<iframe src=\"/tos.html\"></iframe>\n";
  print("</div>\n");
}

function display_fbook_login_button()
{
  print("<fb:login-button length=\"long\"></fb:login-button>\n");
}

function display_fbook_like_button($layout, $movie_id)
{
  if (isset($movie_id) && $movie_id > 0)
    $url = "http://thegaymovielist.com/?movie_id=$movie_id";
  else
    $url = "http://thegaymovielist.com/";

  if ($layout == "standard")
    print("<fb:like href=\"$url\" width=\"400\" height=\"80\"/>\n");
  elseif ($layout == "button_count")
    print("<fb:like layout=\"$layout\" href=\"$url\" width=\"90\" height=\"20\"/>\n");
}

function display_old_fbook_like_button()
{
  $url = "http://thegaymovielist.com";
  print("<div class=\"centered\">\n");
  print("<iframe src=\"http://www.facebook.com/plugins/like.php?href=$url\"\n");
  print("scrolling=\"no\" frameborder=\"0\"");
if ($_SESSION['logged_in'])
  print("style=\"border:none; width:400px; height:55px\"></iframe>\n");
else
  print("style=\"border:none; width:400px; height:55px\"></iframe>\n");
  print("</div>\n");
}

function display_fbook_meta_data($movie_id=0)
{
  global $site_description;
  global $site_title;
  global $site_url;
  global $site_image;

  if ($movie_id != 0)
  {
    $query = "SELECT title,art FROM movie WHERE movie_id=$movie_id LIMIT 1";
    $result = mysql_query($query) or die ("Query Failure: $query");
    $row = mysql_fetch_array($result);
    $title = $row['title'];
    $art   = $row['art'];
    if (isset($art))
    {
      $image = get_image($art, 400);
      if (!is_file($image))
        $image = "";
    }

    if (isset($title) && $title != "")
      display_fbook_meta_tag("og:title", $title);

    display_fbook_meta_tag("og:type", "movie");
    display_fbook_meta_tag("og:url", "$site_url?movie_id=$movie_id");

    if (isset($image) && $image != "")
      display_fbook_meta_tag("og:image", "{$site_url}$image");
  }
  else
  {
    display_fbook_meta_tag("og:title", $site_title);
    display_fbook_meta_tag("og:type", "website");
    display_fbook_meta_tag("og:url", $site_url);
    display_fbook_meta_tag("og:image", "{$site_url}$site_image");
  }
  display_fbook_meta_tag("og:site_name", $site_title);
  display_fbook_meta_tag("fb:app_id", FACEBOOK_APP_ID);
  display_fbook_meta_tag("og:description", $site_description);
}

function display_fbook_meta_tag($property, $content)
{
  print("<meta property=\"$property\" content=\"$content\" />\n");
}

function display_meta_name($name, $content)
{
  print("<meta name=\"$name\" content=\"$content\" />\n");
}

function display_meta_link($rel, $url)
{
  print("<link rel=\"$rel\" href=\"$url\" />\n");
}

function display_fbook_share_button()
{
  global $site_image;

  print("<fb:share-button class=\"meta\">\n");
  display_meta_name("medium", "mult");
  display_meta_name("title", "The Gay Movie list");
  display_meta_name("description", "The first stop when looking for a gay movie to watch. See this week's recommendation; share your own movie ratings and comments with your Facebook friends. Quickly find more information via IMDB and RottenTomato links.");
  display_meta_link("image_src", "http://thegaymovielist.com/$site_image");
  display_meta_link("target_url", "http://thegaymovielist.com/");
  print("</fb:share-button>\n");
}

function handle_login_logout()
{
  global $php_self;
  global $fbUID;

  // Has a logged in user requested to logout?
  $logout_requested = false;
  $login_requested = false;
  $display_login_prompt = false;
  if ($_SESSION['logged_in'] == true)  // logged in currently
  {
    if (isset($_REQUEST['logout']))
      $logout_requested = true;
    elseif (($_SESSION['fb_logged_in'] == true) && ($fbUID == null))
      $logout_requested = true;
  }
  else // logged out currently
  {
    if (isset($_REQUEST['validate_login']))
      $login_requested = true;
    elseif ($fbUID)
      $login_requested = true;
    elseif (isset($_REQUEST['login']))
      $display_login_prompt = true;
  }

  if ($logout_requested)
  {
    set_logged_out_flags();
    if (isset($_REQUEST['logout']))
      redirect_to_movie_list();
  }
  elseif ($login_requested)
  {
    if (isset($_REQUEST['validate_login']))
      validate_login($_REQUEST['username'], $_REQUEST['pwd']);
    else
      validate_login();
  }
  elseif ($display_login_prompt)
  {
    display_login_prompt();
  }
  return;
}

function display_activity()
{
  display_doctype("Frameset");

?>
  <html xmlns="http://www.w3.org/1999/xhtml">

  <style type="text/css">
  @import "my.css";
  @import "columns.css";
  </style>
<?php

  global $php_self;

  print("<i>This is a beta version of this feature...for now, use your back button to return to main website</i><br/><br/>\n");

  $columns = "uid,movie_id,new_rating,created,activity";
  $query = "SELECT $columns FROM activity ORDER BY activity_id DESC";
  $result = mysql_query($query);
  $current_date_string = "";
  if ($result)
  {
    print("<div class=\"colmask doublepage\">\n");
    print("<div class=\"colleft\">\n");

    print("<div class=\"col1\">\n");
    print("<u>Recent User Updates</u>:<br/>\n");
    while ($row = mysql_fetch_array($result)) 
    {
      $uid        = $row['uid'];
      $movie_id   = $row['movie_id'];
      $activity   = $row['activity'];
      $new_rating = $row['new_rating'];
      $firstname  = get_user_firstname($uid);
      $title      = get_movie_title($movie_id);
      $date_integer = strtotime($row['created']);
      $date_string = date('F j', $date_integer);

      if (!isset($title) || $title == "")
        continue;

      if ($activity == 'add_new')
        continue;

      if ($date_string != $current_date_string)
      {
        print("<br/>\n");
        print("<u>$date_string</u>");
        print("<br/>\n");
        $current_date_string = $date_string;
      }

      print("<div class=\"activity\">\n");
      if ($activity == 'rate')
      {
        print("$firstname gave a rating of $new_rating to: ");
      }
      else if ($activity == 'watch')
      {
        print("$firstname has now seen: ");
      }
      else if ($activity == 'want_to_see')
      {
        print("$firstname wants to see: ");
      }
      else if ($activity == 'add_comment')
      {
        if (isset($new_rating) && $new_rating > 0)
          print("$firstname gave a rating of $new_rating to: ");
        else
          print("$firstname commented on: ");
      }
      display_link("$php_self?movie_id=$movie_id", $title, "");
      
      if ($activity == 'add_comment')
      {
        $comment = get_user_movie_comment($uid, $movie_id);
        if (isset($comment))
        {
          $comment = trim($comment);
          print("<p class=\"activity_comment\">\n");
          print("$comment\n");
          print("</p>\n");
        }
      }
      else
        print("<br/>\n");

      print("</div>\n");
    }
    print("</div>\n");

    print("<div class=\"col2\">\n");
    print("<u>Movies Recently Added</u>:<br/>\n");
    mysql_data_seek($result, 0);
    $current_date_string = "";
    while ($row = mysql_fetch_array($result)) 
    {
      $activity = $row['activity'];
      if ($activity == 'add_new')
      {
        $uid        = $row['uid'];
        $movie_id   = $row['movie_id'];
        $activity   = $row['activity'];
        $created    = $row['created'];

        $title      = get_movie_title($movie_id);
        $date_integer = strtotime($row['created']);
        $date_string = date('F j', $date_integer);

        if (!isset($title) || $title == "")
          continue;

        if ($date_string != $current_date_string)
        {
          print("<br/>\n");
          print("<u>$date_string</u>");
          print("<br/>\n");
          $current_date_string = $date_string;
        }

        print("<div class=\"activity\">\n");
        display_link("$php_self?movie_id=$movie_id", $title, "");
        print("<br/>\n");
        print("</div>\n");
      }
    }
    print("</div>\n");

    print("</div>\n");
    print("</div>\n");
  }
?>
  </html>
<?php
}

function display_doctype($type)
{
  if ($type == "Frameset")
  {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<?php
  }
  elseif ($type == "Transitional")
  {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
  }
  elseif ($type == "Strict")
  {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
  }
  else
  {
?>
<!DOCTYPE html>
<?php
  }
}

function display_login_prompt()
{
  global $php_self;

  $privacy_link = "<a href=\"javascript:TOSPopup('/privacy.html')\">privacy statement</a>";
  print "<form method=\"post\" action=\"$php_self\" name=\"login\">\n";

  print 
"You are encouraged to use the Facebook login if possible; it<br/>";
  print 
"provides a richer experience. But if you don't use facebook or<br/>";
  print 
"if after reading the $privacy_link you're still concerned, you<br/>";
  print 
"may email me at gaymovielist@gmail.com to request a standard login<br/>";
  print "<br/>";
  print "You may browse movies on the site without logging in at all.<br/>";
  print "<br/>";

  print ("<table>\n");
  add_textbox("Username", "username");
  add_password_box("Password", "pwd");
  print ("</table>\n");
  print ("<input type=\"submit\" name=\"validate_login\" value=\"Login\"/>\n");
  print ("</form>\n");
  print "<script type=\"text/javascript\" language=\"JavaScript\">\n";
  print "document.forms['login'].elements['username'].focus();\n";
  print "</script>\n";
}

function validate_login($username="", $password="")
{
  global $debug;
  global $facebook;
  global $fbUID;

  // Assume we are logged out until validaated
  set_logged_out_flags();

  $facebook_login = false;
  if (($username == "") && ($password == ""))
  {
    $filter = "fb_userid = $fbUID";
    $facebook_login = true;
  }
  else
  {
    $filter = "username = '$username' AND password = '$password'";
  }
    
  $select = "uid,access,username,fb_userid,friend";
  $query = "SELECT $select FROM user WHERE $filter LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);

  $success = false;
  if ($result && ($row = mysql_fetch_array($result)))
  {
    $access   = $row['access'];
    $username = $row['username'];
    $fb_uid   = $row['fb_userid'];
    $uid      = $row['uid'];
    $friend   = $row['friend'];
    complete_login($access, $username, $uid, $fb_uid, $friend, $facebook_login);
  }
  elseif ($facebook_login)
  {
    $me = $facebook->api('/me');

    $fb_firstname = $me['first_name'];
    $fb_lastname  = $me['last_name'];
    $fb_uid       = $fbUID;
    $fb_firstlast = "$fb_firstname" . "$fb_lastname";

    // Search for Firstname and Lastname before assuming this is new user
    $filter = "fb_firstname='$fb_firstname' AND fb_lastname='$fb_lastname'";
    $select = "access,uid,username,fb_userid";
    $query  = "SELECT $select FROM user WHERE $filter LIMIT 1";
    if ($debug)
      print("Running Query: $query<br>\n");
    $result = mysql_query($query);
    if ($result && ($row = mysql_fetch_array($result)))
    {
      $uid      = $row['uid'];
      $access   = $row['access'];
      $username = $row['username'];
      if ($row['fb_userid'] == $fbUID)
      {
        // Facebook user found
        complete_login($access, $username, $uid, $fbUID, $friend, $facebook_login);
      }
      else
      {
        // User found but fb_userid not stored yet in database
        $set = "fb_userid=$fbUID";
        if (!isset($row['username']))
          $set .= ", username='$fb_firstlast'";
  
        $query = "UPDATE user SET $set WHERE $filter LIMIT 1";
        if ($debug)
          print("Running Query: $query<br>\n");
        $result = mysql_query($query) or die("Query Failed when adding user: $query");
        if ($result)
          complete_login($access, $username, $uid, $fbUID, $friend, $facebook_login);
      }
    }
    else // ASSUME NEW USER
    {
      $set =  "fb_userid=$fbUID";
      $set .= ", fb_firstname='$fb_firstname'";
      $set .= ", fb_lastname='$fb_lastname'";
      $set .= ", username='$fb_firstlast'";
      $set .= ", access='rate'";
      $query = "INSERT INTO user SET $set";
      $result = mysql_query($query) or die("Query Failed when adding user: $query");
      if ($result)
      {
        $uid = mysql_insert_id();
        complete_login("rate", $fb_firstlast, $uid, $fbUID, $friend, $facebook_login);
      }
    }
  }
}

function complete_login($access, $username, $uid, $fb_uid, $friend, $facebook_login)
{
  global $facebook;

  if ($facebook_login)
  {
    $me = $facebook->api('/me');
    $_SESSION['firstname'] = $me['first_name'];
  }
  else
  {
    $_SESSION['firstname']     = $username;
  }
  $_SESSION['logged_in']     = true;
  $_SESSION['username']      = $username;
  $_SESSION['uid']           = $uid;
  $_SESSION['edit_mode']     = 'browse';
  $_SESSION['fb_uid']        = $fb_uid;
  $_SESSION['refresh_count'] = 0;
  if ($facebook_login)
  {
    $_SESSION['fb_logged_in'] = true;
    build_friend_list();
  }
  else
    $_SESSION['fb_logged_in'] = false;

  if (isset($access) && str_contains($access, "admin"))
  {
    $_SESSION['allow_rating'] = true;
    $_SESSION['allow_edit']   = true;
    $_SESSION['allow_admin']  = true;
  }
  else if (isset($access) && str_contains($access, "edit"))
  {
    $_SESSION['allow_rating'] = true;
    $_SESSION['allow_edit']   = true;
    $_SESSION['allow_admin']  = false;
  }
  else if (isset($access) && str_contains($access, "rate"))
  {
    $_SESSION['allow_rating'] = true;
    $_SESSION['allow_edit']   = false;
    $_SESSION['allow_admin']  = false;
  }
  else
  {
    $_SESSION['allow_rating'] = false;
    $_SESSION['allow_edit']   = false;
    $_SESSION['allow_admin']  = false;
  }

  set_friend($friend);

  $_SESSION['first_login'] = is_first_login($uid);
}

function is_first_login($uid)
{
   $query = "SELECT last_login from user where uid=$uid LIMIT 1";
   $result = mysql_query($query) or die("Could not retrieve user record");
   $row = mysql_fetch_array($result);
   $last_login = $row['last_login'];

   $first_login = false;
   if (!isset($last_login) 
    || $last_login == "0000-00-00 00:00:00")
   {
     $first_login = true;
   }

   $query = 
      "UPDATE user SET last_login=CURRENT_TIMESTAMP WHERE uid=$uid LIMIT 1";
   $result = mysql_query($query);
   return($first_login);
}


function set_friend($friend, $save=false)
{
  if (!isset($friend) || $friend == 0)
  {
    $_SESSION['friend_firstname'] = DURWOOD_USERNAME;
    $_SESSION['friend_uid']       = DURWOOD_UID;
  }
  else
  {
    $_SESSION['friend_uid'] = $friend;
    $query = "SELECT fb_firstname FROM user WHERE uid=$friend LIMIT 1";
    $result = mysql_query($query) or die ("Query Failure: $query");
    $row = mysql_fetch_array($result);
    if (isset($row['fb_firstname']))
      $_SESSION['friend_firstname'] = $row['fb_firstname'];
  }
  if ($save)
  {
    $uid = $_SESSION['uid'];
    $query = "UPDATE user SET friend=$friend WHERE uid=$uid";
    $result = mysql_query($query) or die ("Query Failure: $query");
  }
}

function process_form($values)
{
  global $debug;
  global $php_self;

  if ($debug) 
    display_array($values);

  if (isset($values['movie_id']))
    $movie_id = $values['movie_id'];

  if (isset($values['save_recommendation']))
  {
    update_movie_recommendation($values);
    redirect_to_movie_edit($movie_id);
  }
  elseif (isset($values['set_friend']))
  {
    set_friend($values['set_friend'], true);
    redirect_to_movie_list();
    return false; // gdg huh? why?
  }
  elseif (isset($values['save_movie_suggestion']))
  {
    $suggestion = "{$_SESSION['username']}: {$values['suggestion']}";
    save_suggestion("movie_suggestions.txt", $suggestion);
    header("Location: $php_self?movie_suggestion");
    //redirect_to_movie_list();
  }
  elseif (isset($values['save_website_suggestion']))
  {
    $suggestion = "{$_SESSION['username']}: {$values['suggestion']}";
    save_suggestion("website_suggestions.txt", $suggestion);
    header("Location: $php_self?website_suggestion");
    //redirect_to_movie_list();
  }
  elseif (isset($values['cancel_movie_edit']))
  {
    if (isset($movie_id))
      redirect_to_movie_list($movie_id);
    else
      redirect_to_movie_list();
    return false; // gdg ??
  }
  elseif (isset($values['this_movie_edit']))
  {
    redirect_to_movie_edit($movie_id);
  }
  elseif (isset($values['next_movie_edit']))
  {
    $movie_id = get_next_movie_id($movie_id);
    redirect_to_movie_edit($movie_id);
  }
  elseif (isset($values['prev_movie_edit']))
  {
    $movie_id = get_prev_movie_id($movie_id);
    redirect_to_movie_edit($movie_id);
  }
  elseif (isset($values['add_new_movie']))
  {
    redirect_to_movie_edit();
  }
  elseif (isset($values['save_new_movie']))
  {
    $movie_id = update_movie_info($values);
    redirect_to_movie_edit($movie_id);
  }
  elseif (isset($values['save_movie_edit']))
  {
    $movie_id = update_movie_info($values);
    if (isset($movie_id))
    {
      redirect_to_movie_edit($movie_id);
    }
    else
      redirect_to_movie_edit();
  }
  elseif (isset($values['save_movie_rating']))
  {
    $movie_id = update_movie_rating($values);
    if (isset($movie_id))
    {
      redirect_to_movie_edit($movie_id);
    }
    else
    {
      redirect_to_movie_edit();
    }
  }
  elseif ((isset($values['confirm_delete']))
       && (!isset($values['movie_id'])))
  {
    redirect_to_movie_list();
  }
  elseif (isset($values['delete']))
  {
    delete_movie($movie_id);
    redirect_to_movie_list();
  }
  elseif (isset($values['cancel_movie_info']))
  {
    redirect_to_movie_list($movie_id);
  }
  elseif (isset($values['prev_movie_info']))
  {
    $movie_id = get_prev_movie_id($movie_id);
    redirect_to_movie_info($movie_id);
  }
  elseif (isset($values['next_movie_info']))
  {
    $movie_id = get_next_movie_id($movie_id);
    redirect_to_movie_info($movie_id);
  }
  elseif (isset($values['show_activity']))
  {
    display_activity();
  }
  else
  {
     return false;
  }
  return true;  // did a re-direct
}

function save_suggestion($fname, $suggestion)
{
  $fp = fopen($fname, 'a') or die("FAILED: opening $fname for append");
  fwrite ($fp, "$suggestion\n");
  fclose($fp);
}

function get_next_movie_id($movie_id, $reverse = 0)
{
  if (!isset($_SESSION['next_movie_array']))
    return 0;
    //die ("Next movie array is not set");

  if ($reverse)
  {
    return array_search($movie_id, $_SESSION['next_movie_array']);
  }
  else
  {
    return $_SESSION['next_movie_array'][$movie_id];
  }

}

function get_prev_movie_id($movie_id)
{
  return get_next_movie_id($movie_id, 1); 
}

function get_sort_mode_string()
{
  if ($_SESSION['sort_mode'] == "title")
    return "Sort By: Title";
  elseif ($_SESSION['sort_mode'] == "year")
    return "Sort By: Year";
  elseif ($_SESSION['sort_mode'] == "created")
    return "Sort By: Date Added";
  elseif ($_SESSION['sort_mode'] == "combo_rating")
    return "Sort By: Combo Rating";
  elseif ($_SESSION['sort_mode'] == "imdb_rating")
    return "Sort By: IMDB Rating";
  elseif ($_SESSION['sort_mode'] == "rotten_rating")
    return "Sort By: TomatoMeter Rating";
  elseif ($_SESSION['sort_mode'] == "flixster_rating")
    return "Sort By: Flixster Rating";
  elseif ($_SESSION['sort_mode'] == "my_rating")
    return "Sort By: My Rating";
  elseif ($_SESSION['sort_mode'] == "friend_rating")
    return "Sort By: Friend Rating";
}

function get_edit_mode_string()
{
  $header = "Click Icon to:";
  if ($_SESSION['edit_mode'] == "browse")
    return "$header View Details";
  elseif ($_SESSION['edit_mode'] == "rate")
    return "$header Rate Movie";
  elseif ($_SESSION['edit_mode'] == "full_edit")
    return "$header Edit Movie";
  die ("Invalid Edit Mode: {$_SESSION['edit_mode']}");
}

function get_view_mode_string()
{
  $header = "Icon Size:";
  if ($_SESSION['view_mode'] == "list")
    return "$header List";
  elseif ($_SESSION['view_mode'] == "compact")
    return "$header Compact";
  elseif ($_SESSION['view_mode'] == "large")
    return "$header Large";
}

function get_filter_mode_string()
{
  if ($_SESSION['filter_mode'] == "none")
    return "Filter Mode: None";
  elseif ($_SESSION['filter_mode'] == "no_year")
    return "Filter Mode: No Year";
  elseif ($_SESSION['filter_mode'] == "no_art")
    return "Filter Mode: No Art";
  elseif ($_SESSION['filter_mode'] == "no_imdb")
    return "Filter Mode: No IMDB";
  elseif ($_SESSION['filter_mode'] == "no_rotten")
    return "Filter Mode: No TomatoMeter";
  elseif ($_SESSION['filter_mode'] == "no_flixster")
    return "Filter Mode: No Flixster";
  elseif ($_SESSION['filter_mode'] == "no_amazon_dvd")
    return "Filter Mode: No Amazon DVD";
  elseif ($_SESSION['filter_mode'] == "no_scarecrow")
    return "Filter Mode: No Scarecrow";
  elseif ($_SESSION['filter_mode'] == "seen_not_rated")
    return "Filter Mode: Seen Not Rated";
  elseif ($_SESSION['filter_mode'] == "rated_not_seen")
    return "Filter Mode: Rated Not Seen";
}

function display_dropdown_menu()
{
  global $php_self;

  print("\n");
  print("<div id=\"bluemenu\" class=\"bluetabs\">\n");
  print("  <ul>\n");

  //if (($_SESSION['logged_in'])
   //&& ($_SESSION['allow_rating'] == true))
  //{
    //$outstr = get_edit_mode_string();
    //print("  <li><a href=\"#\" rel=\"dropmenu_editmode\">$outstr</a></li>\n");
  //}


  $outstr = get_view_mode_string();
  print("  <li><a href=\"#\" rel=\"dropmenu_viewmode\">$outstr</a></li>\n");

  $outstr = get_sort_mode_string();
  print("  <li><a href=\"#\" rel=\"dropmenu_sortmode\">$outstr</a></li>\n");


  if (allow_edit())
  {
    $outstr = get_filter_mode_string();
    print("  <li><a href=\"#\" rel=\"dropmenu_filtermode\">$outstr</a></li>\n");
  }

  print("  <li><a href=\"#\" rel=\"dropmenu_menu\">Menu</a></li>\n");

  print("  </ul>\n");
  print("</div>\n");

  //--1st drop down menu 
  print("<div id=\"dropmenu_editmode\" class=\"dropmenudiv_b\">\n");
  print("  <a href=\"$php_self?edit_mode=browse\">View Details</a>\n");
  print("  <a href=\"$php_self?edit_mode=rate\">Rate Movie</a>\n");
  if (allow_edit())
    print("  <a href=\"$php_self?edit_mode=full_edit\">Edit Movie</a>\n");
  print("</div>\n");

  print("<div id=\"dropmenu_sortmode\" class=\"dropmenudiv_b\">\n");
  print("  <a href=\"$php_self?sort_mode=title\">Title</a>\n");
  print("  <a href=\"$php_self?sort_mode=year\">Year</a>\n");
  print("  <a href=\"$php_self?sort_mode=created\">Date Added</a>\n");
  print("  <a href=\"$php_self?sort_mode=combo_rating\">Combo Rating</a>\n");
  print("  <a href=\"$php_self?sort_mode=imdb_rating\">Imdb Rating</a>\n");
  print("  <a href=\"$php_self?sort_mode=rotten_rating\">TomatoMeter Rating</a>\n");
  print("  <a href=\"$php_self?sort_mode=flixster_rating\">Flixster Rating</a>\n");
  if ($_SESSION['logged_in'])
    print("  <a href=\"$php_self?sort_mode=my_rating\">My Rating</a>\n");
  if ($_SESSION['fb_logged_in'])
    print("  <a href=\"$php_self?sort_mode=friend_rating\">Friend Rating</a>\n");
  print("</div>\n");

  print("<div id=\"dropmenu_viewmode\" class=\"dropmenudiv_b\">\n");
  print("  <a href=\"$php_self?view_mode=list\">List</a>\n");
  print("  <a href=\"$php_self?view_mode=compact\">Compact Icons</a>\n");
  print("  <a href=\"$php_self?view_mode=large\">Large Icons</a>\n");
  print("</div>\n");

  print("<div id=\"dropmenu_filtermode\" class=\"dropmenudiv_b\">\n");
  print("  <a href=\"$php_self?filter_mode=none\">None</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_year\">No Year</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_art\">No Art</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_imdb\">No IMDB</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_rotten\">No TomatoMeter</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_flixster\">No Flixster</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_amazon_dvd\">No Amazon DVD</a>\n");
  print("  <a href=\"$php_self?filter_mode=no_scarecrow\">No Scarecrow</a>\n");
  print("  <a href=\"$php_self?filter_mode=seen_not_rated\">Seen Not Rated</a>\n");
  print("  <a href=\"$php_self?filter_mode=rated_not_seen\">Rated Not Seen</a>\n");
  print("</div>\n");

  print("<div id=\"dropmenu_menu\" class=\"dropmenudiv_b\">\n");
  if ($_SESSION['logged_in'])
  {
    print("  <a href=\"$php_self?movie_suggestion\">Suggest a Movie</a>\n");
    print("  <a href=\"$php_self?website_suggestion\">Suggest Website Change</a>\n");
    if (allow_edit())
    {
      print("  <a href=\"$php_self?edit\">Add New Movie</a>\n");
      print("  <a href=\"$php_self?update_combo\">Update Combo Ratings</a>\n");
      if ($_SESSION['show_all_recommendations'])
        print("  <a href=\"$php_self?show_all_recommendations=0\">Current Recommendation</a>\n");
      else
        print("  <a href=\"$php_self?show_all_recommendations=1\">All Recommendations</a>\n");
      print("  <a href=\"$php_self?update_sitemap\">Update Sitemap</a>\n");
    }
  }
  print("  <a href=\"$php_self?show_activity\">Show Recent Activity</a>\n");
  $faq_link = "javascript:FAQPopup('/faq.php')";
  print("  <a href=\"$faq_link\">FAQ/Help</a>\n");
  $about_link = "javascript:AboutPopup('$php_self?about')";
  print("  <a href=\"$about_link\">About</a>\n");
  
  print("</div>\n");
  print("\n");

  print("<script type=\"text/javascript\">\n");
  //SYNTAX: tabdropdown.init("menu_id", [integer OR "auto"])
  print("tabdropdown.init(\"bluemenu\")\n");
  print("</script>\n");
  print("\n");
}


function display_sample_dropdown_menu()
{
print("<div id=\"bluemenu\" class=\"bluetabs\">\n");
print("<ul>\n");
print("<li><a href=\"http://www.dynamicdrive.com\">Home</a></li>\n");
print("<li><a href=\"http://www.dynamicdrive.com/style/\" rel=\"dropmenu1_b\">CSS</a></li>\n");
print("<li><a href=\"http://www.dynamicdrive.com/resources/\" rel=\"dropmenu2_b\">Partners</a></li>\n");
print("<li><a href=\"http://tools.dynamicdrive.com\">Tools</a></li>\n");
print("</ul>\n");
print("</div>\n");

//--1st drop down menu 
print("<div id=\"dropmenu1_b\" class=\"dropmenudiv_b\">\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C1/\">Horizontal CSS Menus</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C2/\">Vertical CSS Menus</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C4/\">Image CSS</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C6/\">Form CSS</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C5/\">DIVs and containers</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C7/\">Links and Buttons</a>\n");
print("<a href=\"http://www.dynamicdrive.com/style/csslibrary/category/C8/\">Other</a>\n");
print("</div>\n");


//--2nd drop down menu
print("<div id=\"dropmenu2_b\" class=\"dropmenudiv_b\" style=\"width: 150px;\">\n");
print("<a href=\"http://www.cssdrive.com\">CSS Drive</a>\n");
print("<a href=\"http://www.javascriptkit.com\">JavaScript Kit</a>\n");
print("<a href=\"http://www.codingforums.com\">Coding Forums</a>\n");
print("<a href=\"http://www.javascriptkit.com/jsref/\">JavaScript Reference</a>\n");
print("</div>\n");

print("<script type=\"text/javascript\">\n");
//SYNTAX: tabdropdown.init("menu_id", [integer OR "auto"])
print("tabdropdown.init(\"bluemenu\")\n");
print("</script>\n");

}


function display_sample_menubar()
{
  print("\n");
  print("<div id=\"menu_navcontainer\">\n");
  print("<ul id=\"menu_navlist\">\n");
  print("<li id=\"active\"><a href=\"#\" id=\"current\">Item one</a></li>\n");
  print("<li><a href=\"#\">Item two</a></li>\n");
  print("<li><a href=\"#\">Item three</a></li>\n");
  print("<li><a href=\"#\">Item four</a></li>\n");
  print("<li><a href=\"#\">Item five</a></li>\n");
  print("</ul>\n");
  print("</div>\n");
  print("\n");
}

function display_sample_navbar()
{
print("<div id=\"navcontainer\">\n");
print("<ul id=\"navlist\">\n");
print("<li id=\"active\"><a href=\"#\" id=\"current\">Item one</a></li>\n");
print("<li><a href=\"#\">Item two</a></li>\n");
print("<li><a href=\"#\">Item three</a></li>\n");
print("<li><a href=\"#\">Item four</a></li>\n");
print("<li><a href=\"#\">Item five</a></li>\n");
print("</ul>\n");
print("</div>\n");
}

function display_navbar()
{
  print("<div class=\"lefty\">\n");
  print("  <div id=\"navcontainer\">\n");
  print("    <ul id=\"navlist\">\n");

#navlist li a#inactive
  $tab = get_navbar_tab("recommendation", "Site Recommendations");
  print("    $tab\n");
  $tab = get_navbar_tab("all",            "View All Movies");
  print("    $tab\n");
  $tab = get_navbar_tab("siff",           "2011 SIFF Movies");
  print("    $tab\n");
  $tab = get_navbar_tab("want_to_see",    "Want to See");
  print("    $tab\n");
  $tab = get_navbar_tab("seen",           "Seen");
  print("    $tab\n");
  $tab = get_navbar_tab("not_seen",       "Not Seen");
  print("    $tab\n");
  $tab = get_navbar_tab("not_interested", "Not Interested");
  print("    $tab\n");

  if ($_SESSION['tab_status'] == "recommendation")
  {
    $total_movies = $_SESSION['total_movies']; // THIS ISN"T WORKING ON REFRESH
    $total_movies = count_all_movies();
    if ($_SESSION['logged_in'])
      $right_text = "$total_movies Total Movies";
    else
      $right_text = "$total_movies Total Movies (Select 'All Movies' tab)";
  }
  else
    $right_text = "{$_SESSION['num_movies']} Movies Shown";
  $tab = get_right_text_tab($right_text);
  print("    $tab\n");

  print("    </ul>\n");
  print("  </div>\n");
  print("</div>\n");
}

function get_navbar_tab($tab_status, $title)
{
  global $php_self;

  $nolink  = false;
  $li_id   = "";
  $href_id = "";
  if ($_SESSION['username'] == "")
  {
    if ($_SESSION['tab_status'] == $tab_status)
    {
      $li_id   = " id=\"active\"";
      $href_id = " id=\"current\"";
    }
    elseif (($tab_status != "all") 
         && ($tab_status != "recommendation")
         && ($tab_status != "siff"))
    {
      $nolink = true;
      //$li_id   = " id=\"active\"";
      $href_id = " id=\"inactive\"";
    }
  }
  elseif ($_SESSION['tab_status'] == $tab_status)
  {
    $li_id   = " id=\"active\"";
    $href_id = " id=\"current\"";
  }

  $tab  = "<li" . $li_id . ">";
  if ($nolink)
    $tab .= "<a href=\"#\"" . $href_id . ">$title</a>";
  else
    $tab .= "<a href=\"$php_self?tab_status=$tab_status\"" . $href_id . ">$title</a>";
  $tab .= "</li>";

  return $tab;
}

function get_right_text_tab($text)
{
  $tab  = "<li id=right_text>";
  $tab .= "$text";
  $tab .= "</li>";
  return $tab;
}

function get_current_recommendation_image()
{
  $now = strtotime("today");
  $art = "";

  $result = query_movie_recommendations($_SESSION['uid'],
                                        $_SESSION['friend_uid']);
  while ($row = mysql_fetch_array($result)) 
  {
    $date_integer = strtotime($row['date_recommended']);
    if ($date_integer > $now)
    {
        break;
    }
    else
    {
      $art = $row['art'];
    }
  }

  if (is_file("images/original/$art"))
  {
    $image = "images/original/$art";
    list($width, $height, $type, $attr) = getimagesize($image);
    if ($height > 500)
    {
      $smaller_image = "images/400/$art";
      if (is_file($smaller_image))
        $image = $smaller_image;
    }
  }
  else
    $image = "";

  return $image;
}

function display_movie_recommendation($selected_movie_id=0)
{
  global $php_self;

  $_SESSION['refresh_count']++;

  $result = query_movie_recommendations($_SESSION['uid'],
                                        $_SESSION['friend_uid']);

/*
  $movie_list = create_movie_list($result);
  create_next_movie_array($movie_list);
  mysql_data_seek($result, 0);
*/

  $num_movies = mysql_num_rows($result);
  display_menu();

  $now = strtotime("today");
  $current_recommendation = 0;
  while ($row = mysql_fetch_array($result)) 
  {
    $date_integer = strtotime($row['date_recommended']);

    if ($date_integer > $now)
    {
      if ($_SESSION['show_all_recommendations'] == false)
        break;
    }
    else
    {
      $current_recommendation = $row['movie_id'];
    }

    if ($selected_movie_id == 0 || $selected_movie_id == $row['movie_id'])
    {
      $selected_row = $row;
    }

  }

  $count = 0;
  mysql_data_seek($result, 0);
print("<div class=\"colmask leftmenu\">\n");
print("  <div class=\"colleft\">\n");
print("    <div class=\"col1\">\n");
  display_current_recommendation($current_recommendation, $selected_row);
print("    </div>\n");
print("    <div class=\"col2\">\n");

  while ($row = mysql_fetch_array($result)) 
  {
    $icon_size   = "tiny";
    $icon_height = 100;       // 400 for large

    $movie_id = $row['movie_id'];
    $art      = $row['art'];
    $year     = $row['year'];
    $title    = $row['title'];
    if (isset($year) && $year > 0)
      $title   .= " ($year)";

    $date_integer = strtotime($row['date_recommended']);
    $date_string = date('F j', $date_integer);

    if ( ($_SESSION['show_all_recommendations'] == false)
      && ($date_integer > $now) )
      break;

    $has_art = is_file("images/original/$art");
    if ($has_art)
      $image = get_image($art, $icon_height);

    $link = "$php_self?recommended&amp;movie_id=$movie_id";

    print("\n");
    print("<div id=\"anchor_$movie_id\" class=\"movie_cover $icon_size\">\n");
    display_link("$link", $title, $image, 2);
    print("  <br/>\n");
    if ($movie_id == $current_recommendation)
    {
      display_link("", "Current Recommendation", "", 2);
      update_recommendation_link($art);
    }
    else
      display_link("", $date_string, "", 2);
    print("</div>\n");

    //if ($count % 2)
      //print("<div class='clear'></div>\n");
    $count++;

    if ($selected_movie_id == 0 || $selected_movie_id == $movie_id)
    {
      $selected_row = $row;
    }
  }
print("    </div>\n");
print("  </div>\n");
print("</div>\n");
}

function display_current_recommendation($current_recommendation, $selected_row)
{
  global $php_self;

  $icon_size   = "large";
  $icon_height = 400;       // 400 for large

  $movie_id = $selected_row['movie_id'];
  $art      = $selected_row['art'];
  $year     = $selected_row['year'];
  $title    = $selected_row['title'];
  if (isset($year) && $year > 0)
    $title   .= " ($year)";

  $date_integer = strtotime($selected_row['date_recommended']);
  $date_string = date('F j', $date_integer);

  $has_art = is_file("images/original/$art");
  if ($has_art)
    $image = get_image($art, $icon_height);

  print("\n");
  //print("<div class=\"lefty\">\n");
  print("<div id=\"anchor_$movie_id\" class=\"movie_cover $icon_size\">\n");
  if ($movie_id == $current_recommendation)
    print("  <span class=\"title\">This Week's Movie Recommendation...</span>");
  else
    print("  <span class=\"title\">$date_string Recommendation</span>");
  print("\n");
  print("  <br/>\n");
  print("  <br/>\n");
  print("  <span class=\"title\">$title</span>\n");
  print("  <br/>\n");
  print("  <br/>\n");
  display_siff_button($selected_row['siff2011_url']);
  print("  <br/>\n");
  $link = "$php_self?rate&amp;movie_id=$movie_id";
  if ($_SESSION['logged_in'])
    display_link($link, "Click to Rate this Movie", $image, 2);
  else
    display_link("", "Login to Rate this movie", $image, 2);
  //print("</div>\n");
  if ($movie_id == $current_recommendation)
  {
    print("  <br/>\n");
    display_fbook_like_button("button_count", $movie_id);
  }
print("</div>\n");

  print("\n");
  //print("<div class=\"main_column\">\n");
  display_movie_recommendation_text($selected_row);
//print("</div>\n");
}

function update_recommendation_link($art)
{
  $link = "images/original/recommended_movie.jpg";
  $image = get_image($art, 400);
  $target = "../$image";

  $current_target = readlink($link);
  if ($current_target && $current_target == $target)
    return;
  else
  {
    unlink($link);
    symlink($target, $link);
  }
}

function display_amazon_buy_button($dvd_url, $bluray_url)
{
  if (isset($dvd_url) && $dvd_url != "")
  {
    display_link("$dvd_url", "Buy DVD on Amazon.com", 
                 "/media/amazon_120x42_gold.gif");
  }
}

function display_scarecrow_button($url)
{
  if (isset($url) && $url != "")
  {
    display_link("$url", "Available at Scarecrow Video, Seattle",
                 "/media/scarecrow_logo.jpg");
  }
}

function display_siff_button($url, $small=0)
{
  if (isset($url) && $url != "")
  {
    if ($small)
      display_link("$url", "SIFF Info", "");
    else
      display_link("$url", 
            "Official Selection, 2011 Seattle International Film Festival",
            "/media/SIFF-Fest_RGB.jpg");
  }
}

function display_movie_recommendation_text($values)
{
  $title         = $values['title'];
  $year          = $values['year'];
  $combo_rating  = $values['combo_rating'];
  $imdb_rating   = $values['imdb_rating'];
  $imdb_url      = $values['imdb_url'];
  $rotten_rating = $values['rotten_rating'];
  $flixster_rating = $values['flixster_rating'];
  $rotten_url    = $values['rotten_url'];
  $flixster_url    = $values['flixster_url'];

  $my_name    = $_SESSION['firstname'];
  $my_rating  = $values['my_rating'];
  $my_status  = $values['my_status'];
  $my_comment = $values['my_comment'];

  $friend_name    = $_SESSION['friend_firstname'];
  $friend_rating  = $values['friend_rating'];
  $friend_status  = $values['friend_status'];
  $friend_comment = $values['friend_comment'];

  $movie_id = $values['movie_id'];
  $uid = $_SESSION['uid'];
  $friend_uid = $_SESSION['friend_uid'];
  $random_comment_result = query_random_comments($movie_id, $uid, $friend_uid);

  print("<div class=\"movie_title\"><b>\n");
  print("  <br/>\n");
  print("  <br/>\n");
  //if ($year == 0)
    //print("  <b>$title</b>\n");
  //else
    //print("  <b>$title ($year)</b>\n");
  print("  <br/>\n");
  print("  <br/>\n");

  display_rating("Combo",           $combo_rating   );
  display_rating("IMDB",            $imdb_rating,   $imdb_url);
  display_rating("TomatoMeter",     $rotten_rating, $rotten_url);
  display_rating("Flixster",         $flixster_rating, $flixster_url);
  if ($_SESSION['logged_in'])
  {
    if (isset($my_name))
      display_rating($my_name,        $my_rating);
    if (isset($friend_name))
      display_rating($friend_name,    $friend_rating );
  }
  print("  <br/>\n");
  $recommendation = $values['recommendation'];
  display_recommendation_comment("", $recommendation);

  if ($_SESSION['logged_in'])
  {
    $num_comments = 0;
    if (isset($my_name) && display_comment($my_name, $my_comment))
      $num_comments++;
    if (isset($friend_name) && display_comment($friend_name, $friend_comment))
      $num_comments++;
    if ($num_comments < 2)
    {
      $random_comment = $random_comment_result[0]['comment'];
      $random_uid     = $random_comment_result[0]['uid'];
      $random_name    = get_user_firstname($random_uid);
      display_comment($random_name, $random_comment);
    }
  }
  else
  {
    $random_comment = $random_comment_result[0]['comment'];
    $random_uid     = $random_comment_result[0]['uid'];
    $random_name    = get_user_firstname($random_uid);
    display_comment($random_name, $random_comment);
    if (isset($random_comment[1]))
    {
      $random_comment = $random_comment_result[1]['comment'];
      $random_uid     = $random_comment_result[1]['uid'];
      $random_name    = get_user_firstname($random_uid);
      display_comment($random_name, $random_comment);
    }
    print("  <br/>\n");
  }

  print("\n");
  print("  <br/>\n");
  display_amazon_buy_button($values['amazon_dvd_url'], $values['amazon_bluray_url']);
  display_scarecrow_button($values['scarecrow_url']);
  print("</div>\n\n");
}

function count_all_movies()
{
  global $debug;

  $query = "SELECT movie_id FROM movie";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die ("Query Failure: $query");
  return (mysql_num_rows($result));
}

function display_movie_list()
{
  $result = query_movies($_SESSION['uid'],$_SESSION['friend_uid']);
  $_SESSION['num_movies'] = mysql_num_rows($result);
  display_menu();

  if (($_SESSION['view_mode'] == 'list') 
   || ($_SESSION['is_mobile']))
    $noart = true;
  else
    $noart = false;

  $movie_list = array();
  $movie_list = display_one_movie_list($result, $noart);
  mysql_free_result($result);

  create_next_movie_array($movie_list);
}

function display_menu()
{
  global $php_self;

  print("<div class=\"centered\">\n");
  display_login_menu();
  display_friend_selection();
  print("</div>\n");

  print("<div class=\"centered\">\n");

  print("<br/>\n");

  if (!$_SESSION['is_mobile'])
  {
    print("<br/>\n");
    print("<div class=\"lefty\">");
    display_link("http://www.siff.net/festival/index.aspx",
       "Now featuring films from 2011 Seattle International Film Festival, May 19 - June 12",
       "/media/SIFF-Fest_RGB.jpg");
    print("</div>\n");
    print("\n");

    display_tip();
    display_dropdown_menu();
    print("<br/>\n");

    display_navbar();
    print("\n");
    print("<br/>\n");

   //display_sample_dropdown_menu(); print("<br/>\n");
   //display_dropdown_menu(); print("<br/>\n");
   //display_sample_menubar(); print("<br/>\n");
   //display_sample_navbar();
  }

  print("</div>\n");
}

function display_about()
{
  global $php_self;
  global $version;

  print("<br/>\n");
  print("The Gay Movie List<br/>\n");
  print("(http://thegaymovielist.com)<br/>\n");
  print("<br/>\n");
  print("Current Version $version<br/>\n");
  print("<br/>\n");
  print("Copyright &#169 2011 Durwood Gafford<br/>\n");
  print("All Rights Reserved.<br/>\n");
  print("<br/>\n");
  $privacy_link = "javascript:TOSPopup('/privacy.html')";
  print("<a href=\"$privacy_link\">View Privacy Statement</a>\n");
  print("<br/>\n");
  $tos_link = "javascript:TOSPopup('/tos.html')";
  print("<a href=\"$tos_link\">View Terms of Service</a>\n");
  print("<br/>\n");
  print("<br/>\n");

  print("<form>\n");
  display_donate_button();
  print("<br/>\n");
  print("<input type=\"button\" value=\"Close Window\" onClick=\"window.close()\">\n");
  print("</form>\n");
  //print("<a href=\"javascript:window.close()\">Close Window</a>\n");

  print("<br/>\n");
}

function display_tip()
{
  global $php_self;

  $refresh_count = $_SESSION['refresh_count'];

  if (($_SESSION['tab_status'] == 'recommendation') &&
      ($refresh_count < 10))
  {
    print("<br/>\n");
    print("<div class=\"tip\" title=\"Tips\" onclick=\"location.href='$php_self';\" style=\"cursor:pointer\">\n");
    print("Login to rate and comment on movies and share with friends. Come back for weekly recommendations. Click on 'All Movies' tab to browse.\n");
    print("</div>\n");
    return;
  }
  if (!$_SESSION['logged_in'])
  {
    print("<br/>\n");
    print("<div class=\"tip\" title=\"Tips - you will see more after logging in!\" onclick=\"location.href='$php_self';\" style=\"cursor:pointer\">\n");
    print("Login via Facebook to rate and comment on movies you've seen and to share with friends!");
    print("</div>\n");
    return;
  }

  $tip = array( 
    "These Tips help you learn about the website; at first they will change with each refresh. Click bar to force refresh!",
    "The blue bar below is the menu bar; poke around and see what it does.",
    "What's the title of that movie? Hover over the icon to see the title.",
    "Identify movies you've seen! Click on a movie to change its status, rate it or comment on it.",
    "Have your Facebook friends join so you can share favorites with each other.",
    "Seen a movie? Add a brief comment when you rate the movie.",
    "Is a good movie missing from the list? Suggest it via the pulldown 'Menu.'",
    "Click on the IMDB or Rotten Tomato ratings to view movie at those websites.",
    "You can purchase a movie from Amazon.com by clicking on the button in the movie info screen.",
    "Any movie added to site in the last 2 weeks will have a blue \"NEW\" star by it",
    "Use the tabs (e.g. 'All Movies', 'Want to See') to filter the movie list.",
    "Netflix links are coming soon!",
    "Come back for weekly movie recommendations - updated every Wednesday, just in time for the weekend."
  );

  $refresh_count = $_SESSION['refresh_count'];
  $num_tips = count($tip);

  if ($refresh_count < 5 * $num_tips)
  {
    $frequency = 1;
    $_SESSION['tip_index'] = $refresh_count % $num_tips;
  }
  else
  {
    $frequency = 5;
    if (($refresh_count % $frequency) == 0)
    {
      $_SESSION['tip_index'] = rand(0, $num_tips - 1);
    }
  }

  $index = $_SESSION['tip_index'];
  print("<br/>\n");
  print("<div class=\"tip\" title=\"Tips - Click for more!\" onclick=\"location.href='$php_self';\" style=\"cursor:pointer\">\n");
  print("{$tip[$index]}\n");
  print("</div>\n");
}

function display_friend_selection()
{
  global $facebook;
  global $php_self;
 
  if (($_SESSION['logged_in'])
   && ($_SESSION['fb_logged_in'])
   && (isset($_SESSION['friend_list'])))
  {
    print "<form method=\"post\" action=\"$php_self\" name=\"set_friend\">\n";
    print("Select Friend: \n");
    $selection_list = $_SESSION['friend_list'];

    add_raw_dropdown("Select Friend", $selection_list, "set_friend",
                    $_SESSION['friend_uid'], true);
    print ("</form>\n");
  }
}

function build_friend_list()
{
  global $facebook;
 
  if (($_SESSION['logged_in'])
   && ($_SESSION['fb_logged_in']))
  {
    //debug("Getting UID");
    //$fbuid = $facebook->getUser();
    //debug("UID: $fbuid");

    //debug("Getting ME");
    //$me = $facebook->api('/me');
    //display_array($me, "ME");

    //debug("Getting Friends");
    $fls = $facebook->api('/me/friends');
    $friends = $fls['data'];
    $first = true;
    $num_fb_friends = count($friends);
    foreach ($friends as $friend)
    {
      //debug("Name: {$friend['name']}, UID: {$friend['id']}");
      if ($first)
      {
        $fb_friends = $friend['id'];
        $first = false;
      }
      else
      {
        $fb_friends .= ",{$friend['id']}";
      }
    }
    //debug($fb_friends);
    
    $columns = "uid,fb_userid,fb_firstname,fb_lastname";
    $query = "SELECT $columns FROM user WHERE fb_userid IN ($fb_friends)";
    $result = mysql_query($query);
    if ($result)
    {
      $num_friends = mysql_num_rows($result);
      while ($row = mysql_fetch_array($result)) 
      {
        $first      = $row['fb_firstname'];
        $last       = $row['fb_lastname'];
        $friend_uid = $row['uid'];
        $selection_list[$friend_uid] = "$first $last";
      }
      //$selection_list[1] = "Josh Irwin";  // nocheckin
    }
    else
    {
      $friend_uid = DURWOOD_UID;
      $selection_list[$friend_uid] = "Durwood Gafford";
    }
    $_SESSION['friend_list'] = $selection_list;
  }
}

function display_donate_button()
{
?>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="8E2HJXQK2FP7L">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
<?php
}

function display_login_menu()
{
  global $allow_facebook_login;
  global $php_self;
  global $facebook;

  if ($_SESSION['logged_in'])
  {
    if ($allow_facebook_login && $_SESSION['fb_logged_in'])
    {
      print("<div class=\"like\">\n");
      display_fbook_like_button("standard", 0);
      print("</div>\n");

      $logoutUrl = $facebook->getLogoutUrl();
      display_link("$logoutUrl", "logout", "media/fblogout.gif");
      print("<br/>\n");

      return(false);  // Don't want to tack on anything else
    }
    else
    {
      display_menu_element("$php_self?logout", "logout", 0);
      return(true);
    }
  }
  else
  {
    if ($allow_facebook_login)
    {
      print("<div class=\"like\">\n");
      display_fbook_like_button("standard", 0);
      print("</div>\n");
      print("<div class=\"fbook\">\n");
      display_fbook_login_button();
      print("</div>\n");
      display_menu_element("$php_self?show_privacy",  
                           "Read Privacy Statement", 0);
      display_menu_element("", "--", 0);
      display_menu_element("$php_self?login", "Non-Facebook Login", 0);
    }
    else
    {
      display_menu_element("$php_self?login", "login", 0);
    }
    return(true);
  }
}

function display_edit_mode_menu($movie_id=NULL)
{
  global $php_self;

  // You must at least have rating rights to display menu
  if (allow_rating())
  {
    if ($movie_id == NULL)
      $link = "$php_self?edit_mode=";
    else
      $link = "$php_self?movie_id=$movie_id&amp;edit_mode=";

    print("<div class=\"centered\">\n");
    if (allow_edit())
    {
      display_menu_element("", "Edit Mode - ", 0);
      display_menu_element($link . "browse", "browse", 
                           ($_SESSION['edit_mode'] == "browse"));
      display_menu_element($link . "rate", "rate", 
                           ($_SESSION['edit_mode'] == "rate"));
      display_menu_element($link . "full_edit", "edit", 
                           ($_SESSION['edit_mode'] == "full_edit"));
    }
    else
    {
      if ($_SESSION['edit_mode'] == "rate")
        display_menu_element($link . "browse", 
                             "Click Here to Return to Browse Mode", 
                             ($_SESSION['edit_mode'] == "browse"));
      else if ($_SESSION['edit_mode'] == "browse")
        display_menu_element($link . "rate",
                             "Click Here to Rate Movie or Update Status",
                             ($_SESSION['edit_mode'] == "rate"));
    }
    print("<br/>\n");
    print("</div>\n\n");
  }
}

function old_display_edit_mode_menu($movie_id=NULL)
{
  global $php_self;

  // You must at least have rating rights to display menu
  if (allow_rating())
  {
    if ($movie_id == NULL)
      $link = "$php_self?edit_mode=";
    else
      $link = "$php_self?movie_id=$movie_id&amp;edit_mode=";

    display_menu_element("", "Edit Mode - ", 0);
    display_menu_element($link . "browse", "browse", 
                         ($_SESSION['edit_mode'] == "browse"));
    if (allow_rating())
    {
      display_menu_element($link . "rate", "rate", 
                           ($_SESSION['edit_mode'] == "rate"));
    }
    if (allow_edit())
    {
      display_menu_element($link . "full_edit", "edit", 
                           ($_SESSION['edit_mode'] == "full_edit"));
    }
  }
}

function display_menu_element($link, $title, $bold)
{
  if ($bold)
    print("<span class=\"menu_bold\">");
  else
    print("<span class=\"menu\">");

  if ($link == "")
    print("$title");
  else
    print("<a href=\"$link\">$title</a>");
  print("</span>\n");
}

function get_filter_string()
{
  $filter_string = "";
  if ($_SESSION['filter_mode'] == "none")
    $filter_string = "";
  elseif ($_SESSION['filter_mode'] == "no_year")
    $filter_string = " (year = 0 OR year IS NULL) ";
  elseif ($_SESSION['filter_mode'] == "no_imdb")
    $filter_string = " (imdb_url = '' OR imdb_url IS NULL)";
  elseif ($_SESSION['filter_mode'] == "no_rotten")
    $filter_string = " (rotten_url = '' OR rotten_url IS NULL)";
  elseif ($_SESSION['filter_mode'] == "no_flixster")
    $filter_string = " (flixster_url = '' OR flixster_url IS NULL)";
  elseif ($_SESSION['filter_mode'] == "no_amazon_dvd")
    $filter_string = " (amazon_dvd_url = '' OR amazon_dvd_url IS NULL)";
  elseif ($_SESSION['filter_mode'] == "no_scarecrow")
    $filter_string = " (scarecrow_url = '' OR scarecrow_url IS NULL)";
  elseif ($_SESSION['filter_mode'] == "no_art")
    $filter_string = " (art = '' OR art IS NULL)";
  elseif ($_SESSION['filter_mode'] == "seen_not_rated")
    $filter_string = " u1.status = 'seen' AND u1.one_to_ten <= 0 ";
  elseif ($_SESSION['filter_mode'] == "rated_not_seen")
    $filter_string = " u1.status = 'not_seen' AND u1.one_to_ten > 0 ";
  return $filter_string;
}

function init_session()
{
  global $ranges_calculated;

  // unset old session variable
  unset($_SESSION['status']);

  if (!isset($_SESSION['refresh_count']))
    $_SESSION['refresh_count'] = 0;
  if (!isset($_SESSION['tip_index']))
    $_SESSION['tip_index'] = 0;

  if (!isset($_SESSION['tab_status']))
  {
    $_SESSION['tab_status'] = "recommendation";
  }
  if (!isset($_SESSION['fb_logged_in']))
    $_SESSION['fb_logged_in'] = false;
  if (!isset($_SESSION['logged_in']))
    $_SESSION['logged_in'] = false;
  if (!isset($_SESSION['fb_uid']))
    $_SESSION['fb_uid'] = 0;
  if (!isset($_SESSION['username']))
    $_SESSION['username'] = "";
  if (!isset($_SESSION['firstname']))
    $_SESSION['firstname'] = "";
  if (!isset($_SESSION['uid']))
    $_SESSION['uid'] = 0;
  if (!isset($_SESSION['friend_uid']))
    $_SESSION['friend_uid'] = 0;

  if (!isset($_SESSION['list']))
    $_SESSION['list'] = false;

  if (!isset($_SESSION['allow_rating']))
    $_SESSION['allow_rating']  = false;
  if (!isset($_SESSION['allow_edit']))
    $_SESSION['allow_edit']    = false;
  if (!isset($_SESSION['allow_admin']))
    $_SESSION['allow_admin']    = false;
  if (!isset($_SESSION['edit_mode']))
    $_SESSION['edit_mode'] = 'browse';
  if (!isset($_SESSION['view_mode']))
    $_SESSION['view_mode'] = 'compact';
  if (!isset($_SESSION['sort_mode']))
    $_SESSION['sort_mode'] = 'imdb_rating';
  if (!isset($_SESSION['filter_mode']))
    $_SESSION['filter_mode'] = 'none';
  if (!isset($_SESSION['hide_seen']))
    $_SESSION['hide_seen'] = false;
  if (!isset($_SESSION['hide_not_interested']))
    $_SESSION['hide_not_interested'] = false;
  if (!isset($_SESSION['hide_not_seen']))
    $_SESSION['hide_not_seen'] = false;
  if (!isset($_SESSION['hide_want_to_see']))
    $_SESSION['hide_want_to_see'] = false;
  if (!isset($_SESSION['combo_inited']))
  {
    update_all_combo_ratings();
    $_SESSION['combo_inited'] = true;
  }
  if (!isset($_SESSION['is_mobile']))
    $_SESSION['is_mobile'] = is_mobile();
  if (!isset($_SESSION['is_bot']))
    $_SESSION['is_bot'] = is_bot();

  // ensure that various flags are cleared if user isn't loggedd in
  if ($_SESSION['logged_in'] == false)
    set_logged_out_flags();

  if (!isset($_SESSION['total_movies']))
    $_SESSION['total_movies'] = count_all_movies();
  if (!isset($_SESSION['num_movies']))
    $_SESSION['num_movies'] = $_SESSION['total_movies'];

  if (!isset($_SESSION['show_all_recommendations']))
    $_SESSION['show_all_recommendations'] = false;

  //if (!isset($_SESSION['site_image']))
    $_SESSION['site_image'] = get_current_recommendation_image();
}

function set_logged_out_flags()
{
  $_SESSION['friend_uid']       = 0;
  $_SESSION['friend_firstname'] = "";
  $_SESSION['firstname']        = "";
  $_SESSION['logged_in']    = false;
  $_SESSION['username']     = "";
  $_SESSION['uid']          = 0;
  $_SESSION['fb_logged_in'] = false;
  $_SESSION['fb_userid']    = 0;
  $_SESSION['allow_rating'] = false;
  $_SESSION['allow_edit']   = false;
  $_SESSION['allow_admin']  = false;
  $_SESSION['edit_mode']    = "browse";
  $_SESSION['filter_mode']  = "none";
  if (($_SESSION['tab_status'] != "all" 
    && $_SESSION['tab_status'] != "recommendation"))
    $_SESSION['tab_status'] = "recommendation";
}

function toggle_session()
{
  handle_login_logout();

  if (isset($_REQUEST['tab_status']))
    $_SESSION['tab_status'] = $_REQUEST['tab_status'];

  if (isset($_REQUEST['view_mode']))
    $_SESSION['view_mode'] = $_REQUEST['view_mode'];

  if (isset($_REQUEST['edit_mode']))
    $_SESSION['edit_mode'] = $_REQUEST['edit_mode'];

  if (isset($_REQUEST['filter_mode']))
    $_SESSION['filter_mode'] = $_REQUEST['filter_mode'];

  if (isset($_REQUEST['sort_mode']))
    $_SESSION['sort_mode'] = $_REQUEST['sort_mode'];

  if (isset($_REQUEST['hide_seen']))
    $_SESSION['hide_seen'] = $_REQUEST['hide_seen'];
  if (isset($_REQUEST['hide_not_seen']))
    $_SESSION['hide_not_seen'] = $_REQUEST['hide_not_seen'];
  if (isset($_REQUEST['hide_not_interested']))
    $_SESSION['hide_not_interested'] = $_REQUEST['hide_not_interested'];
  if (isset($_REQUEST['hide_want_to_see']))
    $_SESSION['hide_want_to_see'] = $_REQUEST['hide_want_to_see'];

  if (isset($_REQUEST['show_all_recommendations']))
    $_SESSION['show_all_recommendations'] = $_REQUEST['show_all_recommendations'];
}

function display_one_movie_list($query_result, $noart)
{
  global $debug;
  global $php_self;

  $movie_list = array();

  $_SESSION['refresh_count']++;

  $num_movies = $_SESSION['num_movies'];

  while ($row = mysql_fetch_array($query_result)) 
  {
    $movie_list[] = $row['movie_id'];
    display_movie_list_link($row, $noart);
  }
  print("<div class='clear'></div>\n");

  return $movie_list;
}

function create_movie_list($query_result)
{
  $movie_list = array();
  while ($row = mysql_fetch_array($query_result)) 
  {
    $movie_list[] = $row['movie_id'];
  }
  return $movie_list;
}

function create_next_movie_array($movie_list)
{
  $next_movie_array = array();
  $num_movies = count($movie_list);
  for ($ii = 0; $ii < $num_movies; $ii++)
  {
    $this_movie = $movie_list[$ii];
    if (($ii + 1) < $num_movies)
      $next_movie = $movie_list[$ii + 1];
    else
      $next_movie = $movie_list[0];
    $next_movie_array[$this_movie] = $next_movie;
  }
  $_SESSION['next_movie_array'] = $next_movie_array;
}

function query_movie_recommendations($uid, $friend_uid)
{
  global $debug;
  global $php_self;

  $order  = "ORDER BY date_recommended";
  $where  = "WHERE date_recommended != '0000-00-00 00:00:00' ";
  $where .= "AND recommendation is not null ";

  $columns  = "movie.movie_id,title,year,combo_rating,art";
  $columns .= ",imdb_url,imdb_rating,rotten_url,rotten_rating,flixster_rating";
  $columns .= ",flixster_url,amazon_dvd_url,amazon_bluray_url,scarecrow_url";
  $columns .= ",siff2011_url,netflix_url,netflix_rating";
  $columns .= ",date_recommended,recommendation";
  $columns .= ",u1.status as my_status, u1.one_to_ten as my_rating";
  $columns .= ",u1.comment as my_comment";
  $columns .= ",u2.status as friend_status, u2.one_to_ten as friend_rating";
  $columns .= ",u2.comment as friend_comment";
  $lj1 = 
     "user_movie as u1 on movie.movie_id=u1.movie_id and u1.user_id=$uid";
  $lj2 = 
     "user_movie as u2 on movie.movie_id=u2.movie_id and u2.user_id=$friend_uid";

  $query = 
     "SELECT $columns FROM movie LEFT JOIN $lj1 LEFT JOIN $lj2 $where $order";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die ("Query Failure: $query");

  return ($result);
}

function query_random_comments($movie_id, $uid=0, $friend_uid=0)
{
  global $debug;

  $where = "WHERE movie_id=$movie_id AND comment IS NOT NULL AND comment != \"\"";
  if (isset($uid) && $uid != 0)
    $where .= "AND user_id != $uid ";
  if (isset($friend_uid) && $uid != 0)
    $where .= "AND user_id != $friend_uid ";

  $sort    = "ORDER BY RAND() LIMIT 2";
  $columns = "comment,user_id";
  $query   = "SELECT $columns FROM user_movie $where $sort";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die ("Query Failure: $query");
  if ($result)
  {
    $num_results = mysql_num_rows($result);
    $row = mysql_fetch_array($result);
    $comment[0]['comment'] = $row['comment'];
    $comment[0]['uid'] = $row['user_id'];
    if ($num_results > 1)
    {
      $row = mysql_fetch_array($result);
      $comment[1]['comment'] = $row['comment'];
      $comment[1]['uid'] = $row['user_id'];
    }
    return $comment;
//    $row = mysql_fetch_array($result);
//    $comment[1]['comment'] = $row['comment'];
//    return $comment;
  }
}

function query_movies($uid,$friend_uid)
{
  global $debug;
  global $php_self;

  $sortby = $_SESSION['sort_mode'];
  if ($sortby == "title")
    $order  = "ORDER BY title";
  else if ($sortby == "my_rating") 
  {
    if ($_SESSION['friend_uid'] != 0) 
      $order  = "ORDER BY $sortby DESC, friend_rating DESC, imdb_rating DESC";
    else 
      $order  = "ORDER BY $sortby DESC, imdb_rating DESC, rotten_rating DESC";
  }
  else if ($sortby == "friend_rating")
  {
    $order  = "ORDER BY $sortby DESC, my_rating DESC, imdb_rating DESC";
  }
  else
  {
    if ($_SESSION['friend_uid'] != 0) 
      $order  = "ORDER BY $sortby DESC, my_rating DESC, friend_rating DESC";
    else
      $order  = "ORDER BY $sortby DESC, my_rating DESC";
  }

  $tab_status = $_SESSION['tab_status'];
  $filter = "";
  if (isset($tab_status) && $tab_status != "all")
  {
    if ($debug)
      debug($tab_status);
    if ($tab_status == "siff")
      $filter = "siff2011_url is not null and siff2011_url != ''";
    else if ($tab_status == "not_seen")
      $filter = "(u1.status='$tab_status' OR u1.status is null)";
    else
      $filter = "u1.status='$tab_status'";
  }

  // Add special filters
  $more_filters = get_filter_string();
  if ($more_filters != "")
  {
    if (!isset($filter) || $filter == "")
      $filter = $more_filters;
    else
      $filter .= " AND $more_filters";
  }

  $where = "";
  if (isset($filter) && $filter != "")
    $where = "WHERE $filter";

  $columns  = "movie.movie_id,title,year,combo_rating,art,created";
  $columns .= ",imdb_url,imdb_rating,rotten_url,rotten_rating";
  $columns .= ",flixster_rating,flixster_url,siff2011_url";
  $columns .= ",u1.status as my_status, u1.one_to_ten as my_rating";
  $columns .= ",u2.status as friend_status, u2.one_to_ten as friend_rating";
  $lj1 = 
     "user_movie as u1 on movie.movie_id=u1.movie_id and u1.user_id=$uid";
  $lj2 = 
     "user_movie as u2 on movie.movie_id=u2.movie_id and u2.user_id=$friend_uid";
  $query = 
     "SELECT $columns FROM movie LEFT JOIN $lj1 LEFT JOIN $lj2 $where $order";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die ("Query Failure: $query");

  return ($result);
}

function query_one_movie($uid, $friend_uid, $movie_id)
{
  global $debug;

  $where = "WHERE movie.movie_id=$movie_id";
  $sort  = "ORDER BY RAND() LIMIT 1";
  $columns  = "movie.movie_id,title,alt_title,year,combo_rating,art";
  $columns .= ",imdb_url,imdb_rating,rotten_url,rotten_rating";
  $columns .= ",flixster_rating,flixster_url,amazon_dvd_url,amazon_bluray_url";
  $columns .= ",trailer_url,scarecrow_url,siff2011_url";
  $columns .= ",u1.status as my_status";
  $columns .= ",u1.one_to_ten as my_rating";
  $columns .= ",u1.comment as my_comment";
  $columns .= ",u1.own as own";
  $columns .= ",u2.status as friend_status";
  $columns .= ",u2.one_to_ten as friend_rating";
  $columns .= ",u2.comment as friend_comment";
  $columns .= ",u3.comment as random_comment";
  $columns .= ",u3.user_id as random_uid";
  $columns .= ",u4.fb_firstname as random_name";
  $lj1 = 
     "user_movie as u1 on movie.movie_id=u1.movie_id and u1.user_id=$uid";
  $lj2 = 
     "user_movie as u2 on movie.movie_id=u2.movie_id and u2.user_id=$friend_uid";
  $lj3 =
     "user_movie as u3 on movie.movie_id=u3.movie_id and u3.user_id!=$uid and u3.user_id!=$friend_uid";
  $lj4 =
     "user as u4 on u3.user_id=u4.uid";

  $query = 
     "SELECT $columns FROM movie LEFT JOIN $lj1 LEFT JOIN $lj2 LEFT JOIN $lj3 LEFT JOIN $lj4 $where";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if ($result)
  {
    $row = mysql_fetch_array($result);
    return $row;
  }
}

function display_movie_list_link($row, $noart)
{
  global $php_self;
  global $g_icon_height;
  global $new_icon;
  global $new_icon_height;
  global $new_icon_width;

  if ($_SESSION['view_mode'] == 'compact')
    $icon_size = "small";
  elseif ($_SESSION['view_mode'] == 'large')
    $icon_size = "medium";
  else
    $icon_size = "";

  $movie_id = $row['movie_id'];
  $art      = $row['art'];
  $year     = $row['year'];
  $title    = $row['title'];
  if (isset($year) && $year > 0)
    $title   .= " ($year)";

  $has_art = is_file("images/original/$art");
  if ($has_art && ($g_icon_height > 0))
    $image = get_image($art, $g_icon_height);
  //else
    //$image = get_image("dvd_logo.jpg", $g_icon_height);

  if (allow_edit() && ($_SESSION['edit_mode'] == 'full_edit'))
    $link = "$php_self?edit&movie_id=$movie_id";
  elseif  (allow_rating() && ($_SESSION['edit_mode'] == 'rate'))
    $link = "$php_self?rate&movie_id=$movie_id";
  else
    $link = "$php_self?movie_id=$movie_id";

  if ($noart)
  {
    //print("<div id=\"anchor_$movie_id\" class=\"movie_title $icon_size\">\n");
    print("<div id=\"anchor_$movie_id\" class=\"movie_title\">\n");
    display_link("$link", $title, "");
    display_all_ratings($row, $noart);
    print("</div>\n");
  }
  else
  {
    print("<div id=\"anchor_$movie_id\" class=\"movie_cover $icon_size\">\n");
    if ( is_new($row, $movie_id) 
     && ($_SESSION['tab_status'] != "siff"))
    {
      $height = "height=\"$new_icon_height\"";
      $width  = "width=\"$new_icon_width\"";

      //print("<div class=\"movie_overlay {$icon_sizeid}_olay\">\n");
      print("<div class=\"movie_overlay\">\n");
      print("  <img src=\"$new_icon\" $height $width alt=\"New\"/>");
      print("</div>\n");
    }
    display_link("$link", $title, $image);
    print("<br/>\n");
    //print("<p>\n");
    display_all_ratings($row, $noart);
    //print("</p>\n");
    print("</div>\n");
  }
  // print("<div class=\"clear\"></div\n>");
}

function display_all_ratings($row,  $noart)
{
  global $debug;

  $movie_id       = $row['movie_id'];
  $combo_rating   = $row['combo_rating'];
  $imdb_url       = $row['imdb_url'];
  $imdb_rating    = $row['imdb_rating'];
  $rotten_url     = $row['rotten_url'];
  $rotten_rating  = $row['rotten_rating'];
  $flixster_rating  = $row['flixster_rating'];
  $flixster_url  = $row['flixster_url'];
  $my_rating      = $row['my_rating'];
  $my_status      = $row['my_status'];
  $friend_rating  = $row['friend_rating'];
  $friend_status  = $row['friend_status'];
  $siff2011_url   = $row['siff2011_url'];
     
  if ($_SESSION['logged_in'])
  {
    $my_name      = $_SESSION['firstname'];
    $friend_name  = $_SESSION['friend_firstname'];
  }

  if ( $_SESSION['tab_status'] == "siff")
    // && $siff2011_url != "")
  {
    display_rating("Combo", $combo_rating );
    display_rating("IMDB",  $imdb_rating, $imdb_url);
    display_siff_button($siff2011_url, 1);
  }
  else if ($noart)
  {
    display_by_sort_mode($_SESSION['sort_mode'], $row);
  }
  else if ($_SESSION['sort_mode'] == "combo_rating")
  {
    display_rating("Combo",           $combo_rating   );
    if ($_SESSION['logged_in'])
      display_rating($my_name, $my_rating );
  }
  else
  {
    display_rating("IMDB",        $imdb_rating,    $imdb_url);
    display_rating("TomatoMeter", $rotten_rating,  $rotten_url);
    display_rating("Flixster",     $flixster_rating, $flixster_url);
    if ($_SESSION['logged_in'])
    {
      if ($_SESSION['allow_rating'])
        display_rating($my_name,     $my_rating     );
      display_rating($friend_name, $friend_rating );
    }
  }
}

function display_by_sort_mode($sort_mode, $row)
{
  if ($sort_mode == "combo_rating")
    display_rating(" -- Combo", $row['combo_rating'] );
  else if ($sort_mode == "my_rating")
    display_rating(" -- {$_SESSION['firstname']}", $row['my_rating']    );
  else if ($sort_mode == "friend_rating")
    display_rating(" -- {$_SESSION['friend_firstname']}", $row['friend_rating'] );
  else if ($sort_mode == "imdb_rating")
    display_rating(" -- IMDB", $row['imdb_rating']);
  else if ($sort_mode == "rotten_rating")
    display_rating(" -- TomatoMeter", $row['rotten_rating']);
  else
    return;
}

function display_rating($leadin, $rating, $url="")
{
  $has_link = ($url != "");
  $has_rating = ($rating > 0);

  $rating_string = "$leadin: ";
  if ($has_rating)
    $rating_string .= "$rating";
  else
    $rating_string .= "--";

  if ($has_link)
    display_link($url, $rating_string);
  else
    print("  $rating_string\n");
  print "  <br/>\n";
}

function display_image($title, $img)
{
  print("<img src=\"$img\" border=\"0\" ");
  print("alt=\"$title\" title=\"$title\"/>");
}

function display_link($href, $title, $img="", $indent=0)
{
  for ($ii=0; $ii < $indent; $ii++) print(" ");
  print("<a href=\"$href\">");

  if ($img != "")
  {
    print("\n");
    for ($ii=0; $ii < $indent; $ii++) print(" ");
    print("<img src=\"$img\" border=\"0\" alt=\"$title\" title=\"$title\"/>");
    print("\n");
    for ($ii=0; $ii < $indent; $ii++) print(" ");
    print ("</a>\n");
  }
  else
  {
    print("$title");
    print ("</a>\n");
  }
}

function delete_movie($movie_id)
{
  global $debug;

  if (!allow_edit())
    return;

  if (!isset($movie_id))
    die("delete_movie(): Movie ID is not set.");

  $filter = "movie_id = '$movie_id'";
  $query = "DELETE FROM movie WHERE $filter LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("FAILED: $query");

  $query = "DELETE FROM user_movie WHERE $filter";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("FAILED: $query");

  $_SESSION['total_movies'] = count_all_movies();
}

function update_all_combo_ratings()
{
  global $debug;

  // GET MOVIE LIST -- keep this one around till the end where it's reused
  $columns = "movie_id, imdb_rating, rotten_rating";
  $tables  = "movie";
  $query   = "SELECT $columns FROM $tables";

  if ($debug)
    print("Running Query: $query<br>\n");
  $movie_list_result = mysql_query($query);
  if (!$movie_list_result)
    die("update_all_combo_ratings(): Failed $query");

  $imdb_min    = 100.0;
  $imdb_max    =  -1.0;
  $alex_min    = 100.0;
  $alex_max    =  -1.0;

  while ($row = mysql_fetch_array($movie_list_result))
  {
    $value = (float)$row['imdb_rating'];
    if ($value > -0.5)
    {
      if ($value > $imdb_max)
        $imdb_max = $value; 
      if ($value < $imdb_min)
        $imdb_min = $value;
    }
  }
  $_SESSION['imdb_min'] = $imdb_min;
  $_SESSION['imdb_max'] = $imdb_max;

  // GET ALL USERS WHO HAVE RATED ANY MOVIES -- initialize range for each user
  $columns = "user_id";
  $tables  = "user_movie";
  $query   = "SELECT $columns FROM $tables ORDER BY user_id";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("update_all_combo_ratings(): Failed $query");

  $min_ratings = 10;
  while ($row = mysql_fetch_array($result))
  {
    $min_key = "min_rating_{$row['user_id']}";
    $max_key = "max_rating_{$row['user_id']}";
    if (!isset($this_user_id))
    {
      unset($_SESSION[$min_key]);
      unset($_SESSION[$max_key]);
      $this_user_id = $row['user_id'];
      $ratings_count=0;
    }
    elseif ($this_user_id != $row['user_id'])
    {
      // New user -- clear last user if not enough votes and reset
      // debug("Ratings Count UID $this_user_id: $ratings_count");
      if ($ratings_count < $min_ratings)
      {
        $min_reset = "min_rating_$this_user_id";
        $max_reset = "max_rating_$this_user_id";
        unset($_SESSION[$min_reset]);
        unset($_SESSION[$max_reset]);
      }
      unset($_SESSION[$min_key]);
      unset($_SESSION[$max_key]);
      $this_user_id = $row['user_id'];
      $ratings_count=0;
    }
    $_SESSION[$min_key] = 100;
    $_SESSION[$max_key] = -1;
    $ratings_count++;
  }
  // Clear last user if not enough ratings
  // debug("Ratings Count UID $this_user_id: $ratings_count");
  if ($ratings_count < $min_ratings)
  {
    unset($_SESSION[$min_key]);
    unset($_SESSION[$max_key]);
  }
  mysql_free_result($result);

  // GET USER RATINGS FOR ALL MOVIES -- update rating range for each user
  $columns = "movie_id, user_id, one_to_ten";
  $tables  = "user_movie";
  $query   = "SELECT $columns FROM $tables";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("update_all_combo_ratings(): Failed $query");

  while ($row = mysql_fetch_array($result))
  {
    $min_key = "min_rating_{$row['user_id']}";
    $max_key = "max_rating_{$row['user_id']}";

    $value = (float)$row['one_to_ten'];
    if ((isset($_SESSION[$max_key]))
     && (isset($_SESSION[$min_key]))
     && ($value > 0.5))
    {
      // debug("Updating User {$row['user_id']} range by $value");
      if ($value > $_SESSION[$max_key])
        $_SESSION[$max_key] = $value; 
      if ($value < $_SESSION[$min_key])
        $_SESSION[$min_key] = $value;
    }
  }
  mysql_free_result($result);

  //print("IMDB    Range: $imdb_min,    $imdb_max<br/>\n");
  //print("Alex    Range: $alex_min,    $alex_max<br/>\n");
  //print("Durwood Range: $durwood_min, $durwood_max<br/>\n");

  // Reset result counter on original movie list and update each combo rating
  mysql_data_seek($movie_list_result, 0);
  while ($row = mysql_fetch_array($movie_list_result))
  {
    update_combo_rating($row['movie_id']);
  }
}

function update_combo_rating($movie_id)
{
  global $debug;

  // GET EXTERNAL RATINGS for this movie
  $columns = "title,imdb_rating, rotten_rating, flixster_rating";
  $tables  = "movie";
  $filter  = "movie_id = $movie_id";
  $query   = "SELECT $columns FROM $tables WHERE $filter LIMIT 1";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("update_combo_rating(): Failed Query $query");
  $row = mysql_fetch_array($result);
  $imdb    = (float)$row['imdb_rating'];
  $rotten  = (float)$row['rotten_rating'];
  $flixster = (float)$row['flixster_rating'];
  $imdb_min    = (float)$_SESSION['imdb_min'];
  $imdb_max    = (float)$_SESSION['imdb_max'];

  //debug("");
  //debug("Updating combo rating for {$row['title']}");

  $tot = 0.0;
  $cnt = 0;
  if (isset($imdb) && $imdb > -0.5)
  {
    $scaled = scale_rating($imdb_min, $imdb_max, 1.0, 10.0, $imdb);
    //debug("IMDB Range: $imdb_min, $imdb_max Original: $imdb Scaled: $scaled");

    $tot += $scaled;
    $cnt++;
  }
  if (isset($rotten) && $rotten > -0.5)
  {
    $scaled = scale_rating(0.0, 100.0, 1.0, 10.0, $rotten);
    //debug("Rotten Range: 0.0, 100.0 Orig: $rotten Scaled: $scaled");
    $tot += $scaled;
    $cnt++;
  }
  if (isset($flixster) && $flixster > -0.5)
  {
    $scaled = scale_rating(0.0, 100.0, 1.0, 10.0, $flixster);
    //debug("Flixster Range: 0.0, 100.0 Orig: $flixster Scaled: $scaled");
    $tot += $scaled;
    $cnt++;
  }
  mysql_free_result($result);
  
  // GET USER RATINGS for this movie
  $columns = "user_id, one_to_ten";
  $tables  = "user_movie";
  $query   = "SELECT $columns FROM $tables WHERE movie_id=$movie_id";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("update_combo_rating(): Failed Query $query");

  while ($row = mysql_fetch_array($result))
  {
    $uid = $row['user_id'];
    $min_key = "min_rating_{$uid}";
    $max_key = "max_rating_{$uid}";
    if ((!isset($_SESSION[$min_key]))
     || (!isset($_SESSION[$max_key])))
      continue;

    $value = (float)$row['one_to_ten'];
    if ($value > 0.5)
    {
      $min = (float)$_SESSION[$min_key];
      $max = (float)$_SESSION[$max_key];
      $scaled = scale_rating($min, $max, 1.0, 10.0, $value);
      //debug("UID $uid Range: $min, $max Orig: $value Scaled: $scaled");
      $tot += $scaled;
      $cnt++;
    }
  }
  mysql_free_result($result);

  // Calculate combo rating
  $average = -1.0;
  if ($cnt > 0)
    $average = $tot / $cnt;
  
  //debug("Total: $tot, Count: $cnt, Average: $average");

  // UPDATE NEW COMBO RATING
  $set   = "combo_rating='$average'";
  $query = "UPDATE movie SET $set WHERE $filter LIMIT 1";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die("update_combo_rating(): Failed Query $query");

  return;
}

function scale_rating($src_min, $src_max, $dest_min, $dest_max, $src_value)
{
  $factor = ($src_value - $src_min) / ($src_max - $src_min);
  $scaled = $dest_min + ($factor * ($dest_max - $dest_min));
  return $scaled;
}

function update_movie_recommendation($entry)
{
  global $debug;

  if ($debug)
    display_array($entry);

  $movie_id         = $entry['movie_id'];
  $recommendation   = $entry['recommendation'];
  $date_recommended = $entry['date_recommended'];
  if ((!isset($movie_id)) || ($movie_id == NULL))
    return;

  $set = "recommendation=\"$recommendation\""; 
  if ($date_recommended != "")
  {
    $date_integer = strtotime($date_recommended);
    $mysql_date = date('Y-m-d', $date_integer);
    $set .= ", date_recommended='$mysql_date'";
  }
  else
  {
    $set .= ", date_recommended='0000-00-00 00:00:00'";
  }

  $filter = "movie_id = '$movie_id'";
  $query = "UPDATE movie SET $set WHERE $filter LIMIT 1";

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die ("FAILED: $query");

  return($movie_id);
}

function update_movie_info($entry)
{
  global $debug;

  if ($debug)
    display_array($entry);

  $movie_id          = $entry['movie_id'];
  $created           = $entry['created'];
  $title             = $entry['title'];
  $alt_title         = $entry['alt_title'];
  $art_url           = $entry['art_url'];
  $art_fname         = $entry['art_fname'];
  $year              = $entry['year'];
  $keywords          = $entry['keywords'];
  $trailer_url       = $entry['trailer_url'];
  $imdb_url          = $entry['imdb_url'];
  $imdb_rating       = $entry['imdb_rating'];
  $rotten_url        = $entry['rotten_url'];
  $rotten_rating     = $entry['rotten_rating'];
  $flixster_url       = $entry['flixster_url'];
  $flixster_rating    = $entry['flixster_rating'];
  $tla_url           = $entry['tla_url'];
  $amazon_dvd_url    = $entry['amazon_dvd_url'];
  $amazon_bluray_url = $entry['amazon_bluray_url'];
  $netflix_url       = $entry['netflix_url'];
  $netfilx_rating    = $entry['netfilx_rating'];
  $scarecrow_url     = $entry['scarecrow_url'];
  $siff2011_url      = $entry['siff2011_url'];

  if ($imdb_rating == "")
    $imdb_rating = -1;

  if ($rotten_rating == "")
    $rotten_rating = -1;

  if ($netflix_rating == "")
    $netflix_rating = -1;

  if ($flixster_rating == "")
    $flixster_rating = -1;

  if ($year == "")
    $year = 0;

  // If art_fname is set, we need to possibly add jpg extension
  if (isset($art_fname) && ($art_fname != ""))
  {
    $dotpos = strpos($art_fname, ".");
    if ($dotpos === false)
    { 
      $art_fname = "{$art_fname}_dvd.jpg";
    }
  }

  // Expand the filename
  $fname = "images/original/$art_fname";

  // Update database, storing target filename for art
  // Allow for apostrophes in title by using double-quotes!
  $new_movie = (!isset($movie_id)) || ($movie_id == NULL);

  if ($new_movie)
  {
    $set = "alt_title=\"$alt_title\",
            title=\"$title\",
            created=null";        // needed to autoset creation date
    $query = "INSERT INTO movie SET $set";
  }
  else
  {
    $set = "art='$art_fname', 
            year='$year', 
            title=\"$title\",
            alt_title=\"$alt_title\",
            keywords=\"$keywords\",
            imdb_rating='$imdb_rating',
            rotten_rating='$rotten_rating',
            flixster_rating='$flixster_rating',
            netflix_rating='$netflix_rating', 
            imdb_url='$imdb_url', 
            rotten_url='$rotten_url',
            flixster_url='$flixster_url', 
            netflix_url='$netflix_url', 
            amazon_dvd_url='$amazon_dvd_url', 
            amazon_bluray_url='$amazon_bluray_url', 
            scarecrow_url='$scarecrow_url', 
            siff2011_url='$siff2011_url', 
            tla_url='$tla_url', 
            trailer_url='$trailer_url'"; 

    if ($created != "")
    {
      $date_integer = strtotime($created);
      $mysql_date = date('Y-m-d', $date_integer);
      $set .= ", created='$mysql_date'";
    }

    $filter = "movie_id = '$movie_id'";
    $query = "UPDATE movie SET $set WHERE $filter LIMIT 1";
  }

  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if (!$result)
    die ("FAILED: $query");

  if ($new_movie)
  {
    $movie_id = mysql_insert_id();
    update_activity_new_movie($_SESSION['uid'], $movie_id);
    $_SESSION['total_movies'] = count_all_movies();
  }
  else
  {
    // Recalculate  all combo ratings since this one may have changed scaling
    update_all_combo_ratings();

    // Copy the URL to the target filename
    if (isset($art_url) && ($art_url != "")) {
      save_image($art_url, $art_fname);
    }
  }

  return($movie_id);
}

function is_durwood_logged_in()
{
  if ( ($_SESSION['logged_in'] == true)
    && ($_SESSION['fb_uid'] == DURWOOD_FB_UID))
    return true;
  else
    return false;
}

function is_alex_logged_in()
{
  if ( ($_SESSION['logged_in'] == true)
    && ($_SESSION['fb_uid'] == ALEX_FB_UID))
    return true;
  else
    return false;
}

function is_new($row, $movie_id)
{
  global $debug;

  $rating = $row['my_rating'];
  $status = $row['my_status'];

  // If user's status is something other than 'not-seen', this isn't new
  if (isset($status) && $status == "not_seen" )
  {
    return false;
  }
  // If logged in user has rated the movie, it's not new
  if (isset($rating) && $rating > 0)
  {
    return false;
  }

  $created = strtotime($row['created']);
  $two_weeks_ago = strtotime("-2 weeks");
  $now = strtotime("now");
  return ($created > $two_weeks_ago);
}

function update_movie_rating($entry)
{
  global $debug;

  if ($debug)
    display_array($entry);

  $movie_id  = $entry['movie_id'];
  $my_status = $entry['status'];
  $my_rating = $entry['rating'];
  $comment   = $entry['comment'];
  $own       = isset($entry['own']) ? 1 : 0;

  if (!isset($movie_id))
    return NULL;

  // Update user-movie table, adding new entry if necessary
  update_user_movie_table_entry($movie_id, $my_rating, $my_status, $comment, $own);
  return($movie_id);
}

function update_user_movie_table_entry($movie_id, $my_rating, $my_status, $comment, $own)
{
  global $debug;

  debug("Own: $own");
  if ( ($_SESSION['logged_in']    == false)
    || ($_SESSION['allow_rating'] == false))
    return;

  $user_id = $_SESSION['uid'];
  if ( (!is_valid_movie_id($movie_id))
    || (!is_valid_user_id($user_id)) )
    return;

  // Fixup comment to make sure there's no hidden MySql attack, etc
  $comment = mysql_real_escape_string(trim($comment));
  
  $user_movie_id = get_user_movie_id($user_id, $movie_id);
  if (!isset($user_movie_id))
  {
    // If not default values, add new entry
    if ( (isset($my_rating)  && $my_rating > 0)
      || (isset($my_status)  && $my_status != "not_seen")
      || (isset($comment)    && $comment != "")
      || (isset($own)        && $own != 0))
    {
      $user_movie_id = insert_user_movie($user_id, $movie_id);
      if (!isset($user_movie_id))
        return;
    }
    else
    {
      return;  // Not an error -- no need to add defaults or update activity
    }
  }

  // Get Current info to see if this is an activity to report
  $query = "SELECT one_to_ten,status,comment,own from user_movie WHERE user_movie_id=$user_movie_id LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if ($result && ($row = mysql_fetch_array($result)))
  {
    $added_comment = false;
    if ( isset($comment) 
      && $comment != ""
      && ($comment != trim($row['comment'])))
    {
      $added_comment = true;
    }

    $added_rating = false;
    if ( (isset($my_rating))
      && (!isset($row['one_to_ten']) || ($my_rating != $row['one_to_ten'])))
    {
      $added_rating = true;
    }

    $changed_status = false;
    if ( (isset($my_status))
      && ($my_status == "want_to_see" || $my_status == "seen") 
      && ($my_status != $row['status']))
    {
      $changed_status = true;
    }

    if ($added_rating)
    {
      if ($added_comment)
        update_activity_add_comment_to_movie($user_id, $movie_id, $my_rating);
      else
        update_activity_rate_movie($user_id, $movie_id, $my_rating);
    }
    else if ($added_comment)
        update_activity_add_comment_to_movie($user_id, $movie_id);
    else if ($changed_status)
      update_activity_want_to_see_movie($user_id, $movie_id);
  }

  $set    = "one_to_ten=$my_rating";
  $set   .= ", status='$my_status'";
  $set   .= ", comment=\"$comment\"";
  $set   .= ", own=$own";
  $filter = "user_movie_id=$user_movie_id";
  $query  = "UPDATE user_movie SET $set WHERE $filter LIMIT 1";

  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query failed: $query");
  return true;
}

function get_movie_title($movie_id)
{
  global $debug;

  $query = "SELECT title from movie WHERE movie_id=$movie_id LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if ($result && ($row = mysql_fetch_array($result)))
    return ($row['title']);
  else
    return "";
}

function get_user_firstname($uid)
{
  global $debug;

  if (!isset($uid))
    return;

  $query = "SELECT fb_firstname from user WHERE uid=$uid LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die("Failed query: $query");
  if ($result && ($row = mysql_fetch_array($result)))
    return ($row['fb_firstname']);
  else
    return "";
}
  
function get_user_rating($uid)
{
  $query = "SELECT one_to_ten from user WHERE user_id=$uid LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query);
  if ($result && ($row = mysql_fetch_array($result)))
    return ($row['one_to_ten']);
  else
    return NULL;
}

function update_activity_add_comment_to_movie($uid, $movie_id, $rating=0)
{
  if (($_SESSION['logged_in']    == false)
   || ($_SESSION['allow_rating'] == false))
    return;

  if ( !is_valid_movie_id($movie_id) 
    || !is_valid_user_id($uid) )
    return;

  $set = "activity='add_comment'";
  $set .= ", uid=$uid";
  $set .= ", movie_id=$movie_id";
  if ($rating != 0)
    $set .= ", new_rating=$rating";

  $query = "INSERT INTO activity SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query Failed: $query");
}

function update_activity_want_to_see_movie($uid, $movie_id)
{
  if (($_SESSION['logged_in']    == false)
   || ($_SESSION['allow_rating'] == false))
    return;

  if ( !is_valid_movie_id($movie_id) 
    || !is_valid_user_id($uid) )
    return;

  $set = "activity='want_to_see'";
  $set .= ", uid=$uid";
  $set .= ", movie_id=$movie_id";

  $query = "INSERT INTO activity SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query Failed: $query");
}

function update_activity_rate_movie($uid, $movie_id, $rating)
{
  if (($_SESSION['logged_in']    == false)
   || ($_SESSION['allow_rating'] == false))
    return;

  if ( !is_valid_movie_id($movie_id) 
    || !is_valid_user_id($uid) )
    return;

  $set = "activity='rate'";
  $set .= ", uid=$uid";
  $set .= ", movie_id=$movie_id";
  $set .= ", new_rating=$rating";

  $query = "INSERT INTO activity SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query Failed: $query");
}

function update_activity_seen_movie($uid, $movie_id)
{
  if (($_SESSION['logged_in']    == false)
   || ($_SESSION['allow_rating'] == false))
    return;

  if ( !is_valid_movie_id($movie_id) 
    || !is_valid_user_id($uid) )
    return;

  $set = "activity='watch'";
  $set .= ", uid=$uid";
  $set .= ", movie_id=$movie_id";

  $query = "INSERT INTO activity SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query Failed: $query");
}

function update_activity_new_movie($uid, $movie_id)
{
  if (($_SESSION['logged_in'] == false)
   || ($_SESSION['allow_edit'] == false))
    return;

  if ( !is_valid_movie_id($movie_id) 
    || !is_valid_user_id($uid) )
    return;

  $set  = "activity='add_new'";
  $set .= ", uid=$uid";
  $set .= ", movie_id=$movie_id";
 
  $query = "INSERT INTO activity SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");
  mysql_query($query) or die("Query Failed: $query");
}

function is_valid_movie_id($movie_id)
{
  global $debug;

  $query = "SELECT movie_id from movie WHERE movie_id=$movie_id LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");

  return(mysql_query($query));
}

function is_valid_user_id($user_id)
{
  global $debug;

  $query = "SELECT uid from user WHERE uid=$user_id LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");

  return(mysql_query($query));
}

function get_user_movie_comment($uid, $movie_id)
{
  global $debug;

  $user_movie_id = get_user_movie_id($uid, $movie_id);
  if (isset($user_movie_id))
  {
    $query = "SELECT comment from user_movie WHERE user_movie_id=$user_movie_id LIMIT 1";
    if ($debug)
      print("Running Query: $query<br>\n");
    $result = mysql_query($query);
    if ($result && ($row = mysql_fetch_array($result)))
    {
      return($row['comment']);
    }
  }
  return NULL;
}

function get_user_movie_id($user_id, $movie_id)
{
  global $debug;

  $filter  = "user_id=$user_id";
  $filter .= " AND movie_id=$movie_id";
  $query = "SELECT user_movie_id FROM user_movie WHERE $filter LIMIT 1";
  if ($debug)
    print("Running Query: $query<br>\n");

  $result = mysql_query($query);
  if ($result && ($row = mysql_fetch_array($result)))
  {
    return $row['user_movie_id'];
  }
}

function insert_user_movie($user_id, $movie_id)
{
  global $debug;

  $set  = "user_id=$user_id";
  $set .= ", movie_id=$movie_id";
  $query = "INSERT INTO user_movie SET $set";
  if ($debug)
    print("Running Query: $query<br>\n");

  if ($result = mysql_query($query))
  {
    return mysql_insert_id();
  }
}

function redirect_to_movie_list($movie_id=NULL)
{
  global $php_self;

  unset($_REQUEST['movie_id']);
  if (($movie_id != NULL) && ($_SESSION['tab_status'] != "recommendation"))
    header("Location: $php_self#anchor_$movie_id");
  else
    header("Location: $php_self");
}

function redirect_to_movie_info($movie_id=NULL)
{
  global $php_self;

  unset($_REQUEST['movie_id']);
  if ($movie_id != NULL)
    header("Location: $php_self?movie_id=$movie_id");
  else
    header("Location: $php_self");
}

function redirect_to_movie_edit($movie_id=NULL)
{
  global $php_self;
 
  unset($_REQUEST['movie_id']);
  if ($movie_id == NULL)
    header("Location: $php_self?edit");
  else
    header("Location: $php_self?edit&movie_id=$movie_id#anchor_edit_controls");
    //header("Location: $php_self?edit&movie_id=$movie_id");
}

function save_image($remote_url, $fname)
{
  // Save Original image
  $path = "images/original/$fname";
  saveit($remote_url, $path);

  // Remove any previously derived versions of this image
  $path = "images/100/$fname";
  if (file_exists($path))
    unlink($path);
  $path = "images/200/$fname";
  if (file_exists($path))
    unlink($path);
  $path = "images/300/$fname";
  if (file_exists($path))
    unlink($path);
  $path = "images/400/$fname";
  if (file_exists($path))
    unlink($path);
}

function saveit ($remote_url, $file)
{
   global $debug;

   if ($debug)
     print("Saving $remote_url to $file<br>\n");

   $src_url = str_replace(array(" "), array("%20"),  "$remote_url");
   $src_fp = fopen($src_url, "r");
   if (!$src_fp) {
      die ("Unable to open $src_url for reading\n");
   }
   $dest_fp = fopen($file, "w");
   if (!$dest_fp) {
      die ("Unable to open $file for writing\n");
   }

   while ($buffer = fread($src_fp, 65536)) {
      fwrite($dest_fp, $buffer);
   }
   fclose($dest_fp);
   fclose($src_fp);
   return;
}

function get_image($name, $target_height)
{
  global $debug;

  $src_fname = "images/original/$name";

  list($width, $height, $type, $attr) = getimagesize($src_fname);
  if ($height <= $target_height)
    return $src_fname;
  
  if ($target_height <= 100)
  {
    $target_fname = "images/100/$name";
    $target_scale = 100.0 / $height;
  }
  else if ($target_height <= 200)
  {
    $target_fname = "images/200/$name";
    $target_scale = 200 / $height;
  }
  else if ($target_height <= 300)
  {
    $target_fname = "images/300/$name";
    $target_scale = 300 / $height;
  }
  else if ($target_height <= 400)
  {
    $target_fname = "images/400/$name";
    $target_scale = 400 / $height;
  }
  if (file_exists($target_fname))
    return($target_fname);

  $target_width = $width * $target_scale;

  resize_image($src_fname, $height, $width,
               $target_fname, $target_height, $target_width);
  return($target_fname);
}

function resize_image($src, $src_height, $src_width, 
                      $dest, $dest_height, $dest_width)
{
  global $debug;

  if (is_executable("/usr/local/bin/convert"))
    $convert_path = "/usr/local/bin/convert";
  else if (is_executable("/bin/convert"))
    $convert_path = "/bin/convert";
  else if (is_executable("/usr/bin/convert"))
    $convert_path = "/usr/bin/convert";
  else
    die ("convert_image: Cannot find image conversion program");

  $s = $dest_width . "x" . $dest_height;

  umask(0007);  // Create file as -rw-rw----
  $command = "$convert_path -size $s \"$src\" -resize $s +profile '*' \"$dest\"";
  if ($debug)
    print("Resizing Image: $command<br>\n");

  system($command, $stat);
  if (($stat != 0) && $gOpt['debug'])
    print ("resize_image: Cannot resize image with:\n<br/>$command\n<br/>");
  return $stat;
}

function another_resize_image($src, $src_height, $src_width, 
                              $dest, $dest_height, $dest_width)
{
  $dest_image = imagecreatetruecolor($dest_width, $dest_height);
  $src_image = imagecreatefromjpeg($src);

  //  imagecopyresized($target, $source, 0, 0, 0, 0, 
  //                   $target_width, $taget_height, $width, $height);
  imagecopyresampled($dest_image, $src_image, 0, 0, 0, 0, 
                   $dest_width, $dest_height, $src_width, $src_height);

  imagejpeg($dest_image, $dest, 90);
  return;
}

function display_movie_info($movie_id)
{
  global $debug;
  global $php_self;
  global $g_cover_height;

  $row = query_one_movie($_SESSION['uid'], $_SESSION['friend_uid'], $movie_id);
  $title          = $row['title'];
  $year           = $row['year'];
  $art            = $row['art'];
  $combo_rating   = $row['combo_rating'];
  $imdb_rating    = $row['imdb_rating'];
  $imdb_url       = $row['imdb_url'];
  $rotten_rating  = $row['rotten_rating'];
  $flixster_rating  = $row['flixster_rating'];
  $rotten_url     = $row['rotten_url'];
  $trailer_url    = $row['trailer_url'];
  $scarecrow_url  = $row['scarecrow_url'];
  $siff2011_url   = $row['siff2011_url'];
  $amazon_dvd_url = $row['amazon_dvd_url'];
  $amazon_bluray_url = $row['amazon_bluray_url'];

  if (is_file("images/original/$art"))
    $image = get_image($art, $g_cover_height);
  else
    $image = get_image("dvd_logo.jpg", $g_cover_height);
  
  //print("<div>\n");
  display_edit_mode_menu($movie_id);

  // gdg print("<div class=\"left_column\">\n");

  print("<div class=\"movie_cover large\">\n");
  display_siff_button($row['siff2011_url']);
  if ($_SESSION['tab_status'] == "recommendation")
  {
    $link = "$php_self"; 
    display_link("$link", "$title - Click to Return", $image);
  }
  else
  {
    $next_movie_id = get_next_movie_id($movie_id);
    $link = "$php_self?movie_id=$next_movie_id"; 
    display_link("$link", "$title - Click for Next Movie", $image);
  }
  print("<br/>\n");

  display_amazon_buy_button($amazon_dvd_url, $amazon_bluray_url);
  //print("<br/>\n");
  display_fbook_like_button("button_count", $movie_id);
  print("</div>\n\n");

  print("<div id=\"anchor_info_controls\"class=\"main_column\">\n");
  display_movie_info_controls($movie_id);
  display_movie_info_text($row);
  print("</div>\n\n");

  // gdg print("</div>\n");

}

function display_movie_info_text($values)
{
  $title         = $values['title'];
  $alt_title     = $values['alt_title'];
  $year          = $values['year'];
  $combo_rating  = $values['combo_rating'];
  $imdb_rating   = $values['imdb_rating'];
  $imdb_url      = $values['imdb_url'];
  $rotten_rating = $values['rotten_rating'];
  $flixster_rating = $values['flixster_rating'];
  $flixster_url = $values['flixster_url'];
  $rotten_url    = $values['rotten_url'];

  $my_name    = $_SESSION['firstname'];
  $my_rating  = $values['my_rating'];
  $my_status  = $values['my_status'];
  $my_comment = $values['my_comment'];

  $friend_name    = $_SESSION['friend_firstname'];
  $friend_rating  = $values['friend_rating'];
  $friend_status  = $values['friend_status'];
  $friend_comment = $values['friend_comment'];

  $random_name    = $values['random_name'];
  $random_comment = $values['random_comment'];

  print("<div class=\"movie_title\"><b>\n");
  //print("  <br/>\n");
  //print("  <br/>\n");
  print("  <br/>\n");
  if ($year == 0)
    print("  <b>$title</b><br/>\n");
  else
    print("  <b>$title</b> ($year)<br/>\n");
  if (isset($alt_title))
    print("  <b>$alt_title</b><br/>\n");

  print("  <br/>\n");
  display_rating("Combo",           $combo_rating   );
  display_rating("IMDB",            $imdb_rating,   $imdb_url);
  display_rating("TomatoMeter",     $rotten_rating, $rotten_url);
  display_rating("Flixster",        $flixster_rating, $flixster_url);
  if ($_SESSION['logged_in'])
  {
    print("  <br/>\n");
    if (isset($my_name))
    {
      if ($my_rating > 0)
        display_rating($my_name,        $my_rating    );
      else
        display_status($my_name,        $my_status);
    }
    if (isset($friend_name))
    {
      if ($friend_rating > 0)
        display_rating($friend_name,    $friend_rating );
      else
        display_status($friend_name,    $friend_status);
    }
  
    print("  <br/>\n");
    if (isset($my_name))
      display_comment($my_name,       $my_comment);
    if (isset($friend_name))
      display_comment($friend_name,    $friend_comment);
    if (isset($random_comment))
      display_comment($random_name,    $random_comment);
  }
  else
  {
    $random_comment_result = query_random_comments($values['movie_id']);
    if (isset($random_comment_result[0]))
    {
      $random_name = get_user_firstname($random_comment_result[0]['uid']);
      display_comment($random_name, $random_comment_result[0]['comment']);
    }
    if (isset($random_comment_result[1]))
    {
      $random_name = get_user_firstname($random_comment_result[1]['uid']);
      display_comment($random_name, $random_comment_result[1]['comment']);
    }
  }
  print("</div>\n\n");
}

function display_status($leadin, $status)
{
  if (!isset($leadin))
    return;

  if (!isset($status) || $status == 'not_seen')
    print("$leadin has not seen.<br/>\n");
  elseif ($status == 'not_interested')
    print("$leadin is not interested.<br/>\n");
  elseif ($status == 'seen')
    print("$leadin has seen.<br/>\n");
  elseif ($status == 'want_to_see')
    print("$leadin wants to see.<br/>\n");
}

function display_recommendation_comment($whosaid, $comment)
{
  $str = trim($comment);
  if (!isset($str) || $str == "")
    return;

  print("\n");
  if (isset($whosaid) && $whosaid != "")
    print("$whosaid says...\n");
  //print("<div class=\"lefty\">");
  //print("<div class=\"movie_title\"><b>\n");
  print("<p class=\"comment\">\n");
  print("$str\n");
  print("</p>\n");
  //print("</div>");
  //print("</div>");
  print("\n");
}
function display_comment($whosaid, $comment)
{
  $str = trim($comment);
  if (!isset($str) || $str == "")
    return false;

  print("\n");
  if (isset($whosaid) && $whosaid != "")
  {
    print("<p class=\"comment_author\">\n");
    print("$whosaid says...\n");
  }
  //print("<div class=\"lefty\">");
  //print("<div class=\"movie_title\"><b>\n");
  //print("<div class=\"comment\">\n");
  print("<p class=\"comment\">\n");
  print("<i>$str</i>\n");
  print("</p>\n");
  //print("</div>\n");
  //print("</div>");
  //print("</div>");
  print("\n");
  return true;
}

function display_movie_info_controls($movie_id)
{
  global $php_self;

  $display_next_buttons = true;
  if ($_SESSION['tab_status'] == "recommendation")
    $display_next_buttons = false;

  print "\n";
  print "<form method=\"post\" action=\"$php_self\" name=\"movie_info\">\n";

  add_hidden("movie_id", "$movie_id");

  print("<p>\n");
  if ($display_next_buttons)
    display_submit_button("prev_movie_info",   "Prev");
  display_submit_button("cancel_movie_info", "Return To List");
  if ($display_next_buttons)
    display_submit_button("next_movie_info",   "Next");
  if (allow_edit())
  {
    print("</p>\n");
    print("<p>\n");
    display_submit_button("recommend_movie", "Recommend Movie");
  }
  print("</p>\n");
  print ("</form>\n\n");
}

function rate_movie($movie_id)
{
  global $debug;
  global $php_self;
  global $g_cover_height;

  if (!isset($movie_id))
    die ("rate_movie() called with no movie_id");

  // Add a hidden variable to trigger updating the form contents
  add_hidden("set", "1");
  add_hidden("movie_id", "$movie_id");

  // Retreive movie data
  //query_one_movie($uid, $friend_uid, $movie_id);
  $row = query_one_movie($_SESSION['uid'], $_SESSION['friend_uid'], $movie_id);

  $art    = $row['art'];
  $title  = $row['title'];

  if (is_file("images/original/$art"))
    $image = get_image($art, $g_cover_height);
  else
    $image = get_image("dvd_logo.jpg", $g_cover_height);
  
  // Display Page
  //print "<form method=\"post\" action=\"$php_self\" name=\"movie_edit\">\n";

  if ($_SESSION['tab_status'] != "recommendation")
  {
    display_edit_mode_menu($movie_id);
  }

  //print("<div id=\"anchor_edit_form\" class=\"left_column\">\n");

  // Display Movie Image and Delete Button
  print("<div class=\"movie_cover large\">\n");
  display_siff_button($row['siff2011_url']);
  if ($_SESSION['tab_status'] == "recommendation")
  {
    $link = "$php_self?movie_id=$movie_id"; 
    display_link($link, "$title - Click to Return", $image);
  }
  else
  {
    $next_movie_id = get_next_movie_id($movie_id);
    $link = "$php_self?edit&movie_id=$next_movie_id"; 
    display_link($link, "$title - Click for Next Movie", $image);
  }
  print("<br/>\n");
  display_amazon_buy_button($row['amazon_dvd_url'], $row['amazon_bluray_url']);
  display_scarecrow_button($row['scarecrow_url']);
  print("</div>\n");

  // Display Form
  $full_edit = 0;
  print("<div id=\"anchor_edit_form\"class=\"main_column\">\n");
  print "<form method=\"post\" action=\"$php_self\" name=\"movie_edit\">\n";
  display_buttons($movie_id, $full_edit);
  display_ratings_edit_form($row, $movie_id); 
  print("</form>\n");
  print("</div>\n");   // end Display Form

  // End Enclosing Div
  // print("</div>\n");     // end left_column

  //print("</form>\n");
}

function recommend_movie($movie_id)
{
  global $debug;
  global $php_self;
  global $g_cover_height;

  // Add a hidden variable to trigger updating the form contents
  add_hidden("set", "1");

  // Retreive movie data
  if (!isset($movie_id))
    return;

  $columns = "title,year,art,date_recommended,recommendation";
  $filter  = "movie_id = $movie_id";
  $query   = "SELECT $columns FROM movie WHERE $filter LIMIT 1";
  $result = mysql_query($query) or die ("Failed Query: $query");
  if($row = mysql_fetch_array($result))
  {
    add_hidden("movie_id", "$movie_id");

    $art   = $row['art'];
    $title = $row['title'];

    if (is_file("images/original/$art"))
      $image = get_image($art, $g_cover_height);
    else
      $image = get_image("dvd_logo.jpg", $g_cover_height);
  
    print("  <div class=\"movie_cover large\">\n");
    display_image($title, $image);
    print("  </div>\n");

    print("<div id=\"anchor_recommendation_form\"class=\"main_column\">\n");

    print "<form method=\"post\" action=\"$php_self\" name=\"save_recommendation\">\n";
    display_recommendation_buttons($movie_id);
    display_recommendation_form($row, $movie_id); 
    print ("</form>\n");
    print("</div>\n");
  }
}

function display_recommendation_buttons($movie_id)
{
  if (!isset($movie_id) || $movie_id == NULL)
    return;

  print ("<p>\n");
  add_hidden("movie_id", "$movie_id");
  display_submit_button("cancel_recommendation", "Return To Movie");
  display_submit_button("save_recommendation",   "Save Recommendation");
  print ("</p>\n");
}

function display_recommendation_form($row, $movie_id)
{
  print "<table border=\"1\">\n";
  
  $title = $row['title'];
  $year  = $row['year'];
  if (isset($year))
    $title .= " ($year)";

  add_title($title);
  add_space();

  if ( isset($row['date_recommended']) 
    && $row['date_recommended'] != "0000-00-00 00:00:00")
    add_date_textbox("Date Recommended", "date_recommended", $row['date_recommended']);
  else
    add_date_textbox("Date Recommended", "date_recommended", "");

  print("<br/>\n");
  add_textarea("Movie Recommendation Comment <i>(limit 250 characters)</i>...", 
               "recommendation", $row['recommendation']);
  print "</table>\n";
}

function edit_movie($movie_id=NULL)
{
  global $debug;
  global $php_self;
  global $g_cover_height;

  // Add a hidden variable to trigger updating the form contents
  add_hidden("set", "1");

  // Retreive movie data
  if (isset($movie_id))
  {
    $columns  = "title,created,year,art,alt_title,keywords,trailer_url";
    $columns .= ",imdb_url,imdb_rating,rotten_url,rotten_rating";
    $columns .= ",flixster_url,flixster_rating,netflix_url,netflix_rating";
    $columns .= ",tla_url,amazon_dvd_url,amazon_bluray_url,scarecrow_url";
    $columns .= ",siff2011_url";
    
    $filter = "movie_id = $movie_id";
    $query = "SELECT $columns FROM movie WHERE $filter";
    $result = mysql_query($query) or die ("Query Failure: $query");
    if($row = mysql_fetch_array($result))
      add_hidden("movie_id", "$movie_id");

    $art    = $row['art'];
    $title  = $row['title'];

    if (is_file("images/original/$art"))
      $image = get_image($art, $g_cover_height);
    else
      $image = get_image("dvd_logo.jpg", $g_cover_height);
  }
  
  // Display Page
  //print "<form method=\"post\" action=\"$php_self\" name=\"movie_edit\">\n";
  if (isset($movie_id))
  {
    display_edit_mode_menu($movie_id);
  }


  //print("<div id=\"anchor_edit_form\" class=\"left_column\">\n");

  if (!isset($movie_id) && $_SESSION['allow_edit'])
    $full_edit = true;
  else
    $full_edit = $_SESSION['edit_mode'] == 'full_edit';
  $ratings_edit = $_SESSION['edit_mode'] == 'rate';

  // Display Movie Image and Delete Button
  if (isset($movie_id))
  {
    print("  <div class=\"movie_cover large\">\n");
    display_image($title, $image);
    print("  </div>\n");
  }

  // Display Form
  print("<div id=\"anchor_edit_form\"class=\"main_column\">\n");
  print "<form method=\"post\" action=\"$php_self\" name=\"movie_edit\">\n";
  if (isset($movie_id))
  {
    display_buttons($movie_id, $full_edit);
    display_full_edit_form($row, isset($movie_id) ? $movie_id : NULL); 
  }
  else
  {
    display_buttons(NULL, $full_edit);
    display_new_edit_form();
  }
  print ("</form>\n");
  print("</div>\n");   // end Display Form

  // End Enclosing Div
  //print("  </div>\n");     // end left_column

  //print ("</form>\n");
}

function confirm_delete($movie_id)
{
  global $debug;
  global $php_self;
  global $g_cover_height;

  // If an existing movie id is set, retreive its data
  if (isset($movie_id))
  {
    print "<form method=\"post\" 
                 action=\"$php_self\" 
                 name=\"confirm_delete\">\n";
    add_hidden("set", "1");

    $columns = "title,art";
    $filter = "movie_id = $movie_id";
    $query = "SELECT $columns FROM movie WHERE $filter";
    $result = mysql_query($query);
    if($row = mysql_fetch_array($result))
      add_hidden("movie_id", "$movie_id");
    else
      die("Could Not Retrieve Movie for Deletion: $movie_id");

    $art    = $row['art'];
    $title  = $row['title'];
    if (is_file("images/original/$art"))
      $image = get_image($art, $g_cover_height);
    else
      $image = get_image("dvd_logo.jpg", $g_cover_height);
  
    print("<div class=\"left_column\">\n");
    print("  <div class=\"movie_cover large\">\n");
    display_link("$php_self#anchor_$movie_id", $title, $image);
    print("  <br/>\n");
    print("<b>$title</b> (id=$movie_id)<br/>\n");
    display_submit_button("delete", "Confirm Deletion");
    display_submit_button("cancel_movie_edit", "Cancel");
    print("  </div>\n");
    print ("</form>\n");
  }
}

function display_new_edit_form()
{
  print "<table border=\"1\">\n";
  add_textbox("Movie Title", "title", "");
  add_textbox("Alternate Title", "alt_title", "");
  print ("</table>\n");
}

function display_full_edit_form($values, $movie_id=NULL)
{
  print "<table border=\"1\">\n";
  
  add_textbox("Movie Title", "title", $values['title']);
  add_textbox("Alternate Title", "alt_title", $values['alt_title']);
  if (isset($movie_id) && $movie_id != NULL)
  {
    add_space();
    add_textbox_dragdrop("Art URL", "art_url");

    // Build a reasonable name for image from title if none exists
    $art = $values['art'];
    if (!isset($art) || ($art == ""))
    {
      $art = title_to_string($values['title'], "_", true);
    }
    add_textbox("Art filename", "art_fname", $art);

    if ( isset($values['created']) 
      && $values['created'] != "0000-00-00 00:00:00")
      add_date_textbox("Created", "created", $values['created']);
    else
      add_date_textbox("Created", "created", "");

    if ($values['year'] == 0)
      add_textbox("Year", "year", "");
    else
      add_textbox("Year", "year", $values['year']);
    add_textbox("Keywords", "keywords", $values['keywords']);
    add_textbox_dragdrop("Trailer", "trailer_url", $values['trailer_url']);
    add_space();

    if ($values['imdb_url'] == "")
      add_textbox_dragdrop("IMDB URL", "imdb_url", "",
                           get_imdb_search_url($values['title']));
    else
      add_textbox_dragdrop("IMDB URL", "imdb_url", $values['imdb_url']);

    if ($values['imdb_rating'] < 0.0)
      add_textbox("IMDB Rating", "imdb_rating", "");
    else
      add_textbox("IMDB Rating", "imdb_rating", $values['imdb_rating']);
    add_space();

    if ($values['rotten_url'] == "")
      add_textbox_dragdrop("Rotten URL", "rotten_url", "",
                           get_rotten_search_url($values['title']));
    else
      add_textbox_dragdrop("Rotten URL", "rotten_url", $values['rotten_url']);

    if ($values['rotten_rating'] < 0.0)
      add_textbox("TomatoMeter", "rotten_rating", "");
    else
      add_textbox("TomatoMeter", "rotten_rating", $values['rotten_rating']);

    if ($values['flixster_url'] == "")
      add_textbox_dragdrop("Flixster URL", "flixster_url", "",
                           get_flixster_search_url($values['title']));
    else
      add_textbox_dragdrop("Flixster URL", "flixster_url", 
                            $values['flixster_url']);

    if ($values['flixster_rating'] < 0.0)
      add_textbox("Flixster", "flixster_rating", "");
    else
      add_textbox("Flixster", "flixster_rating", $values['flixster_rating']);
    add_space();

    add_textbox_dragdrop("Netflix URL", "netflix_url", $values['netflix_url']);
    if ($values['netflix_rating'] < 0.0)
      add_textbox("Netflix Rating", "netflix_rating", "");
    else
      add_textbox("Netflix Rating", "netflix_rating", $values['netflix_rating']);

    add_textbox_dragdrop("TlaVideo URL", "tla_url", $values['tla_url']);

    if ($values['amazon_dvd_url'] == "")
      add_textbox_dragdrop("Amazon DVD URL", "amazon_dvd_url", "",
                            get_amazon_search_url($values['title']));
    else
      add_textbox_dragdrop("Amazon DVD URL", "amazon_dvd_url", 
                            $values['amazon_dvd_url']);

    if ($values['amazon_bluray_url'] == "")
      add_textbox_dragdrop("Amazon Bluray URL", "amazon_bluray_url", "",
                           get_amazon_search_url($values['title'], true));
    else
      add_textbox_dragdrop("Amazon Bluray URL", "amazon_bluray_url", 
                           $values['amazon_bluray_url']);

    if ($values['scarecrow_url'] == "")
      add_textbox_dragdrop("Scarecrow URL", "scarecrow_url", "",
                            get_scarecrow_search_url($values['title']));
    else
      add_textbox_dragdrop("Scarecrow URL", "scarecrow_url", 
                            $values['scarecrow_url']);

    add_textbox_dragdrop("Siff 2011 URL", "siff2011_url", 
                          $values['siff2011_url']);
  }  
  print ("</table>\n");
}

function get_imdb_search_url($movie_title)
{  
  $url  = "http://www.imdb.com/find?s=all&amp;q=";
  $url .= title_to_string($movie_title, "+");
  return $url;
}

function get_scarecrow_search_url($movie_title)
{  
  $url  = "http://www.scarecrow.com/rental/?q=";
  $url .= title_to_string($movie_title, "+");
  return $url;
}

function get_amazon_search_url($movie_title, $bluray=false)
{
  $url  = "http://www.amazon.com/s/ref=nb_sb_noss?";
  $url .= "url=search-alias%3Ddvd&amp;field-keywords=";
  $url .= title_to_string($movie_title, "+");
  if ($bluray)
    $url .= "+blu+ray";
  else
    $url .= "+dvd";
  $url .= "&amp;x=0&y=0";
  return $url;
}
function get_rotten_search_url($movie_title)
{
  $url  = "http://www.rottentomatoes.com/search/full_search.php?search=";
  $url .= title_to_string($movie_title, "%20");
  return $url;
}

function get_flixster_search_url($movie_title)
{
  $url  = "http://www.flixster.com/search?q=";
  $url .= title_to_string($movie_title, "+");
  return $url;
}

function title_to_string($title, $space_substitute, $to_lower=false)
{
  $result = $title;
  $result = preg_replace("/, A$/", "", $result);
  $result = preg_replace("/, The$/", "", $result);
  $result = preg_replace("/, An$/", "", $result);
  $result = preg_replace("/, La$/", "", $result);
  $result = preg_replace("/'/", "", $result);
  $result = preg_replace("/,/", "", $result);
  $result = preg_replace("/-/", "", $result);
  $result = preg_replace("/\*/", "", $result);
  $result = preg_replace("/\s{1,2}/", $space_substitute, $result);
  if ($to_lower)
    $result = strtolower($result);
  return $result;
}

function display_ratings_edit_form($values, $movie_id) 
{
  print "<table border=\"1\">\n";
  
  $title = $values['title'];
  $year  = $values['year'];
  if (isset($year))
    $title .= " ($year)";

  add_title($title);
  add_space();

  if (!isset($values['own']))
    $values['own'] = 0;
  if (!isset($values['my_status']))
    $values['my_status'] = "not_seen";


  $status_array = array("not_seen"=>"not_seen",
                        "seen"=>"seen",
                        "want_to_see"=>"want_to_see",
                        "not_interested"=>"not_interested");
  add_radio_button("Status", $status_array, "status", $values['my_status']);

  $rating_array = array("0"=>"none", 
                        "1"=>"1", "2"=>"2", "3"=>"3", "4"=>"4", "5"=>"5",
                        "6"=>"6", "7"=>"7", "8"=>"8", "9"=>"9", "10"=>"10");
  if ( (!isset($values)) 
    || ($values['my_rating'] < 0) )
    $values['my_rating'] = 0;
  add_radio_button("Rating", $rating_array, "rating", $values['my_rating']);

  print("<br/>\n");
  add_textarea("Movie Comments <i>(limit 250 characters)</i>...", 
               "comment", $values['my_comment']);

  add_checkbox("Own this movie?", "own", $values['own']);
  print "</table>\n";
}

function display_buttons($movie_id, $full_edit)
{
  if ($full_edit)
    debug("Movie ID: $movie_id");
  $edit_current = false;
  if (isset($movie_id) && $movie_id != NULL)
    $edit_current = true;

  $from_recommendation_page = false;
  if ($_SESSION['tab_status'] == "recommendation")
    $from_recommendation_page = true;

  print ("<p>\n");
  if ($edit_current)
  {
    add_hidden("movie_id", "$movie_id");
    if (!$from_recommendation_page)
      display_submit_button("prev_movie_edit", "Prev");
  }
  display_submit_button("cancel_movie_edit", "Return To List");
  if ($edit_current)
  {
    if (!$from_recommendation_page)
      display_submit_button("next_movie_edit", "Next");
  }
  print ("</p>\n");

  print ("<p>\n");
  if ($full_edit)
  {
    if ($edit_current)
      display_submit_button("save_movie_edit",   "Save Edits");
    else
      display_submit_button("save_new_movie",    "Save New Movie");
  }
  else
    display_submit_button("save_movie_rating",   "Save Edits");

  if ($edit_current)
  {
    if ($_SESSION['edit_mode'] == 'full_edit')
    {
      display_submit_button("add_new_movie",   "Add New Movie");
      display_submit_button("confirm_delete", "Delete Movie $movie_id");
    }
  }
  print ("</p>\n");
}

function display_submit_button($name, $value)
{
  print ("<input type=\"submit\" name=\"$name\" value=\"$value\"/>\n");
}

function str_contains($haystack, $needle)
{
  if ($needle == NULL || $needle == "")
    return 0;

  if (strpos($haystack, $needle) === false)
    return 0;
  else
    return 1;
}

function debug($string)
{
  print("$string<br/>\n");
}

function display_session($heading)
{
  display_array($_SESSION,  "$heading Session:");
}

function display_request($heading)
{
  display_array($_REQUEST, "$heading Request:");
}

function display_array($array, $heading)
{
  if (isset($heading))
    print("<b>$heading</b>\n");
  print_r($array);
  print("<br/>\n");
}


function is_mobile()
{
  global $debug;

  $isMobile = false;

  if (isset($_SERVER['HTTP_X_OPERAMINI_PHONE']))
    $op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
  if (isset($_SERVER['HTTP_USER_AGENT']))
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
  if (isset($_SERVER['HTTP_ACCEPT']))
    $ac = strtolower($_SERVER['HTTP_ACCEPT']);
  if (isset($_SERVER['REMOTE_ADDR']))
    $ip = $_SERVER['REMOTE_ADDR'];

  if ($debug)
    print("USER AGENT: $ua<br/>\n");

  if (!$isMobile && isset($ac))
    $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false;

  if (!$isMobile && isset($op))
    $isMobile = $op != '';

  if (!$isMobile && isset($ua))
  {
    $isMoblie = strpos($ua, 'sony') !== false 
          || strpos($ua, 'symbian') !== false 
          || strpos($ua, 'nokia') !== false 
          || strpos($ua, 'samsung') !== false 
          || strpos($ua, 'mobile') !== false
          || strpos($ua, 'windows ce') !== false
          || strpos($ua, 'epoc') !== false
          || strpos($ua, 'opera mini') !== false
          || strpos($ua, 'nitro') !== false
          || strpos($ua, 'j2me') !== false
          || strpos($ua, 'midp-') !== false
          || strpos($ua, 'cldc-') !== false
          || strpos($ua, 'netfront') !== false
          || strpos($ua, 'mot') !== false
          || strpos($ua, 'up.browser') !== false
          || strpos($ua, 'up.link') !== false
          || strpos($ua, 'audiovox') !== false
          || strpos($ua, 'blackberry') !== false
          || strpos($ua, 'ericsson,') !== false
          || strpos($ua, 'panasonic') !== false
          || strpos($ua, 'philips') !== false
          || strpos($ua, 'sanyo') !== false
          || strpos($ua, 'sharp') !== false
          || strpos($ua, 'sie-') !== false
          || strpos($ua, 'portalmmm') !== false
          || strpos($ua, 'blazer') !== false
          || strpos($ua, 'avantgo') !== false
          || strpos($ua, 'danger') !== false
          || strpos($ua, 'palm') !== false
          || strpos($ua, 'series60') !== false
          || strpos($ua, 'palmsource') !== false
          || strpos($ua, 'pocketpc') !== false
          || strpos($ua, 'smartphone') !== false
          || strpos($ua, 'rover') !== false
          || strpos($ua, 'ipaq') !== false
          || strpos($ua, 'au-mic,') !== false
          || strpos($ua, 'alcatel') !== false
          || strpos($ua, 'ericy') !== false
          || strpos($ua, 'up.link') !== false
          || strpos($ua, 'vodafone/') !== false
          || strpos($ua, 'wap1.') !== false
          || strpos($ua, 'wap2.') !== false;
  }

  return $isMobile;
}
  
function is_bot()
{
  $isBot = false;

  if (isset($_SERVER['HTTP_X_OPERAMINI_PHONE']))
    $op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
  if (isset($_SERVER['HTTP_USER_AGENT']))
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
  if (isset($_SERVER['HTTP_ACCEPT']))
    $ac = strtolower($_SERVER['HTTP_ACCEPT']);
  if (isset($_SERVER['REMOTE_ADDR']))
    $ip = $_SERVER['REMOTE_ADDR'];

  if (isset($ip))
    $isBot =  $ip == '66.249.65.39';

  if (!$isBot && isset($ua))
  {
    $isBot = strpos($ua, 'googlebot') !== false 
          || strpos($ua, 'mediapartners') !== false 
          || strpos($ua, 'yahooysmcm') !== false 
          || strpos($ua, 'baiduspider') !== false
          || strpos($ua, 'msnbot') !== false
          || strpos($ua, 'slurp') !== false
          || strpos($ua, 'ask') !== false
          || strpos($ua, 'teoma') !== false
          || strpos($ua, 'spider') !== false 
          || strpos($ua, 'heritrix') !== false 
          || strpos($ua, 'attentio') !== false 
          || strpos($ua, 'twiceler') !== false 
          || strpos($ua, 'irlbot') !== false 
          || strpos($ua, 'fast crawler') !== false                        
          || strpos($ua, 'fastmobilecrawl') !== false 
          || strpos($ua, 'jumpbot') !== false
          || strpos($ua, 'googlebot-mobile') !== false
          || strpos($ua, 'yahooseeker') !== false
          || strpos($ua, 'motionbot') !== false
          || strpos($ua, 'mediobot') !== false
          || strpos($ua, 'chtml generic') !== false
          || strpos($ua, 'nokia6230i/. fast crawler') !== false;
  }
  return $isBot;
}

function get_facebook_cookie($app_id, $app_secret)
{
  $args = array();
  parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
  ksort($args);
  //display_array($args);
  //print("<br/>\n");
  $payload = '';
  foreach ($args as $key => $value) {
    //print("KEY: $key, VALUE: $value<br/>\n");
    if ($key != 'sig') {
      $payload .= $key . '=' . $value;
    }
  }
  //print("<br/>\n");
  //print("PAYLOAD: $payload<br/>\n");
  //print("<br/>\n");
  $md5_value = md5($payload . $application_secret);
  //print("$md5_value<br/>\n");
  //print("{$args['sig']}<br/>\n");
  if (md5($payload . $application_secret) != $args['sig']) {
    return null;
  }
  return $args;
}

function add_hidden($name, $value)
{
    print("<input type=\"hidden\" name=\"$name\" value=\"$value\"/>\n");
}

function add_date_textbox($title, $key, $value)
{
  if ($value == "")
  {
    $date_string = "";
  }
  else
  {
    $date_integer = strtotime($value);
    $date_string = date('M j, Y', $date_integer);
  }
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td><input type=\"text\" size=\"70\" name=\"$key\" value=\"$date_string\"/></td>\n");
  add_help($key);
  print("</tr>\n");

  // gdg $date = strtotime($date_string);
}

function add_title($title)
{
  print("<tr>\n");
  print("  <th colspan=\"2\" align=\"center\">$title</td>\n");
  print("</tr>\n");
}

function add_textbox($title, $key, $value)
{
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");

  print("  <td>");
  if ($key != "")
    print("<input type=\"text\" size=\"70\" name=\"$key\" value=\"$value\"/>");
  else
    print($value);
  print("  </td>\n");

  add_help($key);
  print("</tr>\n");
}

function add_textarea($title, $key, $value)
{
  add_title($title);
/*
  print("<tr>\n");
  print("  <td colspan=\"2\" align=\"center\">$title</td>\n");
  print("  <td>");
  print("</tr>\n");
*/

  print("<tr>\n");
  print("  <td colspan=\"2\">\n");
  if ($key != "")
  {
    print("<textarea cols=\"60\" rows=\"4\" name=\"$key\">\n");
    print("$value\n");
    print("</textarea>\n");
  }
  else
  {
    print($value);
  }
  print("  </td>\n");
  print("</tr>\n");


  add_help($key);
  print("</tr>\n");
}

function add_textbox_dragdrop($title, $key, $value="", $search_url="")
{
  print("<tr>\n");
  if ($value != "") 
    print("  <td align=\"right\"><a href=\"$value\">$title</a></td>\n");
  elseif ($search_url != "")
    print("  <td align=\"right\"><a href=\"$search_url\">$title</a></td>\n");
  else
    print("  <td align=\"right\">$title</td>\n");
  print("  <td><input type=\"text\" 
                      ondragenter=\"handleDragDropEvent(event)\"
                      ondragover=\"handleDragDropEvent(event)\"
                      ondragleave=\"handleDragDropEvent(event)\"
                      ondrop=\"handleDragDropEvent(event)\"
                      size=\"70\" 
                      name=\"$key\" 
                      value=\"$value\"/></td>\n");
  add_help($key);
  print("</tr>\n");
}

function add_checkboxes($array, $title="")
{
  global $debug;

  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td>\n");
  foreach ($array as $key => $value)
  {
    print("$key");
    $checked = $value ? " checked=\"checked\"" : "";
    print("  <input type=\"checkbox\" name=\"$key\"/$checked>\n");
  }
  print("</td>\n");
  add_help($title);
  print("</tr>\n");
}

function add_raw_dropdown($title, $labels, $name, $value = 0, $autosubmit=false)
{
  $auto = "";
  if ($autosubmit)
    $auto = "onChange='this.form.submit()'";

  print("<select name='$name' $auto>\n");
  foreach ($labels as $key => $label)
  {
    $selected = "";
    if ($key == $value)
     $selected = "selected";

    print("  <option $selected value=\"$key\">$label</option>\n");
  }
  print("</select>\n");
}

function add_checkbox($title, $key, $value = 0)
{
  global $debug;

  $checked = $value ? " checked=\"checked\"" : "";

  print("<tr>\n");
  //print("  <td align=\"right\">$title</td>\n");
  //print("  <td><input type=\"checkbox\" name=\"$key\"/$checked></td>\n");
  print("  <td colspan='2'>$title<input type=\"checkbox\" name=\"$key\"/$checked></td>\n");
  add_help($key);
  print("</tr>\n");
}

function add_password_box($title, $key)
{
  global $width;
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td><input type=\"password\" size=\"60\" name=\"$key\"></td>\n");
  add_help($key);
  print("</tr>\n");
}

function add_menu($title, $labels, $name, $value)
{
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td>\n");
  print("    <select name=\"$name\">\n");

  foreach ($labels as $key => $label)
  {
    $selected = ($value == $label) ? "selected=\"selected\"" : "";
    print("      <option value=\"$key\" $selected>$label</option>\n");
  }
  print("    </select>\n");
  print("  </td>\n");
  add_help($key);
  print("</tr>\n");
}

function add_radio_button($title, $labels, $name, $value)
{
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td>\n");
  foreach ($labels as $key => $label)
  {
    $checked = ($value == $key) ? "checked=\"checked\"" : "";
    print("    $label<input class=\"pollspacer\" type=\"radio\" $checked name=\"$name\" value=\"$key\"/>\n");
  }
  print("  </td>\n");
  add_help($key);
  print("</tr>\n");
}

function add_space()
{
}

function add_help($key)
{
  global $no_help;

  if ($no_help)
    return;

  print("  <td align=\"right\">\n");
  print("    <a href=\"?help=$key\">?</a>\n");
  print("  </td>\n");
}

function add_heading($title, $value)
{
  print("<tr>\n");
  print("  <td align=\"right\">$title</td>\n");
  print("  <td align=\"center\" colspan=\"2\">$value</td>\n");
  print("</tr>\n");
}

function update_sitemap()
{
  global $debug;

  sitemap_begin();
  sitemap_url("http://thegaymovielist.com", "", 0.8, "daily");
  sitemap_url("http://thegaymovielist.com/?tab_status=all", "", 0.9, "daily");
  sitemap_url("http://thegaymovielist.com/?tab_status=recommendation", "", 0.8, "weekly");

  $query = "SELECT movie_id,created FROM movie ORDER BY movie_id";
  if ($debug)
    print("Running Query: $query<br>\n");
  $result = mysql_query($query) or die ("Query Failure: $query");
  while ($row = mysql_fetch_array($result)) 
  {
    $movie_id = $row['movie_id'];
    $created  = $row['created'];
    $url = "http://thegaymovielist.com/?movie_id=$movie_id";

    $date_string = "";
    if ($created != "0000-00-00 00:00:00")
    {
      $date_integer = strtotime($created);
      $date_string = date('Y-m-d', $date_integer);
    }

    sitemap_url($url, $date_string, 0.5, "weekly");
  }

  sitemap_end();
}

function sitemap_begin()
{
   global $sitemapFp;

   $sitemap_file = "sitemap.xml";

   $sitemapFp = fopen($sitemap_file, "w");

   $fp = $sitemapFp;
   if (!$fp) {
      die ("Unable to open $sitemap_file for writing\n");
   }

   fprintf($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
   fprintf($fp, 
     "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");
}

function sitemap_url($url, $lastmod="", $priority=0.5, $changeFreq="")
{
  global $sitemapFp;

  fprintf($sitemapFp, "  <url>\n");
  fprintf($sitemapFp, "    <loc>$url</loc>\n");
  if ($lastmod != "")
    fprintf($sitemapFp, "    <lastmod>$lastmod</lastmod>\n");
  if ($priority != 0.5)
    fprintf($sitemapFp, "    <priority>%0.1f</priority>\n", $priority);
  if ($changeFreq != "")
    fprintf($sitemapFp, "    <changefreq>$changeFreq</changefreq>\n");
  fprintf($sitemapFp, "  </url>\n");

}

function sitemap_end()
{
  global $sitemapFp;

  fprintf($sitemapFp, "</urlset>\n");
  fclose($sitemapFp);
}


?>


