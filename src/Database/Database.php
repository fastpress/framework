<?php

namespace Fastpress\Database;

class Wrapper
{
    public $conn;

    public function __construct(\PDO $pdo)
    {
        $this->conn = $pdo;
    }

                    
    public function queryHandler($clause, $query, $param)
    {
        if (!$param) {
            $stmt = $this->conn->query($query);
        }

        if ($param) {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($param);
        }

        if ($clause == 'SELECT') {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        if ($clause == 'INSERT') {
            return $this->conn->lastInsertId();
        }

        return $stmt->rowCount();
    }

    public function select($query, array $param = [])
    {
        $stmt =  $this->queryHandler('SELECT', $query, $param);
        return $stmt;
    }

    public function update($query, array $param = [])
    {
        $stmt =  $this->queryHandler('UPDATE', $query, $param);
        return $stmt;
    }

    public function delete($query, array $param = [])
    {
        $stmt =  $this->queryHandler('DELETE', $query, $param);
        return $stmt;
    }

    public function insert($query, array $param = [])
    {
        $stmt =  $this->queryHandler('INSERT', $query, $param);
        return $stmt;
    }
}
