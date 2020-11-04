<?php
class HashtagController extends Controller {

    public function index() {
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


        if ($load = $this->request->input('load')) {
            $captions = $this->model('hashtag')->getAllHashtags($this->request->input('id'));

            return $this->view('hashtag/load', array('hashtags' => $captions));
        }
        return $this->render($this->view('hashtags/index', array('hashtags' => $hashtags)), true);
    }
}