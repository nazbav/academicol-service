<?php
/**
 * Created by PhpStorm.
 * User: Назым
 * Date: 19.12.2019
 * Time: 00:37
 */

use Krugozor\Database\Mysql\Mysql;


/**
 * Class DataBase
 */
Class DataBase
{
    /**
     * @var Mysql
     */
    private $database;

    /**
     * DataBase constructor.
     * @param Mysql $mysql
     */
    public function __construct(Mysql $mysql)
    {
        $this->database = $mysql;
    }

    /**
     *
     * @param int $peer_id
     * @param int $type
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function peers_update(int $peer_id, int $type)
    {
        $this->database->query("UPDATE `peers` SET `type` = '?i' WHERE `peer_id` = '?i';", $type, $peer_id);
    }

    /**
     * @param array $task_to_delete
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function task_delete(array $task_to_delete)
    {
        $this->database->query("DELETE FROM tasks WHERE tasks.id IN(" . implode(',', $task_to_delete) . ");");//
    }

    /**
     * @return string
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function task_count()
    {
        $result = $this->database->query("SELECT COUNT(tasks.id) FROM tasks;");
        $result = $result->getOne();
        return $result ? $result : '0';
    }

    /**
     * @param int $int
     * @return array
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function task_get(int $int)
    {
        $result = $this->database->query("SELECT tasks.id, peers.peer_id, peers.type, peers.tag, replaces_files.name, replaces_files.url FROM tasks INNER JOIN peers ON peers.peer_id=tasks.peer_id INNER JOIN replaces_files ON tasks.replace_id=replaces_files.id LIMIT " . $int . ";");
        $result = $result->fetch_assoc_array();
        return $result ? $result : [];
    }

    /**
     * @param int $replaces_file
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function tasks_add(int $replaces_file)
    {
        $this->database->query("INSERT INTO tasks (peer_id, replace_id) SELECT DISTINCT peers.peer_id, '?i' FROM peers WHERE peers.tag IN(SELECT replaces.tag FROM replaces WHERE replaces.replace_file='?i');", $replaces_file, $replaces_file);//
    }

    /**
     * @param $replaces
     * @param array $tags
     * @return string
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function replaces_add($replaces, array $tags)
    {
        $tag_all = [];

        $this->database->query("INSERT IGNORE INTO replaces_files (`name`,`date`,`url`) VALUES ('?s','?i','?s');", $replaces['name'], $replaces['date'], $replaces['url']);
        // $result = $this->database->query("SELECT id FROM replaces_files WHERE name ='?s';", $replaces['name']);
        // $replaces_file = $result->getOne();
        $replaces_file = $this->database->getLastInsertId();
        foreach ($tags as $tag) {

            $tag_all [] = "('{$replaces_file}', '{$tag}')";
        }
        $tag_all = implode(',', $tag_all);

        $this->database->query("INSERT INTO replaces (replace_file,tag) VALUES " . $tag_all . ";");//костыль, кругозор вроде как не позволяет передавать несколько "скобок" для записи в базу.
        return $replaces_file;
    }

    /**
     * @param $url
     * @return bool
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function replaces_file_name($url)
    {
        $result = $this->database->query("SELECT id FROM replaces_files WHERE replaces_files.name='?s';", $url);
        $result = $result->fetch_assoc();
        return $result ? true : false;
    }

    /**
     * @param $peer_id
     * @param $tag
     * @param int $type
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function peers_add($peer_id, $tag, $type = 1)
    {
        $this->database->query("REPLACE INTO peers SET peer_id= '?i', tag='?s', type='?i';", $peer_id, $tag, $type);//
    }

    /**
     * @param $peer_id
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function peers_delete($peer_id)
    {
        $this->database->query("DELETE FROM peers WHERE peer_id = '?i';", $peer_id);//
    }

    /**
     * @param $peer_id
     * @return array
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function replaces_get_tag($peer_id)
    {
        $result = $this->database->query("SELECT peers.tag, replaces_files.date, replaces_files.name, replaces_files.url FROM peers INNER JOIN replaces ON peers.tag=replaces.tag INNER JOIN replaces_files ON replaces.replace_file = replaces_files.id WHERE peers.peer_id = '?i';", $peer_id);
        $result = $result->fetch_assoc_array();
        return $result ? $result : [];
    }

    /**
     * @param array $replaces
     * @throws \Krugozor\Database\Mysql\Exception
     */
    public function database_clear(array $replaces)
    {
        $this->database->query("DELETE FROM replaces WHERE replaces.replace_file IN(SELECT replaces_files.id FROM replaces_files WHERE replaces_files.name NOT IN(?as));", $replaces);
        $this->database->query("DELETE FROM replaces_files WHERE replaces_files.name NOT IN(?as);", $replaces);
    }
}