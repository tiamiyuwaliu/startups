<?php
    $favicon = 'assets/favicon.png';
/**
 * Parse OPENSSL_VERSION_NUMBER constant to
 * use in version_compare function
 * @param  boolean $patch_as_number        [description]
 * @param  [type]  $openssl_version_number [description]
 * @return [type]                          [description]
 */
function get_openssl_version_number($openssl_version_number=null, $patch_as_number=false) {
    if (is_null($openssl_version_number)) $openssl_version_number = OPENSSL_VERSION_NUMBER;
    $openssl_numeric_identifier = str_pad((string)dechex($openssl_version_number),8,'0',STR_PAD_LEFT);

    $openssl_version_parsed = array();
    $preg = '/(?<major>[[:xdigit:]])(?<minor>[[:xdigit:]][[:xdigit:]])(?<fix>[[:xdigit:]][[:xdigit:]])';
    $preg.= '(?<patch>[[:xdigit:]][[:xdigit:]])(?<type>[[:xdigit:]])/';
    preg_match_all($preg, $openssl_numeric_identifier, $openssl_version_parsed);
    $openssl_version = false;
    if (!empty($openssl_version_parsed)) {
        $alphabet = array(1=>'a',2=>'b',3=>'c',4=>'d',5=>'e',6=>'f',7=>'g',8=>'h',9=>'i',10=>'j',11=>'k',
            12=>'l',13=>'m',14=>'n',15=>'o',16=>'p',17=>'q',18=>'r',19=>'s',20=>'t',21=>'u',
            22=>'v',23=>'w',24=>'x',25=>'y',26=>'z');
        $openssl_version = intval($openssl_version_parsed['major'][0]).'.';
        $openssl_version.= intval($openssl_version_parsed['minor'][0]).'.';
        $openssl_version.= intval($openssl_version_parsed['fix'][0]);
        $patchlevel_dec = hexdec($openssl_version_parsed['patch'][0]);
        if (!$patch_as_number && array_key_exists($patchlevel_dec, $alphabet)) {
            $openssl_version.= $alphabet[$patchlevel_dec]; // ideal for text comparison
        }
        else {
            $openssl_version.= '.'.$patchlevel_dec; // ideal for version_compare
        }
    }
    return $openssl_version;
}

function getTimezones()
{
    $timezoneIdentifiers = DateTimeZone::listIdentifiers();
    $utcTime = new DateTime('now', new DateTimeZone('UTC'));

    $tempTimezones = array();
    foreach ($timezoneIdentifiers as $timezoneIdentifier) {
        $currentTimezone = new DateTimeZone($timezoneIdentifier);

        $tempTimezones[] = array(
            'offset' => (int)$currentTimezone->getOffset($utcTime),
            'identifier' => $timezoneIdentifier
        );
    }

    // Sort the array by offset,identifier ascending
    usort($tempTimezones, function($a, $b) {
        return ($a['offset'] == $b['offset'])
            ? strcmp($a['identifier'], $b['identifier'])
            : $a['offset'] - $b['offset'];
    });

    $timezoneList = array();
    foreach ($tempTimezones as $tz) {
        $sign = ($tz['offset'] > 0) ? '+' : '-';
        $offset = gmdate('H:i', abs($tz['offset']));
        $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' .
            $tz['identifier'];
    }

    return $timezoneList;
}
function curlGet($url, $javascript_loop = 0, $timeout = 100)
{
    $url = str_replace("&amp;", "&", urldecode(trim($url)));

    $cookie = tempnam("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    //curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $content = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);

    return $content;
}
function licenseIsValid($key) {
    $domain = getHost();
    $url = "https://wesmartpost.com/validate/code?domain=".$domain.'&code='.$key;
    $result = json_decode(curlGet($url), true);
    if ($result['status'] == 1) return true;
    return false;
}
function input($name, $default = false) {
    $post = $_POST;
    if (isset($post[$name])) return $post[$name];
    return $default;
}
$completed = false;

?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $favicon?>">
    <link rel="shortcut icon" href="<?php echo $favicon?>">

    <link rel="apple-touch-icon-precomposed" href="<?php echo $favicon?>">
    <link rel="icon" href="<?php echo $favicon?>">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/style.css" />
    <title>SmartPost Installation</title>
</head>
<body>

<div class="container">
    <div class="logo" >
        <img src="assets/logo.png"/>

    </div>

    <div class="step step-one" style="display:<?php echo input('license') ? 'none !important' : 'block';?>">
        <h3>SmartPost Installtion</h3>
        <p>
            Thanks for your purchase of our software , kindly click the button to start the installation process for smartpost
        </p>

        <p style="font-weight: bolder;">Installation process is very easy and it takes less than 2 minutes!</p>

        <button class="btn btn-primary mt-4" onclick="return goTo('step-two')">Start Installation</button>
    </div>
    <div class="step step-two" >
        <div style="text-align: center">
            <h3>Agreement</h3>
            <p>
                To use our script you need to agree with our terms and conditions written below
            </p>
        </div>

        <div class="terms pane">
            <p>
                BY DOWNLOADING, INSTALLING, COPYING, ACCESSING OR USING THIS WEB APPLICATION,
                YOU AGREE TO THE TERMS OF THIS END USER LICENSE AGREEMENT. IF YOU ARE ACCEPTING
                THESE TERMS ON BEHALF OF ANOTHER PERSON OR COMPANY OR OTHER LEGAL ENTITY, YOU REPRESENT
                AND WARRANT THAT YOU HAVE FULL AUTHORITY TO BIND THAT PERSON, COMPANY OR LEGAL ENTITY
                TO THESETERMS.
            </p>

            <p>
                We’re not using the official Instagram API which is available on Instagram Developer Center
                as it’s very limited. So we are using a different API. On the backend, the script behaves like

                the official Android app of the Instagram. We have taken all security measures to reduce the ban rate.
                If you don't publish spammy posts or don't try to send massive amount of the requests to the Instagram
                from the same account, there shouldn't be any problem.
                For all other social network we used the official API so there shouldn't be with them
            </p>

            <p>
                AS IT’S VERY CLEAR, SCRIPT DEPENDS ON INSTAGRAM. WE’RE NOT RESPONSIBLE IF INSTAGRAM MADE CRITICAL CHANGES
                IN THEIR SIDE. ALTHOUGH WE ALWAYS TRY TO MAKE THE SCRIPT UP TO DATE, SOMETIMES IT MIGHT NOT BE POSSIBLE TO
                FIND A PROPER WORKAROUND. WE DON’T GUARANTEE THAT THE COMPATIBILITY OF THE SCRIPT WITH INSTAGRAM API WILL
                BE FOREVER, USE AT YOUR OWN RISK. WE DON’T PROVIDE ANY REFUND FOR PROBLEMS THAT ARE ORIGINATED FROM INSTAGRAM
            </p>
        </div>

        <div style="padding: 30px 0;text-align: center">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="termsaccess">
                <label class="custom-control-label" for="termsaccess">I read and accept terms </label>
            </div>

            <button class="btn btn-primary mt-4" onclick="return goTo('step-three')">Continue</button>
        </div>
    </div>
    <div class="step step-three">
        <div style="text-align: center">
            <h3>Requirements</h3>
        </div>

        <?php
            $canContinue= true;
        ?>

        <div class="pane">
            <h6>Please configure PHP to match following requirements / settings:</h6>
            <table class="table">
                <thead class="thead-dark">
                <tr>
                    <th scope="col">PHP Settings</th>
                    <th scope="col">Required</th>
                    <th scope="col">Current</th>
                    <th scope="col">Check</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th scope="row">PHP Version</th>
                    <td>5.6+</td>
                    <td><?php echo PHP_VERSION?></td>
                    <td>
                        <?php if(version_compare(PHP_VERSION, '5.6') >= 0):?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">allow_url_fopen</th>
                    <td>On</td>
                    <td><?php echo (ini_get('allow_url_fopen')) ? 'ON' : 'OFF'?></td>
                    <td>
                        <?php if(ini_get('allow_url_fopen')):?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">cURL</th>
                    <?php
                        $curl = function_exists('curl_version') ? curl_version() : false;
                    ?>
                    <td>7.19.4+</td>
                    <td><?php echo ($curl) ? $curl['version'] : 'Not Installed'?></td>
                    <td>
                        <?php if($curl and version_compare($curl['version'], '7.19.4') >= 0):?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif?>
                    </td>
                </tr>

                <tr>
                    <?php
                    $openssl = extension_loaded('openssl');
                    if ($openssl && !empty(OPENSSL_VERSION_NUMBER)) {
                        $installed_openssl_version = get_openssl_version_number(OPENSSL_VERSION_NUMBER);
                    }
                    ?>
                    <td><span class="fw-700">OpenSSL</span></td>
                    <td>1.0.1c+</td>
                    <td><?= !empty($installed_openssl_version) ? $installed_openssl_version : "Outdated or not installed"; ?></td>
                    <td class="status">
                        <?php if (!empty($installed_openssl_version) && $installed_openssl_version >= "1.0.1c"): ?>
                            <span class="badge badge-success">Good</span>
                        <?php else:  $canContinue = false; ?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif ?>
                    </td>
                </tr>

                <tr>
                    <?php $pdo = defined('PDO::ATTR_DRIVER_NAME'); ?>
                    <td><span class="fw-700">PDO</span></td>
                    <td>On</td>
                    <td><?= $pdo ? "On" : "Off"; ?></td>
                    <td class="status">
                        <?php if ($pdo): ?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif ?>
                    </td>
                </tr>
                <tr>
                    <?php $gd = extension_loaded('gd') && function_exists('gd_info') ?>
                    <td><span class="fw-700">GD</span></td>
                    <td>On</td>
                    <td><?= $gd ? "On" : "Off"; ?></td>
                    <td class="status">
                        <?php if ($gd): ?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif ?>
                    </td>
                </tr>
                <tr>
                    <?php $mbstring = extension_loaded('mbstring') && function_exists('mb_get_info') ?>
                    <td><span class="fw-700">mbstring</span></td>
                    <td>On</td>
                    <td><?= $mbstring ? "On" : "Off"; ?></td>
                    <td class="status">
                        <?php if ($mbstring): ?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif ?>
                    </td>
                </tr>
                <tr>
                    <?php $exif = function_exists('exif_read_data') ?>
                    <td><span class="fw-700">EXIF</span></td>
                    <td>On</td>
                    <td><?= $exif ? "On" : "Off"; ?></td>
                    <td class="status">
                        <?php if ($exif): ?>
                            <span class="badge badge-success">Good</span>
                        <?php else: $canContinue = false;?>
                            <span class="badge badge-danger">Bad</span>
                        <?php endif ?>
                    </td>
                </tr>

                </tbody>
            </table>
            <h6>Please make sure the following files and folder are writable:</h6>
            <table class="table">
                <thead class="thead-dark">
                <tr>
                    <th scope="col">Name / Path</th>
                    <th scope="col">Check</th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>/config.php</td>
                        <td>
                            <?php if (is_writable('../config.php')): ?>
                                <span class="badge badge-success">Good</span>
                            <?php else: $canContinue = false;?>
                                <span class="badge badge-danger">Bad</span>
                            <?php endif ?>
                        </td>
                    </tr>

                    <tr>
                        <td>/uploads/</td>
                        <td>
                            <?php if (is_writable('../uploads/')): ?>
                                <span class="badge badge-success">Good</span>
                            <?php else: $canContinue = false;?>
                                <span class="badge badge-danger">Bad</span>
                            <?php endif ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if($canContinue):?>
                <button class="btn btn-primary mt-4" onclick="return goTo('step-four')">Continue</button>
            <?php else:?>
                <button class="btn btn-primary mt-4" disabled="disabled">Continue</button>
            <?php endif?>
        </div>
    </div>
    <?php

    function server($name, $default = null)
    {
        if (isset($_SERVER[$name])) return $_SERVER[$name];
        return $default;
    }
    function getHost()
    {
        $request = $_SERVER;
        $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];

        //remove unwanted characters
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        //prevent Dos attack
        if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
            die();
        }

        return $host;
    }
    function isSecure()
    {
        return $isSecure = (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == "on") ? true : false;
    }

    function getScheme()
    {
        return (isSecure()) ? 'https' : 'http';
    }
    function getRoot()
    {
        $base = getBase();

        $url = getScheme() . '://' . getHost() . $base;
        $url = str_replace('install/', '', $url);
        return $url;
    }

    function getBase()
    {
        $filename = basename(server('SCRIPT_FILENAME'));
        if (basename(server('SCRIPT_NAME')) == $filename) {
            $baseUrl = server('SCRIPT_NAME');
        } elseif (basename(server('PHP_SELF')) == $filename) {
            $baseUrl = server('PHP_SELF');
        } elseif (basename(server('ORIG_SCRIPT_NAME')) == $filename) {
            $baseUrl = server('ORIG_SCRIPT_NAME');
        } else {
            $baseUrl = server('SCRIPT_NAME');
        }

        $baseUrl = str_replace('index.php', '', $baseUrl);

        return $baseUrl;
    }


        $baseUrl =  getRoot();


        $message = '';
        if ($licenseKey = input('license')){
            $driver = 'mysql';
            $host = input('host');
            $dbName = input('name');
            $username = input('username');
            $password = input('password');
            $fullName = input('full_name');
            $email = input('email');
            $userPassword = input('user_password');
            $timezone = input('timezone');
            $siteName = input('site');
            if (licenseIsValid($licenseKey)) {
                try {
                    $db = new \PDO("{$driver}:host={$host};dbname={$dbName}", $username, $password);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $configContent = file_get_contents('../config-holder.php');

                    $configContent = str_replace('{host}', $host, $configContent);
                    $configContent = str_replace('{username}', $username, $configContent);
                    $configContent = str_replace('{name}', $dbName, $configContent);
                    $configContent = str_replace('{password}', $password, $configContent);
                    $configContent = str_replace('{purchasecode}', $licenseKey, $configContent);
                    $configContent = str_replace('{sitename}', $siteName, $configContent);
                    $configContent = str_replace('{secure}', (isSecure()) ? 1 : 0, $configContent);
                    file_put_contents('../config.php', $configContent);
                    $sql = file_get_contents('database.sql');
                    if ($sql) $db->query($sql);

                    $db = new \PDO("{$driver}:host={$host};dbname={$dbName}", $username, $password);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $userPassword = md5($userPassword);
                    $time = time();
                    $res = $db->prepare("INSERT INTO users (full_name,password,email,role,timezone,changed,created)VALUES(?,?,?,?,?,?,?)");
                    $res->execute(array($fullName,$userPassword,$email,1,$timezone, $time,$time));

                    $completed = true;
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                }
            } else {
                $message = "Invalid license key";
            }
        }

    ?>
    <div class="step step-four"  style="display:<?php echo input('license') and !$completed ? 'block' : 'none';?>">
        <div class="pane">
            <?php if($message):?>
                <div class="alert alert-warning"><strong>ERROR:</strong> <?php echo $message?></div>
            <?php endif?>
            <form action="" method="post">
                <h6>License</h6>
                <hr/>

                <div class="row">
                    <div class="col-md-6">
                        <label>Purchase Code</label>
                        <div class="text-muted">Please enter your code code <a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">Read here for how to get it</a> </div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" required name="license" value="<?php echo input('license')?>" placeholder="Enter Purchase code"/>
                    </div>
                </div>


                <h6 class="mt-3">Database connection details</h6>
                <hr/>
                <div class="row">
                    <div class="col-md-6">
                        <label>Database Host</label>
                        <div class="text-muted">You should be able to get this info from your web host, if localhost doesn't work</div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="host" required placeholder="localhost" value="<?php echo input('host', 'localhost')?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label>Database Username</label>
                        <div class="text-muted">Your MySQL username</div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="username" required placeholder="Db Username" value="<?php echo input('username')?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label>Database Password</label>
                        <div class="text-muted">Your MySQL password</div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="password" placeholder="Db Password" value="<?php echo input('password')?>"/>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label>Database Name</label>
                        <div class="text-muted">The name of the database you want to install SmartPost in</div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="name" required placeholder="Db Name" value="<?php echo input('name')?>"/>
                    </div>
                </div>

                <h6 class="mt-3">Site Name</h6>
                <hr/>
                <div class="">
                    <input type="text" class="form-control" name="site" required placeholder="Site Name" value="<?php echo input('site', 'SmartPost')?>"/>
                </div>

                <h6 class="mt-3">Administrative account details</h6>
                <hr/>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="full_name" required placeholder="Full name" value="<?php echo input('full_name')?>"/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email address</label>
                            <input type="text" class="form-control" name="email" required placeholder="Email address" value="<?php echo input('email')?>"/>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" class="form-control" name="user_password" required placeholder="Password" value="<?php echo input('user_password')?>"/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Timezone</label>
                            <select class="form-control select-timezone" name="timezone">
                                <option value="">Select timezone</option>
                                <?php foreach(getTimezones() as $key => $name):?>
                                    <option <?php echo (input('timezone') == $key) ? 'selected' : null?> value="<?php echo $key?>"><?php echo $name?></option>
                                <?php endforeach?>
                            </select>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary mt-4" type="submit">Finish Installation</button>
            </form>
        </div>


    </div>
    <?php if($completed):?>
        <div class="pane">
            <div class="alert alert-success">Installation successful you can visit your website by clicking the button below, delete the install/ folder</div>

            <a href="<?php echo getRoot()?>" class="btn btn-primary">Visit Website</a>
        </div>
    <?php endif?>
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="assets/script.js"></script>
<script>
    function goTo(c) {
        if (c === 'step-three') {
            if ($('#termsaccess').prop('checked') != true) {
                return false;
            }
        }
        $('.step').hide();

        $('.'+c).fadeIn();
        return false;
    }

    $(function() {
        var settings = {
            "async": true,
            "crossDomain": true,
            "url": "https://api.ip.sb/geoip",
            "dataType": "jsonp",
            "method": "GET",
            "headers": {
                "Access-Control-Allow-Origin": "*"
            }
        }

        if ($(".select-timezone").length > 0) {
            $.ajax(settings).done(function (response) {
                timezone = response.timezone;
                var selected = $(".select-timezone").val();
                if (selected == '') {
                    $(".select-timezone").val(timezone);
                }
            });
        }

    })
</script>
</body>
</html>