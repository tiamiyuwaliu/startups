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
    public function getFiles($offset = 0, $folderId = 0, $type = 'all',$limit = 40, $term = null) {

        $sql = "SELECT * FROM files WHERE userid=? AND workspace_id=?";
        $param = array(model('user')->authOwnerId, $this->controller()->workspaceId);
        if ($type == 'all') {
            $sql .= " AND (file_type=? OR file_type=?) ";
            $param[] = 'image';
            $param[] = 'video';
        } else {
            $sql .= " AND file_type=? ";
            $param[] = $type;
        }
        if ($folderId) {
            if ($folderId == 'design') {
                $sql .= " AND via_design=? ";
                $param[] = 1;
            } else {
                $sql .= " AND folder_id=?";
                $param[] = $folderId;
            }
        }

        if ($term) {
            $sql .= " AND description LIKE '%$term%'";
        }
        if ($folderId and $folderId != 'design') {
            $sql .= " ORDER BY sort_number ASC ";
        } else {
            $sql .= " ORDER BY id DESC ";
        }
        if ($limit) $sql .= " LIMIT 40 OFFSET $offset ";
        $query  = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFolders($id = null) {
        if($id) {
            $query = $this->db->query("SELECT * FROM files WHERE userid=? AND workspace_id=? AND file_type=? AND folder_id=? ORDER BY sort_number ASC", model('user')->authOwnerId, $this->controller()->workspaceId, 'folder', $id);
        } else {
            $query = $this->db->query("SELECT * FROM files WHERE userid=? AND workspace_id=? AND file_type=? AND folder_id=? ORDER BY sort_number ASC", model('user')->authOwnerId, $this->controller()->workspaceId, 'folder', 0);

        }
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loadFiles($offset = 0, $folderId = 0, $limit =  100) {
        $sql = "SELECT * FROM files WHERE userid=? AND workspace_id=? AND (file_type=? OR file_type=?) AND folder_id=?";
        $param = array(model('user')->authOwnerId, $this->controller()->workspaceId, 'image', 'video', $folderId);


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
        $this->db->query("INSERT INTO files (userid,workspace_id,action_userid,resize_image,file_name,file_size,file_type,created,folder_id,folder_color)VALUES(?,?,?,?,?,?,?,?,?,?)",
            model('user')->authOwnerId, $this->controller()->workspaceId,$this->model('user')->authId,'',$name,0,'folder',time(),$folder_id, $color);
        $fileId =  $this->db->lastInsertId();
        Hook::getInstance()->fire('new.file.folder', null, array($val, $fileId));
        return $fileId;
    }


    public function countDesigns() {
        $query = $this->db->query("SELECT * FROM files WHERE workspace_id=? AND via_design=?", $this->controller()->workspaceId, 1);
        return $query->rowCount();
    }

    public function saveFolder($val) {
        /**
         * @var $name
         * @var $folder_id
         * @var $color
         */
        extract($val);
        $this->db->query("UPDATE files SET file_name=?,folder_color=? WHERE id=?", $name,  $color, $folder_id);
        Hook::getInstance()->fire('save.file.folder', null, array($val));
    }

    public function save($val) {
        $ext = array(
            'file_name' => '',
            'file_size' => '',
            'file_type' => '',
            'resize_image' => '',
            'folder_id' => '0',
            'via_design' => 0
        );
        /**
         * @var $file_name
         * @var $file_size
         * @var $file_type
         * @var $resize_image
         * @var $folder_id
         * @var $via_design
         */
        extract(array_merge($ext, $val));

        $file_size = round($file_size / 1024);
        $query = $this->db->query("INSERT INTO files (via_design,userid,workspace_id,action_userid,resize_image,file_name,file_size,file_type,created,folder_id,sort_number) VALUES(?,?,?,?,?,?,?,?,?,?,?)",
            $via_design,model('user')->authOwnerId, $this->controller()->workspaceId, $this->model('user')->authId,$resize_image,$file_name,$file_size,$file_type, time(),$folder_id, 0);
        $fileId = $this->db->lastInsertId();
        if ($folder_id) $this->rearrangeFolder($folder_id);
        return $fileId;
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM files WHERE id=? OR file_name=?", $id, $id);
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
            //delete_file(path($file['file_name']));
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

    public function countMedia($id = null) {
        $sql = "SELECT id FROM files WHERE workspace_id=? ";
        $param = array($this->controller()->workspaceId);
        if ($id) {
            $sql .= " AND  folder_id=?";
            $param[] = $id;
        } else {
            $sql .= " AND file_size !=? ";
            $param[] = 0;
        }
        $query = $this->db->query($sql, $param);
        return $query->rowCount();
    }

    public function rearrangeFolder($folderId) {
        $files = $this->getFiles(0, $folderId, 'all', false);
        $i = 1;
        foreach($files as $file) {
            $this->db->query("UPDATE files SET sort_number=? WHERE id=?", $i, $file['id']);
            $i++;
        }
    }

    public function copy($ids, $folderId, $source) {

        foreach(explode(',', $ids) as $id) {

            if ($source == 'files') {
                $file = $this->find($id);
                $this->db->query("INSERT INTO files (userid,workspace_id,action_userid,resize_image,file_name,file_size,file_type,folder_id,created,description)VALUES(?,?,?,?,?,?,?,?,?,?)",
                    $this->model('user')->authOwnerId, $this->controller()->workspaceId,$this->model('user')->authId,$file['resize_image'], $file['file_name'],$file['file_size'],$file['file_type'],$folderId,time(),$file['description']);
            }  elseif($source == 'graphics') {
                $file = $this->model('admin')->findGraphic($id);
                $this->db->query("INSERT INTO files (userid,workspace_id,action_userid,resize_image,file_name,file_size,file_type,folder_id,created,description)VALUES(?,?,?,?,?,?,?,?,?,?)",
                    $this->model('user')->authOwnerId, $this->controller()->workspaceId,$this->model('user')->authId,$file['file_url'], $file['file_url'],0,'image',$folderId,time(),$file['title']);

            }
        }
        $this->rearrangeFolder($folderId);
    }

    public function moveByIds($ids, $folderId) {
        foreach(explode(',', $ids) as $id) {
            $this->move($id, $folderId);
        }
    }
    public function move($file, $folder) {
        $this->db->query("UPDATE files SET folder_id=? WHERE id=?", $folder, $file);
        $this->rearrangeFolder($folder);
    }

    public function reOrder($files) {
        $i = 1;
        foreach($files as $file) {
            $this->db->query("UPDATE files SET sort_number=? WHERE id=?", $i, $file);
            $i++;
        }
    }

    public function saveDescription($id, $text) {
        $this->db->query("UPDATE files SET description=? WHERE id=?", $text, $id);
    }

    public function countSearch($term) {
        $query = $this->db->query("SELECT * FROM files WHERE description LIKE '%$term%' AND workspace_id=? ORDER BY id DESC", $this->controller()->workspaceId);
        return $query->rowCount();
    }
}