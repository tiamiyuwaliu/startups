<?php
class AccountController extends Controller {
    private $username;
    private $password;
    private $digits;
    private $instagram;
    private $choice = 0;
    public function index() {
        $this->setTitle(l('manage-accounts'));

        if ($val = $this->request->input('val')) {
            $validator = Validator::getInstance()->scan($val, array(
                'username' => 'required',
                'password' => 'required',
            ));

            if ($validator->passes()) {
                $this->username = $val['username'];
                $this->password = $val['password'];
                $this->digits = $val['digit_1'].$val['digit_2'].$val['digit_3'].$val['digit_4'].$val['digit_5'].$val['digit_6'];

                $this->instagram = $this->model('social')->getObject();
                $this->choice = config('instagram-challenge-type', '0');

                try {
                    $result = $this->login();
                } catch(Exception $e) {

                }
                if ($result['status'] == 'success') {
                    //we have loggedIn
                    $user = $this->instagram->account->getCurrentUser();
                    $user = json_decode($user);
                    $account = $this->model('social')->findAccount($user->user->username);
                    if ($account) {
                        $this->model('social')->updateAccount($user, $account,$this->password);
                    } else {
                        $this->model('social')->addAccount($user, $this->password);
                    }
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'accountAddedSuccess',
                        'message' => l('account-added-success')
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'processAccountResult',
                        'content' => $result
                    ));
                }
            } else {
                return json_encode(array(
                    'type' => 'error',
                    'message' => $validator->first()
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            if ($action == 'delete') {
                $this->model('social')->deleteAccount($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('accounts'),
                    'message' => l('account-deleted')
                ));
            }
        }

        return $this->render($this->view('account/index'), true);
    }


    public function login() {
        if (session_get('auth-factor-'.$this->username)) {
            return $this->processTwoFactor();
        }

        try {
            $result = $this->instagram->login($this->username, $this->password);
            return $this->checkTwoFactor($result);
        } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

            $response = $e->getResponse()->getChallenge();
            if (is_array($response)) {
                $apiPath = $response['api_path'];
            } else {
                $apiPath = $e->getResponse()->getChallenge()->getApiPath();
            }

            return $this->confirmSecurityCode($apiPath);

        } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                'message' => l('login-on-instagram-pass-checkpoint')
            );

        } catch (\InstagramAPI\Exception\AccountDisabledException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-disabled-voilate")
            );

        } catch (\InstagramAPI\Exception\SentryBlockException $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-banned-for-spamming")
            );

        } catch (\InstagramAPI\Exception\IncorrectPasswordException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-password-invalid")
            );

        } catch (\InstagramAPI\Exception\InstagramException $e) {
            if ($e->hasResponse()) {

                if($e->getResponse()->getMessage() == "consent_required"){
                    return array(
                        "status" => "error",
                        "error_type" => 'general',
                        "message" => l("go-to-login-on-instagram-try-again")
                    );
                }

                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => $e->getResponse()->getMessage()
                );

            } else {

                $message = explode(":", $e->getMessage(), 2);
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => end($message)
                );
            }

        } catch (\Exception $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("oops-something-went-wrong")
            );

        }
    }


    public function processTwoFactor() {
        $twoFactorIdentifier = session_get("auth-factor-".$this->username);
        session_forget("auth-factor-".$this->username);
        try {

            $this->instagram->finishTwoFactorLogin($this->username, $this->password,  $twoFactorIdentifier, $this->digits);

            return array(
                "status" => "success",
                "message" => l("login-successful")
            );

        } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                'message' => l('login-on-instagram-pass-checkpoint')
            );

        } catch (\InstagramAPI\Exception\AccountDisabledException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-disabled-voilate")
            );

        } catch (\InstagramAPI\Exception\SentryBlockException $e) {
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-banned-for-spamming")
            );

        } catch (InstagramAPI\Exception\IncorrectPasswordException $e) {

            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("account-password-invalid")
            );

        } catch (\InstagramAPI\Exception\InstagramException $e) {

            if ($e->hasResponse()) {

                if($e->getResponse()->getMessage() == "consent_required"){
                    return array(
                        "status" => "error",
                        "error_type" => 'general',
                        "message" => l("go-to-login-on-instagram-try-again")
                    );
                }

                if ($e->getResponse()->getMessage() == 'challenge_required') {
                    if (session_get('challenge_hidden_code')) {
                        $apiPath = $e->getResponse()->getChallenge()->getApiPath();
                        $this->digits = session_get('challenge_hidden_code');

                        return $this->confirmSecurityCode($apiPath);
                    }
                    $apiPath = $e->getResponse()->getChallenge()->getApiPath();
                    return $this->sendSecurityCode($apiPath);
                }
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => $e->getResponse()->getMessage()
                );

            } else {

                $message = explode(":", $e->getMessage(), 2);
                return array(
                    "status" => "error",
                    "error_type" => 'general',
                    "message" => end($message).'its here 2'
                );
            }

        } catch (\Exception $e) {
            Database::getInstance()->query("DELETE FROM instagram_sessions WHERE username=?", $this->username);
            return array(
                "status" => "error",
                "error_type" => 'general',
                "message" => l("oops-something-went-wrong")
            );

        }
    }

    public function checkTwoFactor($response) {
        try {
            if (!is_null($response) && $response->isTwoFactorRequired()) {

                $phone_number = $response->getTwoFactorInfo()->getObfuscatedPhoneNumber();
                $twoFactorIdentifier = $response->getTwoFactorInfo()->getTwoFactorIdentifier();
                session_put('auth-factor-'.$this->username, $twoFactorIdentifier);
                if ($this->digits) session_put('challenge_hidden_code', $this->digits);

                return array(
                    "status"   => "error",
                    'error_type' => 'enter-digit-two-factor',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $phone_number))
                );

            }

        } catch (Exception $e) {
            print_r($e);
            exit;
        }


        return array(
            "status" => "success",
            "message" => l("login-successful")
        );
    }

    public function  confirmSecurityCode($apiPath) {
        try {

            $confirmSecurityCode = $this->instagram->checkpoint->confirmSecurityCode($this->username, $this->password, $apiPath, $this->digits);
            return $this->checkTwoFactor($confirmSecurityCode);

        } catch (InvalidArgumentException $e) {
            return array(
                "status" => "error",
                'error_type' => 'general',
                "message" => $e->getMessage()
            );

        } catch (Exception $e) {

            if(empty($e)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            $response = $e->getResponse();

            if($response and $response->getStatus() != "ok"){
                try {
                    if($response->getMessage() == "This field is required."){
                        return $this->sendSecurityCode($apiPath);
                    }
                    return $this->resendSecurityCode($apiPath);
                } catch (Exception $e) {

                    return array(
                        "status" => "error",
                        'error_type' => 'general',
                        "message" => $e->getMessage()
                    );

                }
            } else {
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }
        }
    }

    public function sendSecurityCode($apiPath) {
        try {
            $sendSecurityCode = $this->instagram->checkpoint->sendSecurityCode($apiPath, $this->choice);

            if(empty($sendSecurityCode) || is_null($sendSecurityCode)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if(isset($sendSecurityCode->message) && strpos($sendSecurityCode->message, "is not one of the available choices") !== false){
                $new_choice = $this->choice==1?0:1;
                $sendSecurityCode = $this->instagram->checkpoint->sendSecurityCode($apiPath, $new_choice);
            }

            if($sendSecurityCode->status != "ok"){
                if($sendSecurityCode->message == "This field is required."){
                    return $this->resendSecurityCode($apiPath);
                }

                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if($sendSecurityCode->step_name == "verify_email"){
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-email", array('email' => $sendSecurityCode->step_data->contact_point))
                );
            }else{
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $sendSecurityCode->step_data->contact_point))
                );
            }

        } catch (InvalidArgumentException $e) {

            return array(
                "status" => "error",
                'error_type' => 'general',
                "message" => $e->getMessage()
            );

        }
    }

    public function resendSecurityCode($apiPath) {
        try {
            if ($apiPath == '/challenge/') {
                Database::getInstance()->query("DELETE  FROM instagram_sessions WHERE username=?", $this->username);
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-process-try-again-now")
                );
            }
            $resendSecurityCode = $this->instagram->checkpoint->resendSecurityCode($this->username, $apiPath, $this->choice);

            if(empty($resendSecurityCode) || is_null($resendSecurityCode)){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if(isset($resendSecurityCode->message) && strpos($resendSecurityCode->message, "is not one of the available choices") !== false){
                $new_choice = $this->choice==1?0:1;
                $resendSecurityCode = $this->instagram->checkpoint->resendSecurityCode($this->username, $apiPath, $new_choice);
            }

            if($resendSecurityCode->status != "ok"){
                return array(
                    "status" => "error",
                    'error_type' => 'general',
                    "message" => l("could-not-verify-entered-code")
                );
            }

            if($resendSecurityCode->step_name == "verify_email"){
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-email", array('email' => $resendSecurityCode->step_data->contact_point))
                );
            }else{
                return array(
                    "status" => "error",
                    'error_type' => 'enter-digit',
                    "message"  => l("enter-number-sent-to-phone", array('phone' => $resendSecurityCode->step_data->contact_point))
                );
            }
        } catch (Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }
}