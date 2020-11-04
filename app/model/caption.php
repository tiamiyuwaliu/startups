<?php
class CaptionModel extends Model {
    public function getCaptions($term = "") {
        $sql = "SELECT * FROM captions WHERE userid=?";
        $param = array(model('user')->authOwnerId);


        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ? OR caption LIKE ?) ";
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getAllCaptions() {
        $sql = "SELECT * FROM captions WHERE userid=?";
        $param = array(model('user')->authOwnerId);


        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function add($val) {
        $ext = array(
            'title' => '',
            'content' => '',
        );

        /**
         * @var $title
         * @var $content
         */
        extract(array_merge($ext, $val));

        $this->db->query("INSERT INTO captions (userid,title,caption,created)VALUES(?,?,?,?)",
            model('user')->authOwnerId,$title,$content,time());
    }



    public function save($val, $id) {
        $ext = array(
            'title' => '',
            'content' => ''
        );

        /**
         * @var $title
         * @var $content
         */
        extract(array_merge($ext, $val));
        $this->db->query("UPDATE captions SET title=?,caption=? WHERE id=? AND userid=?", $title,$content, $id, model('user')->authOwnerId);

    }

    public function delete($id) {
        $this->db->query("DELETE FROM captions WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM captions WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}