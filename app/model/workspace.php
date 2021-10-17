<?php
class WorkspaceModel extends Model {

    public function save($val) {
        $ext = array(
            'title' => '',
            'timezone' => '',
            'id' => '',
            'approval' => 0
        );
        /**
         * @var $title
         * @var $timezone
         * @var $id
         * @var $approval
         */
        extract(array_merge($ext, $val));
        if (!model('user')->canDoTeam()) $approval = 0;
        return $this->db->query("UPDATE workspace SET title=?,timezone=?,approval=? WHERE id=? AND userid=?", $title,$timezone, $approval, $id, $this->model('user')->authId);
    }

    public function create($val) {
        $ext = array(
            'title' => '',
            'timezone' => '',
        );
        /**
         * @var $title
         * @var $timezone
         */
        extract(array_merge($ext, $val));
        $this->db->query("INSERT INTO workspace (userid,title,timezone,created_at)VALUES(?,?,?,?)",$this->model('user')->authId,$title, $timezone,time());
        return  $this->db->lastInsertId();
    }

    public function invite($val) {
        $ext = array(
            'email' => '',
            'name' => '',
            'permission' => '',
            'type' => '',
            'id' => ''
        );
        /**
         * @var $email
         * @var $name
         * @var $permission
         * @var $type
         * @var $id
         */
        extract(array_merge($ext, $val));
        $user = $this->addUser($val);
        if ($this->userExists($user['id'], $id)) return false;

        $code = md5(time());
        $this->db->query("INSERT INTO workspace_users (assign_userid,workspace_id,permission,user_type,invite_code,created_at) VALUES(?,?,?,?,?,?)",
        $user['id'],$id,$permission,$type,$code,time());
        $id = $this->db->lastInsertId();
        if ($type == 2) {
            $slug = uniqueKey(10, 15);
            $this->db->query("UPDATE workspace_users SET slug=? WHERE id=?", $slug, $id);
        }

        //send invitatation
        $this->sendInvitation($id);
        return $this->findMember($id);
    }

    public function sendInvitation($id) {
        $member = $this->findMember($id);
        $email = Email::getInstance();
        $email->setAddress($member['email'], $member['full_name']);
        $email->setSubject("Timably - Your are invited to collaborate!");
        $workspace = $this->find($member['workspace_id']);
        $email->template("invite-member", array(
            'invitee' => model('user')->authUser['full_name'],
            'workspace' => $workspace['title'],
            'link' => ($member['user_type'] == 2) ? url('client/'.$member['slug']) : url('join/workspace/'.$member['invite_code'])
        ));
        $email->send();
        return true;
    }

    public function addUser($val) {
        $user = $this->model('user')->findUserByEmail($val['email']);
        if ($user) return $user;
        $this->db->query("INSERT INTO users (full_name,email,changed,created)VALUES(?,?,?,?)",
        $val['name'], $val['email'], time(), time());
        return $this->model('user')->getUser($this->db->lastInsertId());
    }

    public function userExists($userid, $workspaceId) {
        $query = $this->db->query("SELECT id FROM workspace_users WHERE assign_userid=? AND workspace_id=?", $userid, $workspaceId);
        return $query->rowCount();
    }

    public function isAMember() {
        return $this->userExists($this->model('user')->authId, $this->controller()->workspaceId);
    }

    public function findMember($id) {
        $query = $this->db->query("SELECT *,users.id as uid,workspace_users.permission as pm FROM workspace_users LEFT JOIN users ON workspace_users.assign_userid=users.id WHERE workspace_users.id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getMembers($id) {
        $query = $this->db->query("SELECT *,users.id as uid,workspace_users.id as wid,workspace_users.permission as pm  FROM workspace_users LEFT JOIN users ON workspace_users.assign_userid=users.id WHERE workspace_id=?", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteMember($id) {
        $this->db->query("DELETE FROM workspace_users WHERE id=?", $id);
    }

    public function getPermissionName($user) {
        switch($user['pm']) {
            case 1:
                return l('contributor');
                break;
            case 2:
                return l('approver');
                break;
            case 3:
                return l('writer');
                break;
            case 4:
                return l('client');
                break;
        }
    }

    public function getMyWorkspace() {
        $query = $this->db->query("SELECT * FROM workspace WHERE userid=?", $this->model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignedWorkspace() {
        $query = $this->db->query("SELECT *,workspace_users.id AS wid FROM workspace_users LEFT JOIN workspace ON workspace.id=workspace_users.workspace_id WHERE workspace_users.assign_userid=? ORDER BY workspace_users.id DESC ",
        $this->model('user')->authId);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allWorkspaces() {

        $workspaces = $this->getMyWorkspace();
        $workspaces = array_merge($workspaces, $this->getAssignedWorkspace());
        return $workspaces;
    }

    public function countAccounts($id) {
        $query = $this->db->query("SELECT id FROM accounts WHERE workspace_id=?", $id);
        return $query->rowCount();
    }

    public function countUsers($id) {
        $query = $this->db->query("SELECT id FROM workspace_users WHERE workspace_id=?", $id);
        return $query->rowCount();
    }

    public function countWorkspace() {
        $query = $this->db->query("SELECT id FROM workspace WHERE userid=?", $this->model('user')->authId);
        return $query->rowCount();
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM workspace WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByCode($id) {
        $query = $this->db->query("SELECT * FROM workspace_users WHERE invite_code=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getWorkspaceData($id) {
        $workspace = $this->find($id);
        if ($workspace['userid'] == $this->model('user')->authId)return $id;
        if ($this->userExists($this->model('user')->authId, $workspace['id'])) return $id;
        return false;
    }

    public function canCreate() {
        $user = $this->model('user')->authUser;
        if ($user['role'] == 1) return 1;
        if ($user['workspace_limit'] == 0) return 1;
        if ($user['package'] == 'team') return 1;
    }
}