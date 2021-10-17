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

        return json_encode($result);
    }


}