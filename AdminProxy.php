<?php
require_once 'Database.php';

// Interface
interface IAdminDashboard {
    public function render();
}

// Real Subject
class RealAdminDashboard implements IAdminDashboard {
    public function render() {
        return true; // Access Granted signal
    }
}

// Proxy
class AdminProxy implements IAdminDashboard {
    private $realAdmin;

    public function render() {
        if (session_status() == PHP_SESSION_NONE) session_start();

        // Check Authentication & Role
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo "<h2>Access Denied: You do not have Admin privileges.</h2>";
            echo "<a href='login_register.php'>Login</a>";
            exit();
        }

        // Lazy Initialization
        if ($this->realAdmin == null) {
            $this->realAdmin = new RealAdminDashboard();
        }
        
        return $this->realAdmin->render();
    }
}
?>