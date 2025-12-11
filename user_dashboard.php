<?php
session_start();
require_once 'Database.php';
require_once 'Product.php';

// User Authentication Session check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login_register.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// ============================
// SAFE FILTER SYSTEM
// ============================
$conditions = [];
$order_sql = "";

// Search
if (!empty($_GET['search'])) {
    $s = $db->real_escape_string($_GET['search']);
    $conditions[] = "name LIKE '%$s%'";
}

// Sorting
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] == "low")  $order_sql = "ORDER BY price ASC";
    if ($_GET['sort'] == "high") $order_sql = "ORDER BY price DESC";
    if ($_GET['sort'] == "new")  $order_sql = "ORDER BY product_id DESC";
}

// WHERE
$where_sql = "";
if (!empty($conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $conditions);
}

// QUERY
$sql = "SELECT * FROM products $where_sql $order_sql";
$products = $db->query($sql);

if (!$products) {
    die("DB ERROR: " . $db->error);
}

// ============================
// ADD TO CART
// ============================
if (isset($_POST['add_cart'])) {

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][] = [
        'id'    => $_POST['pid'],
        'name'  => $_POST['pname'],
        'price' => $_POST['pprice'],
        'qty'   => $_POST['qty']
    ];
// Feedback Message for user
    echo "<script>alert('Added to Cart');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Digital Tech Hub</title>
    
    <link rel="stylesheet" href="user_dashboard.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo-icon">
                <i class="fa-solid fa-microchip"></i>
            </div>
            <span>Digital Tech Hub</span>
        </div>

        <div class="user-profile-widget">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <span class="badge">Customer</span>
            </div>
        </div>

        <nav class="side-nav">
            <ul>
                <li><a href="#" class="active"><i class="fa fa-store"></i> Products</a></li>
                <li><a href="cart_view.php"><i class="fa fa-cart-shopping"></i> My Cart</a></li>
                <li><a href="checkout_process.php?history=true"><i class="fa fa-box-open"></i> Orders</a></li>
                <li class="logout-item"><a href="login_register.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="welcome-text">
                <h2>Find your gadget</h2>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>

            <div class="top-actions">
                <form class="global-search" method="GET">
                    <i class="fa fa-search"></i>
                    <input type="text" name="search" placeholder="Search...">
                </form>
                <div class="notification-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
            </div>
        </header>

        <div class="scrollable-content">

            <section class="control-panel">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Filter by Name</label>
                        <div class="input-wrapper">
                            <i class="fa fa-filter"></i>
                            <input type="text" name="search" placeholder="e.g. Xiaomi..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Sort Price</label>
                        <select name="sort" onchange="this.form.submit()">
                            <option value="new" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'new') echo 'selected'; ?>>Newest Arrivals</option>
                            <option value="low" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'low') echo 'selected'; ?>>Price: Low to High</option>
                            <option value="high" <?php if(isset($_GET['sort']) && $_GET['sort'] == 'high') echo 'selected'; ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-refresh"><i class="fa fa-sync"></i></button>
                </form>
            </section>

            <section class="products-grid">
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()) { 

                        $productObj = new BasicProduct($row['name'], $row['price']);
                        if (isset($row['has_warranty']) && $row['has_warranty'] == 1) {
                            $productObj = new WarrantyDecorator($productObj);
            }?>
                    <div class="product-card">
                        <div class="image-box">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        </div>

                        <div class="card-details">
                            <h4 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <p class="product-price">৳<?php echo number_format($row['price']); ?></p>
                            
                            <form method="POST" class="add-cart-form">
                                <input type="hidden" name="pid" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="pname" value="<?php echo $row['name']; ?>">
                                <input type="hidden" name="pprice" value="<?php echo $row['price']; ?>">

                                <div class="action-row">
                                    <div class="qty-input">
                                        <button type="button" onclick="this.parentNode.querySelector('input').stepDown()">-</button>
                                        <input type="number" name="qty" value="1" min="1" readonly>
                                        <button type="button" onclick="this.parentNode.querySelector('input').stepUp()">+</button>
                                    </div>
                                    <button name="add_cart" class="btn-add-cart">
                                        <i class="fa fa-cart-plus"></i> Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php } ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa fa-search"></i>
                        <p>No products found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>
</div>

</body>
</html>