<?php
class MentionModel extends Model {
    public function getMentions($term = "") {
        $sql = "SELECT * FROM mentions WHERE userid=?";
        $param = array(model('user')->authOwnerId);


        if ($term ) {
            $term = '%'.$term.'%';
            $sql .= " AND (title LIKE ? OR content LIKE ?) ";
            $param[] = $term;
            $param[] = $term;
        }

        $sql .= " ORDER BY id DESC ";
        $query = $this->db->query($sql, $param);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getAllMentions() {
        $sql = "SELECT * FROM mentions WHERE userid=?";
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

        $this->db->query("INSERT INTO mentions (userid,title,content,created)VALUES(?,?,?,?)",
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
        $this->db->query("UPDATE mentions SET title=?,content=? WHERE id=? AND userid=?", $title,$content, $id, model('user')->authOwnerId);

    }

    public function delete($id) {
        $this->db->query("DELETE FROM mentions WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM mentions WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}