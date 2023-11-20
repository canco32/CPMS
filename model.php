<?php

class PostgreSQLConnection {

    private $host = "localhost";
    private $port = 5432;
    private $database = "postgres";
    private $username = "postgres";
    private $password = "1";

    private $connection;

    public function __construct() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Помилка підключення: " . $e->getMessage();
        }
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function getConnection() {
        return $this->connection;
    }
}
class DataBaseActions {
    private $dbConnection;

    public function __construct(PostgreSQLConnection $dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    public function dataTableDelete($table_name) {
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Видалення таблиці \"$table_sanitized\"</h2><br>";

        try {
            $pdo = $this->dbConnection->getConnection();
            if ($this->checkForeignKey($table_sanitized)) {
                $result .= "<p>Таблиця містить зовнішній ключ. Видаліть зовнішній ключ перед видаленням таблиці.</p>";
            } else {
                $sql = $this->generateDeleteTableSQL($table_sanitized);
                $pdo->exec($sql);

                $result .= "<p>Таблиця \"$table_sanitized\" успішно видалено.</p>";
            }
        } catch (PDOException $e) {
            $result .= "<br>Помилка виконання запиту: " . $e->getMessage();
        }

        return $result;
    }

    public function cascadeTruncate($table_name) {
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Каскадне очищення таблиці \"$table_sanitized\"</h2><br>";

        try {
            $pdo = $this->dbConnection->getConnection();
            $sql = $this->generateTruncateTableSQL($table_sanitized);
            $pdo->exec($sql);
            $result .= "<p>Таблиця \"$table_sanitized\" та усі залежні від неї таблиці каскадно очищені.</p>";
        } catch (PDOException $e) {
            $result .= "<br>Помилка каскадного видалення: " . $e->getMessage();
        }

        return $result;
    }

    private function checkForeignKey($table_name) {
        $pdo = $this->dbConnection->getConnection();
        $sql = "SELECT COUNT(*) FROM information_schema.table_constraints 
                WHERE constraint_type = 'FOREIGN KEY' AND table_name = :table_name";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':table_name', $table_name, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    private function generateDeleteTableSQL($table_name) {
        return "DROP TABLE IF EXISTS \"$table_name\"";
    } 

    private function generateTruncateTableSQL($table_name) {
        return "TRUNCATE \"$table_name\" RESTART IDENTITY CASCADE";
    } 
}

?>