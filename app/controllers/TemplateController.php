<?php
class TemplateController extends Controller {

    public function captions() {
        $this->setTitle(l('captions'));
        $this->setActiveIconMenu('captions');

        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'title' => 'required',
                    'content' => 'required'
                ));

                autoLoadVendor();
                $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                $val['content'] = $emojione->toShort($val['content']);

                

                if ($validator->passes()) {

                    $this->model('caption')->add($val);

                    return json_encode(array(
                        'message' => l('caption-created-successful'),
                        'type' => 'url',
                        'value' =>  url('captions')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }


            if (isset($val['edit']) and $id = $val['edit']) {
                if (isset($val['content'])) {
                    autoLoadVendor();
                    $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                    $val['content'] = $emojione->toShort($val['content']);
                } else {
                    $val['content'] = '';
                }

                $this->model('caption')->save($val, $id);
                return json_encode(array(
                    'message' => l('caption-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#captionEditModal'.$id,
                    'value' => url('captions')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            switch($action) {
                case 'delete':
                    $this->model('caption')->delete($id);
                    break;
            }

            return json_encode(array(
                'message' => l('caption-action-successful'),
                'type' => 'url',
                'value' => url('captions')
            ));
        }

        if ($save = $this->request->input('save')) {
            $text = $this->request->input('text');
            autoLoadVendor();
            $emojione = new \Emojione\Client(new \Emojione\Ruleset());
            $title = mb_substr($text, 0, 50);
            $title = $emojione->toShort($title);
            $val = array('title' => $title);
            $val['content'] = $emojione->toShort($text);
            $this->model('caption')->add($val);
            return l('caption-save-successful');
        }

        $captions = $this->model('caption')->getCaptions($this->request->input('term'));


        return $this->render($this->view('templates/captions/index', array('captions' => $captions)), true);
    }

    public function hashtag() {
        $this->setTitle(l('hashtags'));
        $this->setActiveIconMenu('captions');

        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'title' => 'required',
                    'content' => 'required'
                ));

                autoLoadVendor();
                $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                $val['content'] = $emojione->toShort($val['content']);



                if ($validator->passes()) {

                    $this->model('hashtag')->add($val);

                    return json_encode(array(
                        'message' => l('hashtag-created-successful'),
                        'type' => 'url',
                        'value' =>  url('hashtags')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }


            if (isset($val['edit']) and $id = $val['edit']) {
                if (isset($val['content'])) {
                    autoLoadVendor();
                    $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                    $val['content'] = $emojione->toShort($val['content']);
                } else {
                    $val['content'] = '';
                }

                $this->model('hashtag')->save($val, $id);
                return json_encode(array(
                    'message' => l('hashtag-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#captionEditModal'.$id,
                    'value' => url('hashtags')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            switch($action) {
                case 'delete':
                    $this->model('hashtag')->delete($id);
                    break;
            }

            return json_encode(array(
                'message' => l('hashtag-action-successful'),
                'type' => 'url',
                'value' => url('hashtags')
            ));
        }



        $hashtags = $this->model('hashtag')->getHashtags($this->request->input('term'));



        return $this->render($this->view('templates/hashtags/index', array('hashtags' => $hashtags)), true);
    }
    public function mention() {
        $this->setTitle(l('mentions-template'));
        $this->setActiveIconMenu('captions');

        if ($val = $this->request->input('val')) {

            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
                    'title' => 'required',
                    'content' => 'required'
                ));

                if ($validator->passes()) {

                    $val['content'] = implode(',', $val['content']);
                    $this->model('mention')->add($val);

                    return json_encode(array(
                        'message' => l('mention-created-successful'),
                        'type' => 'url',
                        'value' =>  url('mentions')
                    ));
                } else {
                    return json_encode(array(
                        'message' => $validator->first(),
                        'type' => 'error'
                    ));
                }
            }


            if (isset($val['edit']) and $id = $val['edit']) {
                $val['content'] = implode(',', $val['content']);
                $this->model('mention')->save($val, $id);
                return json_encode(array(
                    'message' => l('mention-save-successful'),
                    'type' => 'modal-url',
                    'content' => '#captionEditModal'.$id,
                    'value' => url('mentions')
                ));
            }
        }

        if($action = $this->request->input('action') and $id = $this->request->input('id')) {
            switch($action) {
                case 'delete':
                    $this->model('mention')->delete($id);
                    break;
            }

            return json_encode(array(
                'message' => l('mention-action-successful'),
                'type' => 'url',
                'value' => url('mentions')
            ));
        }




        $hashtags = $this->model('mention')->getMentions($this->request->input('term'));


        if ($load = $this->request->input('load')) {
            $captions = $this->model('mention')->getAllMentions($this->request->input('id'));

            return $this->view('mention/load', array('mentions' => $captions));
        }

        if ($load = $this->request->input('fetch')) {

            $account = $this->model('social')->firstAccount();
            $instagram = $this->model('social')->login($account);
            try {
                $response = $instagram->getObject()->people->search($this->request->input('key'), array(), \InstagramAPI\Signatures::generateUUID());
                $response = json_decode($response);
                if(isset($response->users) && !empty($response->users)){
                    $result = array();
                    foreach($response->users as $username) {
                        $result[] = array('value' => $username->username, 'text' => $username->username);
                    }
                    return json_encode($result);
                }
                return json_encode(array());
            } catch (Exception $e) {
                return json_encode(array());
            }

        }
        return $this->render($this->view('mentions/index', array('mentions' => $hashtags)), true);
    }

    public function load() {
        $list = array();
        $type = $this->request->input('type');
        switch ($type) {
            case 'captions' :
                $list = $this->model('caption')->getAllCaptions();
                break;
            case 'hashtag':
                $list = $this->model('hashtag')->getAllHashtags();
                break;
            case 'mention':
                $list = $this->model('mention')->getAllMentions();
                break;
        }

        return $this->view('templates/load', array('list' => $list, 'type' => $type));
    }
}