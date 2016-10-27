<?php

// --------------- INCLUDE PREVIOUS CODE ---------------

define("IGNORE_SQLITE_PDO_HELPERS_INSPECTION", true);
define("IGNORE_EVENTS_INSPECTION", true);

require __DIR__ . "/sqlite-pdo-helpers.php";
require __DIR__ . "/events.php";

// --------------- DEFINE HELPERS ---------------

function store(PDO $connection, array $events) {
    foreach($events as $event) {
        storeOne($connection, $event);
    }
}

function storeOne(PDO $connection, Event $event) {
    $payload = $event->payload();

    if ($event instanceof ProductInvented) {
        inventProduct(
            $connection,
            newProductId($connection),
            $payload["name"],
            $payload["date"]
        );
    }

    if ($event instanceof ProductPriced) {
        priceProduct(
            $connection,
            productIdFromName($connection, $payload["product"]),
            $payload["cents"],
            $payload["date"]
        );
    }

    if ($event instanceof OutletOpened) {
        openOutlet(
            $connection,
            newOutletId($connection),
            $payload["name"],
            $payload["date"]
        );
    }

    if ($event instanceof OutletStocked) {
        stockOutlet(
            $connection,
            outletIdFromName($connection, $payload["outlet"]),
            $payload["servings"],
            productIdFromName($connection, $payload["product"]),
            $payload["date"]
        );
    }
}

function newProductId(PDO $connection): int {
    execute(
        $connection,
        "INSERT INTO product VALUES (null)"
    );

    return $connection->lastInsertId();
}

function inventProduct(PDO $connection, int $id, string $name, string $date) {
    execute(
        $connection,
        "INSERT INTO event_product_invented (id, name, date) VALUES (:id, :name, :date)",
        ["id" => $id, "name" => $name, "date" => $date]
    );
}

function productIdFromName(PDO $connection, string $name): int {
    $row = row(
        $connection,
        "SELECT * FROM event_product_invented WHERE name = :name",
        ["name" => $name]
    );

    if (!$row) {
        throw new InvalidArgumentException("Product not found");
    }

    return $row["id"];
}

function priceProduct(PDO $connection, int $product, int $cents, string $date) {
    execute(
        $connection,
        "INSERT INTO event_product_priced (product, cents, date) VALUES (:product, :cents, :date)",
        ["product" => $product, "cents" => $cents, "date" => $date]
    );
}

function newOutletId(PDO $connection): int {
    execute(
        $connection,
        "INSERT INTO outlet VALUES (null)"
    );

    return $connection->lastInsertId();
}

function openOutlet(PDO $connection, int $id, string $name, string $date) {
    execute(
        $connection,
        "INSERT INTO event_outlet_opened (id, name, date) VALUES (:id, :name, :date)",
        ["id" => $id, "name" => $name, "date" => $date]
    );
}

function outletIdFromName(PDO $connection, string $name): int {
    $row = row(
        $connection,
        "SELECT * FROM event_outlet_opened WHERE name = :name",
        ["name" => $name]
    );

    if (!$row) {
        throw new InvalidArgumentException("Outlet not found");
    }

    return $row["id"];
}

function stockOutlet(PDO $connection, int $outlet, int $servings, int $product, string $date) {
    execute(
        $connection,
        "INSERT INTO event_outlet_stocked (outlet, servings, product, date) VALUES (:outlet, :servings, :product, :date)",
        ["outlet" => $outlet, "servings" => $servings, "product" => $product, "date" => $date]
    );
}

if (!defined("IGNORE_STORING_EVENTS_INSPECTION")) {

    // --------------- DEFINE EVENTS ---------------

    $events = [];

    $events[] = new ProductInvented("Chocolate");
    $events[] = new ProductPriced("Chocolate", 499);
    $events[] = new OutletOpened("Pismo Beach");
    $events[] = new OutletStocked("Pismo Beach", 24, "Chocolate");

    // --------------- STORE EVENTS ---------------

    $connection = connect("sqlite::memory:");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS product (
            id INTEGER PRIMARY KEY AUTOINCREMENT
        )
    ");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS event_product_invented (
            id INT,
            name TEXT,
            date TEXT
        )
    ");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS event_product_priced (
            product INT,
            cents INT,
            date TEXT
        )
    ");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS outlet (
            id INTEGER PRIMARY KEY AUTOINCREMENT
        )
    ");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS event_outlet_opened (
            id INT,
            name TEXT,
            date TEXT
        )
    ");

    execute($connection, "
        CREATE TABLE IF NOT EXISTS event_outlet_stocked (
            outlet INT,
            servings INT,
            product INT,
            date TEXT
        )
    ");

    store($connection, $events);

    var_dump(
        rows($connection, "SELECT * FROM event_product_invented")
    );

    var_dump(
        rows($connection, "SELECT * FROM event_product_priced")
    );

    var_dump(
        rows($connection, "SELECT * FROM event_outlet_opened")
    );

    var_dump(
        rows($connection, "SELECT * FROM event_outlet_stocked")
    );

    // --------------- TEST STORAGE ---------------

    store($connection, [
        new ProductInvented("Cheesecake"),
    ]);

    $row = row(
        $connection,
        "SELECT * FROM event_product_invented WHERE name = :name",
        ["name" => "Cheesecake"]
    );

    assert(!is_null($row));

}
