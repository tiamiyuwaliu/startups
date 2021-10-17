<?php
class PartyModel extends Model {
    public function getTemplates($limit = 50, $term = "") {
        $sql = "SELECT * FROM parties WHERE userid=? AND workspace_id=?  ";
        if ($term ) {
            $sql .= " AND title LIKE '%$term%'";
        }
        $sql .= " ORDER BY id DESC";
        $param = array($this->model('user')->authOwnerId, $this->controller()->workspaceId);
        return $this->db->paginate($sql, $param,$limit );
    }

    public function getSharedIds() {
        $query = $this->db->query("SELECT party_id FROM parties_shared WHERE user_id=?", $this->model('user')->authId);
        $ids = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['party_id'];
        }
        return $ids;
    }
    public function getShareTemplates($limit = 0) {
        $ids = implode(',', $this->getSharedIds());
        $sql = "SELECT * FROM parties WHERE id IN ($ids) AND id!=?  ORDER BY id DESC";
        $param = array('');
        return $this->db->paginate($sql, $param,$limit );
    }

    public function getParties($limit = 50, $term = null) {
        $sql = "SELECT * FROM parties_scheduled WHERE userid=? AND workspace_id=? ";
        if ($term) {
            $sql .= " AND title LIKE '%$term%'";
        }
        $sql .= "ORDER BY id DESC";
        $param = array($this->model('user')->authOwnerId, $this->controller()->workspaceId);
        return $this->db->paginate($sql, $param,$limit );
    }

    public function getAllTemplates() {
        $sql = "SELECT * FROM parties WHERE userid=? AND workspace_id=?";
        $param = array($this->model('user')->authOwnerId, $this->controller()->workspaceId);
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countPosts($partyId, $unique = false) {
        if($unique) {
            $query = $this->db->query("SELECT DISTINCT day_number FROM parties_template_posts WHERE party_id=?", $partyId);
        } else {
            $query = $this->db->query("SELECT * FROM parties_template_posts WHERE party_id=?", $partyId);
        }
        return $query->rowCount();
    }
    public function addTemplate($val) {
        $ext = array(
            'title' => '',
            'color' => '',
            'medias' => '',
            'template_id' => '',
            'party_id' => ''
        );
        /***
         * @var $title
         * @var $color
         * @var $medias
         * @var $template_id
         * @var $party_id
         */
        extract(array_merge($ext, $val));
        $key = uniqueKey(10);
        $this->db->query("INSERT INTO parties (userid,workspace_id,action_userid,title,unique_key,color,created_at)VALUES(?,?,?,?,?,?,?)",
        $this->model('user')->authOwnerId,$this->controller()->workspaceId, $this->model('user')->authId, $title, $key, $color,time());
        $id = $this->db->lastInsertId();
        if ($medias) {
            $medias = explode(',', $medias);
            foreach($medias as $media) {
                $this->addTemplatePost(null,array($media), $id);
            }
        }

        if ($template_id) {
            $posts = $this->getTemplatePosts($template_id);
            foreach($posts as $post) {
                $medias = perfectUnserialize($post['medias']);
                $this->addTemplatePost($post['caption'],$medias, $id, $post['schedule_time'], $post['day_number']);
            }
        }

        if ($party_id) {
            $posts = $this->getPartyPosts($party_id);
            $dates = array();
            $days = 0;
            foreach($posts as $post) {
                $date = date('m/d/Y', $post['schedule_time']);
                if (!isset($dates[$date])) {
                    $days++;
                    $dates[$date] = $days;
                }
                $time = date('H:i', $post['schedule_time']);
                $medias = perfectUnserialize($post['medias']);
                $this->addTemplatePost($post['caption'],$medias, $id, $time, $days);
            }
        }
        $key = $key.$id;
        $this->db->query("UPDATE parties SET unique_key=? WHERE id=?", $key, $id);
        return $key;
    }

    public function createParty($val) {
        $ext = array(
            'title' => '',
            'template' => '',
            'type' => '',
            'accounts' => array(),
            'date' => '',
            'timezone' =>  '',
            'color' => '#000',
            'replaces' => array(),
            'medias' => '',
            'group' => array()
        );
        /***
         * @var $title
         * @var $template
         * @var $type
         * @var $accounts
         * @var $date
         * @var $timezone
         * @var $replaces
         * @var $color
         * @var $medias
         * @var $group
         */
        extract(array_merge($ext, $val));
        $key = uniqueKey(10);
        $status = 0;
        if ($val['type'] == 2) {
            $partyTemplate = $this->findTemplate($template);
            $title = $partyTemplate['title'];
            $status = 1;
        }

        $accounts = perfectSerialize($accounts);
        $date = strtotime($date);
        $replaces = perfectSerialize($replaces);
        $group = perfectSerialize($group);
        $this->db->query("INSERT INTO parties_scheduled (color,title,userid,workspace_id,action_userid,account,account_pages,start_date,timezone,party_id,unique_key,replaces,status,created_at)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        $color,$title,$this->model('user')->authOwnerId,$this->controller()->workspaceId, $this->model('user')->authId, $accounts,$group,$date,$timezone,$template,$key,$replaces,$status, time());
        $id = $this->db->lastInsertId();
        if ($medias) {
            $medias = explode(',', $medias);
            foreach($medias as $media) {
                $this->addPartyPost(null,array($media), $id, $date);
            }
            $this->db->query("UPDATE parties_scheduled SET status=? WHERE id=?", 1, $id);
        }
        $key = $key.$id;
        $this->db->query("UPDATE parties_scheduled SET unique_key=? WHERE id=?", $key, $id);

        if ($template) {
            //lets populate the party posts now

            $this->populatePartyPosts($id, $date, $template);
            $this->db->query("UPDATE parties_scheduled SET status=? WHERE id=?", 1, $id);
        }
        return $key;
    }

    public function populatePartyPosts($id, $date, $template) {
        $length = 1;
        $nextDate = $date;
        $party = $this->findParty($id);
        $replaces = perfectUnserialize($party['replaces']);
        while($length <= 30) {
            $posts = $this->getTemplatePostsByDay($template, $length);

            foreach($posts as $post) {
                $time = strtotime(date('m/d/Y', $nextDate).' '.$post['schedule_time']);
                $caption = $post['caption'];
                if ($replaces['host']) $caption = str_replace("[host]", $replaces['host'], $caption);
                if ($replaces['link']) $caption = str_replace("[link]", $replaces['link'], $caption);
                $this->db->query("INSERT INTO parties_scheduled_posts (party_id,caption,medias,schedule_time)VALUES(?,?,?,?)",
                $party['id'],$caption,$post['medias'], $time);
            }
            $nextDate = $nextDate + 86400;
            $length++;
        }
    }

    public function saveTemplate($val, $id) {
        $ext = array(
            'title' => '',
            'color' => ''
        );
        /***
         * @var $title
         * @var $color
         */
        extract(array_merge($ext, $val));
        $this->db->query("UPDATE parties SET title=?,color=? WHERE id=?", $title,$color,$id);
        return true;
    }

    public function findByKey($key) {
        $query = $this->db->query("SELECT * FROM parties WHERE unique_key=?", $key);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findPartyByKey($key) {
        $query = $this->db->query("SELECT * FROM parties_scheduled WHERE unique_key=?", $key);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function findParty($key) {
        $query = $this->db->query("SELECT * FROM parties_scheduled WHERE id=?", $key);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findTemplate($key) {
        $query = $this->db->query("SELECT * FROM parties WHERE id=?", $key);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getTemplatePosts($partyId) {
        $query = $this->db->query("SELECT * FROM parties_template_posts WHERE party_id=? ORDER BY day_number ASC,schedule_time DESC", $partyId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTemplatePostsByDay($partyId, $day) {
        $query = $this->db->query("SELECT * FROM parties_template_posts WHERE party_id=? AND day_number=?", $partyId, $day);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPartyPosts($partyId) {
        $query = $this->db->query("SELECT * FROM parties_scheduled_posts WHERE party_id=? ORDER BY schedule_time ASC", $partyId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addTemplatePost($text, $medias, $partyId, $time = '1:00', $day = null) {
        $medias = perfectSerialize($medias);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $text = $emojione->toShort($text);
        $day = ($day) ? $day: $this->countPosts($partyId, true) + 1;
        $this->db->query("INSERT INTO parties_template_posts (party_id,caption,medias,schedule_time,day_number)VALUES(?,?,?,?,?)", $partyId, $text, $medias, $time,$day);
        $id =  $this->db->lastInsertId();

        return $id;
    }

    public function findTemplatePost($id) {
        $query = $this->db->query("SELECT * FROM parties_template_posts WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePartyTemplate($id) {
        $this->db->query("DELETE FROM parties WHERE id=?", $id);
        $this->db->query("DELETE FROM parties_template_posts WHERE party_id=?", $id);
    }


    public function addPartyPost($text, $medias, $partyId, $time = 0) {
        $medias = perfectSerialize($medias);
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $text = $emojione->toShort($text);

        $this->db->query("INSERT INTO parties_scheduled_posts (party_id,caption,medias,schedule_time)VALUES(?,?,?,?)", $partyId, $text, $medias, $time);
        return $this->db->lastInsertId();
    }

    public function findPartyPost($id) {
        $query = $this->db->query("SELECT * FROM parties_scheduled_posts WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteParty($id) {
        $this->db->query("DELETE FROM parties_scheduled WHERE id=?", $id);
        $this->db->query("DELETE FROM parties_scheduled_posts WHERE party_id=?", $id);
    }

    public function countPartyPosts($partyId, $status = 0) {
        $query = $this->db->query("SELECT id FROM parties_scheduled_posts WHERE party_id=? AND status=?", $partyId, $status);
        return $query->rowCount();
    }

    public function countAllPartyPosts($partyId) {
        $query = $this->db->query("SELECT id FROM parties_scheduled_posts WHERE party_id=?", $partyId);
        return $query->rowCount();
    }

    public function getRunningParties() {
        $query  = $this->db->query("SELECT * FROM parties_scheduled WHERE userid=? AND workspace_id=?",
            $this->model('user')->authOwnerId, $this->controller()->workspaceId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPartiesPendingPosts($partyId) {
        $query = $this->db->query("SELECT * FROM parties_scheduled_posts WHERE party_id=? AND status=? AND schedule_time !=?",
        $partyId, 0, 0);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSharedUsers($partyId) {
        $query = $this->db->query("SELECT *,parties_shared.id as shared_id FROM parties_shared LEFT JOIN users ON parties_shared.user_id = users.id WHERE party_id=?", $partyId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sharedUserExist($user, $id) {
        $query = $this->db->query("SELECT id FROM parties_shared WHERE party_id=? AND user_id=?", $id, $user);
        return $query->rowCount();
    }
    public function findSharedUser($user, $id) {
        $query = $this->db->query("SELECT * FROM parties_shared WHERE party_id=? AND user_id=?", $id, $user);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function findUser( $id) {
        $query = $this->db->query("SELECT *,parties_shared.id as shared_id FROM parties_shared LEFT JOIN users ON parties_shared.user_id = users.id WHERE parties_shared.id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function addShareUser($user, $partyId) {
        if (!$this->sharedUserExist($user,$partyId)) {
            $this->db->query("INSERT INTO parties_shared (user_id,party_id,joined)VALUES(?,?,?)", $user, $partyId,time());
        }
    }

    public function canEdit($party) {
        if ($party['userid'] == $this->model('user')->authId) return true;
        if ($this->model('workspace')->isAMember()) return true;
        if ($user = $this->findSharedUser($this->model('user')->authId, $party['id']))  {
            if ($user['party_permission'] == 1) return true;
        }
        return false;
    }

    public function getPendingPosts() {
        $time  = time() + (60 * 60 *10);
        $query = $this->db->query("SELECT * FROM parties_scheduled_posts WHERE schedule_time < $time AND schedule_time !=? AND status=? ", 0,0);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setPublished($post) {
        $this->db->query("UPDATE parties_scheduled_posts  SET status=? WHERE id=?", 1, $post['id']);
    }

    public function countSearch($term, $type ) {
        if ($type == 'templates') {
            $query =  $this->db->query("SELECT id FROM parties WHERE title LIKE '%$term%' AND workspace_id=?", $this->controller()->workspaceId);

        } else {
            $query =  $this->db->query("SELECT id FROM parties_scheduled WHERE title LIKE '%$term%' AND workspace_id=?", $this->controller()->workspaceId);

        }
        return $query->rowCount();
    }
}