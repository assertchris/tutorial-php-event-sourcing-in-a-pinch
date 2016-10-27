<?php

// --------------- CONNECT TO A DATABASE ---------------

$connection = new PDO("sqlite::memory:");

$connection->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

// --------------- CREATE TABLES ---------------

$statement = $connection->prepare("
    CREATE TABLE IF NOT EXISTS product (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT
    )
");

$statement->execute();

// --------------- INSERT ROWS ---------------

$statement = $connection->prepare("INSERT INTO product (name) VALUES (:name)");
$statement->bindValue("name", "Chocolate");
$statement->execute();

// --------------- FETCH ONE ROW ---------------

$statement = $connection->prepare("
    SELECT * FROM product
");

$statement->execute();

$row = $statement->fetch(PDO::FETCH_ASSOC);

// --------------- FETCH MANY ROWS ---------------

$statement = $connection->prepare("
    SELECT * FROM product
");

$statement->execute();

$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

// --------------- INSPECT RESULTS ---------------

var_dump($row);
var_dump($rows);
