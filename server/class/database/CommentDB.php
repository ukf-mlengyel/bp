<?php

require_once "DB.php";

class CommentDB extends DB{
    public static function getComments(int $parent_type, int $parent_id, int $userid, int $offset) : array{
        // kolko vysledkov sa bude zobrazovat na jednu stranu
        // TODO: Viac strán pre komentáre
        $limit = 25;
        $offset *= $limit;

        // query podla toho ci je uzivatel prihlaseny
        $query = $userid > 0 ?
            "SELECT c.id, c.user_id, u.username, u.image, DATE_FORMAT(c.creation_date, '%d.%m.%Y, %H:%i') AS creation_date, c.content, 
            (SELECT COUNT(*) FROM ".self::COMMENTLIKETABLE." l WHERE c.id = l.comment_id) AS likecount,
            (SELECT COUNT(*) FROM ".self::COMMENTLIKETABLE." l WHERE c.id = l.comment_id AND l.user_id = $userid) AS isliked
            FROM ".self::COMMENTTABLE." c
            INNER JOIN ".self::USERTABLE." u
            ON c.user_id = u.id
            WHERE c.parent_type = $parent_type 
            AND c.parent_id = $parent_id
            ORDER BY c.creation_date DESC
            LIMIT $limit OFFSET $offset;
            " :
            "SELECT c.id, c.user_id, u.username, u.image, c.creation_date, c.content, 
            (SELECT COUNT(*) FROM ".self::COMMENTLIKETABLE." l WHERE c.id = l.comment_id) AS likecount
            FROM ".self::COMMENTTABLE." c
            INNER JOIN ".self::USERTABLE." u
            ON c.user_id = u.id
            WHERE c.parent_type = $parent_type 
            AND c.parent_id = $parent_id
            ORDER BY c.creation_date DESC
            LIMIT $limit OFFSET $offset;
            ";

        $conn = self::getConnection();
        if ($conn->connect_error) return array();

        $result = $conn->query($query);
        $conn->close();

        $rows = array();
        while($r = $result->fetch_assoc()){
            $rows[] = $r;
        }
        return $rows;
    }

    public static function addComment(int $parent_type, int $parent_id, int $userid, string $content) : array{
        $conn = self::getConnection();
        if($conn->connect_error) return array(false, 0);

        $content = mysqli_real_escape_string($conn, $content);

        $stmt = $conn->prepare("INSERT INTO ".self::COMMENTTABLE." (parent_type, parent_id, user_id, content) VALUES (?,?,?,?)");
        $stmt->bind_param("iiis",
            $parent_type, $parent_id, $userid, $content);

        $result = $stmt->execute();
        $id = mysqli_insert_id($conn);
        $stmt->close();
        $conn->close();

        return array($result, $id);
    }

    public static function removeComment(int $id, int $userid) : bool{
        // zistime ci je spravne user id
        $conn = self::getConnection();
        $commentuid = $conn->query("SELECT user_id FROM ".self::COMMENTTABLE." WHERE id = $id")->fetch_array()[0];
        if ($commentuid != $userid) return false;

        //zmazeme liky a comment
        $conn->query("DELETE FROM ".self::COMMENTLIKETABLE." WHERE comment_id = $id;");
        return $conn->query("DELETE FROM ".self::COMMENTTABLE." WHERE id = $id");
    }
}
