<?php
class TemplateController extends Controller {


    public function captions() {
        $this->setTitle(l('captions'));
        $this->setActiveIconMenu('library');

        if ($val = $this->request->input('val')) {
            if (isset($val['create'])) {
                $validator = Validator::getInstance()->scan($val, array(
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


    public function load() {
        $list = array();
        $type = $this->request->input('type');
        $list = $this->model('caption')->getAllCaptions();
        return $this->view('templates/load', array('list' => $list, 'type' => $type));
    }
}