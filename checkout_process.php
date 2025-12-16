<?php
session_start();
require_once 'OrderFacade.php';
require_once 'Database.php';

$db = Database::getInstance()->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Successful</title>

<style>
:root {
    --bg: #f4f7fb;
    --card: #ffffff;
    --primary: #2563eb;
    --success: #22c55e;
    --text: #1f2937;
    --muted: #6b7280;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: system-ui, -apple-system, sans-serif;
}

body {
    background: var(--bg);
    color: var(--text);
}

.wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.card {
    width: 460px;
    background: var(--card);
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.1);
    animation: slideUp .6s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.checkmark {
    width: 72px;
    height: 72px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: var(--success);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pop .5s ease-out;
}

@keyframes pop {
    from { transform: scale(.5); }
    to { transform: scale(1); }
}

.checkmark svg {
    width: 38px;
    color: white;
}

h2 {
    text-align: center;
    font-size: 22px;
}

.subtitle {
    text-align: center;
    color: var(--muted);
    margin: 6px 0 20px;
    font-size: 14px;
}

.details {
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    padding: 16px 0;
    margin-bottom: 16px;
}

.details div {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin: 8px 0;
}

.badge {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    color: #fff;
}

.bkash { background: #e2136e; }
.nagad { background: #f97316; }
.cod { background: #64748b; }
.online { background: #16a34a; }

h3 {
    font-size: 15px;
    margin-bottom: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
}

th, td {
    font-size: 13px;
    padding: 10px;
    border-bottom: 1px solid #e5e7eb;
}

th {
    text-align: left;
    color: var(--muted);
}

.info {
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 16px;
}

.success-msg {
    color: var(--success);
    font-size: 13px;
    margin-bottom: 12px;
}

.btn {
    display: block;
    text-align: center;
    padding: 12px;
    background: var(--primary);
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: .2s;
}

.btn:hover {
    background: #1e4ed8;
}
</style>
</head>

<body>
<div class="wrapper">

<?php
// =======================
// CHECKOUT SUCCESS
// =======================
if (isset($_POST['checkout']) && isset($_SESSION['cart'])) {

    $facade = new OrderFacade();

    $uid    = $_SESSION['user_id'];
    $amount = $_POST['pay_amount'];
    $method = $_POST['method'];
    $items  = $_SESSION['cart'];

    // Place order
    $orderId = $facade->placeOrder($uid, $amount, $method, $items);
    unset($_SESSION['cart']);

    // ✅ FIX: update order & payment status after successful payment
    if (strtolower($method) !== 'cod') {
        $db->query("
            UPDATE orders
            SET payment_status = 'Paid',
                order_status = 'Processing'
            WHERE order_id = $orderId
        ");
    }

    $products = $db->query("
        SELECT p.name, oi.quantity, oi.unit_price
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = $orderId
    ");

    $badge = strtolower($method);
?>

<div class="card">

    <div class="checkmark">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                  d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <h2>Order Placed Successfully</h2>
    <div class="subtitle">Thanks for shopping with Digital Tech Hub</div>

    <div class="details">
        <div><span>Order ID</span><strong>#<?= $orderId ?></strong></div>
        <div>
            <span>Payment</span>
            <span class="badge <?= $badge ?>"><?= strtoupper($method) ?></span>
        </div>
        <div><span>Status</span><strong>Processing</strong></div>
    </div>

    <h3>Purchased Items</h3>
    <table>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
        </tr>
        <?php while ($p = $products->fetch_assoc()) { ?>
        <tr>
            <td><?= $p['name'] ?></td>
            <td><?= $p['quantity'] ?></td>
            <td><?= $p['unit_price'] ?></td>
        </tr>
        <?php } ?>
    </table>

    <?php
    if (isset($_SESSION['msg_email'])) {
        echo "<div class='success-msg'>{$_SESSION['msg_email']}</div>";
    }
    ?>

    <p class="info">
        Redirecting to dashboard in <strong><span id="count">20</span></strong> seconds…
    </p>

    <a href="user_dashboard.php" class="btn">Go to Dashboard</a>
</div>

<?php } else { ?>

<div class="card">
    <h2>Cart Empty</h2>
    <p class="info">No order was found.</p>
    <a href="user_dashboard.php" class="btn">Go Back</a>
</div>

<?php } ?>

</div>

<script>
let t = 20;
const c = document.getElementById('count');

const timer = setInterval(() => {
    t--;
    if (c) c.textContent = t;
    if (t <= 0) {
        clearInterval(timer);
        window.location.href = 'user_dashboard.php';
    }
}, 1000);
</script>

</body>
</html>
