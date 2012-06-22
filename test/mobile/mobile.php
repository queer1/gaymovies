<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
<title>Mobile Browser Test</title>
</head>

<body>
<div id="container">

<?php

  if (is_mobile())
    print("<br/><b>Mobile Browser detected</b><br/>\n");
  else
    print("<br/><b>Standard (non-mobile) Browser detected</b><br/>\n");

?>

</div>
</body>
</html>

<?php

function is_mobile()
{
  $isMobile = false;

  $op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
  $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
  $ac = strtolower($_SERVER['HTTP_ACCEPT']);
  $ip = $_SERVER['REMOTE_ADDR'];

  print("USER AGENT: $ua<br/>\n");

  $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
          || $op != ''
          || strpos($ua, 'sony') !== false 
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

  return $isMobile;
}

?>


