<?php
$request->any('/', array('uses' => 'Home@index', 'secure' => false));
$request->any('login', array('uses' => 'Home@login', 'secure' => false));
$request->any('logout', array('uses' => 'Home@logout', 'secure' => false));
$request->any('signup', array('uses' => 'Home@signup', 'secure' => false));
$request->any('forgot', array('uses' => 'Home@forgot', 'secure' => false));
$request->any('change/language', array('uses' => 'Home@changeLanguage', 'secure' => false));


$request->any("dashboard", array('uses' => 'Dashboard@index', 'secure' => true));
$request->any("admincp", array('uses' => 'Admin@index', 'secure' => true));
$request->any("admincp/users", array('uses' => 'Admin@users', 'secure' => true));
$request->any("admincp/settings", array('uses' => 'Admin@settings', 'secure' => true));
$request->any("admincp/email/setup", array('uses' => 'Admin@emailSetup', 'secure' => true));
$request->any("admincp/email/templates", array('uses' => 'Admin@emailTemplates', 'secure' => true));

$request->any('api/login', array('uses' => 'Api@login', 'secure' => false));
$request->any('api/signup', array('uses' => 'Api@signup', 'secure' => false));
$request->any('api/dashboard', array('uses' => 'Api@dashboard', 'secure' => false));
