<?php
$request->any('/', array('uses' => 'Home@index', 'secure' => false));
$request->any('blogs', array('uses' => 'Home@blogs', 'secure' => false));
$request->any('blog/{slug}', array('uses' => 'Home@blog', 'secure' => false))->where(array('slug' => '[0-9a-zA-Z\-\_]+'));
$request->any('contact-us', array('uses' => 'Home@contact', 'secure' => false));
$request->any('helps', array('uses' => 'Home@helps', 'secure' => false));
$request->any("help/load", array('uses' => 'Dashboard@loadHelp', 'secure' => true));
$request->any('helps/{slug}', array('uses' => 'Home@helps', 'secure' => false))->where(array('slug' => '[0-9a-zA-Z\-\_]+'));
$request->any('help/{slug}', array('uses' => 'Home@help', 'secure' => false))->where(array('slug' => '[0-9a-zA-Z\-\_]+'));
$request->any('save/graphics', array('uses' => 'File@saveGraphics', 'secure' => false));
$request->any('early', array('uses' => 'Home@early', 'secure' => false));
$request->any('login', array('uses' => 'Home@login', 'secure' => false));
$request->any('logout', array('uses' => 'Home@logout', 'secure' => false));
$request->any('signup', array('uses' => 'Home@signup', 'secure' => false));
$request->any('privacy', array('uses' => 'Home@privacy', 'secure' => false));
$request->any('terms', array('uses' => 'Home@terms', 'secure' => false));
$request->any('forgot', array('uses' => 'Home@forgot', 'secure' => false));
$request->any("refer/{id}", array('uses' => 'Home@refer', 'secure' => false))->where(array('id' => '[0-9a-zA-Z\-\_]+'));
$request->any("join/workspace/{id}", array('uses' => 'Home@joinWorkspace', 'secure' => false))->where(array('id' => '[0-9a-zA-Z\-\_]+'));
$request->any("share/{id}", array('uses' => 'Home@share', 'secure' => false))->where(array('id' => '[0-9a-zA-Z\-\_]+'));

$request->any('change/language', array('uses' => 'Home@changeLanguage', 'secure' => false));

$request->any('cron/posts', array('uses' => 'Cron@run', 'secure' => false));
$request->any('cron/parties', array('uses' => 'Cron@parties', 'secure' => false));

$request->any('facebook/auth', array('uses' => 'Home@facebookAuth', 'secure' => false));
$request->any('twitter/auth', array('uses' => 'Home@twitterAuth', 'secure' => false));
$request->any('google/auth', array('uses' => 'Home@googleAuth', 'secure' => false));

$request->any('paddle/hook', array('uses' => 'Home@paddleHook', 'secure' => false));
$request->any('expired', array('uses' => 'Home@expired', 'secure' => false));

$request->any("home", array('uses' => 'Dashboard@index', 'secure' => true));
$request->any("search", array('uses' => 'Dashboard@search', 'secure' => true));
$request->any("notifications", array('uses' => 'Dashboard@notifications', 'secure' => true));
$request->any("getstarted", array('uses' => 'Dashboard@getstarted', 'secure' => true));


$request->any("accounts", array('uses' => 'Account@index', 'secure' => true));
$request->any("accounts/facebook", array('uses' => 'Account@facebook', 'secure' => true));
$request->any("accounts/twitter", array('uses' => 'Account@twitter', 'secure' => true));
$request->any("accounts/instagram", array('uses' => 'Account@index', 'secure' => true));
$request->any("accounts/linkedin", array('uses' => 'Account@linkedin', 'secure' => true));

$request->any("profile", array('uses' => 'Profile@index', 'secure' => true));
$request->any("profile/refer", array('uses' => 'Profile@index', 'secure' => true));
$request->any("profile/billing", array('uses' => 'Profile@index', 'secure' => true));
$request->any("delete/account", array('uses' => 'Profile@delete', 'secure' => true));
$request->any("captions", array('uses' => 'Template@captions', 'secure' => true));
$request->any("load/templates", array('uses' => 'Template@load', 'secure' => true));
$request->any("hashtags", array('uses' => 'Template@hashtag', 'secure' => true));
$request->any("party/templates", array('uses' => 'Template@templates', 'secure' => true));
$request->any("templates/{id}", array('uses' => 'Template@templatesPage', 'secure' => true))->where(array('id' => '[0-9a-zA-Z\-\_]+'));

$request->any("files", array('uses' => 'File@index', 'secure' => true));
$request->any("files/design", array('uses' => 'File@index', 'secure' => true));
$request->any("graphics", array('uses' => 'File@graphics', 'secure' => true));
$request->any("graphics/{id}", array('uses' => 'File@graphics', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any("graphics/{id}/{cat}", array('uses' => 'File@graphics', 'secure' => true))->where(array('id' => '[0-9]+','cat' => '[0-9]+'));
$request->any("files/load", array('uses' => 'File@load', 'secure' => true));
$request->any("files/open", array('uses' => 'File@open', 'secure' => true));

$request->any("files/load/{id}", array('uses' => 'File@load', 'secure' => true))->where(array('id' => '[0-9]+'));

$request->any("files/{id}", array('uses' => 'File@index', 'secure' => true))->where(array('id' => '[0-9]+'));
$request->any("publishing", array('uses' => 'Publishing@index', 'secure' => true));
$request->any("publishing/settings", array('uses' => 'Publishing@settings', 'secure' => true));
$request->any("publishing/posts", array('uses' => 'Publishing@posts', 'secure' => true));
$request->any("publishing/parties", array('uses' => 'Publishing@parties', 'secure' => true));
$request->any("publishing/parties/templates", array('uses' => 'Template@templates', 'secure' => true));
$request->any("publishing/parties/shared", array('uses' => 'Template@templates', 'secure' => true));
$request->any("publishing/party/{id}", array('uses' => 'Publishing@party', 'secure' => true))->where(array('id' => '[0-9a-zA-Z\-\_]+'));
$request->any("publishing/history/scheduled", array('uses' => 'Publishing@posts', 'secure' => true));
$request->any("publishing/history/published", array('uses' => 'Publishing@posts', 'secure' => true));
$request->any("publishing/history/failed", array('uses' => 'Publishing@posts', 'secure' => true));
$request->any("publishing/calendar/data", array('uses' => 'Publishing@calendarData', 'secure' => true));

$request->any("workspace", array('uses' => 'Workspace@index', 'secure' => true));

$request->any("admincp", array('uses' => 'Admin@index', 'secure' => true));
$request->any("admincp/graphics", array('uses' => 'Admin@graphics', 'secure' => true));
$request->any("admincp/users", array('uses' => 'Admin@users', 'secure' => true));
$request->any("admincp/settings", array('uses' => 'Admin@settings', 'secure' => true));
$request->any("admincp/helps", array('uses' => 'Admin@helps', 'secure' => true));
$request->any("admincp/email/setup", array('uses' => 'Admin@emailSetup', 'secure' => true));
$request->any("admincp/email/templates", array('uses' => 'Admin@emailTemplates', 'secure' => true));



$request->any('activate/{code}', array('uses' => 'Home@activate', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
$request->any('reset/{code}', array('uses' => 'Home@reset', 'secure' => false))->where(array('code' => '[a-zA-Z0-9]+'));
