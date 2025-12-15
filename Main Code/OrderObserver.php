<?php
interface IObserver {
    public function update($orderId, $status);
}

class EmailNotifier implements IObserver {
    public function update($orderId, $status) {
        // In real life, use mail() function here
        $_SESSION['msg_email'] = "[Email Sent]: Order #$orderId status changed to '$status'.";
    }
}

class AdminLogObserver implements IObserver {
    public function update($orderId, $status) {
        // Logs to a file or DB
        $_SESSION['msg_log'] = "[System Log]: Order #$orderId logged at " . date('H:i:s');
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