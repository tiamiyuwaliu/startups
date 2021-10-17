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
        $sql = "SELECT * FROM users WHERE id !=? ";
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

    public function addGraphicCategory($val) {
        $ext = array(
            'title' => '',
            'cat_id' => 0,
            'color' => '#000'
        );
        /**
         * @var $title
         * @var $cat_id
         * @var $color
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO graphics_category (title,category_id,color)VALUES(?,?,?)",$title, $cat_id,$color);
        return true;
    }

    public function getGraphicCategory($category) {
        $query = $this->db->query("SELECT * FROM graphics_category WHERE id=?", $category);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countCategoryGraphics($id) {
        $query = $this->db->query("SELECT * FROM graphics WHERE category_id=?", $id);
        return $query->rowCount();
    }

    public function countAllCategoryGraphics($id) {
        $query = $this->db->query("SELECT * FROM graphics WHERE main_category=?", $id);
        return $query->rowCount();
    }

    public function getCategoryColors($id) {
        $query = $this->db->query("SELECT * FROM graphics_colors WHERE category_id=?", $id);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getMonthGraphicCategory() {
        $month = strtolower(date('M'));
        $query = $this->db->query("SELECT * FROM graphics_category WHERE month_cat=?", $month);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public function getGraphicCategories($catId = null) {
        $sql = "SELECT * FROM graphics_category WHERE id !=? AND month_cat=? ";
        $param = array(0, 0);
        if ($catId) {
            $sql .= " AND category_id=? ";
            $param[] = $catId;
        } else {
            $sql .= " AND (category_id=? OR category_id=?) ";
            $param[] = 0;
            $param[] = '';
        }

        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addGraphic($val) {
        $ext = array(
            'title' => '',
            'description' => '',
            'id' => '',
            'img' => '',
            'category' => '',
            'cat' => '',
            'colors' => '',
            'styles' => array()
        );
        /**
         * @var $title
         * @var $description
         * @var $id
         * @var $img
         * @var $category
         * @var $colors
         * @var $styles
         * @var $cat
         */
        extract(array_merge($ext, $val));
        $styles = implode(',', $styles[0]);
        $this->addGraphicColors($colors, $cat);
        $this->db->query("INSERT INTO graphics (title,description,file_url,file_id,colors,category_id,main_category,styles)VALUES(?,?,?,?,?,?,?,?)",
        $title, $description,$img,$id,$colors,$category,$cat,$styles);
        return true;
    }
    public function updateGraphic($val) {
        $ext = array(
            'title' => '',
            'description' => '',
            'id' => '',
            'file_id' => '',
            'img' => '',
            'category' => '',
            'colors' => '',
            'styles' => array()
        );
        /**
         * @var $title
         * @var $description
         * @var $id
         * @var $img
         * @var $category
         * @var $colors
         * @var $styles
         * @var $file_id
         */
        extract(array_merge($ext, $val));
        $styles = implode(',', $styles[0]);
       // $this->addGraphicColors($colors, $cat);
        $this->db->query("UPDATE graphics SET title=?,description=?,file_url=?,file_id=?,colors=?,category_id=?,styles=? WHERE id=?",
            $title, $description,$img,$file_id,$colors,$category,$styles, $id);
        return true;
    }
    public function addGraphicColors($colors, $cat) {
        if ($colors) {
            foreach(explode(',', $colors) as $color) {
                $query = $this->db->query("SELECT id FROM graphics_colors WHERE color=? AND category_id=?", $color, $cat);
                if (!$query->rowCount()) {
                    $this->db->query("INSERT INTO graphics_colors (color,category_id)VALUES(?,?)", $color, $cat);
                }
            }
        }
    }

    public function getGraphicStyles() {
        return array(
            'illustration',
            'modern',
            'border',
            'simple',
            'gold',
            'minimalist',
            'simple',
            'gold',
            'floral',
            'pattern',
            'photo',
            'colorful',
            'frame',
            'dots',
            'vintage',
            'bold',
            'elegant',
            'script',
            'cute',
            'dark',
            'fun',
            'line',
            'lines',
            'cartoon',
            'circle',
            'feminine',
            'happy',
            'classic',
            'grid',
            'funky',
            'geometric',
            'retro',
            'minimal',
            'round',
            'watercolor',
            'playful',
            'abstract',
            'animated',
            'pastel',
            'rustic',
            'texture',
            'classy',
            'fancy',
            'light',
            'festive',
            'funny',
            'charcoal',
            'clean',
            'neon',
            'rainbow',
            'table',
            'collage',
            'creative',
            'cursive',
            'dotted',
            'glitter',
            'gradient',
            'nautical',
            'organic',
            'arrow',
            'blob',
            'grunge',
            'hipster',
            'maximalist',
            'papercraft',
            'sketch',
            'spartkle',
        );
    }

    public function getGraphics($catId = null, $colors = null, $styles = null, $limit = null, $term = null) {
        $sql = "SELECT * FROM graphics WHERE id !=?";
        $param = array(0);
        if ($catId) {
            if (preg_match('#main#', $catId)) {
                list($w,$catId) = explode('-', $catId);
                if ($catId) {
                    $sql .= " AND main_category=? ";
                    $param[] = $catId;
                }
            } else {
                $sql .= " AND category_id IN ($catId) ";
            }

        }
        
        if ($colors ) {
            $sql .= " AND ( ";
            $addSql = "";
            foreach(explode(',', $colors) as $color) {
                $addSql .= ($addSql) ? " OR colors LIKE '%$color%' " : " colors LIKE '%$color%'";
            }
            $sql .= $addSql.')';
        }

        if ($styles ) {
            $sql .= " AND ( ";
            $addSql = "";
            foreach(explode(',', $styles) as $style) {
                $addSql .= ($addSql) ? " OR styles LIKE '%$style%' " : " styles LIKE '%$style%'";
            }
            $sql .= $addSql.')';
        }

        if ($term) {
            $sql .= " AND title LIKE '%$term%' ";
        }

        $sql .= " ORDER BY id DESC ";
        if ($limit) {
            $sql .= " LIMIT $limit ";
        }
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findGraphic($id) {
        $query = $this->db->query("SELECT * FROM graphics WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countGraphicSearch($term) {
        $query = $this->db->query("SELECT * FROM graphics WHERE title LIKE '%$term%' ORDER BY id DESC");
        return $query->rowCount();
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
            'userid' => '',
            'period' => ''
        );
        /**
         * @var $amount
         * @var $email
         * @var $type
         * @var $sale_id
         * @var $name
         * @var $userid
         * @var $period
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO transactions (userid,name,email,sale_id,amount,type,period_time,date_created)VALUES(?,?,?,?,?,?,?,?)",
            $userid,$name,$email,$sale_id,$amount,$type,$period,time());
        $this->model('user')->processReferral($userid, $amount);
        Hook::getInstance()->fire('transaction.added', null, array($val,$this->db->lastInsertId()));
    }

    public function getTransactions($userid) {
        $query = $this->db->query("SELECT * FROM transactions WHERE userid=? ORDER BY id DESC", $this->model('user')->authId);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHelpCategories() {
        return array(
            'connect-accounts' => 'Connecting social accounts',
            'scheduling' => 'Scheduling & manage posts',
            'creating-party' => 'Creating Party',
            'photo-editor' => 'Photo Editor',
            'content-studio' => 'Content Studio',
            'organizer' => 'Organizer',
            'workspace' => 'Workspace & Team collaboration',
            'refer' => 'Refer & Earn',
        );
    }

    public function addHelp($val, $id = null) {
        $ext = array(
            'title' => '',
            'type' => '',
            'category' => '',
            'tags' => '',
            'description' => '',
            'content' => '',
            'img' => '',
            'video' => '',
            'basic' => '',
            'goals' => '',
        );
        /**
         * @var $title
         * @var $type
         * @var $category
         * @var $tags
         * @var $description
         * @var $content
         * @var $img
         * @var $video
         * @var $basic
         * @var $goals
         */
        extract(array_merge($ext, $val));
        $slug = toAscii($title);
        if ($id) {
            $this->db->query("UPDATE helps SET slug=?,title=?,description=?,content=?,video_link=?,tags=?,type=?,category=?,basic=?,goals=? WHERE id=?",
                $slug,$title, $description, $content,$video,$tags,$type,$category,$basic,$goals, $id);
            if ($img) {
                $this->db->query("UPDATE helps SET banner=? WHERE id=?", $img, $id);
            }
        } else {
            $this->db->query("INSERT INTO helps (slug,title,description,content,video_link,banner,tags,type,category,basic,goals,created_at)VALUES(?,?,?,?,?,?,?,?,?,?,?,?)",
            $slug,$title, $description, $content,$video,$img,$tags,$type,$category,$basic,$goals,time());
        }
    }

    public function getHelps($type = null, $category = null, $basic = null, $goals = null, $term = null) {
        $sql = "SELECT * FROM helps WHERE id!=? ";
        $param = array('');
        if ($type) {
            $sql .= " AND type=? ";
            $param[] = $type;
        }
        if ($category) {
            $sql .= " AND category=? ";
            $param[] = $category;
        }
        if ($basic) {
            $sql .= " AND basic=? ";
            $param[] = 1;
        }
        if ($goals) {
            $sql .= " AND goals=? ";
            $param[] = 1;
        }
        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ? ) ";
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        return $this->db->paginate($sql, $param, 20);
    }

    public function findHelp($id) {
        $query = $this->db->query("SELECT * FROM helps WHERE id=? OR slug=?", $id, $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}