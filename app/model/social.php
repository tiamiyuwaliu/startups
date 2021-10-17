<?php
require_once path('app/vendor/autoload.php');
require_once path('app/vendor/instagram-php/autoload.php');

class SocialModel extends Model {
    public function addAccount($socialType,$sid,$username,$token,$avatar = '', $profileType = '') {
        if ($account = $this->findAccountBySID($sid, $socialType, $profileType)) {
            $this->db->query("UPDATE accounts SET username=?,avatar=?,social_token=? WHERE id=?", $username,$avatar,$token, $account['id']);
            return $account['id'];
        } else {
            $this->db->query("INSERT INTO accounts (userid,action_userid,workspace_id,username,social_type,social_id,social_token,social_account_type,avatar,created)VALUES(?,?,?,?,?,?,?,?,?,?)",
                $this->model('user')->authOwnerId,$this->model('user')->authId, $this->controller()->workspaceId,$username,$socialType,$sid,$token,$profileType,$avatar,time());
            return $this->db->lastInsertId();
        }


    }

    public function canAdd() {
        return true;
    }
    public function getAvatar($user) {
        if (isset($user->user->profile_pic_url)) {
            $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($user->user->username).'.jpg';
            getFileViaCurl($user->user->profile_pic_url, $file);
            return $file;
        }
        return 'assets/images/user-avatar.png';
    }

    public function deletePageGroups($lastId) {
        return $this->db->query("DELETE  FROM accounts_page_groups WHERE group_id=? AND userid=?", $lastId, $this->model('user')->authOwnerId);
    }

    public function findPageGroup($id) {
        $query = $this->db->query("SELECT *  FROM accounts_page_groups WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getGroupPages($id) {
        $query = $this->db->query("SELECT * FROM accounts_page_groups WHERE group_id=?", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPageGroup($groupId, $pageId, $token, $name) {
        return $this->db->query("INSERT INTO accounts_page_groups (userid,page_id,group_id,name,token)VALUES(?,?,?,?,?)",
        $this->model('user')->authOwnerId, $pageId, $groupId,$token,$name);
    }

    public function findAccount($username) {
        $query = $this->db->query("SELECT * FROM accounts WHERE (username=? OR id=?) AND userid=?", $username, $username, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findAccountBySID($sid, $socialType, $profileType = '') {
        $query = $this->db->query("SELECT * FROM accounts WHERE (social_id=? AND social_type=? AND social_account_type=?) AND userid=? AND workspace_id=?", $sid, $socialType, $profileType, model('user')->authOwnerId, $this->controller()->workspaceId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * @alias findAccount
     * @param $username
     * @return mixed
     */
    public function find($username) {
        $query = $this->db->query("SELECT * FROM accounts WHERE (username=? OR id=?) AND userid=?", $username, $username, model('user')->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getAccounts($except = null, $term = null, $type = 'all', $accountType =  '') {
        $sql = "SELECT * FROM accounts WHERE userid=? ";
        $param  = array(model('user')->authOwnerId);
        if ($except) {
            $sql .= " AND id!=? ";
            $param[] = $except;
        }
        if ($type !== 'all') {
            $sql .= " AND social_type=? ";
            $param[] = $type;
            if ($accountType) {
                $sql .= " AND social_account_type=? ";
                $param[] = $accountType;
            }

        }
        if ($term) {
            $sql .= " AND (username LIKE '%$term%')";
        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function firstAccount() {
        $accounts = $this->getAccounts();
        if ($accounts) return $accounts[0];
        return null;
    }

    public function firstAccountId() {
        $account = $this->firstAccount();
        if ($account) return $account['id'];
        return false;
    }

    public function findOneActive() {
        $query = $this->db->query("SELECT * FROM accounts WHERE userid=? AND status=? ORDER BY rand() LIMIT 1", model('user')->authOwnerId, 1);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


    public function deleteAccount($id) {
        $account = $this->findAccount($id);
        delete_file($account['avatar']); // delete the avatar on the system to prevent duplicate of files
        $this->db->query("DELETE FROM accounts WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
         return true;
    }
}