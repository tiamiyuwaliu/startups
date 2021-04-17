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

    public function addGas($val) {
        /**
         * @var $label
         * @var $location
         * @var $size
         * @var $time
         * @var $state
         * @var $design
         */
        extract($val);
        return $this->db->query("INSERT INTO cylinders (userid,label,location,use_time,size,state,design)VALUES(?,?,?,?,?,?,?)",
            model('user')->authId, $label, $location, $time, $size,$state,$design);
    }

    public function saveGas($val, $id) {
        /**
         * @var $label
         * @var $location
         * @var $size
         * @var $time
         * @var $state
         * @var $design
         */
        extract($val);
        return $this->db->query("UPDATE cylinders SET label=?,location=?,use_time=?,size=?,state=?,design=? WHERE id=?",
            $label, $location, $time, $size,$state,$design, $id);
    }

    public function getCylinders() {
        $query = $this->db->query("SELECT * FROM cylinders WHERE userid=?", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrders($status = 0) {
        $query = $this->db->query("SELECT * FROM orders WHERE userid=? AND order_status=? ORDER BY id DESC", model('user')->authId, $status);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agentOrders() {
        $agentLocation = explode(',', model('user')->authUser['agent_locations']);
        $newLocation = '';
        foreach($agentLocation as $l) {
            $newLocation .= ($newLocation) ? ",'$l'" :  "'$l'";
        }
        $agentLocation = $newLocation;
        $query = $this->db->query("SELECT * FROM orders WHERE  order_status=? AND order_location_state IN ($agentLocation) AND  (order_agent =? OR order_agent=?) ORDER BY id DESC",  0, 0, model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNewPendingOrders() {
        $agentLocation = explode(',', model('user')->authUser['agent_locations']);
        $newLocation = '';
        foreach($agentLocation as $l) {
            $newLocation .= ($newLocation) ? ",'$l'" :  "'$l'";
        }
        $agentLocation = $newLocation;
        $query = $this->db->query("SELECT * FROM orders WHERE order_status=? AND order_location_state IN ($agentLocation) AND  (order_agent =?) ORDER BY id DESC",  0, 0);
        return $query->rowCount();
    }

    public function getRecurringOrders() {
        $query = $this->db->query("SELECT * FROM orders WHERE userid=? AND recurring=? ORDER BY id DESC", model('user')->authId, 1);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addOrder($val) {
        /**
         * @var $type
         * @var $pay_type
         * @var $ref_id
         * @var $price
         * @var $location
         * @var $quantity
         * @var $title
         * @var $recurring
         * @var $recurring_after
         * @var $quantity_type
         * @var $state
         *
         */
        extract($val);
        return $this->db->query("INSERT INTO orders (order_location_state,order_quantity_type,userid, order_type,pay_type, ref_id,order_price,order_location,order_quantity,order_date,order_title,recurring,recurring_after)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",$state, $quantity_type,model('user')->authId, $type,$pay_type,$ref_id,$price,$location,$quantity,time(),$title,$recurring, $recurring_after);
    }

    public function getWalletHistory() {
        $query = $this->db->query("SELECT * FROM wallet_history WHERE userid=? ORDER BY id DESC", model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fcmTokenExist($fcm) {
        $query = $this->db->query("SELECT * FROM fcm_tokens WHERE token=? AND userid=?", $fcm, model('user')->authId);
        return $query->rowCount();
    }

    public function saveFCMToken($fcm) {
        if ($this->fcmTokenExist($fcm)) return true;
        return $this->db->query("INSERT INTO fcm_tokens (userid,token)VALUES(?,?)", model('user')->authId, $fcm);
    }

    public function sendPush($users, $title, $message) {
        $users = implode(',', $users);
        $query = $this->db->query("SELECT * FROM fcm_tokens WHERE userid IN ($users)");

        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {

            $this->sendPushNotification($fetch['token'], array(
                'title' => $title,
                'body' => $message
            ));
        }
    }

    public function getAgentsInState($state) {
        $query = $this->db->query("SELECT * FROM users WHERE is_agent=? AND agent_locations LIKE '%$state%'", 1);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrder($id) {
        $query = $this->db->query("SELECT * FROM orders WHERE id=? ", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function sendPushNotification($to = '', $data = array()){

        $api_key = 'AAAA4Vx6Mxs:APA91bH8LqC10sV_C0aTmio5PPt8HnOhUGcoLJOH91o8vc1F-LY6sjDugLN_3JZDt52bZvHwZyb64RYiaELtpKChZ38KQEq4EU2R5PchRZj9GQ_tuKi8H1JQDY7M5RDhH29VSZkR6VHb';
        $fields = array('to' => $to, 'notification' => $data);

        $headers = array(
            'Content-Type:application/json',
            'Authorization:key='.$api_key
        );
        $url = 'https://fcm.googleapis.com/fcm/send';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        print_r($result);
        return $result;
    }

    public function findReferral($userid){
        $query = $this->db->query("SELECT * FROM referrals WHERE userid=? AND status=?", $userid, 0);
        return $query->fetch(PDO::FETCH_ASSOC);
    }


}