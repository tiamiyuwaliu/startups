<?php
class HomeController extends  Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setfrontend();
    }


    public function index() {

        return $this->render(view('home/index'));
    }

    public function refer() {
        $code = $this->request->segment(1);
        setcookie("refer_code", $code, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        return $this->request->redirect(url('signup'));
    }

    public function joinWorkspace() {
        $this->setWrapLayout('auth/layout');
        $this->setTitle("Join Workspace");
        $code = $this->request->segment(2);
        $user = $this->model('workspace')->getUserByCode($code);
        if (!$user) return $this->request->redirect(url());
        $workspace = $this->model('workspace')->find($user['workspace_id']);
        $realUser = model('user')->getUser($user['assign_userid']);
        if ($val = $this->request->input('val')) {
            if (isset($val['password'])) {
                $password = hash_value($val['password']);
                $realUser['password'] = $password;
                Database::getInstance()->query("UPDATE users SET password=? WHERE id=?", $password, $realUser['id']);
            }

            Database::getInstance()->query("UPDATE users SET get_started=? WHERE id=?", 1, $realUser['id']);
            Database::getInstance()->query("UPDATE workspace_users SET user_status=? WHERE id=?", 1, $user['id']);
            session_put('selected-workspace', perfectSerialize($workspace['id']));
            $this->model('user')->loginWithObject($realUser);
            return json_encode(array(
                'type' => 'normal-url',
                'value' => url('home'),
                'message' => 'Thanks for accepting the invitation'
            ));
        }

        return $this->render($this->view('workspace/join', array('workspace' => $workspace,'user' => $user)), true);
    }

    public function share() {

        $code = $this->request->segment(1);
        $template = $this->model('party')->findByKey($code);
        if (!$template) return $this->request->redirect(url('home'));
        $user = $this->model('user')->getUser($template['userid']);
        if (!$this->model('user')->isLoggedIn()) {
            $referral = $this->model('user')->getReferral($user['id']);
            return $this->request->redirect(url('refer/'.$referral['referral_code']));
        }
        $this->model('party')->addShareUser($this->model('user')->authId, $template['id']);
        return $this->request->redirect(url('templates/'.$code));
    }
    public function login() {
        if ($this->model('user')->isLoggedIn()) return $this->request->redirect(url('home'));
        $this->setWrapLayout('auth/layout');
        if ($val = $this->request->input('val')) {
            $email = $val['email'];
            $password = $val['password'];

            if ($login = $this->model('user')->loginUser($email, $password, true)) {
                return json_encode(array(
                    'message' => l('login-successful'),
                    'type' => 'normal-url',
                    'value' => url('home')
                ));
            } else {
                return json_encode(array(
                    'message' => l('invalid-login-details'),
                    'type' => 'error'
                ));
            }
        }

        $this->setTitle(l('login'));
        return $this->render($this->view('auth/login'), true);
    }

    public function forgot() {
        $this->setTitle(l('forgot-password'));
        $this->setWrapLayout('auth/layout');
        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'email' => 'required',
            ));

            if ($validator->passes()) {
                $user = $this->model('user')->findUserByEmail($val['email']);
                if ($user) {
                    $this->model('user')->sendResetLink($user['id'], $user['email'], $user['full_name']);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'showActivationMessage'
                    ));
                } else {
                    return json_encode(array(
                        'message' => l('invalid-email-address'),
                        'type' => 'error',
                    ));
                }
            } else {
                return json_encode(array(
                    'type' => 'error',
                    'message'=> $validator->first()
                ));
            }

        }
        return $this->render($this->view('auth/forgot'), true);
    }

    public function logout() {
        $this->model('user')->logoutUser();
        return $this->request->redirect(url());
    }

    public function signup() {
        if (!config('user-signup', true) or config('disable-landing', false)) return $this->request->redirect(url(''));
        if ($this->model('user')->isLoggedIn()) return $this->request->redirect(url('dashboard'));
        $this->setWrapLayout('auth/layout');
        if ($val = $this->request->input('val')) {

            $validator = Validator::getInstance()->scan($val, array(
                'full_name' => 'required',
                'email' => 'required|email|unique:users',
                'timezone' => 'required',
                'password' => 'required'
            ));
            if ($validator->passes()) {
                $secretKey = '6Le2R34cAAAAABCPDd6A1UqcyH-tgI0fpnCkm2b8';
                $captcha = $this->request->input('g-recaptcha-response');
                if (!$captcha) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('please-check-captcha')
                    ));
                }
                $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
                $response = file_get_contents($url);
                $responseKeys = json_decode($response,true);
                if(!$responseKeys["success"]) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('invalid-captcha-code')
                    ));
                }
                $userid = $this->model('user')->addUser($val);
                $this->model('user')->loginUser($val['email'], $val['password'], true);
                if (isset($_COOKIE['refer_code'])) {
                    $code = $_COOKIE['refer_code'];
                    $referral = $this->model('user')->findReferralByCode($code);
                    Database::getInstance()->query("INSERT INTO referral_users (userid,f_userid)VALUES(?,?)", $referral['userid'], $userid);
                }
                return json_encode(array(
                    'message' => l('registration-successful'),
                    'type' => 'normal-url',
                    'value' => url('home')
                ));
            } else {
                return json_encode(array(
                    'type' => 'error',
                    'message'=> $validator->first()
                ));
            }
        }

        $this->setTitle(l('signup'));
        return $this->render($this->view('auth/register'), true);
    }

    public function activate() {
        $code = $this->request->segment(1);

        $time = mDcrypt($code);
        if($time > time() - 172800) {
            if ($user = $this->model('user')->findByActivationCode($code)) {
                model('user')->activateUser($user);
                $this->model('user')->loginWithObject($user);
                return $this->request->redirect(url('post'));
            }
        }

        return $this->render($this->view('activate/index'));
    }


    public function reset() {
        $code = $this->request->segment(1);

        $time = mDcrypt($code);
        $expired = false;
        if($time > time() - 172800) {
            if ($user = $this->model('user')->findByResetCode($code)) {
                if ($val = $this->request->input('val')) {
                    $validator = Validator::getInstance()->scan($val, array(
                        'confirm' => 'required',
                        'password' => 'required'
                    ));
                    if ($validator->passes()) {
                        if ($val['password'] != $val['confirm']) {
                            return json_encode(array(
                                'type' => 'error',
                                'message'=> l('password-does-not-match')
                            ));
                        } else {
                            $this->model('user')->updatePassword($val['password'], $user['id']);
                            $this->model('user')->loginUser($user['email'], $val['password'], true);
                            return json_encode(array(
                                'message' => l('reset-successful'),
                                'type' => 'normal-url',
                                'value' => url('home')
                            ));
                        }
                    } else {
                        return json_encode(array(
                            'type' => 'error',
                            'message'=> $validator->first()
                        ));
                    }
                }
            }
        } else {
            $expired = true;
        }

        return $this->render($this->view('reset/index', array('expired' => $expired)));
    }

    public function facebookAuth() {

        if ($code = $this->request->input('code')) {

            $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
            $fbApi->setPermissions(array('email'));
            $accessToken = $fbApi->getUserAccessToken(url('facebook/auth'));
            if (!$accessToken) return $this->request->redirect($fbApi->loginUrl(url('facebook/auth')));
            $fbApi->setAccessToken($accessToken);

            $user = $fbApi->getLoginUser('name,id,email');

            $val = array(
                'full_name' => $user->name,
                'email' => $user->email,
                'password' => time().$user->email,
                'timezone' => $this->request->ipInfo->timezone
            );
            if ($user = $this->model('user')->getUser($user->email)) {
                $this->model('user')->loginWithObject($user);
                return $this->request->redirect(url('home'));
            } else {
                $userid = $this->model('user')->addUser($val);
                $this->model('user')->loginUser($val['email'], $val['password'], true);
                return $this->request->redirect(url('home'));
            }
        }

        $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
        return $this->request->redirect($fbApi->loginUrl(url('facebook/auth')));
    }

    public function twitterAuth() {

        if ($verifyIdentifier = $this->request->input('oauth_verifier')) {
            $twitter = $this->api('twitter')->init();
            $accessToken = (object)$twitter->getToken();
            $twitter->setToken(json_encode($accessToken));
            $user = $twitter->userInfo();
            $val = array(
                'full_name' => $user->screen_name,
                'email' => $user->email,
                'password' => time().$user->email,
                'timezone' => $this->request->ipInfo->timezone
            );
            if ($user = $this->model('user')->getUser($user->email)) {
                $this->model('user')->loginWithObject($user);
                return $this->request->redirect(url('home'));
            } else {
                $userid = $this->model('user')->addUser($val);
                $this->model('user')->loginUser($val['email'], $val['password'], true);
                return $this->request->redirect(url('home'));
            }
        }
        return $this->request->redirect($this->api('twitter')->loginUrl('twitter/auth'));
    }

    public function googleAuth() {
        if ($code = $this->request->input('code')) {
            $googleAPI = $this->api('google');
            $googleAPI->setRedirectURI('google/auth');
            $token = $googleAPI->getToken();
            $googleAPI->setToken($token);

            $user = $googleAPI->getCurrentUser();

            list($name,$domain) = explode('@',$user['email']);
            $val = array(
                'full_name' => $name,
                'email' => $user['email'],
                'password' => time().$user['email'],
                'timezone' => $this->request->ipInfo->timezone
            );

            if ($user = $this->model('user')->getUser($user->email)) {
                $this->model('user')->loginWithObject($user);
                return $this->request->redirect(url('home'));
            } else {
                $userid = $this->model('user')->addUser($val);
                $this->model('user')->loginUser($val['email'], $val['password'], true);
                return $this->request->redirect(url('home'));
            }
        }
        $googleApi = $this->api('google');
        $googleApi->setRedirectURI('google/auth');

        return $this->request->redirect($googleApi->loginUrl());
    }

    public function changeLanguage() {
        $id = $this->request->input('id');
        setcookie("language", $id, time() + 90 * 24 * 60 * 60, config('cookie_path'));
        return $this->request->redirectBack();
    }

    public function page() {
        $slug = $this->request->segment(0);
        $page = $this->model('admin')->findPage($slug);

        $this->setfrontend();
        if(!$page) {
            return $this->view('error/404');
        }
        $this->setTitle(l($page['name']));

        return $this->render($this->view('page/index', array('page' => $page)));
    }

    public function blogs() {
        $this->setTitle(l('Blogs'));

        $blogs = $this->model('admin')->getHelps(1,null,null,null, $this->request->input('term'));
        return $this->render($this->view('blogs/index', array('blogs' => $blogs)));
    }

    public function blog() {
        $slug = $this->request->segment(1);
        $blog = $this->model('admin')->findHelp($slug);
        if (!$blog) return $this->request->redirect(url('blogs'));
        $this->setTitle($blog['title']);
        return $this->render($this->view('blogs/page', array('blog' => $blog)));
    }

    public function helps() {
        $this->setTitle(l('Training guides'));
        $category = $this->request->segment(1, null);
        $term = $this->request->input('term');
        $helps = $this->model('admin')->getHelps(0,$category,null,null, $term);
        return $this->render($this->view('helps/index', array('helps' => $helps, 'category' => $category, 'term' => $term)));
    }

    public function help() {
        $slug = $this->request->segment(1);
        $help = $this->model('admin')->findHelp($slug);
        if (!$help) return $this->request->redirect(url('helps'));
        $this->setTitle($help['title']);
        return $this->render($this->view('helps/page', array('help' => $help)));
    }

    public function contact() {
        $this->setTitle('Contact Us');

        if ($val = $this->request->input('val')) {
            return Email::getInstance()->setFrom($val['email'], $val['firstname'].' '.$val['lastname'])
                ->setAddress('team@timably.com', 'Timably')
                ->setSubject('Timably - New Contact Message')
                ->setMessage($val['message'])
                ->send();
        }
        return $this->render($this->view('contact/index'));
    }

    public function privacy() {
        $this->setTitle("Privacy Policy");
        $this->setWrapLayout('auth/layout');
        return $this->render(view('pages/privacy'), true);
    }

    public function expired() {
        $this->setWrapLayout('auth/layout');
        $this->setTitle("Subscription expired");

        return $this->render($this->view('expired/index'), true);

    }
    public function paddleHook() {
        $public_key_string = "-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2s+KCuJG0pLAPOGgocV3
9xgh80oWIb0cRBNVxoj+/Q1w5UDUozrSh32Q1qm8bgX+RhL+QTu2uyMHkVYt31i1
1qYD5RQCMdddodznNStoxRafXCmKm4zDxNMELOc78M52Dq5ofaqyH4G5qqveTcRB
24E8QYTRQKfdRTq4IJwbVq0zDo4MUm2t1cS69EbTuLJT4O9S5XGSPUiFevXf+jVm
1MR0AfTSzafJmP1G8rAfZAZz3TG+kmllKC1KOa80BbsO2wOyeOGtnW8RnnniD2XI
k7MHGfbxx42ocH++cyvpwn+f3xi5chv7i6E7IJ/dN1WwRgoMBoOJgIwXX1yaEDoV
rBC0Ii2uUjy/Dw3pIRQf/C/0qUBlmCfYHzPTzlKZBhxjc7VXYhM9BTJjlBfZqk3M
dTTp1Vfsd2l2ezoWuPOSzEgshi87c4FVqQuBaMtQ6dpwFUQpUaDapUDG3z24vFxg
71YHb10VNFlt3q80uJPIFTwcIBV7GIfmjCgTNDDRrE5aOWVYJXaFbQ4rodnaNOCk
4XJVtr9XEKekk8tDK3BrjIIrGI3H2NyROpBxXX39kWXfd3z0KgKJAgAJLJXRxKBY
yCbqwhy4xlzaM8zduNZJ2qSm3u3b7Z6QOuiH/HmMbSmIcbgpogcoXnoIGXMrQjbK
ha7F7sro1kMJrTszYZRn4vMCAwEAAQ==
-----END PUBLIC KEY-----";
        $public_key = openssl_get_publickey($public_key_string);


        // Get the p_signature parameter & base64 decode it.
        $signature = base64_decode($_POST['p_signature']);

        // Get the fields sent in the request, and remove the p_signature parameter
        $fields = $_POST;
        unset($fields['p_signature']);

        // ksort() and serialize the fields
        ksort($fields);
        foreach($fields as $k => $v) {
            if(!in_array(gettype($v), array('object', 'array'))) {
                $fields[$k] = "$v";
            }
        }
        $data = serialize($fields);

        // Verify the signature
        $verification = openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA1);


        if($verification == 1) {
            //echo 'Yay! Signature is valid!';

            if ($fields['alert_name'] == 'subscription_created') {

                list($duration,$userid,$type) = explode('-', $fields['passthrough']);
                $ref = $fields['subscription_id'];

                $user = $this->model('user')->getUser($userid);
                if ($user['payment_ref']) {
                    //we need cancel the old subscription
                }
                $expireDate = ($duration == 'monthly') ? 86400 * 30 : 86400 * 365;
                $expireDate = time() + $expireDate;
                $amount = $fields['unit_price'];
                $this->db->query("UPDATE users SET package=?,expire_date=?,payment_ref=?,payment_cycle=?,payment_amount=? WHERE id=?", $type, $expireDate,$ref,$duration,$amount, $userid);

            }elseif ($fields['alert_name'] == 'subscription_payment_succeeded') {

                list($duration,$userid,$type) = explode('-', $fields['passthrough']);
                $ref = $fields['subscription_id'];
                $user = $this->model('user')->getUser($userid);
                $expireDate = ($duration == 'monthly') ? 86400 * 30 : 86400 * 365;
                $expireDate = time() + $expireDate;
                $amount = $fields['earnings'];
                $this->db->query("UPDATE users SET package=?,expire_date=?,payment_ref=?,payment_cycle=?,payment_amount=? WHERE id=?", $type, $expireDate,$ref,$duration,$amount, $userid);

                $this->model('admin')->addTransaction(array(
                    'amount' =>  $amount,
                    'email' => $fields['email'],
                    'period' => date('m/d/Y').' - '.date('m/d/Y', $expireDate),
                    'sale_id' => $fields['order_id'],
                    'name' => $user['full_name'],
                    'userid' => $userid,
                    'type' => $fields['payment_method']
                ));

            }
            http_response_code(200);
        } else {
            //echo 'The signature is invalid!';
            http_response_code(404);
        }
    }
}