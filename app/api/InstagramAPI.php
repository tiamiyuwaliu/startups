<?php
require_once path('app/vendor/autoload.php');
require_once path('app/vendor/instagram-php/autoload.php');

class InstagramAPI extends API {

    public $username;
    public $password;
    public $proxy;
    public $instagramObj;
    public $sCode;
    public $vCode;
    public $twoFactorIdentifier = null;
    public $choice;

    public function init($username = null, $password = null, $proxy = null, $isLogin = false, $sCode = null, $vCode = null)
    {
        parent::__construct();


        return $this;
    }


    public function post($post, $account) {
        $spintax = new Spintax();
        $account = model('social')->find($account);
        $this->accountId = $account['id'];
        getController()->api('facebook')->init(config('facebook-app-id'), config('facebook-app-secret'));
        getController()->api('facebook')->setAccessToken($account['social_token']);
        $postData = perfectUnserialize($post['type_data']);

        $caption    = @$spintax->process($postData['content']);
        autoLoadVendor();
        $emojione = new \JoyPixels\Client(new \JoyPixels\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $medias = explode(',', $postData['media']);
        if (!$medias) return false;
        if (count($postData['media'])  == 1) {
            $media = $medias[0];
            if (isImage($media)) {

                $media = getJPGImageFile($media);
                $param = array(
                    'image_url' => assetUrl($media),
                    'caption' => $caption,
                );

                $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media', $param);

                $param = array(
                    'creation_id' => $response->id
                );

                $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media_publish', $param);
                return true;
            } else {
                $param = array(
                    'video_url' => assetUrl($media),
                    'media_type' => 'VIDEO',
                    'caption' => $caption,
                );

                $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media', $param);
                $checking  = true;

                $id = $response->id;
                while($checking) {
                    $result = getController()->api('facebook')->fetchGet("/$id?fields=status,status_code");
                    if ($result->status_code == 'FINISHED' ) {
                        $param = array(
                            'creation_id' => $response->id,
                        );

                        $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media_publish', $param);

                        $checking = false;
                    } elseif ($result->status_code == 'ERROR') {
                        $checking = false;
                    }
                    sleep(30);
                }
                return true;
            }
        } elseif(count($medias) > 0) {
            $medias = array();
            $count = 0;
            foreach($postData['media'] as $media) {
                if (isImage($media)) {
                    $media = getJPGImageFile($media);
                    $param = array(
                        'image_url' => assetUrl($media),
                        'caption' => $caption,
                    );

                    $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media', $param);

                    $param = array(
                        'creation_id' => $response->id
                    );

                    $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media_publish', $param);

                } else {
                    $param = array(
                        'video_url' => assetUrl($media),
                        'media_type' => 'VIDEO',
                        'caption' => $caption,
                    );

                    $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media', $param);
                    $checking  = true;
                    sleep(10);
                    $id = $response->id;
                    while($checking) {
                        $result = getController()->api('facebook')->fetchGet("/$id?fields=status,status_code");

                        if ($result->status_code == 'FINISHED' ) {
                            $param = array(
                                'creation_id' => $response->id,
                            );
                            $response = getController()->api('facebook')->fetchPost('/'.$account['social_id'].'/media_publish', $param);

                            $checking = false;
                        } elseif ($result->status_code == 'ERROR') {
                            $checking = false;
                        }
                        sleep(30);
                    }
                }
            }
            return true;
        }

        return true;
    }


}