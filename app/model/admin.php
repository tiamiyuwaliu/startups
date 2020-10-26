<?php
class AdminModel extends Model {

    public function loadSettings() {
        $query = $this->db->query("SELECT settings_key,settings_value FROM settings ");
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            Request::instance()->setConfig($fetch['settings_key'], $fetch['settings_value']);
        }
    }

    public function saveSettings($val) {
        foreach($val as $key => $value) {
            if(is_array($value)) $value  = implode(',', $value);
            $query = $this->db->query("SELECT * FROM settings WHERE settings_key=?", $key);
            if ($query->rowCount() > 0) {
                $this->db->query("UPDATE settings SET settings_value=? WHERE settings_key=? ", $value, $key);
            } else {
                $this->db->query("INSERT INTO settings (settings_key,settings_value) VALUES(?,?)", $key, $value);
            }
        }

        $this->loadSettings(); //to silently load admin settings
        return true;
    }

    public function listPlugins() {
        $path = path('module');
        $handle = opendir($path);
        $result = array();
        while($read = readdir($handle)) {

            if (!is_file($path.$read) and substr($read, 0, 1) != '.') {
                $pluginFolder = $path.'/'.$read.'/';
                $pluginId = $read;
                $infoFile = $pluginFolder.'info.php';
                if (file_exists($infoFile)) {
                    $info = include $infoFile;
                    $info['icon'] = assetUrl('module/'.$read.'/icon.png');
                    $result[$pluginId] = $info;
                }
            }
        }
        return $result;
    }

    public function getActivePlugins() {
        $query = $this->db->query("SELECT id FROM plugins");
        $result = array();
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $fetch['id'];
        }
        return $result;
    }

    public function savePlugins($plugin) {
        $activePlugins = $this->getActivePlugins();
        //$this->db->query("DELETE FROM plugins");
        if (!in_array($plugin, $activePlugins)) {
            //its new activate plugin
            $installFile = path('module/'.$plugin.'/install.php');
            $updateFile = path('module/'.$plugin.'/update.php');
            if (file_exists($installFile)) require_once $installFile;
            if (file_exists($updateFile)) require_once  $updateFile;
            $this->db->query("INSERT INTO plugins(id,active)VALUES(?,?)", $plugin, 1);
        } else {
            $this->db->query("DELETE FROM plugins WHERE id=?", $plugin);
        }

        return true;
    }

    public function getUsers($term = '') {
        $sql = "SELECT * FROM users WHERE is_team=? ";
        $param = array('0');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (full_name LIKE ? OR email = ?) ";
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 10);
    }



    public function getPages($term = '') {
        $sql = "SELECT * FROM pages WHERE id!=? ";
        $param = array('');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (name LIKE ? ) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 100);
    }

    public function getPagesByMenu($location, $limit = 5) {
        $sql = "SELECT * FROM pages WHERE position=? ";
        $param = array($location);



        $sql .= " ORDER BY id DESC LIMIT $limit ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPage($val) {
        $ext = array(
            'name' => '',
            'position' => '',
            'content' => '',
            'slug' => '',
        );

        /**
         * @var $name
         * @var $position
         * @var $content
         * @var $slug
         */
        extract(array_merge($ext, $val));

        return $this->db->query("INSERT INTO pages (name,slug,position,content,changed,created) VALUES(?,?,?,?,?,?)", $name, $slug, $position, $content, time(), time());
    }

    public function savePage($val, $id) {
        $ext = array(
            'name' => '',
            'position' => '',
            'content' => '',
        );

        /**
         * @var $name
         * @var $position
         * @var $content
         */
        extract(array_merge($ext, $val));
        return $this->db->query("UPDATE pages SET name=?, position=?,content=?,changed=? WHERE id=?", $name, $position, $content, time(), $id);
    }

    public function findPage($id) {
        $query = $this->db->query("SELECT * FROM pages WHERE id=? OR slug=?", $id, $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePage($id) {
        return $this->db->query("DELETE FROM pages WHERE id=?", $id);
    }

    public function enablePage($id) {
        return $this->db->query("UPDATE pages SET status=? WHERE id=?", 1, $id);
    }

    public function disablePage($id) {
        return $this->db->query("UPDATE pages SET status=? WHERE id=?", 0, $id);
    }

    public function getLanguages($term = '') {
        $sql = "SELECT * FROM languages WHERE id!=? ";
        $param = array('');

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (id LIKE ? OR name LIKE ?) ";
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC LIMIT 100";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLanguage($val) {
        $ext = array(
            'name' => '',
            'code' => '',
            'is_default' => 0
        );
        /**
         * @var $name
         * @var $code
         * @var $is_default
         */
        extract(array_merge($ext,$val));
        $this->db->query("INSERT INTO languages (name,id,is_default)VALUES(?,?,?)", $name, $code, $is_default);

        //we always copy languages from the directory
        $file = path('languages/'.$code.'.php');
        if (!file_exists($file)) $file  = path('languages/en.php'); //fallback to english
        if (file_exists($file)) {
            //incase the user deleted the file so we should do nothing then
            $translations = include $file;
            $inserts = '';
            foreach($translations as $key => $value) {
                $value = str_replace("'", "\'", $value);
                $inserts .= ($inserts) ? ",('$code','$key', '$value','$value')" : "('$code','$key', '$value','$value')";
            }
            $this->db->query("INSERT INTO translations (lang,lang_key,original,translated) VALUES $inserts ");
        }
        return true;
    }

    public function saveLanguage($val, $id) {
        $ext = array(
            'name' => '',
            'code' => '',
            'is_default' => 0
        );
        /**
         * @var $name
         * @var $code
         * @var $is_default
         */
        extract(array_merge($ext,$val));

        if ($is_default) $this->db->query("UPDATE languages SET is_default=? ", 0);
        return $this->db->query("UPDATE languages SET name=?,id=?,is_default=? WHERE id=?", $name, $code, $is_default, $id);
    }

    public function deleteLanguage($id) {
        $this->db->query("DELETE FROM languages WHERE id=?", $id);
        $this->db->query("DELETE FROM translations WHERE lang=?", $id);
    }

    public function languageExists($id) {
        $query = $this->db->query("SELECT * FROM languages WHERE id=?", $id);
        return  $query->rowCount();
    }

    public function languageWordExists($id, $key) {
        $query = $this->db->query("SELECT * FROM translations WHERE lang=? AND lang_key=?", $id, $key);
        return  $query->rowCount();
    }

    public function getLanguageWords($id, $term = '') {
        $sql = "SELECT * FROM translations WHERE lang=? ";
        $param = array($id);

        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (translated LIKE ?) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";

        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveLanguageWord($val, $dId) {
        return $this->db->query("UPDATE translations SET translated=? WHERE id=? ", $val['word'], $dId);
    }

    public function addLanguageWord($val, $id) {
        return $this->db->query("INSERT INTO translations (lang,lang_key,original,translated) VALUES(?,?,?,?) ", $id, $val['lang_key'], $val['word'], $val['word']);
    }

    public function countStatistics($status = 'all') {
        $sql = " SELECT id FROM users ";
        if ($status != 'all') {
            $sql .= " WHERE status='$status' ";
        }

        $query = $this->db->query($sql);
        return $query->rowCount();
    }

    public function monthlyStatistics() {
        $result = array();
        $months = getMonths();
        foreach($months  as $month) {
            $startDate = strtotime("first day of $month 2019");
            $endDate = strtotime("last day of $month 2019 12pm");
            $query = $this->db->query("SELECT id FROM users WHERE created >= '$startDate' AND created <= $endDate ");
            $result[] = $query->rowCount();
        }
        return $result;
    }

    public function countByTime($time) {
        $endDate = time();
        $query = $this->db->query("SELECT id FROM users WHERE created >= '$time' AND created <= $endDate ");
        return $query->rowCount();
    }

    public function getTransaction($id) {
        $query = $this->db->query("SELECT * FROM transactions WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function addTransaction($val) {
        $ext  = array(
            'amount' => '',
            'email' => '',
            'type' => '',
            'sale_id' => '',
            'name' => '',
            'userid' => ''
        );
        /**
         * @var $amount
         * @var $email
         * @var $type
         * @var $sale_id
         * @var $name
         * @var $userid
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO transactions (userid,name,email,sale_id,amount,type,date_created)VALUES(?,?,?,?,?,?,?)",
            $userid,$name,$email,$sale_id,$amount,$type,time());
        Hook::getInstance()->fire('transaction.added', null, array($val,$this->db->lastInsertId()));
    }
}