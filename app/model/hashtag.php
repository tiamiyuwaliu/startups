<?php 
class HashtagModel extends Model {
    public function getHashtags($term = "") {
        $sql = "SELECT * FROM hashtags WHERE userid=?";
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


    public function getAllHashtags() {
        $sql = "SELECT * FROM hashtags WHERE userid=?";
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

        $this->db->query("INSERT INTO hashtags (userid,title,content,created)VALUES(?,?,?,?)",
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
        $this->db->query("UPDATE hashtags SET title=?,content=? WHERE id=? AND userid=?", $title,$content, $id, model('user')->authOwnerId);

    }

    public function delete($id) {
        $this->db->query("DELETE FROM hashtags WHERE id=? AND userid=?", $id, model('user')->authOwnerId);
    }

    public function find($id) {
        $query = $this->db->query("SELECT * FROM hashtags WHERE id=?", $id);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}