<?php
class HomeController extends  Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setfrontend();
    }

    public function index() {
        if (config('disable-landing', false)) return $this->login();
        return $this->render(view('home/index'));
    }

    public function login() {
        if ($this->model('user')->isLoggedIn()) return $this->request->redirect(url('post'));
        $this->setWrapLayout('auth/layout');
        if ($val = $this->request->input('val')) {
            $email = $val['email'];
            $password = $val['password'];

            if ($login = $this->model('user')->loginUser($email, $password, true)) {
                return json_encode(array(
                    'message' => l('login-successful'),
                    'type' => 'normal-url',
                    'value' => url('dashboard')
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
        if ($this->model('user')->isLoggedIn()) return $this->request->redirect(url('post'));
        $this->setWrapLayout('auth/layout');
        if ($val = $this->request->input('val')) {

            $validator = Validator::getInstance()->scan($val, array(
                'full_name' => 'required',
                'email' => 'required|email|unique:users',
                'timezone' => 'required',
                'password' => 'required'
            ));
            if ($validator->passes()) {
                if (config('enable-captcha', false)) {
                    $secretKey = config('captcha-site-secret-key');
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
                }
                $userid = $this->model('user')->addUser($val);
                if (config('email-verification', false)) {
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'showActivationMessage'
                    ));
                } else {
                    $this->model('user')->loginUser($val['email'], $val['password'], true);
                    return json_encode(array(
                        'message' => l('registration-successful'),
                        'type' => 'normal-url',
                        'value' => url('dashboard')
                    ));
                }
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
                                'value' => url('post')
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
                return $this->request->redirect(url('post'));
            } else {
                $userid = $this->model('user')->addUser($val);
                if (config('email-verification', false)) {
                    return $this->request->redirect(url('signup', array('message' => 'activate')));
                } else {
                    $this->model('user')->loginUser($val['email'], $val['password'], true);
                    return $this->request->redirect(url('post'));
                }
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
                return $this->request->redirect(url('post'));
            } else {
                $userid = $this->model('user')->addUser($val);
                if (config('email-verification', false)) {
                    return $this->request->redirect(url('signup', array('message' => 'activate')));
                } else {
                    $this->model('user')->loginUser($val['email'], $val['password'], true);
                    return $this->request->redirect(url('post'));
                }
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
                return $this->request->redirect(url('post'));
            } else {
                $userid = $this->model('user')->addUser($val);
                if (config('email-verification', false)) {
                    return $this->request->redirect(url('signup', array('message' => 'activate')));
                } else {
                    $this->model('user')->loginUser($val['email'], $val['password'], true);
                    return $this->request->redirect(url('post'));
                }
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
}