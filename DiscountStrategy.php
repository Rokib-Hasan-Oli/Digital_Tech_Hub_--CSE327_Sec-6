<?php
interface IDiscountStrategy {
    public function calculate($total);
}

class NoDiscount implements IDiscountStrategy {
    public function calculate($total) { return $total; }
}

class HolidayDiscount implements IDiscountStrategy {
    public function calculate($total) { return $total * 0.90; }
}

class DiscountContext {
    private $strategy;

    public function __construct() {
        $this->strategy = new NoDiscount();
    }

    public function setStrategy(IDiscountStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function getFinalPrice($rawTotal) {
        return $this->strategy->calculate($rawTotal);
    }
}
?>