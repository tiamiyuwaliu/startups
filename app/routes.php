<?php
$request->any('/', array('uses' => 'Home@index', 'secure' => false));
$request->any('login', array('uses' => 'Home@login', 'secure' => false));
$request->any('logout', array('uses' => 'Home@logout', 'secure' => false));
$request->any('signup', array('uses' => 'Home@signup', 'secure' => false));
$request->any('forgot', array('uses' => 'Home@forgot', 'secure' => false));
$request->any('change/language', array('uses' => 'Home@changeLanguage', 'secure' => false));


$request->any('facebook/auth', array('uses' => 'Home@facebookAuth', 'secure' => false));
$request->any('twitter/auth', array('uses' => 'Home@twitterAuth', 'secure' => false));
$request->any('google/auth', array('uses' => 'Home@googleAuth', 'secure' => false));

$request->any("dashboard", array('uses' => 'Dashboard@index', 'secure' => true));
$request->any("accounts", array('uses' => 'Account@index', 'secure' => true));
$request->any("profile", array('uses' => 'Profile@index', 'secure' => true));
$request->any("delete/account", array('uses' => 'Profile@delete', 'secure' => true));
$request->any("captions", array('uses' => 'Template@captions', 'secure' => true));
$request->any("load/templates", array('uses' => 'Template@load', 'secure' => true));
$request->any("hashtags", array('uses' => 'Template@hashtag', 'secure' => true));
$request->any("mentions", array('uses' => 'Template@mention', 'secure' => true));
$request->any("files", array('uses' => 'File@index', 'secure' => true));
$request->any("files/load", array('uses' => 'File@load', 'secure' => true));
$request->any("files/load/{id}", array('uses' => 'File@load', 'secure' => true))->where(array('id' => '[0-9]+'));

$request->any("files/{id}", array('uses' => 'File@index', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any("posts", array('uses' => 'Post@index', 'secure' => true));
$request->any("post/location", array('uses' => 'Post@fetchLocation', 'secure' => true));
$request->any("posts/drafts", array('uses' => 'Post@drafts', 'secure' => true));
$request->any("posts/history", array('uses' => 'Post@history', 'secure' => true));
$request->any("posts/bulk", array('uses' => 'Post@bulk', 'secure' => true));

$request->any("admincp", array('uses' => 'Admin@index', 'secure' => true));
$request->any("admincp/users", array('uses' => 'Admin@users', 'secure' => true));
$request->any("admincp/settings", array('uses' => 'Admin@settings', 'secure' => true));
$request->any("admincp/email/setup", array('uses' => 'Admin@emailSetup', 'secure' => true));
$request->any("admincp/email/templates", array('uses' => 'Admin@emailTemplates', 'secure' => true));



$request->any('activate/{code}', array('uses' => 'Home@activate', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
$request->any('reset/{code}', array('uses' => 'Home@reset', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
