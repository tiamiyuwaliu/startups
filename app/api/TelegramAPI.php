<?php
autoLoadVendor();
class TelegramAPI extends API {
    public function post($post, $account) {
        $account = model('account')->find($account);
        $this->accountId = $account['id'];
        $postData = perfectUnserialize($post['type_data']);
        $caption = $postData['text'];
        autoLoadVendor();
        $emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $emojione->shortnameToUnicode($caption);
        $link = $postData['link'];
        $spintax = new Spintax();
        $title = ($postData['title']) ? @$spintax->process($postData['title']) : '';
        try {
            switch ($post['type']){
                case 'text':

                    $params = array(
                        'chat_id' => $account['sid'],
                        'text' => $caption,
                        'parse_mode' => 'Markdown'
                    );

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api.telegram.org/bot' . $account['access_token'] . '/sendMessage?' . http_build_query($params),
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: text/xml; charset=utf-8"
                        ),
                        CURLOPT_RETURNTRANSFER => true
                    ));

                    $result = curl_exec($curl);

                    curl_close($curl);
                    break;
                case 'photo' or 'media':
                    $media = $postData['media'][0];
                    if (isImage($postData['media'][0])) {
                        if (model('user')->hasPermission('watermark')) {
                            $file = getWatermarkTmpFile($media);
                            $media = doWaterMark($media, $file);
                        }

                        $params = array(
                            'chat_id' => $account['sid'],
                            'photo' => assetUrl($media),
                            'caption' => $post,
                            'parse_mode' => 'Markdown'
                        );

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.telegram.org/bot' . $account['access_token'] . '/sendPhoto?' . http_build_query($params),
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: text/xml; charset=utf-8"
                            ),
                            CURLOPT_RETURNTRANSFER => true
                        ));

                        $result = curl_exec($curl);

                        curl_close($curl);

                    } else {
                        $params = array(
                            'chat_id' => $account['sid'],
                            'video' => assetUrl($media),
                            'caption' => $post,
                            'parse_mode' => 'Markdown'
                        );

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.telegram.org/bot' . $account['access_token'] . '/sendVideo?' . http_build_query($params),
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: text/xml; charset=utf-8"
                            ),
                            CURLOPT_RETURNTRANSFER => true
                        ));

                        $result = curl_exec($curl);

                        curl_close($curl);
                    }

                    if ($caption) {
                        $params = array(
                            'chat_id' => $account['sid'],
                            'text' => $caption,
                            'parse_mode' => 'Markdown'
                        );

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.telegram.org/bot' . $account['access_token'] . '/sendMessage?' . http_build_query($params),
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: text/xml; charset=utf-8"
                            ),
                            CURLOPT_RETURNTRANSFER => true
                        ));

                        $result = curl_exec($curl);
                    }
                    break;
                case 'link':
                    $params = array(
                        'chat_id' => $account['sid'],
                        'text' => $caption.''.$link,
                        'parse_mode' => 'Markdown'
                    );

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api.telegram.org/bot' . $account['access_token'] . '/sendMessage?' . http_build_query($params),
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: text/xml; charset=utf-8"
                        ),
                        CURLOPT_RETURNTRANSFER => true
                    ));

                    $result = curl_exec($curl);

                    curl_close($curl);

                    break;
            }


            $publish = json_decode($result);

            // Verify if the post was published
            if ( !empty($publish->ok) ) {
                model('post')->setResult('posted successfully', $post['id']);
                return true;
            } else {
                model('post')->setResult('Unknown error', $post['id']);
                return false;
            }
        } catch (Exception $e) {
            model('post')->setResult($e->getMessage(), $post['id']);
            return false;
        }
    }
}