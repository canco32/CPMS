<?php 

require_once('model.php');
require_once('view.php');
require_once ('vendor/autoload.php');

$connection = new PostgreSQLConnection();
$viewer = new Viewer($connection);
$database = new DataBaseActions($connection);


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <title>CPMS, Silin KV-12</title>
    </head>
    <body>
        <div class="container mt-4">
            <?php if(isset($_GET['view_table'])): ?>
                <a class="btn btn-primary mb-2" href="index.php">Усі таблиці</a>
                <a class="btn btn-primary mb-2" href="index.php?add_note=<?=$_GET['view_table']?>">Додати запис</a>
                <a class="btn btn-primary mb-2" href="index.php?delete_table=<?=$_GET['view_table']?>">Видалити таблицю</a>
                <a class="btn btn-primary mb-2" href="index.php?truncate_table=<?=$_GET['view_table']?>">Очистити каскадно</a>
                <a class="btn btn-primary mb-2" href="index.php?fill_table=<?=$_GET['view_table']?>">Випадкові поля</a>
                <?= $viewer->displayTables($_GET['view_table']); ?>
            <?php endif; ?>
            <?php if(!isset($_GET['view_table'])): ?>
                <a class="btn btn-primary mb-2" href="index.php">Оновити таблиці</a>
                <?= $viewer->displayTables(); ?>
            <?php endif; ?>
            <?php if(isset($_GET['add_note'])): ?>
                <?= $viewer->addNote($_GET['add_note']); ?>
            <?php endif; ?>
            <?php if(isset($_GET['edit_table']) && isset($_GET['edit'])): ?>
                <?= $viewer->editTableNote($_GET['edit_table'], $_GET['edit']); ?>
            <?php endif; ?>
            <?php if(isset($_GET['edit_table']) && isset($_GET['delete'])): ?>
                <?= $viewer->deleteTableNote($_GET['edit_table'], $_GET['delete']); ?>
            <?php endif; ?>
            <?php if(isset($_GET['delete_table'])): ?>
                <?= $database->dataTableDelete($_GET['delete_table']);?>
            <?php endif; ?>
            <?php if(isset($_GET['truncate_table'])): ?>
                <?= $database->cascadeTruncate($_GET['truncate_table']);?>
            <?php endif; ?>
            <?php if(isset($_GET['fill_table'])): ?>
                <?= $viewer->dataTableFill($_GET['fill_table']);?>
            <?php endif; ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <?php $connection->disconnect(); ?>
    </body>
</html>