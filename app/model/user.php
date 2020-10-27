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
        $this->authOwnerId = ($result['is_team']) ? $result['is_team'] : $this->authId;
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
        $this->authOwnerId = ($result['is_team']) ? $result['is_team'] : $this->authId;
        $this->getOwner();
        $this->saveData($result['id'], $result['password']);
        return true;
    }

    function loginWithObject($result) {
        $this->authId = $result['id'];
        $this->authUser = $result;
        $this->authOwnerId = ($result['is_team']) ? $result['is_team'] : $this->authId;
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



        if (!$noActivate) {
            if ($active == 0) {
                $this->sendActivationLink($userid, $email, $full_name);
            } else {
                $this->sendWelcomeMail($email, $full_name);
            }
            Hook::getInstance()->fire('user.signup.success', null, array($userid, $val));

        } else {
            $this->sendWelcomeMail($email, $full_name);
            $this->db->query("UPDATE users SET status=? WHERE id=?", 1, $userid);
        }
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

        return Email::getInstance()->setAddress($email, $full_name)
            ->setSubject(config('reset-subject'), array('full_name' => $full_name))
            ->setMessage(config('reset-content'), array( 'full_name' => $full_name, 'reset_link' => $link))
            ->send();
    }

    public function sendWelcomeMail($email, $full_name) {
        if (!config('enable-welcome-mail',false)) return false;
        return Email::getInstance()->setAddress($email, $full_name)
            ->setSubject(config('welcome-subject'), array('full_name' => $full_name))
            ->setMessage(config('welcome-content'), array( 'full_name' => $full_name))
            ->send();
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

    public function getNameLetters($user = null) {
        $user = ($user) ? $user : $this->authUser;
        $explode = explode(' ', $user['full_name']);
        if (count($explode) > 1) {
            list($first, $second) = $explode;
        } else {
            $first = $user['full_name'];
        }
            $letters = mb_substr($first, 0, 1);
        if (isset($second)) $letters .= mb_substr($second, 0, 1);
        return mb_strtoupper($letters);
    }

    public function getAvatar($user = null) {
        $user = ($user) ? $user : $this->authUser;

        if (!$user['avatar']) return assetUrl('assets/images/user-avatar.png');
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
            'date_format' => ''
        );

        /**
         * @var $full_name
         * @var $email
         * @var $timezone
         * @var $date_format
         */
        extract(array_merge($ext, $val));

        return $this->db->query("UPDATE users SET full_name=?,email=?,timezone=?,date_format=? WHERE id=?", $full_name,$email,$timezone,$date_format, $this->authId);
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

    public function getPermissions() {
        $permissions =  perfectUnserialize($this->authOwner['permission']);
        return (empty($permissions)) ? array() : $permissions;
    }



    public function hasPermission($key, $default = null) {
        $permissions = $this->getPermissions();
        if (empty($permissions)) {
            if ($default ) return $default;
            return true;
        }
        if (isset($permissions[$key])) return $permissions[$key];
        return ($default) ? $default : false;
    }

    public function permission($key, $default = null) {
        $permissions = $this->getPermissions();
        if (empty($permissions)) {
            if ($default ) return $default;
            return true;
        }
        if (isset($permissions[$key])) return $permissions[$key];
        return ($default) ? $default : false;
    }


    public function getTotalSize() {
        $size = $this->hasPermission('storage');
        return (!$size) ? 'unlimited' : $size;
    }

    public function getUsedSize() {
        $query = $this->db->query("SELECT SUM(file_size) as size FROM files WHERE userid=?", $this->authOwnerId);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return  ($result) ? round($result['size'] / 1000) : 0;
    }

    public function getAllowSize() {
        if(!$this->isLoggedin()) return 0;
        $permissions = $this->getPermissions();
        $size = (isset($permissions['file_size'])) ? $permissions['file_size']: null;
        if (!$size) return 1000;
        return $size;
    }

    public function canUpload() {
        $used = $this->getUsedSize();
        $total = $this->getTotalSize();
        if ($total == 'unlimited') return true;
        if ($used < $total) return true;
        return false;
    }

    public function saveTeam($val, $id = null) {
        $ext = array(
            'name' => '',
            'email' => '',
            'permission' => array()
        );
        /**
         * @var $name
         * @var $email
         * @var $permission
         */
        extract(array_merge($ext, $val));

        $permission = perfectSerialize($permission);
        if ($id) {
            $this->db->query("UPDATE user_team SET permissions=? WHERE id=?", $permission, $id);
        } else {
            if ($this->teamExists($email)) return false;

            $this->db->query("INSERT INTO user_team (name,email,ownerid,permissions)VALUES(?,?,?,?)",
                $name,$email,$this->authOwnerId,$permission);
            $this->sendInviteCode($this->db->lastInsertId());
            return true;
        }
    }

    public function findTeam($id) {
        $query = $this->db->query("SELECT * FROM user_team WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function findTeamByCode($code) {
        $query = $this->db->query("SELECT * FROM user_team WHERE invite_code=?", $code);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function sendInviteCode($id) {
        $team = $this->findTeam($id);
        $name = $team['name'];
        $email = $team['email'];
        //send invite code
        $code = generateHash($name.$email.time());

        $link = url('activate/invite/'.$code);

        $this->db->query("UPDATE user_team SET invite_code=? WHERE id=?", $code, $id);
        return Email::getInstance()->setAddress($email, $name)
            ->setSubject(l('invite-member-subject', array('name' => $this->authOwner['full_name'], 'site' => config('site-title', 'SmartPost'))))
            ->setMessage(l('invite-member-message',array( 'name' => $name, 'link' => $link)))
            ->send();
    }

    public function deleteTeam($id) {
        return $this->db->query("DELETE FROM user_team WHERE id=? AND ownerid=?", $id, $this->authOwnerId);
    }

    public function teamExists($email) {
        $query = $this->db->query("SELECT * FROM user_team WHERE ownerid=? AND email=?", $this->authOwnerId, $email);
        return $query->rowCOunt();
    }

    public function getTeamMembers() {
        $query = $this->db->query("SELECT * FROM user_team WHERE ownerid=?", $this->authOwnerId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setOwnerId($id, $userid) {
        $this->db->query("UPDATE users SET is_team=? WHERE id=?", $id, $userid);
        return $this->db->query("UPDATE user_team SET last_active_time=? WHERE ownerid=? AND userid=?", time(), $id, $userid);
    }

    public function activateTeamMember($team, $user) {
        $this->setOwnerId($team['ownerid'], $user['id']);
        return $this->db->query("UPDATE user_team SET status=?,last_active_time=?,userid=? WHERE id=?", 1,time(),$user['id'], $team['id']);
    }

    public function isOriginalOwner() {
        return ($this->authId == $this->authOwnerId);
    }

    public function findTheTeam() {
        $query = $this->db->query("SELECT * FROM user_team WHERE userid=? AND ownerid=?", $this->authId, $this->authOwnerId);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function teamCanUse($p) {
        $team = ($this->team) ? $this->team : $this->findTheTeam();
        $permission = perfectUnserialize($team['permissions']);
        return $permission[$p];
    }

    public function countTeamMember() {
        $query = $this->db->query("SELECT * FROM user_team WHERE ownerid=?", $this->authOwnerId);
        return $query->rowCount();
    }
    public function isTeamMEmber() {
        $query = $this->db->query("SELECT * FROM user_team WHERE userid=?", $this->authId);
        return $query->rowCount();
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
}