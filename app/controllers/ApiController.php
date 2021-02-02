<?php
class ApiController  extends Controller {
    private $tokenValid  = false;
    private $userid = null;
    public function __construct($request)
    {
        parent::__construct($request);
        //check the keys
            $this->apiKey = config('api-key-token', 'DEFAULTAPIKEY');
        $receivedAPIKey = $this->request->input('key');
        $token = $this->request->input('token');

        if ($this->apiKey != $receivedAPIKey) exit(json_encode(array('status' => 0)));
        if ($token) {
            $mobileKey = $this->model('api')->findKey($token);
            if ($mobileKey)  {
                $this->tokenValid = true;
                $this->userid = $mobileKey['userid'];
                $user = $this->model('user')->getUser($this->userid);
                $this->model('user')->loginWithObject($user);
            }
        }
    }

    public function login() {
        $email = $this->request->input('username');
        $password = $this->request->input('password');
        $device = $this->request->input('device');

        if($login = $this->model('user')->loginUser($email, $password)) {
            $token = $this->model('api')->addToken($this->model('user')->authId, $device);
            $user = $this->model('user')->authUser;
            $user['avatar'] = str_replace('%w', 200, $user['avatar']);
            return json_encode(array(
                'status' => 1,
                'token' => $token,
                'userid' => $this->model('user')->authId,
                'user' => $user
            ));
        } else {
            return json_encode(array('status' => 0));
        }
    }

    public function signup() {
        $email = $this->request->input('username');
        $password = $this->request->input('password');
        $device = $this->request->input('device');
        $fullname = $this->request->input('name');
        $timezone = $this->request->input('timezone');

        $userid = $this->model('user')->addUser(array(
            'email' => $email,
            'password' => $password,
            'full_name' => $fullname,
            'timezone' => $timezone
        ),false,false, true);
        $user = $this->model('user')->getUser($userid);
        $this->model('user')->loginWithObject($user);
        $token = $this->model('api')->addToken($this->model('user')->authId, $device);
        $user = $this->model('user')->authUser;
        $user['avatar'] = str_replace('%w', 200, $user['avatar']);
        return json_encode(array(
            'status' => 1,
            'token' => $token,
            'userid' => $this->model('user')->authId,
            'user' => $user
        ));
    }

    private function tokenRequired() {
        if (!$this->tokenValid) {
            exit(json_encode(array('status' => 0)));
        }
    }

    public function dashboard() {
        $this->tokenRequired();
    }
}