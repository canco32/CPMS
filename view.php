<?php 

class Viewer {
    private $dbConnection;

    public function __construct(PostgreSQLConnection $dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    public function displayTables($table_name = NULL) {
        $result = "";
    
        if ($table_name == NULL) {
            $result = "
                    <h2>Таблиці</h2><br>
                    <table class=\"table\">
                        <thead>
                            <tr>
                                <th>Назва таблиці</th>
                                <th>Кількість записів</th>
                            </tr>
                        </thead>
                        <tbody>
                  ";
            try {
                $pdo = $this->dbConnection->getConnection();
                $tables = $this->getAllTables($pdo);
    
                foreach ($tables as $table) {
                    $result .= "<tr>";
                    $result .= "<td><a href=\"index.php?view_table={$table['table_name']}\">{$table['table_name']}</a></td>";
                    $result .= "<td>{$table['row_count']}</td>";
                    $result .= "</tr>";
                }
    
                $result .= "    </tbody>
                            </table>
                           ";
            } catch (PDOException $e) {
                echo "<br>Помилка виконання запиту: " . $e->getMessage();
            }
        } else {
            $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
            $result = "<h2>$table_sanitized</h2><br>";
    
            try {
                $pdo = $this->dbConnection->getConnection();
                $columns_data = $this->getColumnsNames($pdo, $table_sanitized);
                $table_data = $this->getNecessaryTable($pdo, $table_sanitized);
                $first_column_saved = false;
                $data_id = "";
    
                $result .= "
                            <table class=\"table\">
                                <thead>
                                    <tr>
                            ";
    
                foreach ($columns_data as $column) {
                    if (!$first_column_saved) {
                        $data_id = $column['column_name'];
                    }
                    $result .= "<th>{$column['column_name']}</th>";
                    $first_column_saved = true;
                }

                $result .= "<th style=\"text-align: center;\" colspan=\"2\">Дії</th>";
    
                $result .= "       </tr>
                                </thead>
                                <tbody>
                            ";
    
                foreach ($table_data as $row) {
                    $result .= "<tr>";
    
                    foreach ($row as $cell) {
                        $result .= "<td>{$cell}</td>";
                    }
                    $result .= "<td><a href=\"index.php?edit_table=$table_sanitized&edit=$row[$data_id]\">Змінити</a></td>";
                    $result .= "<td><a href=\"index.php?edit_table=$table_sanitized&delete=$row[$data_id]\">Видалити</a></td>";
                    $result .= "</tr>";
                }
    
                $result .= "    </tbody>
                            </table>
                            ";
            } catch (PDOException $e) {
                echo "<br>Помилка виконання запиту: " . $e->getMessage();
            }
        }
    
        return $result;
    }
    public function addNote($table_name) {
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Додання запису до таблиці \"$table_sanitized\"</h2><br>";
        try {
            $pdo = $this->dbConnection->getConnection();
            $columns_data = $this->getColumnsNames($pdo, $table_sanitized);
            $result .= "<form method=\"post\">";
            $id = 0;
            foreach($columns_data as $input) {
                if($id != 0) {
                    if (stripos($input['column_name'], 'date') !== false || stripos($input['column_name'], 'deadline') !== false) {
                        $result.=   "
                        <div class=\"form-group\">
                            <label for=\"input$id\">Поле \"{$input['column_name']}\"</label>
                            <input name=\"{$input['column_name']}\" type=\"date\" class=\"form-control\" placeholder=\"Введіть значення для поля '{$input['column_name']}'\">
                        </div>
                        ";
                    } else {
                        $result.=   "
                            <div class=\"form-group\">
                                <label for=\"input$id\">Поле \"{$input['column_name']}\"</label>
                                <input name=\"{$input['column_name']}\" type=\"text\" class=\"form-control\" placeholder=\"Введіть значення для поля '{$input['column_name']}'\">
                            </div>
                            ";
                    }
                }
                $id++;
            }
            $result.=   "
                            <button type=\"submit\" class=\"btn btn-primary\">Готово</button>
                        </form>
                        ";
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processForm($pdo, $table_sanitized, $columns_data, $_POST);
            }
        } catch (PDOException $e) {
            echo "<br>Помилка виконання запиту: " . $e->getMessage();
        }
        return $result;
    }
    public function editTableNote($table_name, $note_id) {
        $note_id = trim(filter_var($note_id, FILTER_SANITIZE_SPECIAL_CHARS));
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Редагування запису №$note_id у таблиці \"$table_sanitized\"</h2><br>";
        try {
            $pdo = $this->dbConnection->getConnection();
            $columns_data = $this->getColumnsNames($pdo, $table_sanitized);
            $columns_content = $this->getColumnsContentById($pdo, $table_sanitized, $note_id);
            $result .= "<form method=\"post\">";
            $id = 0;
            foreach($columns_data as $input) {
                if($id != 0) {
                    $column_name = $input['column_name'];
                    $value = $columns_content[0][$column_name];

                    if (stripos($input['column_name'], 'date') !== false || stripos($input['column_name'], 'deadline') !== false) {
                        $result.=   "
                        <div class=\"form-group\">
                            <label for=\"input$id\">Поле \"{$input['column_name']}\"</label>
                            <input name=\"{$input['column_name']}\" type=\"date\" value=\"$value\" class=\"form-control\" placeholder=\"Введіть значення для поля '{$input['column_name']}'\">
                        </div>
                        ";
                    } else {
                        $result.=   "
                            <div class=\"form-group\">
                                <label for=\"input$id\">Поле \"{$input['column_name']}\"</label>
                                <input name=\"{$input['column_name']}\" type=\"text\" value=\"$value\" class=\"form-control\" placeholder=\"Введіть значення для поля '{$input['column_name']}'\">
                            </div>
                            ";
                    }
                }
                $id++;
            }
            $result.=   "
                            <button type=\"submit\" class=\"btn btn-primary\">Готово</button>
                        </form>
                        ";
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processUpdateNote($pdo, $table_sanitized, $columns_data, $_POST, $note_id);
            }
        } catch (PDOException $e) {
            echo "<br>Помилка виконання запиту: " . $e->getMessage();
        }
        return $result;
    }
    public function deleteTableNote($table_name, $note_id) {
        $note_id = trim(filter_var($note_id, FILTER_SANITIZE_SPECIAL_CHARS));
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Видалення запису №$note_id з таблиці \"$table_sanitized\"</h2><br>";
        $pdo = $this->dbConnection->getConnection();
        $this->processDeleteNote($pdo, $table_sanitized, $note_id);
        return $result;
    }
    public function dataTableFill($table_name) {
        $table_sanitized = trim(filter_var($table_name, FILTER_SANITIZE_SPECIAL_CHARS));
        $result = "<h2>Додання випадкових полів до таблиці \"$table_sanitized\"</h2><br>";

        $result .= "
                    <form method=\"post\">
                        <div class=\"form-group\">
                            <label for=\"input\">Введіть кількість нових випадкових полей для таблиці</label>
                            <input name=\"fillTable\" type=\"number\" min=\"1\" max=\"100000\" class=\"form-control\" placeholder=\"Введіть ціле значення від 0 до 100000  \">
                        </div>
                        <button type=\"submit\" class=\"btn btn-primary\">Створити</button>
                    </form>
                    ";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = $this->dbConnection->getConnection();
            $this->randomFillTable($pdo, $table_sanitized, $_POST);
        }
        return $result;
    }
    private function randomFillTable($pdo, $table_name, $form_data) {
        $faker = new Faker\Generator();
        $faker->addProvider(new Faker\Provider\uk_UA\Person($faker));
        $faker->addProvider(new Faker\Provider\uk_UA\Company($faker));
        $limit = $form_data['fillTable'];
        $startTime = microtime(true);
        $columns_data = $this->getColumnsNames($pdo, $table_name);
        $primary_key_name = $columns_data[0]['column_name'];
        $sql = "INSERT INTO \"$table_name\" (";
    
        foreach ($columns_data as $column) {
            $column_name = $column['column_name'];
            if ($column_name !== $primary_key_name) {
                $sql .= "$column_name, ";
            }
        }
    
        $sql = rtrim($sql, ", ") . ") VALUES ";
    
        for ($i = 0; $i < $limit; $i++) {
            $sql .= "(";
            foreach ($columns_data as $column) {
                $column_name = $column['column_name'];
                if($column_name !== $primary_key_name) {
                    switch($column_name) {
                        case "employee_department":
                            $sql .= "(SELECT department_id FROM \"Department\" ORDER BY RANDOM() LIMIT 1), ";
                            break;
                        case "department_name":
                            $fake_department = $faker->company();
                            $sql .= "('$fake_department'),";
                            break;
                        case "employee_name":
                            $fake_name = $faker->name();
                            if (strpos($fake_name, "'") !== false) {
                                $fake_name = str_replace("'", "`", $fake_name);
                            }
                            $sql .= "('$fake_name'),";
                            break;
                        case "employee_position":
                            $fake_position = $faker->jobTitle();
                            $sql .= "('$fake_position'),";
                            break;
                        case "task_employee":
                            $sql .= "(SELECT employee_id FROM \"Employees\" ORDER BY RANDOM() LIMIT 1), ";
                            break;
                        case "task_project":
                            $sql .= "(SELECT project_id FROM \"project\" ORDER BY RANDOM() LIMIT 1), ";
                            break;
                        case "project_department":
                            $sql .= "(SELECT department_id FROM \"Department\" ORDER BY RANDOM() LIMIT 1), ";
                            break;
                        case "task_date":
                            $sql .= "
                                    (SELECT
                                    date_trunc('day', CURRENT_DATE - INTERVAL '1 month') +
                                    random() * (CURRENT_DATE - date_trunc('day', CURRENT_DATE - INTERVAL '1 month')) AS random_date
                                    ), 
                                    ";
                            break;
                        case "task_deadline":
                                $sql .= "
                                        (SELECT
                                        CURRENT_DATE +
                                        random() * INTERVAL '6 months' AS random_date
                                        )
                                        ";
                                break;
                        case "employee_salary":
                            $sql .= "('$' || floor(random() * (10000 - 300 + 1) + 300)::int), ";
                            break;
                        default: 
                            $sql .= $this->generateRandomSqlValue($pdo, $column) . ", ";
                            break;
                    }
                }
            }
            $sql = rtrim($sql, ", ") . "), ";
        }
    
        $sql = rtrim($sql, ", ") . ";";
    
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        echo "Запит виконано за " . number_format($executionTime, 2, '.', ' ') . " мс";
    }
    

    private function generateRandomSqlValue($pdo, $column) {
        switch ($column['data_type']) {
            case 'integer':
                return 'floor(random() * 100)';
            case 'character varying':
                return "(SELECT left(md5(random()::text), 10))"; 
            default:
                return 'NULL';
        }
    }
      
    private function getAllTables(PDO $pdo) {
        $sql = "
            SELECT table_name, (xpath('/row/cnt/text()', xml_count))[1]::text::int AS row_count
            FROM (
                SELECT table_name,
                    query_to_xml(format('SELECT COUNT(*) AS cnt FROM %I.%I', table_schema, table_name), false, true, '') AS xml_count
                FROM information_schema.tables
                WHERE table_schema = 'public'
            ) AS counts;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $tables;
    }
    private function getNecessaryTable(PDO $pdo, $table_name, $note_id = NULL, $primary_key = NULL) {
        $primary_key_table = $this->getColumnsNames($pdo, $table_name, NULL, 1);
        $primary_key_name = $primary_key_table[0]['column_name'];
        if($note_id != NULL && $primary_key != NULL) {
            $sql = "SELECT * FROM \"$table_name\" WHERE $primary_key = '$note_id'";
        } else {
            $sql = "SELECT * FROM \"$table_name\" ORDER BY $primary_key_name ASC";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    
        $table_content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $table_content;
    } 

    private function getColumnsNames(PDO $pdo, $table_name, $limit = NULL) {
        if($limit == NULL) {
            $sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table_name' ORDER BY ordinal_position";
        } else {
            $sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table_name' ORDER BY ordinal_position LIMIT $limit";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    
        $columns_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return $columns_names;
    }   

    private function processForm(PDO $pdo, $table_name, $columns_data, $form_data) {
        try {
            $valid_data = [];
            $current_date = new DateTime();
            $one_week_later = (clone $current_date)->add(new DateInterval('P7D'))->format('Y-m-d H:i:sP');

            foreach ($columns_data as $column_info) {
                if ($column_info === $columns_data[0]) {
                    continue;
                }
            
                $column = $column_info['column_name'];

                if (is_scalar($column) && isset($form_data[$column]) && !empty($form_data[$column])) {
                    $valid_data[$column] = trim(filter_var($form_data[$column], FILTER_SANITIZE_SPECIAL_CHARS));
                    if (isset($form_data[$column]) && !empty($form_data[$column])) {
                        if (stripos($column, 'date') !== false || stripos($column, 'deadline') !== false) {
                            $date_time = DateTime::createFromFormat('Y-m-d', $form_data[$column]);
                            $valid_data[$column] = $date_time ? $date_time->format('Y-m-d H:i:sP') : $one_week_later;
                        } else {
                            $valid_data[$column] = trim(filter_var($form_data[$column], FILTER_SANITIZE_SPECIAL_CHARS));
                        }
                    }
                } else {
                    echo "Поле '$column' обов'язкове для заповнення.";
                    return;
                }
            }
 
            $columns_str = implode(', ', array_keys($valid_data));
            $values_str = implode(', ', array_fill(0, count($valid_data), '?'));
    
            $sql = "INSERT INTO \"$table_name\" ($columns_str) VALUES ($values_str)";
            $stmt = $pdo->prepare($sql);
    
            $i = 1;
            foreach ($valid_data as $value) {
                $stmt->bindValue($i++, $value);
            }
    
            $stmt->execute();
    
            echo "Дані успішно додані до таблиці $table_name.";
    
        } catch (PDOException $e) {
            echo "Помилка при вставці даних: " . $e->getMessage();
        }
    }   
    private function processDeleteNote(PDO $pdo, $table_name, $note_id) {
        try {
            $first_columnQuery = "
                                SELECT column_name
                                FROM information_schema.columns
                                WHERE table_name = :table_name
                                ORDER BY ordinal_position
                                LIMIT 1
                                ";
    
            $stmt = $pdo->prepare($first_columnQuery);
            $stmt->bindParam(':table_name', $table_name, PDO::PARAM_STR);
            $stmt->execute();
    
            $first_column = $stmt->fetchColumn();
    
            $delete_query = "DELETE FROM \"$table_name\" WHERE $first_column = :note_id";
            $stmt = $pdo->prepare($delete_query);
            $stmt->bindParam(':note_id', $note_id, PDO::PARAM_INT);
            $stmt->execute();
    
            echo "Запис успішно видалена із таблиці $table_name.";
    
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            if ($errorCode == '23503') {
                echo "Помилка зовнішнього ключа: $errorMessage";
            } else {
                echo "Інша помилка: $errorMessage";
            }
        }
    }
    private function getColumnsContentById($pdo, $table_name, $note_id) {
        $first_element = $this->getColumnsNames($pdo, $table_name, 1);
        foreach($first_element as $primary_key) {
            $table_primary_key = $primary_key['column_name'];
            break;
        }
        $result = $this-> getNecessaryTable($pdo, $table_name, $note_id, $table_primary_key);
        return $result;
    }
    private function processUpdateNote(PDO $pdo, $table_name, $columns_data, $form_data, $note_id) {
        try {
            $update_query = "UPDATE \"$table_name\" SET ";
            $primary_key_name = $columns_data[0]['column_name'];
            foreach ($columns_data as $input) {
                $column_name = $input['column_name'];
                if ($column_name != $primary_key_name) {
                    $update_query .= "$column_name = :$column_name, ";
                }
            }
    
            $update_query = rtrim($update_query, ', ');
            $update_query .= " WHERE $primary_key_name = :$primary_key_name";
            $stmt = $pdo->prepare($update_query);

            foreach ($columns_data as $input) {
                $column_name = $input['column_name'];
    
                if ($column_name != $primary_key_name) {
                    $input_value = trim(filter_var($form_data[$column_name], FILTER_SANITIZE_SPECIAL_CHARS));
                    $stmt->bindValue(":$column_name", $input_value);
                }
            }
            $stmt->bindValue(":$primary_key_name", $note_id);
            $stmt->execute();
    
            echo "Дані успішно оновлено у таблиці $table_name.";
        } catch (PDOException $e) {
            echo "Помилка оновлення даних: " . $e->getMessage();
        }
    }
}
?>