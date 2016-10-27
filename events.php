<?php

abstract class Event
{
    /**
     * @var string
     */
    private $date;

    protected function __construct()
    {
        $this->date = date("Y-m-d H:i:s");
    }

    public function date(): string
    {
        return $this->date;
    }

    public function withDate(string $date): self
    {
        $new = clone $this;
        $new->date = $date;

        return $new;
    }

    abstract public function payload(): array;

    abstract
    public
    static
    function
    from(PDO $connection, array $data);
}

final class ProductInvented extends Event
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->name = $name;
    }

    public function payload(): array
    {
        return [
            "name" => $this->name,
            "date" => $this->date(),
        ];
    }

    public static function from(PDO $connection, array $data)
    {
        $new = new static(
            $data["name"]
        );

        return $new->withDate($data["date"]);
    }
}

final class ProductPriced extends Event
{
    /**
     * @var string
     */
    private $product;

    /**
     * @var int
     */
    private $cents;

    public function __construct(string $product, int $cents)
    {
        parent::__construct();

        $this->product = $product;
        $this->cents = $cents;
    }

    public function payload(): array
    {
        return [
            "product" => $this->product,
            "cents" => $this->cents,
            "date" => $this->date(),
        ];
    }

    public static function from(PDO $connection, array $data)
    {
        $new = new static(
            productNameFromId($connection, $data["product"]),
            $data["cents"]
        );

        return $new->withDate($data["date"]);
    }
}

final class OutletOpened extends Event
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->name = $name;
    }

    public function payload(): array
    {
        return [
            "name" => $this->name,
            "date" => $this->date(),
        ];
    }

    public static function from(PDO $connection, array $data)
    {
        $new = new static(
            $data["name"]
        );

        return $new->withDate($data["date"]);
    }
}

final class OutletStocked extends Event
{
    /**
     * @var string
     */
    private $outlet;

    /**
     * @var int
     */
    private $servings;

    /**
     * @var string
     */
    private $product;

    public function __construct(string $outlet, int $servings, string $product)
    {
        parent::__construct();

        $this->outlet = $outlet;
        $this->servings = $servings;
        $this->product = $product;
    }

    public function payload(): array
    {
        return [
            "outlet" => $this->outlet,
            "servings" => $this->servings,
            "product" => $this->product,
            "date" => $this->date(),
        ];
    }

    public static function from(PDO $connection, array $data)
    {
        $new = new static(
            outletNameFromId($connection, $data["outlet"]),
            $data["servings"],
            productNameFromId($connection, $data["product"])
        );

        return $new->withDate($data["date"]);
    }
}

if (!defined("IGNORE_EVENTS_INSPECTION")) {

    // --------------- CREATE EVENTS ---------------

    $events = [];

    $events[] = new ProductInvented("Chocolate");
    $events[] = new ProductPriced("Chocolate", 499);
    $events[] = new OutletOpened("Pismo Beach");
    $events[] = new OutletStocked("Pismo Beach", 24, "Chocolate");

    // --------------- INSPECT RESULTS ---------------

    var_dump(
        array_map(function(Event $event) {
            return $event->payload();
        }, $events)
    );

}
