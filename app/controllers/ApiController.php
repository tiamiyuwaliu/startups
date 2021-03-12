<?php
class ApiController  extends Controller {
    private $tokenValid  = false;
    private $userid = null;
    public function __construct($request)
    {
        parent::__construct($request);
        //check the keys
            $this->apiKey = config('api-key-token', 'DEFAULTAPIKEY');
        $receivedAPIKey = $this->request->input('key');
        $token = $this->request->input('token');

        if ($this->apiKey != $receivedAPIKey) exit(json_encode(array('status' => 0)));
        if ($token) {
            $mobileKey = $this->model('api')->findKey($token);
            if ($mobileKey)  {
                $this->tokenValid = true;
                $this->userid = $mobileKey['userid'];
                $user = $this->model('user')->getUser($this->userid);
                $this->model('user')->loginWithObject($user);
            }
        }
    }

    public function login() {
        $email = $this->request->input('username');
        $password = $this->request->input('password');
        $device = $this->request->input('device');

        if($login = $this->model('user')->loginUser($email, $password)) {
            $token = $this->model('api')->addToken($this->model('user')->authId, $device);
            $user = $this->model('user')->authUser;
            $user['avatar'] = ($user['avatar']) ? str_replace('%w', 200, $user['avatar']) : 'assets/images/user-avatar.png';
            return json_encode(array(
                'status' => 1,
                'token' => $token,
                'userid' => $this->model('user')->authId,
                'user' => $user
            ));
        } else {
            return json_encode(array('status' => 0));
        }
    }

    public function signup() {
        $email = $this->request->input('username');
        $password = $this->request->input('password');
        $device = $this->request->input('device');
        $fullname = $this->request->input('name');
        $timezone = $this->request->input('timezone');

        $user = $this->model('user')->getUser($email);
        if ($user) {
            return json_encode(array(
                'status' => 0
            ));
        }
        $userid = $this->model('user')->addUser(array(
            'email' => $email,
            'password' => $password,
            'full_name' => $fullname,
            'timezone' => $timezone
        ),false,false, true);
        $user = $this->model('user')->getUser($userid);
        $this->model('user')->loginWithObject($user);
        $token = $this->model('api')->addToken($this->model('user')->authId, $device);
        $user = $this->model('user')->authUser;
        $user['avatar'] = str_replace('%w', 200, $user['avatar']);
        return json_encode(array(
            'status' => 1,
            'token' => $token,
            'userid' => $this->model('user')->authId,
            'user' => $user
        ));
    }

    private function tokenRequired() {
        if (!$this->tokenValid) {
            exit(json_encode(array('status' => 0)));
        }
    }

    public function dashboard() {
        $this->tokenRequired();
    }

    public function saveGas() {
        $this->tokenRequired();
        $id = $this->request->input('id');

        $val = array(
            'label' => $this->request->input('label'),
            'location' => $this->request->input('location'),
            'time' => $this->request->input('time'),
            'size' => $this->request->input('size'),
            'state' => $this->request->input('state')
        );
        if ($id) {
            $this->model('api')->saveGas($val, $id);
        } else {
            $this->model('api')->addGas($val);
        }
        exit(json_encode(array('status' => 1)));
    }

    public function loadGas() {
        $this->tokenRequired();
        $cylinders = $this->model('api')->getCylinders();
        return json_encode($cylinders);
    }

    public function addOrder() {
        $this->tokenRequired();
        $val = array(
            'type' => $this->request->input('type'),
            'pay_type' => $this->request->input('paytype'),
            'price' => $this->request->input('price'),
            'ref_id' => $this->request->input('id'),
            'location' => $this->request->input('location'),
            'state' => $this->request->input('state'),
            'quantity' => $this->request->input('quantity'),
            'quantity_type' => $this->request->input('qtytype'),

            'title' => $this->request->input('title'),
            'recurring' => $this->request->input('recurring', 0),
            'recurring_after' => $this->request->input('recurringafter'),
        );

        $this->model('api')->addOrder($val);

        $agents  = $this->model('api')->getAgentsInState($this->request->input('state'));

        foreach($agents as $agent) {
            $name = $agent['full_name'];
            $this->model('api')->sendPush(array($agent['id']), "New Order", "Hello $name, you have new order , view if you can attend to it");
        }
        exit(json_encode(array('status' => 1)));
    }

    public function orders() {
        $this->tokenRequired();
        $status = $this->request->input('status', 0);
        if ($status == 2){
            $orders = $this->model('api')->getRecurringOrders($status);
        } else {
            $orders = $this->model('api')->getOrders($status);
        }
        $newOrders = array();
        foreach($orders as $order) {
            $order['order_date'] = date('F j, Y, g:i a', $order['order_date']);
            $order['completed_date'] = date('F j, Y, g:i a', $order['completed_date']);
            $newOrders[] = $order;
        }
        return json_encode($newOrders);
    }

    public function settings() {
        $this->tokenRequired();
        return json_encode(array(
            'gas_rate' => 400,
            'states' => array(
                'osogbo',
                'ede',
                'ilobu'
            )
        ));
    }

    public function walletBalance() {
        $this->tokenRequired();
        return json_encode(array(
            'balance' => $this->model('user')->authUser['wallet']
        ));
    }

    public function payWallet() {
        $this->tokenRequired();
        $amount = $this->request->input('amount');
        $wallet = $this->model('user')->authUser['wallet'];
        $newWallet = $wallet - $amount;

        Database::getInstance()->query("UPDATE users SET wallet=? WHERE id=?", $newWallet, $this->model('user')->authId);
        return json_encode(array(
            'balance' => $newWallet
        ));
    }

    public function saveProfile() {
        $this->tokenRequired();
        $phone = $this->request->input('phone');
        $avatar = $this->request->input('avatar');
        $name = $this->request->input('name');
        $password = $this->request->input('passsword');
        $oldPassword = $this->model('user')->authUser['password'];
        if ($password) $oldPassword = md5($password);
        if ($avatar) {

        } else {
            $avatar = $this->model('user')->authUser['avatar'];
        }

        Database::getInstance()->query("UPDATE users SET full_name=?,email=?,password=?,avatar=? WHERE id=?", $name, $phone,$oldPassword, $avatar, $this->model('user')->authId);
        return json_encode(array(
            'status' => 1
        ));
    }

    public function loadWallet() {
        $this->tokenRequired();
        $amount = $this->request->input('amount');
        $wallet = $this->model('user')->authUser['wallet'];
        $newWallet = $wallet + $amount;

        Database::getInstance()->query("UPDATE users SET wallet=? WHERE id=?", $newWallet, $this->model('user')->authId);
        Database::getInstance()->query("INSERT INTO wallet_history (userid,amount,time)VALUES(?,?,?)", $this->model('user')->authId, $amount, time());
        return json_encode(array(
            'balance' => $newWallet
        ));
    }

    public function walletHistory() {
        $this->tokenRequired();
        $walletHistory = $this->model('api')->getWalletHistory();
        $result = array();
        foreach($walletHistory as $history) {
            $history['time'] = date('F j, Y, g:i a', $history['time']);
            $result[] = $history;
        }
        return json_encode($result);
    }

    public function sendContact() {
        $this->tokenRequired();
        $phone = $this->request->input('phone');
        $email = $this->request->input('email');
        $name = $this->request->input('name');
        $message = $this->request->input('message');

        Email::getInstance()->setFrom("tiamiyuwaliu1212@gmail.com")
            ->setAddress("tiamiyuwaliu1212@gmail.com", 'SmartHome')
            ->setSubject($name.' send a message')
            ->setMessage("Hello <br/> You have a new message from {name} with the following phone and email <br/>
<strong>Phone Number:</strong> {phone}<br/>
<strong>Email:</strong> {email} <br/>
<strong>Message: </strong> {message}", array(
    'phone' => $phone,
                'email' => $email,
                'name' => $name,
                'message' => $message
            ))->send();

        return json_encode(array(
            'status' => 1
        ));
    }

    public function saveDeviceId() {
        $this->tokenRequired();
        $id = $this->request->input("id");
        Database::getInstance()->query("UPDATE users SET device_id=? WHERE id=?", $id, $this->model('user')->authId);
        return json_encode(array(
            'status' => 1
        ));
    }

    public function cancelOrder() {
        $this->tokenRequired();
        $id = $this->request->input('id');
        Database::getInstance()->query("UPDATE orders SET order_status=? WHERE id=? ", 3, $id);

        //send notifications to customer about the order
        $order = $this->model('api')->findOrder($id);
        $this->model('api')->sendPush(array($order['userid']), "Your order was cancelled", "Hello  your has been cancelled successfully");

        return json_encode(array(
            'status' => 1
        ));
    }

    public function acceptOrder() {
        $this->tokenRequired();
        if ($this->model('user')->authUser['is_agent'] == 0) return json_encode(array(
            'status' => 0
        ));
        $id = $this->request->input('id');
        Database::getInstance()->query("UPDATE orders SET order_agent=? WHERE id=? ", $this->model('user')->authId, $id);

        //send notifications to customer about the order
        $order = $this->model('api')->findOrder($id);
        $this->model('api')->sendPush(array($order['userid']), "Your order is now assigned", "Hello  an agent is assigned to your order and he/she will get in touch");

        return json_encode(array(
            'status' => 1
        ));
    }

    public function markOrder() {
        $this->tokenRequired();
        if ($this->model('user')->authUser['is_agent'] == 0) return json_encode(array(
            'status' => 0
        ));
        $id = $this->request->input('id');
        Database::getInstance()->query("UPDATE orders SET order_status=? WHERE id=? ", 1, $id);

        //send notifications to customer about the order
        $order = $this->model('api')->findOrder($id);
        $this->model('api')->sendPush(array($order['userid']), "Your order is delivered", "Your order has been delivered, Thank you");

        return json_encode(array(
            'status' => 1
        ));
    }

    public function agentOrders() {
        $this->tokenRequired();
        if ($this->model('user')->authUser['is_agent'] == 0) return json_encode(array(
            'status' => 0
        ));
        $orders = $this->model('api')->agentOrders();
        $newOrders = array();
        foreach($orders as $order) {
            $order['user'] = $this->model('user')->getUser($order['userid']);
            $order['order_date'] = date('F j, Y, g:i a', $order['order_date']);
            $order['completed_date'] = date('F j, Y, g:i a', $order['completed_date']);
            $newOrders[] = $order;
        }
        return json_encode(array(
            'pending' => $this->model('api')->countNewPendingOrders(),
            'lists' => $newOrders
        ));

    }

    public function saveFcm() {
        $this->tokenRequired();
        $fcm = $this->request->input('fcm');
        $this->model('api')->saveFCMToken($fcm);
        return json_encode(array(
            'status' => 1
        ));
    }

    public function testPush() {
        $this->model('api')->sendPush(array(1), "Testing push notification", "Your order has been delivered, Thank you");
        exit('Done');
    }
}