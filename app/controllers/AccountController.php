<?php
class AccountController extends Controller {

    public function __construct($request)
    {
        parent::__construct($request);
        $this->setActiveIconMenu('accounts');
    }

    public function index() {
        $this->setTitle(l('manage-accounts'));


        if ($action = $this->request->input('action')) {
            if ($action == 'delete') {
                $this->model('social')->deleteAccount($this->request->input('id'));
                return json_encode(array(
                    'type' => 'url',
                    'value' => url('accounts'),
                    'message' => l('account-deleted')
                ));
            }elseif($action == 'delete-action'){
                foreach($this->request->input('accounts') as $account) {
                    $this->model('social')->deleteAccount($account);
                }
            }  elseif($action == 'search') {
                $accounts = model('social')->getAccounts(null,$this->request->input('term'));
                return view('account/list', array('accounts' => $accounts));
            }
        }

        $page  = $this->request->segment(1, 'all');
        return $this->render($this->view('account/index', array('page' => $page)), true);
    }

    public function linkedin() {
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('linkedin')->loginUrl()
            ));
        }

        $details = array();

        if ($code = $this->request->input('code')) {
            $linkedin = $this->api('linkedin');
            $token = $linkedin->getToken();
            $linkedin->setToken($token);
            $user = (object)$linkedin->getCurrentUser($token);
            //$companies = $linkedin->getCompanies();
            $firstName_param = (array)$user->firstName->localized;
            $lastName_param = (array)$user->lastName->localized;

            $firstName = reset($firstName_param);
            $lastName = reset($lastName_param);
            $fullname = $firstName." ".$lastName;


            $add = $this->model('social')->addAccount('linkedin',$user->id, $fullname,$token,$this->api('linkedin')->getAvatar($user),'profile');

            try {
                $companies = $linkedin->getCompanies();
                if(!empty($companies)){
                    foreach ($companies->elements as $company) {
                        $company = (array)$company;
                        $company = $company['organizationalTarget~'];
                        $account = model('account')->findAccountBySID($company->id, 'linkedin', 'page');
                        $logo = (array)$company->logoV2;
                        $logo = $logo['original~'];
                        $logo = $logo->elements[0]->identifiers[0]->identifier;
                        if (!$this->model('social')->canAdd()) {
                            $this->request->redirect(url('accounts/twitter?message=account-limit-reached&type=error'));
                        }
                        $add = $this->model('social')->addAccount('linkedin',$company->id, $company->localizedName,$token,$logo,'page');
                    }

                }
            } catch (Exception $e) {

            }

            return $this->request->redirect(url('accounts/linkedin'));

        }

        return $this->index();
    }
    public function twitter() {
        if ($auth = $this->request->input('auth')) {
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $this->api('twitter')->loginUrl()
            ));

        }

        if ($verifyIdentifier = $this->request->input('oauth_verifier')) {
            $twitter = $this->api('twitter')->init();
            $accessToken = (object)$twitter->getToken();
            $account = $this->model('social')->findAccountBySID($accessToken->user_id, 'twitter');
            $avatar = $this->api('twitter')->getAvatar($accessToken->user_id,json_encode($accessToken));

            $add = $this->model('social')->addAccount('twitter',$accessToken->user_id, $accessToken->screen_name,json_encode($accessToken),$avatar,'');

            return $this->request->redirect(url('accounts/twitter'));
        }

        return $this->index();
    }
    public function facebook() {
        if ($auth = $this->request->input('auth')) {
            $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
            $type = $this->request->input('type');
            session_put('facebook.auth.type', $type);
            return json_encode(array(
                'type' => 'normal-url',
                'value' => $fbApi->loginUrl(url('accounts/facebook'))
            ));
        }

        if ($code = $this->request->input('code')) {
            $this->setTitle(l('choose-account'));
            $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
            $accessToken = $fbApi->getUserAccessToken(url('accounts/facebook'));
            if (!$accessToken) return $this->request->redirect($fbApi->loginUrl(url('accounts/facebook')));
            $fbApi->setAccessToken($accessToken);

            $user = $fbApi->getLoginUser();
            $type = session_get('facebook.auth.type');
            $groups = json_decode(json_encode($fbApi->getGroups(true)), true);
            if ($type == 'group'){
                $newData = array();
                foreach($groups['data'] as $group){
                    $group['installed'] = $fbApi->isAppInstalled($group['id']);
                    $newData[] = $group;
                }
                $groups['data'] = $newData;
            }

            $pages = json_decode(json_encode($fbApi->getPages()), true);

            $instagrams = array();
            if ($type == 'instagram') {

                foreach($pages['data'] as $page) {
                    $pageInstagram = $this->api('facebook')->getPageInstagram($page['id']);
                    if (isset($pageInstagram['instagram_business_account'])){
                        $instagramId = $pageInstagram['instagram_business_account']['id'];
                        $instagramDetail = $this->api('facebook')->getInstagramDetails($instagramId);
                        $instagrams[] = array(
                            'username' => $instagramDetail->username,
                            'avatar' => (isset($instagramDetail->profile_picture_url)) ? $instagramDetail->profile_picture_url : assetUrl('assets/images/no-profile.png'),
                            'id' => $instagramId,
                        );
                    }
                }
            }
            return $this->render($this->view('account/facebook/index', array('token' => $accessToken,'groups' => $groups, 'pages' => $pages, 'instagrams' => $instagrams, 'type' => $type)), true);
        }

        if ($action = $this->request->input('action')) {
            $type = $this->request->input('type');
            $id = $this->request->input('id');
            $name = $this->request->input('name');
            $token = $this->request->input('token');
            //if (!$this->model('social')->canAdd()) return 0;
            $avatar = $this->api('facebook')->generateAvatar(html_entity_decode($this->request->input('avatar')));

            if ($type == 'instagram') {
                $add = $this->model('social')->addAccount('instagram',$id,$name,$token,$avatar,'');
            } else {
                $add = $this->model('social')->addAccount('facebook',$id,$name,$token,$avatar,$type);
            }
            $lastId = $add;
            if ($type == 'group') {
                $fbApi = $this->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
                $fbApi->setAccessToken($token);
                $pages = json_decode(json_encode($fbApi->getPages()), true);
                $this->model('social')->deletePageGroups($lastId);
                foreach($pages['data'] as $page) {
                    $groups = $this->api('facebook')->getPageGroups($page['id']);

                    if ($groups and !empty($groups->data)) {
                        foreach($groups->data as $group) {
                            if ($group->id == $id) $this->model('social')->addPageGroup($lastId, $page['id'], $token, $page['name']);
                        }
                    }
                }


            }


            return 1;
        }


        return $this->index();
    }
}