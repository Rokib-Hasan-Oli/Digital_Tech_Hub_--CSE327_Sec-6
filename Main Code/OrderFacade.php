<?php
require_once 'Database.php';
require_once 'PaymentFactory.php';
require_once 'OrderObserver.php';

class OrderFacade {
    public function placeOrder($userId, $cartTotal, $paymentMethod, $cartItems) {
        $db = Database::getInstance()->getConnection();

        // 1. Handle Payment (Factory)
        $paymentObj = PaymentFactory::create($paymentMethod);
        $paymentStatus = $paymentObj->pay($cartTotal); // In real app, check return value

        // 2. Create Order (Database)
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, payment_method, order_status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("ids", $userId, $cartTotal, $paymentMethod);
        $stmt->execute();
        $orderId = $db->insert_id;

        // 3. Save Items
        foreach ($cartItems as $item) {
            $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmtItem->bind_param("iiid", $orderId, $item['id'], $item['qty'], $item['price']);
            $stmtItem->execute();
        }

        // 4. Notify (Observer)
        $subject = new OrderSubject();
        $subject->attach(new EmailNotifier());
        $subject->attach(new AdminLogObserver());
        $subject->notify($orderId, 'Pending');

        return $orderId;
    }
}
?>