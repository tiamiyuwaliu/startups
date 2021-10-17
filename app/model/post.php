<?php
class PostModel extends Model {

    public function findAvailableTime() {
        $settings = perfectUnserialize($this->controller()->workspace['timetable']);
        if (!$settings) return false;
        $today = date('l');
        $days = $this->getDays($today);
        $finding = true;
        $count = 1;
        while($finding) {
            if ($count > 2) $finding = false;
            foreach($days as $day) {
                $date = new DateTime();
                $date->modify('next '.$day);
                $date = date('Y-m-d', $date->getTimestamp());
                $times = $settings[$day];
                foreach($times as $time) {
                    $timev = strtotime($date.' '.$time);
                    $countPosts = $this->countPostByTime($timev);
                    if ($countPosts<$count) {
                        $finding = false;
                        return $date.' '.$time;
                        break;
                    }
                }
            }
            $count++;
        }

    }

    public function countPostByTime($time) {
        $query = $this->db->query("SELECT id FROM posts WHERE schedule_date=? AND workspace_id=?", $time, $this->controller()->workspaceId);
        return $query->rowCount();
    }

    public function getDays($today) {

        switch(strtolower($today)) {
            case 'monday';
            return array('tuesday','wednesday','thursday','friday','saturday','sunday', 'monday');
            break;
            case 'tuesday':
                return array('wednesday','thursday','friday','saturday','sunday', 'monday', 'tuesday');
                break;
            case 'wednesday':
                return array('thursday','friday','saturday','sunday', 'monday', 'tuesday','wednesday');
                break;
            case 'thursday':
                return array('friday','saturday','sunday', 'monday', 'tuesday','wednesday','thursday');
                break;
            case 'friday':
                return array('saturday','sunday', 'monday', 'tuesday','wednesday','thursday','friday');
                break;
            case 'saturday':
                return array('sunday', 'monday', 'tuesday','wednesday','thursday','friday','saturday');
                break;
            case 'sunday':
                return array( 'monday', 'tuesday','wednesday','thursday','friday','saturday','sunday');
                break;
        }
    }
    public function addPost($account, $val) {
        $ext = array(
            'post_type' => 'media',
            'post_time' => '',
            'timezone' => '',
            'media' => array(),
            'status' => '1',
            'labels' => array()
        );
        /**
         * @var $post_type
         * @var $schedule
         * @var $post_time
         * @var $timezone
         * @var $status
         * @var $labels
         */
        extract(array_merge($ext, $val));

        unset($val['accounts']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['content'] = $emojione->toShort($val['content']);
        $data = perfectSerialize($val);

        //$post_time = convertTimeByTimezone($post_time, false, $account);
        $post_time = strtotime($post_time);
        $labels = json_encode($labels);

        $this->db->query("INSERT INTO posts (action_userid,workspace_id,userid,account,type,type_data,caption,is_scheduled,schedule_date,created_date,status,timezone,labels)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
            model('user')->authId,$this->controller()->workspaceId,model('user')->authOwnerId,$account,$post_type,$data, $val['content'],$schedule,$post_time, time(),$status,$timezone,$labels);

        $postId = $this->db->lastInsertId();
        Hook::getInstance()->fire('post.added', null, array($postId,$val));
        return $postId;
    }

    public function savePost($val, $account, $id) {
        $ext = array(
            'post_type' => 'media',
            'post_time' => '',
            'timezone' => '',
            'media' => array(),
            'status' => '1',
            'labels' => array()
        );
        /**
         * @var $post_type
         * @var $schedule
         * @var $post_time
         * @var $timezone
         * @var $status
         * @var $labels
         */
        extract(array_merge($ext, $val));

        unset($val['accounts']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['content'] = $emojione->toShort($val['content']);
        $data = perfectSerialize($val);

        $account = $account[0];

        //$post_time = convertTimeByTimezone($post_time, false, $account);
        $post_time = strtotime($post_time);
        $labels = json_encode($labels);
        $this->db->query("UPDATE posts SET type=?,type_data=?,caption=?,schedule_date=?,labels=? WHERE id=?", $post_type, $data,$val['content'], $post_time,$labels, $id);
        return true;
    }

    public function publish($postId) {
        $post = $this->find($postId);
        try {
            $account = $this->model('social')->find($post['account']);
            if ($account) {

                $this->controller()->api($account['social_type'])->post($post, $post['account']);
                $this->setPublished($post['id']);
            } else {
                $this->setunPublished($post['id']);
            }

        } catch ( Exception $e) {
            $this->setunPublished($post['id']);
        }
        return true;
    }

    public function getPosts($status, $accounts = null, $limit = 10, $term = null) {
        $sql = " SELECT * FROM posts WHERE status=?  AND userid=? ";
        $param = array($status, model('user')->authOwnerId);

        if ($accounts and $accounts != 'all') {
            $sql .= " AND account IN ($accounts) ";
        }

        if ($term ) {
            $sql .= " AND caption LIKE '%$term%'";
        }
        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, $limit);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM posts WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($postId, $status) {
        return $this->db->query("UPDATE posts SET status=? WHERE id=?", $status, $postId);
    }

    public function deletePost($id) {
        $this->db->query("DELETE FROM posts WHERE id=? AND userid=?", $id, $this->model('user')->authOwnerId);
    }

    public function addComment($val) {
        $ext = array(
            'text' => '',
            'id' => '',
            'file' => ''
        );
        /**
         * @var $text
         * @var $id
         * @var $file
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO posts_comment (post_id,userid,comment,file,created)VALUES(?,?,?,?,?)",
        $id, $this->model('user')->authId,$text,$file,time());
        $commentId = $this->db->lastInsertId();
        $this->addActivity($id, 'left-comment');
        return $this->findComment($commentId);
    }

    public function addActivity($id, $activity) {
        $this->db->query("INSERT INTO posts_activities (post_id,userid,activity,created)VALUES(?,?,?,?)", $id,$this->model('user')->authId, $activity,time());
    }

    public function getComments($postId) {
        $query = $this->db->query("SELECT * FROM posts_comment WHERE post_id=? ORDER BY id DESC", $postId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivities($postId) {
        $query = $this->db->query("SELECT * FROM posts_activities WHERE post_id=? ORDER BY id DESC", $postId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function approve($id, $v) {
        $this->addActivity($id, ($v) ? 'approved-post' : 'rejected-post');
        return $this->db->query("UPDATE posts SET approved=? WHERE id=? AND workspace_id=?", $v, $id, $this->controller()->workspaceId);
    }

    public function findComment($id) {
        $query = $this->db->query("SELECT * FROM posts_comment WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteComment($id) {
        $comment = $this->findComment($id);
        $this->addActivity($comment['post_id'], 'deleted-comment');
        return $this->db->query("DELETE FROM posts_comment WHERE id=? AND userid=?", $id, $this->model('user')->authId);
    }

    public function markComment($id) {
        $comment = $this->findComment($id);
        $this->addActivity($comment['post_id'], 'mark-comment-resolved');
        return $this->db->query("UPDATE posts_comment SET resolved=? WHERE id=? ",1, $id);

    }
    public function getCalendarData($all) {
        $sql = "SELECT * FROM posts WHERE userid=? AND workspace_id=? ";
        $param = array($this->model('user')->authOwnerId, $this->controller()->workspaceId);

        if (!$all) {
            $sql .= " AND (status=? OR status=?)";
            $param[] = 4;
            $param[] = 2;
        }
        $query = $this->db->query($sql,  $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingPosts() {
        $time  = time() + (60 * 60 *10);
        $query = $this->db->query("SELECT * FROM posts WHERE schedule_date < $time  AND approved=? AND status=?", 1, 2);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setPublished($id) {
        return $this->db->query("UPDATE posts SET status=? WHERE id=?", 1, $id);
    }
    public function setunPublished($id) {
        return $this->db->query("UPDATE posts SET status=? WHERE id=?", 3, $id);
    }

    public function countSearch($term, $type) {
        $status = 4;
        if ($type == 'scheduled') $status = 2;
        if ($type == 'published') $status = 1;
       $query =  $this->db->query("SELECT id FROM posts WHERE caption LIKE '%$term%' ANd status=? AND workspace_id=?", $status, $this->controller()->workspaceId);
       return $query->rowCount();
    }
}