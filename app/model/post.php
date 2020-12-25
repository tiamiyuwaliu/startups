<?php
class PostModel extends Model {

    public function addPost($account, $val) {
        $ext = array(
            'post_type' => 'media',
            'post_time' => '',
            'timezone' => '',
            'media' => array(),
            'status' => '1'
        );
        /**
         * @var $post_type
         * @var $schedule
         * @var $post_time
         * @var $timezone
         * @var $status
         */
        extract(array_merge($ext, $val));

        unset($val['accounts']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['content'] = $emojione->toShort($val['content']);
        $data = perfectSerialize($val);

        if (is_array($account)) {
            $post_time = convertTimeByTimezone($post_time, false, $account[0]);
            $account  = implode(',', $account);
        } else {
            $post_time = convertTimeByTimezone($post_time, false, $account);

        }

        $this->db->query("INSERT INTO posts (userid,account,type,type_data,is_scheduled,schedule_date,created_date,status,timezone)VALUES(?,?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId,$account,$post_type,$data,$schedule,$post_time, time(),$status,$timezone);

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
            'status' => '1'
        );
        /**
         * @var $post_type
         * @var $schedule
         * @var $post_time
         * @var $timezone
         * @var $status
         */
        extract(array_merge($ext, $val));

        unset($val['accounts']);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $val['text'] = $emojione->toShort($val['text']);
        $data = perfectSerialize($val);

        $post_time = convertTimeByTimezone($post_time, false, $account);

        $this->db->query("UPDATE posts SET type=?,type_data=?,schedule_date=? WHERE id=?", $post_type, $data, $post_time, $id);
        return true;
    }

    public function publish($postId) {
        return true;
    }

    public function updateStatus($postId, $status) {
        return $this->db->query("UPDATE posts SET status=? WHERE id=?", $status, $postId);
    }
}