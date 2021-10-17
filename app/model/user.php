<?php
class UserModel extends Model {
    public $authId;
    public $authUser;
    public $authOwnerId;
    public $authOwner;
    public $team;

    function isLoggedin() {
        return $this->authId;
    }

    function getUserid() {
        return $this->authId;
    }

    function isAdmin() {
        if ($this->authUser and $this->authUser['role'] == 1) return true;
        return false;
    }

    function getOwner() {
        if ($this->authId == $this->authOwnerId) {
            $this->authOwner = $this->authUser;
            return $this->authUser;
        }
        if ($this->authOwner) return $this->authOwner;
        return $this->authOwner = $this->getUser($this->authOwnerId);
    }

    function getUser($id = null) {
        if ($id) {
            $query = $this->db->query("SELECT * FROM users WHERE (id=? OR email=?) ", $id,$id);
            return $query->fetch(PDO::FETCH_ASSOC);
        } else {
            return  $this->authUser;
        }
    }

    function findByActivationCode($code) {
        $query = $this->db->query("SELECT * FROM users WHERE (activation_code=?) ", $code);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    function findUserByEmail($email) {
        $query = $this->db->query("SELECT * FROM users WHERE (email=? AND status=?) ", $email, 1);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    function findByResetCode($code) {
        $query = $this->db->query("SELECT * FROM users WHERE (recovery_code=?) ", $code);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    function checkemail_address($email_address, $id = null) {
        $sql = "SELECT id FROM users WHERE email=?";
        $param = array($email_address);
        if ($id) {
            $sql .= " AND id != ?";
            $param[] = $id;
        }

        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    function processLogin() {

        $loginId = "";
        $password = "";
        if (isset($_COOKIE['loginid']) and isset($_COOKIE['user_token'])) {
            $loginId = $_COOKIE['loginid'];
            $password = $_COOKIE['user_token'];
        }
        if (isset($_SESSION['loginid']) and isset($_SESSION['user_token'])) {
            $loginId = $_SESSION['loginid'];
            $password = $_SESSION['user_token'];
        }

        if (!$loginId) return false;
        $query = $this->db->query("SELECT * FROM users WHERE id = ?", $loginId);
        $result = $query->fetch(\PDO::FETCH_ASSOC);
        if (!hash_check($result['password'], $password)) return false;
        //@TODO - Other processes for specific auth types
        $this->authId = $result['id'];
        $this->authUser = $result;
        $this->authOwnerId = $this->authId;
        $this->getOwner();

        $this->saveData($result['id'], $result['password'], $this->authOwnerId);
        return true;
    }

    function loginUser($email, $password) {
        $query = $this->db->query("SELECT * FROM users WHERE (email = ?)  ", $email);
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$result) return false;
        if (!hash_check($password, $result['password'])) return false;
        if ($result['status'] == 0) {
            $this->sendActivationLink($result['id'], $result['email'], $result['full_name']);
            exit(json_encode(array(
                'type' => 'error',
                'message' => l('please-activate-your-account')
            )));
        }
         $this->authId = $result['id'];
        $this->authUser = $result;
        $this->authOwnerId =  $this->authId;
        $this->getOwner();
        $this->saveData($result['id'], $result['password']);
        return true;
    }

    function loginWithObject($result) {
        $this->authId = $result['id'];
        $this->authUser = $result;
        $this->authOwnerId =  $this->authId;
        $this->getOwner();
        $this->saveData($result['id'], $result['password']);
    }

    function changePassword($new) {
        $password = hash_value($new);
        $this->db->query("UPDATE users SET password=? WHERE id=? ", $password, $this->authId);
        //refresh session data now
        $this->saveData($this->authId, $password);
    }

    function saveData($id, $password) {
        session_put("loginid", $id);
        session_put("user_token", hash_value($password));
        setcookie("loginid", $id, time() + 30 * 24 * 60 * 60, config('cookie_path'));
        setcookie("user_token", hash_value($password), time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
    }

    function logoutUser() {
        unset($_SESSION['loginid']);
        unset($_SESSION['user_token']);
        unset($_COOKIE['loginid']);
        unset($_COOKIE['user_token']);
        setcookie("loginid", "", 1, config('cookie_path'));
        setcookie("user_token", "", 1, config('cookie_path'));
    }


    public function addUser($val, $isAdmin = false, $noActivate = false) {
        $exp = array(
            'password' => '',
            'email' => '',
            'full_name' => '',
            'timezone' => ''
        );

        /**
         * @var $password
         * @var $email
         * @var $full_name
         * @var $timezone
         */
        extract(array_merge($exp, $val));


        $password = hash_value($password);
        $active = config('email-verification', false) ? 0 : 1;
        if ($isAdmin) $active = 1;
        $query = $this->db->query("INSERT INTO users (password,email,full_name,created,changed,status,timezone) VALUES(?,?,?,?,?,?,?)", $password,$email,$full_name,time(),time(), $active, $timezone);
        $userid = $this->db->lastInsertId();
        if ($isAdmin) {
            Hook::getInstance()->fire('admin.add.user', null, array($userid, $val));
        }
        $expireTime = time() + 864000;
        $this->db->query("UPDATE users SET expire_date=?,package=? WHERE id=?", $expireTime,'trial', $userid);

        try{
            $this->sendWelcomeMail($email, $full_name);
        } catch (Exception $e){}
        $this->db->query("UPDATE users SET status=? WHERE id=?", 1, $userid);
        return $userid;
    }

    public function adminEditUser($val, $id) {
        $exp = array(
            'password' => '',
            'email' => '',
            'full_name' => '',
            'timezone' => '',
            'role' => '0'
        );

        /**
         * @var $password
         * @var $email
         * @var $full_name
         * @var $timezone
         * @var $role
         */
        extract(array_merge($exp, $val));


        $password = ($password) ? hash_value($password) : '';
        $this->db->query("UPDATE users SET full_name=?,email=?,timezone=?,role=? WHERE id=?", $full_name, $email, $timezone, $role, $id);
        if ($password) {
            $this->db->query("UPDATE users SET password=? WHERE id=?",  $password, $id);
        }



        Hook::getInstance()->fire('admin.user.save', null, array($id, $val));
        return true;
    }
    public function sendActivationLink($userid, $email, $full_name) {
        $code = mEncrypt(''.time().'');
        $link = url('activate/'.$code);
        $this->db->query("UPDATE users SET activation_code=? WHERE id=?", $code, $userid);
        return Email::getInstance()->setAddress($email, $full_name)
            ->setSubject(config('activation-subject'), array('full_name' => $full_name))
            ->setMessage(config('activation-content'), array('site-name' => config('site-title', 'SmartPost'), 'full_name' => $full_name, 'activation_link' => $link))
            ->send();
    }

    public function sendResetLink($userid, $email, $full_name) {
        $code = mEncrypt(''.time().'');
        $link = url('reset/'.$code);
        $this->db->query("UPDATE users SET recovery_code=? WHERE id=?", $code, $userid);
        $email = Email::getInstance();
        $email->setAddress($email, $full_name);
        $email->setSubject("Timably - Reset your password!");
        $email->template("reset-password", array(
            'link' => $link
        ));
        return $email->send();

    }

    public function sendWelcomeMail($emailA, $full_name) {
        $email = Email::getInstance();
        $email->setAddress($emailA, $full_name);
        $email->setSubject("Timably - Welcome on board!");
        $email->template("welcome-email", array(
            'full_name' => $full_name
        ));
        return $email->send();
    }

    public function updatePassword($password, $userid) {
        return $this->db->query("UPDATE users SET password=? WHERE id=?", hash_value($password), $userid);
    }

    public function activateUser($user) {
        $this->db->query("UPDATE users SET status=? WHERE id=?", 1, $user['id']);
    }

    public function deleteUser($id) {
        if ($id == 1) return true;//to prevent deleting admin account
        $this->db->query("DELETE FROM accounts WHERE userid=?", $id);
        $this->db->query("DELETE FROM captions WHERE userid=?", $id);

        $query = $this->db->query("SELECT * FROM files WHERE userid=?", $id);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($fetch['resize_image']) {
                delete_file(path($fetch['resize_image']));
            }
            delete_file(path($fetch['file_name']));
        }

        $this->db->query("DELETE FROM instagram_analytics WHERE userid=?", $id);
        $this->db->query("DELETE FROM instagram_analytics_stats WHERE userid=?", $id);
        $this->db->query("DELETE FROM posts WHERE userid=?", $id);
        $this->db->query("DELETE FROM transactions WHERE userid=?", $id);
        $this->db->query("DELETE FROM users WHERE id=?", $id);
        return true;
    }

    public function enableUser($id) {
        $this->db->query("UPDATE users SET status=? WHERE id=?", 1, $id);
    }

    public function disableUser($id) {
        $this->db->query("UPDATE users SET status=? WHERE id=?", 0, $id);
    }

    public function getAllowFileSize(){
        return 30;
    }

    public function getSettings($key) {
        $settings = ($this->authOwner['data']) ? perfectUnserialize($this->authOwner['data']) : array();
        if (isset($settings[$key])) return $settings[$key];
        return '';
    }

    public function saveSettings($values) {
        $settings = ($this->authOwner['data']) ? perfectUnserialize($this->authOwner['data']) : array();
        $settings = array_merge($settings, $values);

        return $this->db->query("UPDATE users SET data=? WHERE id=?", perfectSerialize($settings), $this->authOwnerId);
    }

    public function getRecentUsers($limit = 5) {
        $query = $this->db->query("SELECT * FROM users ORDER BY id DESC LIMIT $limit");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotalUsers() {
        $query = $this->db->query("SELECT * FROM users ");
        return $query->rowCount();
    }

    public function getNameLetters($user = null) {
        $user = ($user) ? $user : $this->authUser;
        return $this->extractLetters($user['full_name']);
    }

    public function extractLetters($words) {
        $explode = explode(' ', $words);
        if (count($explode) > 1) {
            list($first, $second) = $explode;
        } else {
            $first = $words;
        }
        $letters = mb_substr($first, 0, 1);
        if (isset($second)) $letters .= mb_substr($second, 0, 1);
        return mb_strtoupper($letters);
    }

    public function getAvatar($user = null) {
        $user = ($user) ? $user : $this->authUser;

        if (!$user['avatar']) return assetUrl('assets/images/user-avatar.png');
        return url_img($user['avatar'], 200);
    }

    public function userData($field) {
        return (isset($this->authUser[$field])) ? $this->authUser[$field] : '';
    }

    public function emailExists($email) {
        $query = $this->db->query("SELECT id FROM users WHERE email=? AND id !=? ", $email, $this->authId);
        return $query->rowCount();
    }

    public function saveProfile($val) {
        $ext = array(
            'full_name' => '',
            'email' => '',
            'timezone' => '',
            'date_format' => '',
            'avatar' => $this->authUser['avatar']
        );

        /**
         * @var $full_name
         * @var $email
         * @var $timezone
         * @var $date_format
         * @var $avatar
         */
        extract(array_merge($ext, $val));

        return $this->db->query("UPDATE users SET full_name=?,email=?,timezone=?,date_format=?,avatar=? WHERE id=?", $full_name,$email,$timezone,$date_format,$avatar, $this->authId);
    }

    public function savePassword($val) {
        /**
         * @var $password
         */
        extract($val);

        $password = md5($password);
        $this->saveData($this->authUser['id'], $password);
        return $this->db->query("UPDATE users SET password=? WHERE id=?", $password, $this->authId);

    }

    public function canDoTeam() {
        $result = false;
        if ($this->model('workspace')->isAMember()) return true;
        //if (!$this->hasTried()) return false;
        if ($this->authOwner['package'] == 'team' or (!$this->authOwner['package'] )) $result = 1;
        return $result;
    }

    public function hasTried() {
        $result = false;
        return $this->model('workspace')->countWorkspace();

    }


    public function getSwitchAccounts() {
        $ids = array($this->authId);
        $query = $this->db->query("SELECT ownerid FROM user_team WHERE userid=?", $this->authId);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['ownerid'];
        }
        $ids = implode(',', $ids);
        $query = $this->db->query("SELECT * FROM users WHERE id IN ($ids)");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findReferral($userid = null) {
        $userid = ($userid) ? $userid : $this->authId;
        $query = $this->db->query("SELECT * FROM referral WHERE userid=?", $userid);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function enableReferral() {
        if (!$this->findReferral()) {
            $code = uniqueKey(20,20);
            $this->db->query("INSERT INTO referral(userid,referral_code)VALUES(?,?)", $this->authId, $code);
        }
    }

    public function getTotalReferrals() {
        $query = $this->db->query("SELECT * FROM referral_users WHERE userid=?", $this->authId);
        return $query->rowCount();
    }

    public function getReferrals() {
        $query = $this->db->query("SELECT * FROM referral_users WHERE userid=? ORDER BY id DESC", $this->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReferralIds() {
        $query = $this->db->query("SELECT * FROM referral_users WHERE userid=? ORDER BY id DESC LIMIT 50", $this->authId);
        $ids = array(0);
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $fetch['f_userid'];
        }
        return $ids;
    }

    public function countActiveReferrals() {
        $ids = $this->getReferralIds();
        $ids = implode(',', $ids);
        $time = time();
        $query = $this->db->query("SELECT * FROM users WHERE package!=? AND expire_date>$time AND id IN ($ids)", 'trial');
        return $query->rowCount();
    }

    public function getMyPayouts() {
        $query = $this->db->query("SELECT * FROM referral_payments WHERE userid=? ORDER BY id DESC", $this->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPayout($val) {
        $ext = array(
            'email' => '',
            'amount' => ''
        );
        /**
         * @var $email
         * @var $amount
         */
        extract(array_merge($ext, $val));
        $referral = $this->findReferral();
        if ($amount < 20 ) return false;
        if ($amount > $referral['balance']) return false;
        $this->db->query("INSERT INTO referral_payments (userid,amount,created)VALUES(?,?,?)", $this->authId, $amount, time());
        $newBalance = $referral['balance'] - $amount;
        $this->db->query("UPDATE referral SET paypal_email=?,balance=? WHERE userid=? ", $email, $newBalance, $this->authId);
        return true;
    }

    public function findReferralByCode($code) {
        $query = $this->db->query("SELECT * FROM referral WHERE referral_code=?", $code);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function processReferral($userid, $amount) {
        $query = $this->db->query("SELECT * referral_users WHERE f_userid=?", $userid);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $referralUser = $this->model('user')->getUser($result['userid']);
            $referral = $this->findReferral($referralUser['id']);
            $percent = ($result['status']) ? $referralUser['referral_bonus'] : $referralUser['referral_rbonus'];
            $gain = ($amount * $percent) / 100;
            $balance = $referral['balance'] + $gain;
            $this->db->query("UPDATE referral_users SET last_payment=? WHERE id=?", time(), $result['id']);
            $this->db->query("UPDATE referral SET balance=? WHERE id=?", $balance, $referral['id']);
        }
    }
}