<?php
class ApiModel extends Model {
    public function findKey($token) {
        $query = $this->db->query("SELECT * FROM mobile_keys WHERE token=?", $token);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function addToken($userid, $device) {
        $token = md5(time().$userid.$device.'dsjdskjksdf');
        $this->db->query("INSERT INTO mobile_keys (userid,token,device,created)VALUES(?,?,?,?)", $userid, $token, $device, time());
        return $token;
    }
}