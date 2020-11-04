<?php
class CaptionController extends Controller {

    public function index() {
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


        if ($load = $this->request->input('load')) {
            $captions = $this->model('caption')->getAllCaptions($this->request->input('id'));

            return $this->view('caption/load', array('captions' => $captions));
        }

        return $this->render($this->view('captions/index', array('captions' => $captions)), true);
    }
}