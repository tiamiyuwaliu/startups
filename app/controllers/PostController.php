<?php
class PostController extends Controller {
    public function index() {
        $this->setTitle(l('posts'));
        $this->activeIconMenu = 'posts';
        if ($val = $this->request->input('val')) {
            if (isset($val['draft'])) {
                if (isset($val['edit_post'])) {
                    $this->model('post')->savePost($val,$val['accounts'][0], $val['edit_post']);
                    return json_encode(array(
                        'type' => 'function',
                        'message' => l('draft-saved-success')
                    ));
                } else {
                    foreach($val['accounts'] as $account) {
                        $val['status'] = 4;
                        $this->model('post')->addPost($account, $val);
                    }
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'PostEditor.newDraftSaved',
                        'message' => l('draft-saved-success')
                    ));
                }
            } else {
                if (isset($val['edit_post'])) {
                    $this->model('post')->savePost($val,$val['accounts'][0], $val['edit_post']);
                    if ($val['edit_action'] == 'save') {
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'PostEditor.draftSaved',
                            'message' => l('post-saved-success')
                        ));
                    } elseif($val['edit_action'] == 'publish') {
                        $publish = $this->model('post')->publish($val['edit_post']);
                        if ($publish) {
                            return json_encode(array(
                                'type' => 'function',
                                'value' => 'PostEditor.postCompleted',
                                'message' => l('post-success')
                            ));
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('post-failed-in-all')
                            ));
                        }
                    } elseif($val['edit_action'] == 'schedule') {
                        $this->model('post')->updateStatus($val['edit_post'], 2);
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'PostEditor.postCompleted',
                            'message' => l('post-scheduled-success')
                        ));
                    }
                }
                if (!$val['schedule']) {
                    $success = array();
                    $failed = array();
                    foreach($val['accounts'] as $account) {
                        $postId = $this->model('post')->addPost($account, $val);
                        $publish = $this->model('post')->publish($postId);
                        if ($publish) {
                            $success[] = $account;
                        } else {
                            $failed[] = $account;
                            $this->model('post')->updateStatus($postId, 3);
                        }
                    }
                    if (empty($failed)) {
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'PostEditor.postCompleted',
                            'message' => l('post-success')
                        ));
                    } else {
                        if (empty($success)) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('post-failed-in-all')
                            ));
                        } else {
                            $successNames = '';
                            $failedNames = '';
                            foreach($success as $item) {
                                $theAccount = model('social')->find($item);
                                $successNames .= ($successNames) ? ','.$theAccount['username']: $theAccount['username'];
                            }
                            foreach($failed as $item) {
                                $theAccount = model('social')->find($item);
                                $failedNames .= ($failedNames) ? ','.$theAccount['username']: $theAccount['username'];
                            }

                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('post-failed-in-some-accounts', array('success'=> '<strong>'.$successNames.'</strong>', 'failed' => '<strong>'.$failedNames.'</strong>'))
                            ));
                        }
                    }
                } else {
                    $val['status'] = 2;
                    foreach($val['accounts'] as $account) {
                        $this->model('post')->addPost($account, $val);
                    }
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'PostEditor.postCompleted',
                        'message' => l('post-scheduled-success')
                    ));
                }
            }
        }
        return $this->render($this->view('posts/compose'), true);
    }

    public function fetchLocation() {
        $term = $this->request->input('term');
        $account = $this->model('social')->findOneActive();

        $result = array();

        if($account) {

            try {
                $this->model('social')->login($account);
                $response = $this->model('social')->getObject()->location->findPlaces($term, array(), \InstagramAPI\Signatures::generateUUID());
                $response = json_decode($response);
                if(isset($response->items) && !empty($response->items)){
                    $result =  $response->items;
                }
            } catch (Exception $e) {
                print_r($e);
            }
        }
        return $this->view('posts/location', array('results' => $result));
    }

    public function drafts() {
        $this->setTitle(l('drafts'));

        $selectedAccounts = $this->request->input('accounts', 'all');

        $posts = $this->model('post')->getPosts(4, $selectedAccounts);
        return $this->render($this->view('posts/drafts', array('posts' => $posts,'selectedAccounts' => explode(',', $selectedAccounts))), true);
    }

    public function history() {

    }

    public function bulk() {

    }
}