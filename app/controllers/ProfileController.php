<?php
class ProfileController extends Controller {

    public function index() {
        $this->setTitle(l('my-profile'));

        $user = $this->model('user')->getUser($this->model('user')->authId);

        if ($this->request->segment(1) == 'refer') $this->setActiveIconMenu('refer');
        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if ($val['action'] == 'profile') {
                $validator = Validator::getInstance()->scan($val, array(
                    'full_name' => 'required',
                    'email' => 'required',
                    'timezone' => 'required',
                ));
                if ($validator->passes()) {

                    if ($image = $this->request->inputFile('avatar')) {
                        $uploader = new Uploader($image);
                        $uploader->setPath("users/avatar/".$this->model('user')->authId.'/');
                        if ($uploader->passed()) {
                            $val['avatar'] = $uploader->resize()->result();

                        } else {
                            return json_encode(array(
                                'message' => $uploader->getError(),
                                'type' => 'error'
                            ));
                        }
                    }

                    if ($val['email'] != model('user')->userData('email')) {
                        if (model('user')->emailExists($val['email'])) {
                            return json_encode(array(
                                'message' => l('new-email-already-exits'),
                                'type' => 'error'
                            ));
                        }
                    }

                    $this->model('user')->saveProfile($val);
                    return json_encode(array(
                        'message' => l('profile-save-successful'),
                        'type' => 'url',
                        'value' => url('profile')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }

            }

            if ($val['action'] == 'password') {

                if (md5($val['currentpassword']) !== $user['password']) {
                    return json_encode(array(
                        'message' => l('password-does-not-found'),
                        'type' => 'error'
                    ));
                }
                if ($val['password'] != $val['confirm']) {
                    return json_encode(array(
                        'message' => l('password-does-not-match'),
                        'type' => 'error'
                    ));
                }
                $this->model('user')->savePassword($val);
                return json_encode(array(
                    'message' => l('password-changed-success'),
                    'type' => 'url',
                    'value' => url('profile')
                ));
            }

            if ($val['action'] == 'request-payout') {
                $result = $this->model('user')->addPayout($val);
                if ($result) {
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => 'Payout request has been submit , You will receive your payment on our pay day, Thanks'
                    ));
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => 'Something went wrong, make sure your balance is more than $20'
                    ));
                }
            }

        }
        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'enable-referral':
                    $this->model('user')->enableReferral();
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => 'Congrats! Referral program enabled successfully'
                    ));
                    break;
            }
        }
        return $this->render($this->view('profile/index', array('user' => $user)), true);
    }


    public function delete() {

        if ((config('demo', false) and $this->model('user')->authId == 66) or $this->model('user')->authId == 1) return $this->request->redirect(url('profile'));

        $this->model('user')->deleteUser($this->model('user')->authId);

        $this->model('user')->logoutUser();
        return $this->request->redirect(url(''));
    }
}