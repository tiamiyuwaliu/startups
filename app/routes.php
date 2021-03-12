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
$request->any('api/save/gas', array('uses' => 'Api@saveGas', 'secure' => false));
$request->any('api/load/gas', array('uses' => 'Api@loadGas', 'secure' => false));
$request->any('api/submit/order', array('uses' => 'Api@addOrder', 'secure' => false));
$request->any('api/cancel/order', array('uses' => 'Api@cancelOrder', 'secure' => false));
$request->any('api/accept/order', array('uses' => 'Api@acceptOrder', 'secure' => false));
$request->any('api/mark/order', array('uses' => 'Api@markOrder', 'secure' => false));
$request->any('api/agent/orders', array('uses' => 'Api@agentOrders', 'secure' => false));

$request->any('api/orders', array('uses' => 'Api@orders', 'secure' => false));

$request->any('api/save/fcm', array('uses' => 'Api@saveFcm', 'secure' => false));

$request->any('test/push', array('uses' => 'Api@testPush', 'secure' => false));


$request->any('api/settings', array('uses' => 'Api@settings', 'secure' => false));
$request->any('api/wallet/balance', array('uses' => 'Api@walletBalance', 'secure' => false));
$request->any('api/pay/wallet', array('uses' => 'Api@payWallet', 'secure' => false));
$request->any('api/load/wallet', array('uses' => 'Api@loadWallet', 'secure' => false));
$request->any('api/wallet/history', array('uses' => 'Api@walletHistory', 'secure' => false));


$request->any('api/save/profile', array('uses' => 'Api@saveProfile', 'secure' => false));
$request->any('api/save/device/id', array('uses' => 'Api@saveDeviceId', 'secure' => false));
$request->any('api/send/contact', array('uses' => 'Api@sendContact', 'secure' => false));
