<?php
class CronController extends Controller {
    public function run() {
        $posts = $this->model('post')->getPendingPosts();

        foreach($posts as $post) {
            $user = $this->model('user')->getUser($post['userid']);
            $this->model('user')->loginWithObject($user);

            $workspace = $this->model('workspace')->find($post['workspace_id']);
            if ($user['timezone']) date_default_timezone_set($user['timezone']);
            if ($workspace['timezone']) {
                date_default_timezone_set($workspace['timezone']);
            }

            if ($post['schedule_date'] < time()) {

                try {
                    $account = $this->model('social')->find($post['account']);
                    if ($account) {

                        $this->api($account['social_type'])->post($post, $post['account']);
                        $this->model('post')->setPublished($post['id']);
                    } else {
                        $this->model('post')->setunPublished($post['id']);
                    }

                } catch ( Exception $e) {
                    $this->model('post')->setunPublished($post['id']);
                }
            }
            $this->model('user')->logoutUser();
        }



    }

    public function parties() {
        /**
         * Lets run due parties
         */
        $partyPosts = $this->model('party')->getPendingPosts();
        foreach($partyPosts as $post) {
            $party = $this->model('party')->findParty($post['party_id']);
            if($party['status']) {
                $user = $this->model('user')->getUser($party['userid']);
                $this->model('user')->loginWithObject($user);
                if ($user['timezone']) date_default_timezone_set($user['timezone']);
                if ($party['timezone']) {
                    date_default_timezone_set($party['timezone']);
                }
                if ($post['schedule_time'] < time()) {
                    $accounts = perfectUnserialize($party['account']);
                    foreach($accounts as $account) {
                        try {
                            $account = $this->model('social')->find($account);
                            if ($account) {
                                $this->api($account['social_type'])->post($post, $account['id'], $party);
                            }
                        } catch ( Exception $e) {
                            exit($e->getMessage());
                        }
                    }
                    $this->model('party')->setPublished($post);

                }
                $this->model('user')->logoutUser();
            }
        }
    }
}