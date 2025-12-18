<?php
require_once 'Database.php';

// 1. The Observer Interface
interface IObserver {
    public function update($orderId, $status);
}

// 2. Concrete Observer: Database Notification (The Real Feature)
class DatabaseNotifier implements IObserver {
    private $userId;

    // We need the User ID to know WHO to notify
    public function __construct($userId) {
        $this->userId = $userId;
    }

    public function update($orderId, $status) {
        $db = Database::getInstance()->getConnection();
        
        $message = "Your Order #$orderId is now confirmed! Status: $status";
        
        // Save to Database
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $this->userId, $message);
            $stmt->execute();
        }
    }
}

// 3. Concrete Observer: Email (Simulation)
class EmailNotifier implements IObserver {
    public function update($orderId, $status) {
        $_SESSION['msg_email'] = "Email sent to user for Order #$orderId";
    }
}

// 4. Concrete Observer: Admin Log
class AdminLogObserver implements IObserver {
    public function update($orderId, $status) {
        // Keeps a log for admins
        error_log("Order #$orderId placed at " . date('Y-m-d H:i:s'));
    }
}

// 5. The Subject (Observable)
class OrderSubject {
    private $observers = [];

    public function attach(IObserver $observer) {
        $this->observers[] = $observer;
    }

    public function notify($orderId, $status) {
        foreach ($this->observers as $observer) {
            $observer->update($orderId, $status);
        }
    }
}
?>