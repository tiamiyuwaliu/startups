<?php
require_once path('app/vendor/autoload.php');
require_once path('app/vendor/instagram-php/autoload.php');

class SocialModel extends Model {
    private $instagram;

    public function getObject() {

        if ($this->instagram) return $this->instagram;
        $this->instagram = new \InstagramAPI\Instagram(false,false, array(
            'storage' => 'mysql',
            'dbhost' => config('db_host'),
            'dbname' => config('db_name'),
            'dbusername' => config('db_username'),
            'dbpassword' => config('db_password'),
            'dbtablename' => "instagram_sessions",
        ));

        $this->instagram->setVerifySSL(false);
        return $this->instagram;
    }

    public function login($account) {
        $instagram =  $this->getObject();
        try {
            $instagram->login($account['username'], mDcrypt($account['password']));
        } catch (Exception $e) {
            //there is problem login user to disable this account here
            print_r($e);
        }

        return $this;
    }



    public function setProxy() {

    }

    function getCurrentUser(){
        try {
            $user = $this->instagram->account->getCurrentUser();
            return json_decode($user);
        } catch (\Exception $e) {
        }
    }

    public function getSelfInfo() {
        try {
            $user = $this->instagram->people->getSelfInfo();
            return json_decode($user);
        } catch (\Exception $e) {
        }
    }

    public function addAccount($user, $password,  $proxy = '') {
        $this->db->query("INSERT INTO accounts (userid,username,full_name, password,user_pk,avatar,proxy,created)VALUES(?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId,$user->user->username,$user->user->full_name,$password, $user->user->pk,$this->getAvatar($user), $proxy, time());
    }

    public function updateAccount($user, $account, $password, $proxy = '') {
        /**
         * In other to prevent duplicate of instagram photo lets always delete previous ones
         */
       if (!preg_match('#user-avatar.png#', $account['avatar'])) {
           delete_file($account['avatar']);
       }

       return $this->db->query("UPDATE accounts SET username=?,full_name=?,password=?,user_pk=?,avatar=?,proxy=? WHERE id=?",
           $user->user->username,$user->user->full_name,$password, $user->user->pk,$this->getAvatar($user), $proxy, $account['id']);

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



    public function findAccount($username) {
        $query = $this->db->query("SELECT * FROM accounts WHERE (username=? OR id=?) AND userid=?", $username, $username, model('user')->authOwnerId);
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

    public function getAccounts($except = null, $term = null) {
        $sql = "SELECT * FROM accounts WHERE userid=? ";
        $param  = array(model('user')->authOwnerId);
        if ($except) {
            $sql .= " AND id!=? ";
            $param[] = $except;
        }
        if ($term) {
            $sql .= " AND (username LIKE '%$term%' OR full_name LIKE '%$term%')";
        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function firstAccount() {
        $accounts = $this->getAccounts();
        if ($accounts) return $accounts[0];
        return null;
    }

    public function findOneActive() {
        $query = $this->db->query("SELECT * FROM accounts WHERE userid=? AND status=? ORDER BY rand() LIMIT 1", model('user')->authOwnerId, 1);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function sync($medias,$followers,$following, $id) {
        $this->db->query("UPDATE accounts SET posts=?,followers=?,following=?,last_sync=? WHERE id=?", $medias, $followers, $following,time(), $id);
    }

    public function deleteAccount($id) {
        $account = $this->findAccount($id);
        delete_file($account['avatar']); // delete the avatar on the system to prevent duplicate of files
        $this->db->query("DELETE FROM accounts WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
        $this->db->query("DELETE FROM instagram_sessions WHERE username=?", $account['username']);
        //we need to this other activities as well
        return true;
    }
}