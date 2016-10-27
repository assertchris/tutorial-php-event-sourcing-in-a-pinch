<?php

// --------------- INCLUDE PREVIOUS CODE ---------------

define("IGNORE_STORING_EVENTS_INSPECTION", true);

require __DIR__ . "/storing-events.php";

// --------------- DEFINE HELPERS ---------------

function fetch(PDO $connection): array {
    $events = [];

    $tables = [
        ProductInvented::class => "event_product_invented",
        ProductPriced::class => "event_product_priced",
        OutletOpened::class => "event_outlet_opened",
        OutletStocked::class => "event_outlet_stocked",
    ];

    foreach ($tables as $type => $table) {
        $rows = rows($connection, "SELECT * FROM {$table}");

        $rows = array_map(
            function($row) use ($connection, $type) {
                return $type::from($connection, $row);
            }, $rows
        );

        $events = array_merge($events, $rows);
    }

    usort($events, function(Event $a, Event $b) {
        return strtotime($a->date()) - strtotime($b->date());
    });

    return $events;
}

function project(PDO $connection, array $events): array {
    $entities = [
        "products" => [],
        "outlets" => [],
    ];

    foreach ($events as $event) {
        $entities = projectOne($connection, $entities, $event);
    }

    return $entities;
}

function projectOne(PDO $connection, array $entities, Event $event): array {
    if ($event instanceof ProductInvented) {
        $entities = projectProductInvented(
            $connection, $entities, $event
        );
    }

    if ($event instanceof ProductPriced) {
        $entities = projectProductPriced(
            $connection, $entities, $event
        );
    }

    if ($event instanceof OutletOpened) {
        $entities = projectOutletOpened(
            $connection, $entities, $event
        );
    }

    if ($event instanceof OutletStocked) {
        $entities = projectOutletStocked(
            $connection, $entities, $event
        );
    }

    return $entities;
}

function projectProductInvented(PDO $connection, array $entities, ProductInvented $event): array {
    $payload = $event->payload();

    $entities["products"][] = [
        "id" => productIdFromName($connection, $payload["name"]),
        "name" => $payload["name"],
    ];

    return $entities;
}

function projectProductPriced(PDO $connection, array $entities, ProductPriced $event): array {
    $payload = $event->payload();

    foreach ($entities["products"] as $i => $product) {
        if ($product["name"] === $payload["product"]) {
            $entities["products"][$i]["price"] = $payload["cents"];
        }
    }

    return $entities;
}

function projectOutletOpened(PDO $connection, array $entities, OutletOpened $event): array {
    $payload = $event->payload();

    $entities["outlets"][] = [
        "id" => outletIdFromName($connection, $payload["name"]),
        "name" => $payload["name"],
        "stock" => [],
    ];

    return $entities;
}

function projectOutletStocked(PDO $connection, array $entities, OutletStocked $event): array {
    $payload = $event->payload();

    foreach ($entities["outlets"] as $i => $outlet) {
        if ($outlet["name"] === $payload["outlet"]) {
            foreach ($entities["products"] as $j => $product) {
                if ($product["name"] === $payload["product"]) {
                    $entities["outlets"][$i]["stock"][] = [
                        "product" => &$product,
                        "servings" => $payload["servings"],
                    ];
                }
            }
        }
    }

    return $entities;
}

function productNameFromId(PDO $connection, int $id): string {
    $row = row(
        $connection,
        "SELECT * FROM event_product_invented WHERE id = :id",
        ["id" => $id]
    );

    if (!$row) {
        throw new InvalidArgumentException("Product not found");
    }

    return $row["name"];
}

function outletNameFromId(PDO $connection, int $id): string {
    $row = row(
        $connection,
        "SELECT * FROM event_outlet_opened WHERE id = :id",
        ["id" => $id]
    );

    if (!$row) {
        throw new InvalidArgumentException("Outlet not found");
    }

    return $row["name"];
}

// --------------- LOAD EVENTS ---------------

$connection = connect("sqlite::memory:");

$events = [];

$events[] = new ProductInvented("Chocolate");
$events[] = new ProductPriced("Chocolate", 499);
$events[] = new OutletOpened("Pismo Beach");
$events[] = new OutletStocked("Pismo Beach", 24, "Chocolate");

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

$events = project($connection, fetch($connection));

// --------------- INSPECT RESULTS ---------------

print_r($events);
