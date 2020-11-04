<?php
class FileModel extends Model {
    public function getFolderColors() {
        return array(
            '#2f3542',
            '#55efc4',
            '#81ecec',
            '#74b9ff',
            '#a29bfe',
            '#00b894',
            '#00cec9',
            '#0984e3',
            '#6c5ce7',
            '#ffeaa7',
            '#fab1a0',
            '#ff7675',
            '#fd79a8',
            '#fdcb6e',
            '#e17055',
            '#d63031',
            '#e84393',
            '#2d3436',
            '#e1b12c',
            '#273c75',
            '#e84118',
            '#487eb0',
            '#B53471',
            '#ff7f50',
            '#0be881',
            '#ef5777',
            '#0fbcf9',
            '#32ff7e'
        );
    }
    public function getFiles($offset = 0, $folderId = 0) {
        $sql = "SELECT * FROM files WHERE userid=? AND (file_type=? OR file_type=?) ";
        $param = array(model('user')->authOwnerId, 'image', 'video');
        if ($folderId != 'all') {
            $sql .= " AND folder_id=?";
            $param[] = $folderId;
        } elseif ($folderId == 0) {
            $sql .= " AND folder_id=0 ";
        }

        $sql .= " ORDER BY sort_number ASC LIMIT 40 OFFSET $offset ";
        $query  = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFolders($id = null) {
        if($id) {
            $query = $this->db->query("SELECT * FROM files WHERE userid=? AND file_type=? AND folder_id=? ORDER BY sort_number ASC", model('user')->authOwnerId, 'folder', $id);
        } else {
            $query = $this->db->query("SELECT * FROM files WHERE userid=? AND file_type=? AND folder_id=? ORDER BY sort_number ASC", model('user')->authOwnerId, 'folder', 0);

        }
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loadFiles($offset = 0, $folderId = 0, $limit =  100) {
        $sql = "SELECT * FROM files WHERE userid=? AND (file_type=? OR file_type=?) AND folder_id=?";
        $param = array(model('user')->authOwnerId, 'image', 'video', $folderId);


        $sql .= " ORDER BY sort_number ASC ";
        $sql .= " LIMIT $limit OFFSET $offset";

        return $this->db->query($sql, $param);
    }

    public function addFolder($val) {
        /**
         * @var $name
         * @var $folder_id
         * @var $color
         */
        extract($val);
        $this->db->query("INSERT INTO files (userid,resize_image,file_name,file_size,file_type,created,folder_id,folder_color)VALUES(?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId,'',$name,0,'folder',time(),$folder_id, $color);
        $fileId =  $this->db->lastInsertId();
        Hook::getInstance()->fire('new.file.folder', null, array($val, $fileId));
        return $fileId;
    }

    public function move($file, $folder) {
        $this->db->query("UPDATE files SET folder_id=? WHERE id=?", $folder, $file);
    }

    public function saveFolder($val) {
        /**
         * @var $name
         * @var $folder_id
         * @var $color
         */
        extract($val);
        $this->db->query("UPDATE files SET file_name=?,folder_color=? WHERE id=?", $name, $folder_id, $color);
        Hook::getInstance()->fire('save.file.folder', null, array($val));
    }

    public function save($val) {
        $ext = array(
            'file_name' => '',
            'file_size' => '',
            'file_type' => '',
            'resize_image' => '',
            'folder_id' => '0'
        );
        /**
         * @var $file_name
         * @var $file_size
         * @var $file_type
         * @var $resize_image
         * @var $folder_id
         */
        extract(array_merge($ext, $val));

        $file_size = round($file_size / 1024);
        $query = $this->db->query("INSERT INTO files (userid,resize_image,file_name,file_size,file_type,created,folder_id,sort_number) VALUES(?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId,$resize_image,$file_name,$file_size,$file_type, time(),$folder_id, 0);
        return $this->db->lastInsertId();
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM files WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $file = $this->find($id);
        if ($file['file_type'] == 'folder') {
            $this->db->query("DELETE FROM files WHERE id=?", $id);
            $query = $this->db->query("SELECT * FROM files WHERE folder_id=?", $id);
            while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->delete($fetch['id']);
            }
        } else {
            delete_file(path($file['file_name']));
            if($file['resize_image']) delete_file(path($file['resize_image']));
            $this->db->query("DELETE FROM files WHERE id=?", $id);
        }
    }

    public function validSelectedFile($fileName) {
        $validated = false;
        if (isImage($fileName) or isVideo($fileName)) {
            if(isImage($fileName)) $validated = true;
            if(isVideo($fileName)) $validated = true;

            $validated = Hook::getInstance()->fire('validate.file.selected', $validated, array($fileName));
        }

        return $validated;
    }

    public function findId($media) {
        $query = $this->db->query("SELECT * FROM files WHERE file_name=?", $media);
        $file =  $query->fetch(PDO::FETCH_ASSOC);
        return ($file) ? $file['id'] : '';
    }
}