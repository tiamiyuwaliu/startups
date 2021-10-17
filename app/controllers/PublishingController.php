<?php
class PublishingController extends Controller {
    public function __construct($request)
    {
        parent::__construct($request);
        $this->activeIconMenu = 'publishing';
        //exit(strtotime('08/16/2021, 01:00 AM').'sddsd');
       // exit(date('m/d/Y h:i A', 1629939600));
    }

    public function index() {
        $this->setTitle(l('calendar'));

        if ($val = $this->request->input('val')) {
            if (!isset($val['accounts'])) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('please-choose-account-post')
                ));
            }

            if ($val['edit_post']) {
                $this->model('post')->savePost($val,$val['accounts'], $val['edit_post']);
                if ($val['type'] == 0) {
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'PostEditor.postCompleted',
                        'message' => l('post-saved-success')
                    ));
                } elseif($val['type'] == 1) {
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
                } elseif($val['type'] == 2) {
                    $this->model('post')->updateStatus($val['edit_post'], 2);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'PostEditor.postCompleted',
                        'message' => l('post-scheduled-success')
                    ));
                }
            }
            if (empty($val['media']) and !$val['content']) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('your-post-need-content')
                ));
            }
            if ($val['type'] == 0) {
                foreach($val['accounts'] as $account) {
                    $postId = $this->model('post')->addPost($account, $val);
                    $this->model('post')->updateStatus($postId, 4);
                }
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'PostEditor.postCompleted',
                    'message' => l('post-saved-success')
                ));
            } elseif ($val['type'] == 1) {
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
            } elseif($val['type'] == 2) {
                $val['status'] = 2;
                foreach($val['accounts'] as $account) {
                    $postId = $this->model('post')->addPost($account, $val);
                    if ($this->workspace['approval']) {
                        Database::getInstance()->query("UPDATE posts SET approved=? WHERE id=?", 0, $postId);
                    }
                }
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'PostEditor.postCompleted',
                    'message' => l('post-scheduled-success')
                ));
            } elseif($val['type'] == 3) {
                $val['status'] = 2;
                $val['post_time'] = $this->model('post')->findAvailableTime();

                if (!$val['post_time']) {
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('no-suitable-time-found')
                    ));
                }
                foreach($val['accounts'] as $account) {
                    $postId = $this->model('post')->addPost($account, $val);
                    if ($this->workspace['approval']) {
                        Database::getInstance()->query("UPDATE posts SET approved=? WHERE id=?", 0, $postId);
                    }
                }
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'PostEditor.postCompleted',
                    'message' => l('post-scheduled-success')
                ));
            }
        }
        return $this->render($this->view('posts/calendar'), true);
    }

    public function settings() {
        $this->setTitle(l('publishing-settings'));

        if ($val = $this->request->input('val')) {
            $color = $this->request->input('label');
            $timetable = perfectSerialize($val);
            $label = perfectSerialize($color);
            if (!isset($val['approval'])) $val['approval'] = 0;
            Database::getInstance()->query("UPDATE workspace SET approval=?,timezone=?,timetable=?,labels=? WHERE id=?",
            $val['approval'],$val['timezone'], $timetable, $label, $this->workspaceId);

            return json_encode(array(
                'type' => 'function',
                'message' => l('publishing-settings-saved')
            ));
        }

        return $this->render($this->view('posts/settings'), true);
    }

    public function posts() {
        $this->setTitle(l('posts'));
        $type = $this->request->segment(2, 'drafts');
        $status = 4;
        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'post-comment') {
                $comment = $this->model('post')->addComment($val);
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'Comment.added',
                    'content' => array('display' => $this->view('posts/comment/display', array('comment' => $comment)), 'id' => $val['id']),
                    'message' => l('comment-added')
                ));
            }
        }
        if ($action = $this->request->input('action')) {

            switch($action) {
                case 'approve' :
                    $id = $this->request->input('id');
                    $post = $this->model('post')->find($id);
                    if ($post['approved']) {
                        $this->model('post')->approve($id, 0);
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'Comment.approved',
                            'content' => array('type' => 'reject', 'id' => $id, 'title' => l('approve-post')),
                        ));
                    } else {
                        $this->model('post')->approve($id, 1);
                        return json_encode(array(
                            'type' => 'function',
                            'value' => 'Comment.approved',
                            'content' => array('type' => 'approve', 'id' => $id, 'title' => l('disapprove-post')),
                        ));
                    }
                    break;
                case 'delete-comment':
                    $id = $this->request->input('id');
                    $this->model('post')->deleteComment($id);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'Comment.deleted',
                        'content' => $id,
                        'message' => l('comment-deleted')
                    ));
                    break;
                case 'mark-resolved':
                    $id = $this->request->input('id');
                    $this->model('post')->markComment($id);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'Comment.marked',
                        'content' => $id,
                        'message' => l('comment-marked')
                    ));
                    break;
                case 'delete':
                    $ids = explode(',', $this->request->input('id'));
                    foreach($ids as $id) {
                        $this->model('post')->deletePost($id);
                    }
                    return json_encode(array(
                        'type' => 'reload',
                        'message'=> l('post-deleted-success')
                    ));
                    break;
                case 'publish':
                    $postId = $this->request->input('id');
                    $publish = $this->model('post')->publish($postId);
                    if ($publish ) {
                        return json_encode(array(
                            'type' => 'reload',
                            'message' => l('post-success')
                        ));
                    } else {
                        $this->model('post')->updateStatus($postId, 3);
                        return json_encode(array(
                            'type' => 'function',
                            'message' => l('post-failed-in-all')
                        ));
                    }
                    break;
                case 'schedule':
                    $postId = $this->request->input('id');
                    $this->model('post')->updateStatus($postId, 2);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('publishing/posts/scheduled'),
                       'message' => l('post-scheduled-success')
                    ));
                    break;
                case 'change-date':
                    $id = $this->request->input('id');
                    $date = strtotime($this->request->input('date'));
                    Database::getInstance()->query("UPDATE posts SET schedule_date=? WHERE id=?", $date, $id);
                    break;
            }
        }
        switch($type) {
            case 'scheduled':
                $type = 'scheduled';
                $status = 2;
                break;
            case 'published':
                $type = 'published';
                $status = 1;
                break;
            case 'failed':
                $type = 'failed';
                $status = 3;
                break;
            default:
                $type = 'drafts';
                break;
        }
        $selectedAccounts = ($this->selectedAccount) ? implode(',', $this->selectedAccount) : 'all';
        $posts = $this->model('post')->getPosts($status, $selectedAccounts, 30, $this->request->input('term'));

        return $this->render($this->view('posts/posts', array('posts' => $posts,'type' => $type)), true);
    }

    public function parties() {
        $this->setTitle(l('parties'));

        if ($val = $this->request->input('val')) {
            if (!isset($val['accounts'])) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('please-choose-facebookgroup-account')
                ));
            }
            if (!$val['date']) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('please-select-date-party')
                ));
            }
            if ($val['type'] == 1 and !$val['title']) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('give-party-title')
                ));
            }

            if ($val['type'] == 2 and !$val['template']) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('choose-a-party-template')
                ));
            }
            $party = $this->model('party')->createParty($val);
            return json_encode(array(
                'type' => 'function',
                'value' => 'Parties.created',
                'content' => url('publishing/party/'.$party),
                'message' => l('party-started-success')
            ));
        }

        $parties = $this->model('party')->getParties(50, $this->request->input('term'));
        return $this->render($this->view('parties/index', array('parties' => $parties)), true);
    }

    public function party() {
        $partyKey = $this->request->segment(2);
        $party = $this->model('party')->findPartyByKey($partyKey);

        $this->setTitle(l('parties'));
        $posts = $this->model('party')->getPartyPosts($party['id']);
        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'update-settings') {
                $accounts = perfectSerialize($val['accounts']);
                $date = strtotime($val['date']);
                $color = (isset($val['color'])) ? $val['color']:'#000';
                Database::getInstance()->query("UPDATE parties_scheduled SET color=?,title=?,timezone=?,start_date=? WHERE id=?",$color,$val['title'],  $val['timezone'], $date, $party['id']);
                return json_encode(array(
                    'type' => 'reload-modal',
                    'content' => '#partySettingsModal',
                    'message' => l('party-settings-updated')
                ));
            }
        }
        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'add-text':
                    $text = $this->request->input('text');

                    $post = $this->model('party')->addPartyPost($text, array(), $party['id']);
                    $post = $this->model('party')->findPartyPost($post);
                    return $this->view('parties/display-post', array('post' => $post));
                    break;
                case 'save-caption':
                    $text = $this->request->input('text');
                    autoLoadVendor();
                    $emojione = new \Emojione\Client(new \Emojione\Ruleset());
                    $text = $emojione->toShort($text);
                    $id = $this->request->input('id');
                    Database::getInstance()->query("UPDATE parties_scheduled_posts SET caption=? WHERE id=?", $text, $id);
                    break;
                case 'save-time':
                    $id = $this->request->input('id');
                    $text = strtotime($this->request->input('text'));
                    Database::getInstance()->query("UPDATE parties_scheduled_posts SET schedule_time=? WHERE id=?", $text, $id);

                    break;
                case 'update-media':
                    $id = $this->request->input('id');
                    $media = $this->request->input('medias', array());
                    $media = perfectSerialize($media);
                    Database::getInstance()->query("UPDATE parties_scheduled_posts SET medias=? WHERE id=?", $media, $id);
                    break;
                case 'status':
                    $status = $this->request->input('status');
                    if ($status == 1) {
                        if ($this->model('party')->countAllPartyPosts($party['id']) < 1) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => "You need to add posts to your party before enabling this"
                            ));
                        }
                    }
                    Database::getInstance()->query("UPDATE parties_scheduled SET status=? WHERE id=?", $status, $party['id']);
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('party-status-updated')
                    ));
                    break;
                case 'add-media-to-post':
                    $id = $this->request->input('id');
                    $files = $this->request->input('files');
                    $medias = array();
                    foreach($files as $file) {
                        $medias[] = $file['file_name'];
                    }
                    $post = $this->model('party')->findPartyPost($id);
                    $postMedias = perfectUnserialize($post['medias']);
                    $postMedias = array_merge($medias, $postMedias);
                    $media = perfectSerialize($postMedias);
                    $post['medias'] = $media;
                    Database::getInstance()->query("UPDATE parties_scheduled_posts SET medias=? WHERE id=?", $media, $id);
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
                        $post = $this->model('party')->addPartyPost('', $medias, $party['id']);
                        $post = $this->model('party')->findPartyPost($post);
                        $content .= $this->view('parties/display-post', array('post' => $post));
                    } else {
                        foreach($medias as $media) {
                            $post = $this->model('party')->addPartyPost('', array($media), $party['id']);
                            $post = $this->model('party')->findPartyPost($post);
                            $content .= $this->view('parties/display-post', array('post' => $post));
                        }
                    }
                    return $content;
                    break;
                case 'delete-post':
                    Database::getInstance()->query("DELETE FROM parties_scheduled_posts WHERE id=?", $this->request->input('id'));
                    break;
                case 'delete-party':
                    $this->model('party')->deleteParty($party['id']);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => url('publishing/parties')
                    ));
                    break;
                case 'duplicate-post':
                    $id = $this->request->input('id');
                    $post = $this->model('party')->findPartyPost($id);
                    Database::getInstance()->query("INSERT INTO parties_scheduled_posts (party_id,caption,medias,schedule_time)VALUES(?,?,?,?)",
                        $post['party_id'],$post['caption'],$post['medias'],$post['schedule_time']);
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('post-duplicated')
                    ));
                    break;
            }
        }
        return $this->render($this->view('parties/page', array('party' => $party, 'posts' => $posts)), true);
    }

    public function calendarData() {
        $posts =   $this->model('post')->getCalendarData($this->request->input('all'));
        $result = array();
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        foreach($posts as $post) {
            $postData = perfectUnserialize($post['type_data']);

            $caption = $emojione->shortnameToUnicode($postData['content']);
            $account = $this->model('social')->find($post['account']);
            if ($postData['media']) {
                $medias = (is_array($postData['media'])) ? $postData['media'] : explode(',', $postData['media']);
            } else {
                $medias = array();
            }
            $image = ($medias) ? assetUrl($medias[0]) : '';
            $color = $account['social_type'].'-border-bg-color';
            $icon = ($account['social_type'] == 'facebook') ? '<i class="lab la-facebook-f"></i>' : '<i class="lab la-'.$account['social_type'].'"></i>';
            $statusTitle = '';
            $statusColor = '';
            switch($post['status']) {
                case 2:
                    $statusColor = 'scheduled-color';
                    $statusTitle = l('scheduled-post');
                    break;
                case 1:
                    $statusColor = 'published-color';
                    $statusTitle = l('published-post');
                    break;
                case 3:
                    $statusColor = 'failed-color';
                    $statusTitle = l('failed-post');
                    break;
                default:
                    $statusTitle = l('draft-post');
                    $statusColor = 'draft-color';
                    break;
            }
            $result[] = array(
                'id' => $post['id'],
                'title' => '',
                'editable' => ($post['status'] == 2 or $post['status'] == 4) ? true : false,
                //'url' => url('publishing/post'),
                'start' => date('Y-m-d', $post['schedule_date']).'T'.date('h', $post['schedule_date']).':00:00',
                'image' => $image,
                'bgcolor' => $color,
                'medias' => $medias,
                'caption' => format_output_text($caption),
                'postId' => $post['id'],
                'schedule_date' => date('m/d/Y h:iA', $post['schedule_date']),
                'social_icon' => $icon,
                'status_title' => $statusTitle,
                'status_color' => $statusColor,
                'time' => date('h:iA', $post['schedule_date']),
                'edit' => array(
                    'account' => $post['account'],
                    'status' => $post['status'],
                    'medias' => $medias,
                    'id' => $post['id'],
                    'caption' => $caption,
                    'time' => date( 'm/d/Y H:i',$post['schedule_date']),
                )
            );
        }

        //getting data for parties
        $parties = $this->model('party')->getRunningParties();

        foreach($parties as $party) {
            $posts = $this->model('party')->getPartiesPendingPosts($party['id']);
            foreach($posts as $post) {
                $medias = perfectUnserialize($post['medias']);
                $image = ($medias) ? assetUrl($medias[0]) : '';
                $caption = $emojione->shortnameToUnicode($post['caption']);
                $icon = '';
                $statusTitle = l('pending-post');
                $statusColor = 'draft-color';
                $result[] = array(
                    'id' => $post['id'],
                    'title' => '',
                    'editable' =>  false,
                    //'url' => url('publishing/post'),
                    'start' => date('Y-m-d', $post['schedule_time']).'T'.date('h', $post['schedule_time']).':00:00',
                    'image' => $image,
                    'bgcolor' => '',
                    'bgcolorValue' => $party['color'],
                    'medias' => $medias,
                    'caption' => format_output_text($caption),
                    'postId' => $post['id'],
                    'schedule_date' => date('m/d/Y h:iA', $post['schedule_time']),
                    'social_icon' => $icon,
                    'party_title' => $party['title'],
                    'party_link' => url('publishing/party/'.$party['unique_key']),
                    'status_title' => $statusTitle,
                    'status_color' => $statusColor,
                    'time' => date('h:iA', $post['schedule_time']),
                    'edit' => false,
                );
            }
        }

        return json_encode($result);
    }


}