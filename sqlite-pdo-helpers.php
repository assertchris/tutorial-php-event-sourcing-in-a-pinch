<?php

// --------------- DEFINE HELPERS ---------------

function connect(string $dsn): PDO {
    $connection = new PDO($dsn);

    $connection->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    return $connection;
}

function execute(PDO $connection, string $query, array $bindings = []): array {
    $statement = $connection->prepare($query);

    foreach ($bindings as $key => $value) {
        $statement->bindValue($key, $value);
    }

    $result = $statement->execute();

    return [$statement, $result];
}

function rows(PDO $connection, string $query, array $bindings = []): array {
    $executed = execute($connection, $query, $bindings);

    /** @var PDOStatement $statement */
    $statement = $executed[0];

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function row(PDO $connection, string $query, array $bindings = []): array {
    $executed = execute($connection, $query, $bindings);

    /** @var PDOStatement $statement */
    $statement = $executed[0];

    return $statement->fetch(PDO::FETCH_ASSOC);
}

if (!defined("IGNORE_SQLITE_PDO_HELPERS_INSPECTION")) {

    // --------------- REPEAT PREVIOUS STEPS ---------------

    $connection = connect("sqlite::memory:");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS product (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT
        )
    ");

    execute($connection, "
        INSERT INTO product (
            name
        ) VALUES (
            :name
        )
    ", ["name" => "Chris"]);

    $row = row($connection, "
        SELECT * FROM product
    ");

    $rows = rows($connection, "
        SELECT * FROM product
    ");

    // --------------- INSPECT RESULTS ---------------

    var_dump($row);
    var_dump($rows);

    // --------------- TEST HELPERS ---------------

    $fake = new class("sqlite::memory:") extends PDO {
        private $valid = true;

        function prepare($statement, $options = null) {
            if ($statement !== "SELECT * FROM product") {
                $this->valid = false;
            }

            return $this;
        }

        function execute() {
            return $this;
        }

        function fetchAll() {
            if (!$this->valid) {
                throw new Exception();
            }

            return [];
        }
    };

    assert(connect("sqlite::memory:") instanceof PDO);
    assert(is_array(rows($fake, "SELECT * FROM product")));

}
