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

    public function setProxy() {

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

    public function getAccounts() {
        $query = $this->db->query("SELECT * FROM accounts WHERE userid=?", model('user')->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function firstAccount() {
        $accounts = $this->getAccounts();
        if ($accounts) return $accounts[0];
        return null;
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