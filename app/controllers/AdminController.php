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

    public function helps() {
        $this->setTitle(l('Helps'));
        $this->setActiveIconMenu('helps');
        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'add') {
                if ($imageFile = $this->request->inputFile('img')) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("helps/");
                    if ($uploader->passed()) {
                        $val['img'] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $message
                        ));
                    }

                }
                $this->model('admin')->addHelp($val);
                return json_encode(array(
                    'type' => 'reload',
                    'message'=> 'Content added successfully'
                ));
            } elseif($val['action'] == 'edit') {
                if ($imageFile = $this->request->inputFile('img')) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("helps/");
                    if ($uploader->passed()) {
                        $val['img'] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $message
                        ));
                    }

                }
                $this->model('admin')->addHelp($val, $val['id']);
                return json_encode(array(
                    'type' => 'reload',
                    'message'=> 'Content saved successfully'
                ));
            }
        }
        $helps = $this->model('admin')->getHelps(null,null,null, null,null, $this->request->input('term'));
        return $this->render(view('admincp/helps/index', array('helps' => $helps)), true);
    }
    public function graphics() {
        $this->setTitle(l('graphics'));
        $this->setActiveIconMenu('graphics');
        $category = $this->request->input('category');
        $subCategory  = $this->request->input('sub');

        if ($val = $this->request->input('val')) {
            if($val['action'] == 'add-category') {
                $this->model('admin')->addGraphicCategory($val);
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('graphic-category-added')
                ));
            } elseif($val['action'] == 'upload-graphics' ) {

                if ($imageFile = $this->request->inputFile('img')) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("graphics/");
                    if ($uploader->passed()) {
                        $val['img'] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $message
                        ));
                    }
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => 'You need to upload the image of this graphics'
                    ));
                }

                if ($fileHtml = $this->request->inputFile('file')) {
                    $uploader = new Uploader($fileHtml, 'file');
                    $uploader->setPath("graphics/html/");
                    if ($uploader->passed()) {
                        $val['id'] = $uploader->uploadFile()->result();
                    }
                } else {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => 'You need to upload the studio html file'
                    ));
                }

                $this->model('admin')->addGraphic($val);
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('Graphic uploaded successfully')
                ));
            } elseif($val['action'] == 'edit-graphics') {
                if ($imageFile = $this->request->inputFile('img')) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("graphics/");
                    if ($uploader->passed()) {
                        $val['img'] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                        $message  = $uploader->getError();
                        return json_encode(array(
                            'type' => 'error',
                            'message' => $message
                        ));
                    }
                }
                $this->model('admin')->updateGraphic($val);
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('Graphic updated successfully')
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            if ($action == 'delete-graphic') {
                Database::getInstance()->query("DELETE FROM graphics WHERE id=?", $this->request->input('id'));
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('Graphic deleted successfully')
                ));
            } elseif($action == 'delete-category') {
                Database::getInstance()->query("DELETE FROM graphics_category WHERE id=?", $this->request->input('id'));
                return json_encode(array(
                    'type' => 'reload',
                    'message' => l('Graphic category deleted successfully')
                ));
            }
        }
        return $this->render($this->view('admincp/graphics/index', array('category' => $category, 'subCategory' => $subCategory)), true);
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