<?php
require_once 'Database.php';
interface IPayment {
    public function pay($amount);
}

class BkashPayment implements IPayment {
    public function pay($amount) { return "Paid $amount Tk via Bkash (Trans ID: BK-" . rand(1000,9999) . ")"; }
}

class NagadPayment implements IPayment {
    public function pay($amount) { return "Paid $amount Tk via Nagad (Trans ID: NG-" . rand(1000,9999) . ")"; }
}

class CODPayment implements IPayment {
    public function pay($amount) { return "Amount $amount Tk to be paid on Delivery."; }
}

class PaymentFactory {
    public static function create($method) {
        if ($method == 'Bkash') return new BkashPayment();
        if ($method == 'Nagad') return new NagadPayment();
        return new CODPayment(); // Default
    }
}
?>