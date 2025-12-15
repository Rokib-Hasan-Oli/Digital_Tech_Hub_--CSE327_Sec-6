<?php
require_once 'Database.php';
// 1. Component Interface
interface IProduct {
    public function getPrice();
    public function getDescription();
}

// 2. Concrete Component (The Core Product)
class BasicProduct implements IProduct {
    protected $name;
    protected $price;

    public function __construct($name, $price) {
        $this->name = $name;
        $this->price = $price;
    }

    public function getPrice() { return $this->price; }
    public function getDescription() { return $this->name; }
}

// 3. Decorator (Adds Warranty)
class WarrantyDecorator implements IProduct {
    protected $product;

    public function __construct(IProduct $product) {
        $this->product = $product;
    }

    public function getPrice() {
        return $this->product->getPrice() + 2000; // Warranty costs 2000 Tk
    }

    public function getDescription() {
        return $this->product->getDescription() . " + 1 Year Official Warranty";
    }
}
?>