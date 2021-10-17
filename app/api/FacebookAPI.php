    <?php
    autoLoadVendor();
    require_once path('app/vendor/Facebook/autoload.php');

    class FacebookAPI extends API {
        private $appId;
        private $appSecret;
        private $accessToken;
        private $fb;
        private $accountId = null;
        private $permissions = array();
        public function __construct()
        {
            parent::__construct();

        }

        public function setPermissions($permissions) {
            $this->permissions = $permissions;
        }
        public function init($appId, $appSecret) {
            $this->appId = $appId;
            $this->appSecret = $appSecret;

            try {
                $this->fb = new \Facebook\Facebook(array(
                    'app_id' => $this->appId,
                    'app_secret' => $this->appSecret,
                    'default_graph_version' => 'v5.0',
                ));
            } catch (Exception $e){
                print_r($e);
                exit;
            }

            return $this;
        }

        public function loginUrl($url) {
            $helper = $this->fb->getRedirectLoginHelper();
            $permissions = (!empty($this->permissions)) ? $this->permissions : ['pages_manage_posts,pages_read_engagement,pages_manage_engagement,publish_to_groups,pages_show_list,instagram_basic,instagram_content_publish'];
            //instagram_basic,instagram_content_publish,instagram_manage_comments,instagram_manage_insights
            $permissions = Hook::getInstance()->fire('facebook.permissions', $permissions);
            $loginUrl = $helper->getLoginUrl($url, $permissions);

            return $loginUrl;
        }

        public function instagramUrl($url) {
            $helper = $this->fb->getRedirectLoginHelper();
            $permissions = (!empty($this->permissions)) ? $this->permissions : ['instagram_basic,pages_show_list'];

            $permissions = Hook::getInstance()->fire('facebook.permissions', $permissions);
            $loginUrl = $helper->getLoginUrl($url, $permissions);

            return $loginUrl;
        }

        function getUserAccessToken($url){
            $helper = $this->fb->getRedirectLoginHelper();
            try {
                $accessToken = $helper->getAccessToken($url);
                return $accessToken->getValue();
            } catch (Exception $e) {
                print_r($e->getMessage());
                exit;
            }
        }

        function getLoginUser($fields = 'name,id'){
            return $this->fetchGet('/me?fields='.$fields);
        }

        function setAccessToken($access_token){
            $this->fb->setDefaultAccessToken($access_token);
            $this->accessToken = $access_token;
        }

        function fetchAccessToken($pid){
            $response = $this->fetchGet('/'.$pid.'/?fields=access_token');
            if(is_object($response)){
                return $response->access_token;
            }else{
                return false;
            }
        }

        public function isAppInstalled($groupId) {
            $token = "558941382144086|TeYwx_yigwbqOABGtQCdYu0ZVNU";
            $appId = config('facebook-app-id');
            $this->setAccessToken($token);
            $response = $this->fetchGet('/'.$appId.'/app_installed_groups?group_id='.$groupId);
            $result = json_decode(json_encode($response), true);

            if (!empty($result['data'])) return true;
            return false;
        }
        function getGroups($admin = false){
            $result = $this->fetchGet('/me/groups?fields=id,icon,name,description,email,privacy,cover'.($admin?"&admin_only=true":"").'&limit=10000');
            if(is_string($result)){
                $result = $this->fetchGet('/me/groups?fields=id,icon,name,description,email,privacy,cover'.($admin?"&admin_only=true":"").'&limit=3');
            }
            return $result;
        }

        public function getPages() {
            $result = $this->fetchGet('/me/accounts?fields=id,name,single_line_address,phone,emails,website,fan_count,link,is_verified,about,picture,category&limit=10000');
            if(is_string($result)){
                $result = $this->fetchGet('/me/accounts?fields=id,name,single_line_address,phone,emails,website,fan_count,link,is_verified,about,picture,category&limit=3');
            }
            return $result;
        }

        public function fetchGet($params, $app_version = null){
            try {
                $response = $this->fb->get($params, null, null, $app_version);
                return json_decode($response->getBody());
            } catch(Facebook\Exceptions\FacebookResponseException $e) {
                return 'Graph returned an error: ' . $e->getMessage();
            } catch(Facebook\Exceptions\FacebookSDKException $e) {
                return 'Facebook SDK returned an error: ' . $e->getMessage();
            }
        }

        public function doLogin($response){
            $response = $response->getResponse()->getBody();
            $response = json_decode($response);

            if(isset($response->error) && $this->accountId != 0 &&
                (
                    $response->error->code == 190
                    || $response->error->code == 368
                    || $response->error->code == 10
                )
            ){
               if ($this->accountId) {
                   Hook::getInstance()->fire('account.disabled', null, array($this->accountId));
                   //$this->db->query("UPDATE accounts SET status=? WHERE id=?", 0, $this->accountId);
               }
            }
        }

        public function fetchPost($params, $data){
            try {
                $response = $this->fb->post($params, $data);
                return json_decode($response->getBody());
            } catch(Facebook\Exceptions\FacebookResponseException $e) {
                print_r($e->getResponse()->getBody());
                //exit;
                if ($e->getMessage() == "Missing or invalid image file") return true;
                return false;
            } catch(Facebook\Exceptions\FacebookSDKException $e) {
                print_r($e->getMessage());
                //exit;
                return false;
            }
        }

        public function getPageAvatar($page, $generated = false) {
            if (isset($page['picture'])) {
                $avatar =  $page['picture']['data']['url'];
                if (!$generated) return $avatar;
                return $this->generateAvatar($avatar);
            }
            return ($generated)  ? 'assets/images/page.png' : assetUrl('assets/images/page.png');
        }

        public function getGroupAvatar($page, $generated = false) {
            if (isset($page['cover'])) {
                $avatar =  $page['cover']['source'];
                if (!$generated) return $avatar;
                return $this->generateAvatar($avatar);
            }
            return ($generated ) ? 'assets/images/jpg.png' : assetUrl('assets/images/group.jpg');
        }

        public function generateAvatar($avatar) {
            if (preg_match('#assets/images#', $avatar)) return str_replace(url(), '', $avatar);
            $dir = "uploads/avatar/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($avatar).'.jpg';
            getFileViaCurl($avatar, $file);
            return $file;
        }

        function getPageAccessToken($sid){
            $response = $this->fetchGet('/'.$sid.'/?fields=access_token');
            if(is_object($response)){
                return $response->access_token;
            }else{
                return false;
            }
        }

        public function preInit($account) {
            $this->init(config('facebook-app-id'), config('facebook-app-secret'));
            $this->setAxccessToken($account['access_token']);
            $this->accountId = $account['id'];
            if ($account['account_type'] == 'page') {
                //set token for page
                $accessToken = $this->getPageAccessToken($account['sid']);
                if ($accessToken) {
                    $this->setAccessToken($accessToken);
                }
            }
            return $this;
        }

        public function getPageGroups($page) {
            $accessToken = $this->getPageAccessToken($page);
            $response = $this->fb->get("/".$page.'/groups', $accessToken, null,null);
            return json_decode($response->getBody());
        }

        public function getPageInstagram($page) {
            $accessToken = $this->getPageAccessToken($page);
            $response = $this->fb->get("/".$page.'?fields=instagram_business_account', $accessToken, null,null);
            return json_decode($response->getBody(), true);
        }

        public function getInstagramDetails($id) {
            $response = $this->fetchGet("/".$id.'?fields=id%2Cusername%2Cname%2Cprofile_picture_url%2Cig_id');
            return $response;
        }

        public function runReplyThings() {

            $account = model('account')->find(2785);
            $this->init(config('facebook-app-id'), config('facebook-app-secret'));
            $this->setAccessToken($account['access_token']);
            $accessToken = $this->getPageAccessToken($account['sid']);
            if ($accessToken) {
                $this->setAccessToken($accessToken);
            }

            $postId = '534977687212023_784222755620847';
            $postId = '332440751725067_332973468338462';
            //$response = $this->fetchPost('/'.$postId.'/comments', array('message' => 'i need the answer'));
            //print_r($response);
            //exit;
        }

        public function postComment($fetch) {

            $post = model('post')->find($fetch['post_id']);
            $account = model('account')->find($post['account']);
            $postData = perfectUnserialize($post['type_data']);
            if ($account['social_type'] != 'facebook') return false;
            if ($account['social_type'] == 'facebook' and $account['account_type'] == 'group') {
                if (!isset($postData['groups']) or !isset($postData['groups'][$account['id']])) return false;
            }

            $this->accountId = $account['id'];
            $this->init(config('facebook-app-id'), config('facebook-app-secret'));
            $this->setAccessToken($account['access_token']);
            if ($account['account_type'] == 'page') {
                //set token for page
                $accessToken = $this->getPageAccessToken($account['sid']);
                if ($accessToken) {
                    $this->setAccessToken($accessToken);
                }
            }
            if ($account['social_type'] == 'facebook' and $account['account_type'] == 'group') {
                if (isset($postData['groups']) and isset($postData['groups'][$account['id']])) {
                    $page = model('account')->find($postData['groups'][$account['id']]);
                    if ($page) {

                        $accessToken = $this->getPageAccessToken($page['sid']);
                        if ($accessToken) {
                            $this->setAccessToken($accessToken);
                        }
                    }
                }
            }
            $spintax = new Spintax();
            $caption    = @$spintax->process($fetch['reply_message']);
            autoLoadVendor();
            $emojione = new \Emojione\Client(new \Emojione\Ruleset());
            $caption = $emojione->shortnameToUnicode($caption);
            $param = array('message' => $caption, 'attachment_url' => ($fetch['reply_media']) ? assetUrl($fetch['reply_media']) : '');
            if ($fetch['comment_id']) {
                $response = $this->fetchPost('/'.$fetch['comment_id'].'/comments', $param);
            } else {
                $response = $this->fetchPost('/'.$fetch['fb_post_id'].'/comments', $param);
            }
            Database::getInstance()->query("UPDATE automation_replies SET reply_status=? WHERE id=?", 1, $fetch['id']);
        }

        public function post($post, $account, $party = null) {
            $spintax = new Spintax();
            $account = model('social')->find($account);
            $this->accountId = $account['id'];
            $this->init(config('facebook-app-id'), config('facebook-app-secret'));
            $this->setAccessToken($account['social_token']);
            if ($account['social_account_type'] == 'page') {
                //set token for page
                $accessToken = $this->getPageAccessToken($account['social_id']);
                if ($accessToken) {
                    $this->setAccessToken($accessToken);
                }
            }

            if ($account['social_type'] == 'facebook' and $account['social_account_type'] == 'group') {

                $pageId = null;
                if (!$party) {
                    $postData = perfectUnserialize($post['type_data']);
                    if (isset($postData['group']) and isset($postData['group'][$account['id']])) {
                        $pageId = $postData['group'][$account['id']];
                    }
                } else {
                    $pageGroups = perfectUnserialize($party['account_pages']);
                    if (isset($pageGroups[$account['id']]))  $pageId = $pageGroups[$account['id']];
                }
                if ($pageId) {
                    $page = model('social')->findPageGroup($pageId);

                    if ($page) {
                        $accessToken = $this->getPageAccessToken($page['page_id']);
                        if ($accessToken) {
                            $this->setAccessToken($accessToken);
                        }
                    }
                }
            }


            if (!$party) {
                $postData = perfectUnserialize($post['type_data']);
                $caption    = @$spintax->process($postData['content']);
                $medias = explode(',', $postData['media']);
            } else {
                $caption = @$spintax->process($post['caption']);
                $medias = perfectUnserialize($post['medias']);
            }

            autoLoadVendor();
            $emojione = new \JoyPixels\Client(new \JoyPixels\Ruleset());
            $caption = $emojione->shortnameToUnicode($caption);


            if ($medias) {
                if (count($medias)  == 1) {
                    $media = $medias[0];
                    if (isImage($media)) {

                        $param = array(
                            'url' => assetUrl($media),
                            'value' => 'EVERYONE',
                            'message' => $caption
                        );

                        $response = $this->fetchPost('/'.$account['social_id'].'/photos', $param);

                        return true;
                    } else {
                        $param = array(
                            'file_url' => assetUrl($media),
                            'value' => 'EVERYONE',
                            'description' => $caption,
                        );
                        $response = $this->fetchPost('/'.$account['social_id'].'/videos', $param);

                        return true;
                    }
                } elseif(count($medias) > 0) {
                    $mediasSelect = array();
                    $count = 0;
                    foreach($medias as $media) {
                        if (isImage($media)) {

                            $param = array(
                                'url' => assetUrl($media),
                                'published' => false
                            );
                            $r = $this->fetchPost('/'.$account['social_id'].'/photos', $param);
                            if (is_object($r)) {
                                $mediasSelect['attached_media['.$count.']'] = '{"media_fbid":"'.$r->id.'"}';
                                $count++;
                            }
                        } else {
                            $param = array('file_url' => assetUrl($media));
                            $r = $this->fetchPost('/'.$account['social_id'].'/videos', $param);
                            if (is_object($r)) {
                                $mediasSelect['attached_media['.$count.']'] = '{"media_fbid":"'.$r->id.'"}';
                                $count++;
                            }
                        }
                    }

                    $param = array(
                        'message' => $caption,
                    );
                    $param = array_merge($param, $mediasSelect);
                    $response = $this->fetchPost('/'.$account['social_id'].'/feed', $param);
                    return true;
                }
            } else {
                $param = array('message' => $caption);
                $response = $this->fetchPost('/'.$account['social_id'].'/feed', $param);
            }

            return true;
        }
    }