<?php
class TemplateController extends Controller {

    public function templates() {
        $this->setTitle(l('party-templates'));
        $this->setActiveIconMenu('publishing');

        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'add') {
                $partyId = $this->model('party')->addTemplate($val);
                return json_encode(array(
                    'type' => 'modal-url',
                    'content' => '#addTemplateModal',
                    'value' => url('templates/'.$partyId),
                    'message' => l('party-template-created-success')
                ));
            } elseif($val['action'] == 'edit') {
                $this->model('party')->saveTemplate($val, $val['id']);
                return json_encode(array(
                    'type' => 'modal-url',
                    'content' => '#editTemplateModal'.$val['id'],
                    'value' => url('templates'),
                    'message' => l('party-template-saved-success')
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'make-admin':
                    $id = $this->request->input('id');
                    $type = $this->request->input('type');
                    Database::getInstance()->query("UPDATE parties_shared SET party_permission=? WHERE id=?", $type, $id);
                    $user = $this->model('party')->findUser($id);
                    return $this->view('templates/party/display-user', array('user' => $user));
                    break;

            }
        }
        $page = $this->request->segment(2, 'templates');
        $parties = ($page == 'templates') ? $this->model('party')->getTemplates(50, $this->request->input('term')) : $this->model('party')->getShareTemplates();
        return $this->render($this->view('templates/party/index', array('parties' => $parties, 'pageType' => $page)), true);
    }

    public function templatesPage() {
        $this->setTitle(l('party-template'));
        $this->setActiveIconMenu('publishing');
        $party = $this->model('party')->findByKey($this->request->segment(1));

        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'add-text':
                    $text = $this->request->input('text');

                    $post = $this->model('party')->addTemplatePost($text, array(), $party['id']);
                    $post = $this->model('party')->findTemplatePost($post);
                    return $this->view('templates/party/display-template-post', array('post' => $post));
                    break;
                case 'save-caption':
                    $text = $this->request->input('text');
                    autoLoadVendor();
                    $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                    $text = $emojione->toShort($text);
                    $id = $this->request->input('id');
                    Database::getInstance()->query("UPDATE parties_template_posts SET caption=? WHERE id=?", $text, $id);
                    break;
                case 'save-day':
                    $id = $this->request->input('id');
                    $text = $this->request->input('text');
                    Database::getInstance()->query("UPDATE parties_template_posts SET day_number=? WHERE id=?", $text, $id);
                    break;
                case 'save-time':
                    $id = $this->request->input('id');
                    $text = $this->request->input('text');
                    Database::getInstance()->query("UPDATE parties_template_posts SET schedule_time=? WHERE id=?", $text, $id);

                    break;
                case 'update-media':
                    $id = $this->request->input('id');
                    $media = $this->request->input('medias', array());
                    $media = perfectSerialize($media);
                    Database::getInstance()->query("UPDATE parties_template_posts SET medias=? WHERE id=?", $media, $id);
                    break;
                case 'add-media-to-post':
                    $id = $this->request->input('id');
                    $files = $this->request->input('files');
                    $medias = array();
                    foreach($files as $file) {
                        $medias[] = $file['file_name'];
                    }
                    $post = $this->model('party')->findTemplatePost($id);
                    $postMedias = perfectUnserialize($post['medias']);
                    $postMedias = array_merge($medias, $postMedias);
                    $media = perfectSerialize($postMedias);
                    $post['medias'] = $media;
                    Database::getInstance()->query("UPDATE parties_template_posts SET medias=? WHERE id=?", $media, $id);
                    return $this->view('templates/party/utils/template-medias', array('post' => $post));
                    break;
                case 'add-media':
                    $type = $this->request->input('type');
                    $files = $this->request->input('files');
                    $medias = array();
                    foreach($files as $file) {
                        $medias[] = $file['file_name'];
                    }
                    $content = '';
                    if ($type) {
                        $post = $this->model('party')->addTemplatePost('', $medias, $party['id']);
                        $post = $this->model('party')->findTemplatePost($post);
                        $content .= $this->view('templates/party/display-template-post', array('post' => $post));
                    } else {
                        foreach($medias as $media) {
                            $post = $this->model('party')->addTemplatePost('', array($media), $party['id']);
                            $post = $this->model('party')->findTemplatePost($post);
                            $content .= $this->view('templates/party/display-template-post', array('post' => $post));
                        }
                    }
                    return $content;
                    break;
                case 'delete-post':
                    Database::getInstance()->query("DELETE FROM parties_template_posts WHERE id=?", $this->request->input('id'));
                    break;
                case 'delete-party':
                    $this->model('party')->deletePartyTemplate($party['id']);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('publishing/parties/templates')
                    ));
                    break;
                case 'duplicate-post':
                    $id = $this->request->input('id');
                    $post = $this->model('party')->findTemplatePost($id);
                    Database::getInstance()->query("INSERT INTO parties_template_posts (party_id,caption,medias,schedule_time,day_number,sort_number)VALUES(?,?,?,?,?,?)",
                        $post['party_id'],$post['caption'],$post['medias'],$post['schedule_time'],$post['day_number'],$post['sort_number']);
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('post-duplicated')
                    ));
                    break;
            }
        }
        $posts = $this->model('party')->getTemplatePosts($party['id']);
        return $this->render($this->view('templates/party/page', array('party' => $party, 'posts' => $posts)), true);
    }
    public function captions() {
        $this->setTitle(l('captions'));
        $this->setActiveIconMenu('library');

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
        $this->setActiveIconMenu('library');

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

        }

        return $this->view('templates/load', array('list' => $list, 'type' => $type));
    }
}