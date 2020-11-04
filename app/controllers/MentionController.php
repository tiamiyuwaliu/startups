<?php
class MentionController extends Controller {
    public function index() {
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
}