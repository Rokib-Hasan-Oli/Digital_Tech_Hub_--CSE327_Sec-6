<?php
require_once 'Database.php';


interface IObserver {
    public function update($orderId, $status);
}

class DatabaseNotifier implements IObserver {
    private $userId;

   
    public function __construct($userId) {
        $this->userId = $userId;
    }

    public function update($orderId, $status) {
        $db = Database::getInstance()->getConnection();
        
        $message = "Your Order #$orderId is now confirmed! Status: $status";
        
       
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $this->userId, $message);
            $stmt->execute();
        }
    }
}

class EmailNotifier implements IObserver {
    public function update($orderId, $status) {
        $_SESSION['msg_email'] = "Email sent to user for Order #$orderId";
    }
}

class AdminLogObserver implements IObserver {
    public function update($orderId, $status) {
        error_log("Order #$orderId placed at " . date('Y-m-d H:i:s'));
    }
}

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