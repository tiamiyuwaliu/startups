<?php
autoLoadVendor();
require_once path('app/vendor/Google/autoload.php');
class GoogleAPI extends API {
    public $client;
    private $business;
    private $redirectURI = 'accounts/google';

    public function __construct(){

        $this->client = new Google_Client();
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt("force");
        $this->client->setApplicationName('YouTube Tools');
        $this->client->setRedirectUri(url($this->redirectURI));
        $this->client->setClientId('648589569312-vgthib7pb7t0qm7kv18ctl47k38onc9u.apps.googleusercontent.com');
        $this->client->setClientSecret('eIcg5eDLKSefwRDctkLIEmkb');
        $this->client->setDeveloperKey('AIzaSyDMcM0oLJYcAURKF2V41mA_MGuXfGy-sT8');
        $this->client->setScopes(array( 'https://www.googleapis.com/auth/userinfo.email'));


    }

    public function setClientSecret($clientId, $secret) {
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($secret);
        return $this;
    }

    public function setRedirectURI($url) {
        $this->redirectURI = $url;
        $this->client->setRedirectUri(url($this->redirectURI));
    }

    function loginUrl(){
        return $this->client->createAuthUrl();
    }

    function getToken(){
        try {
            if($code = Request::instance()->input('code')){
                $this->client->authenticate($code);
                $oauth2 = new Google_Service_Oauth2($this->client);
                $token = $this->client->getAccessToken();
                $this->client->setAccessToken($token);

                return $token;
            }else{
                Request::instance()->redirect(url($this->redirectURI, array('auth' => true)));
            }

        } catch (Exception $e) {
            Request::instance()->redirect(url($this->redirectURI, array('auth' => true)));
        }
    }

    function setToken($token){
        $this->client->setAccessToken($token);
    }

    function getCurrentUser($token = null){
        try {
            $oauth = new Google_Service_Oauth2($this->client);
            $userinfo = $oauth->userinfo->get();
            return $userinfo;
        } catch (Exception $e) {
            return false;
        }
    }

    public function listAccounts() {
        return $this->business->accounts->listAccounts()->getAccounts();
    }

    public function getLocations($accountName) {
        return $this->business->accounts_locations->listAccountsLocations($accountName);
    }

    public function getAvatar() {
        return 'assets/images/google-business.png';
    }


}

