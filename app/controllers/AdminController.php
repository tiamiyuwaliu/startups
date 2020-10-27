<?php
class AdminController extends  Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->setAdminLayout();
    }

    public function index() {
        $this->setTitle(l('dashboard'));
        $this->setActiveIconMenu('dashboard');
        return $this->render($this->view('admincp/dashboard/index'), true);
    }

    public function users() {
        $this->setTitle(l('manage-users'));
        $this->setActiveIconMenu('users');

        if ($val = $this->request->input('val')) {
            $this->defendDemo();
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'full_name' => 'required',
                    'password' => 'required',
                    'email' => 'required|email|unique:users'
                ));

                if ($validator->passes()) {
                    if ($val['password'] != $val['confirm']) {
                        return json_encode(array(
                            'message' => l('password-does-not-match'),
                            'type' => 'error'
                        ));
                    }
                    $userid = $this->model('user')->addUser($val, true);

                    return json_encode(array(
                        'message' => l('user-created-successful'),
                        'type' => 'url',
                        'value' => url('admincp/users')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            $this->defendDemo();
            switch($action) {
                case 'delete':
                    $this->model('user')->deleteUser($id);
                    break;
                case 'enable':
                    $this->model('user')->enableUser($id);
                    break;
                case 'disable':
                    $this->model('user')->disableUser($id);
                    break;
                case 'access':
                    session_put('shadow_userid', $id);
                    return json_encode(array(
                        'type' => 'normal-url',
                        'value' => url('dashboard'),
                        'message' => l('you-now-viewing-user')
                    ));
                    break;
            }

            return json_encode(array(
                'message' => l('user-action-successful'),
                'type' => 'url',
                'value' => url('admincp/users')
            ));
        }

        $users = $this->model('admin')->getUsers($this->request->input('term'));

        if (isset($_GET['term']) and is_ajax() and !isFullSearch()) {
            return json_encode(array(
                'content' => $this->view('admincp/users/display', array('users' => $users)),
                'title' => $this->getTitle(),
            ));
        }

        return $this->render($this->view('admincp/users/index', array('users' => $users)),true);
    }

    public function settings() {
        $this->setTitle(l('site-settings'));
        $this->setActiveIconMenu('settings');

        $message = null;

        if ($val = $this->request->input("val", null, false)) {
            $this->defendDemo();
            $images = $this->request->input('img');
            if ($images) {
                foreach ($images as $image => $value) {
                    $val[$image] = $value;
                    if ($imageFile = $this->request->inputFile($image)) {
                        $uploader = new Uploader($imageFile);
                        $uploader->setPath("settings/");
                        if ($uploader->passed()) {
                            $val[$image] = $uploader->uploadFile()->result();
                        } else {
                            //there is problem
                            $message  = $uploader->getError();
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $message
                            ));
                        }
                    }
                }
            }

            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('settings-saved')
            ));
        }
        return $this->render($this->view('admincp/settings/index'),true);
    }

    public function emailSetup() {
        $this->setTitle(l('email-setup'));
        $this->setActiveIconMenu('email-setup');
        if ($val = $this->request->input("val", null, false)) {
            $this->defendDemo();
            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('settings-saved')
            ));
        }
        return $this->render($this->view('admincp/email/setup'),true);
    }

    public function emailTemplates() {
        $this->setTitle(l('email-templates'));
        $this->setActiveIconMenu('email-templates');
        if ($val = $this->request->input("val", null, false)) {
            $this->defendDemo();
            $this->model('admin')->saveSettings($val);
            return json_encode(array(
                'type' => 'success',
                'message' => l('settings-saved')
            ));
        }
        return $this->render($this->view('admincp/email/templates'),true);
    }
}