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

// Ensure we have the User ID
if (!isset($_SESSION['user_id'])) {
    $identifier = $_SESSION['email'] ?? $_SESSION['name']; 
    $safe_id = $db->real_escape_string($identifier);
    $u_query = "SELECT user_id FROM users WHERE email = '$safe_id' OR full_name = '$safe_id'";
    $u_res = $db->query($u_query);
    if ($u_res && $u_res->num_rows > 0) {
        $_SESSION['user_id'] = $u_res->fetch_assoc()['user_id'];
    }
}
$user_id = $_SESSION['user_id'] ?? 0;

// [NEW Logic] Mark notifications as read if viewing them
if (isset($_GET['view']) && $_GET['view'] == 'notifications') {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
}

// [NEW Logic] Count unread notifications
$notif_count = 0;
$nc_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0";
$nc_res = $db->query($nc_sql);
if ($nc_res) {
    $notif_count = $nc_res->fetch_assoc()['count'];
}

// ============================
// DATA FETCHING (Only for Products View)
// ============================
$products = null;
$categories = null;

if (!isset($_GET['view']) || $_GET['view'] == 'products') {
    $cat_query = "SELECT * FROM categories";
    $categories = $db->query($cat_query);

    // Filter System
    $conditions = [];
    $order_sql = "";

    if (!empty($_GET['search'])) {
        $s = $db->real_escape_string($_GET['search']);
        $conditions[] = "name LIKE '%$s%'";
    }

    if (!empty($_GET['category'])) {
        $cat_id = (int)$_GET['category'];
        $conditions[] = "category_id = '$cat_id'";
    }

    if (!empty($_GET['sort'])) {
        if ($_GET['sort'] == "low")  $order_sql = "ORDER BY price ASC";
        if ($_GET['sort'] == "high") $order_sql = "ORDER BY price DESC";
        if ($_GET['sort'] == "new")  $order_sql = "ORDER BY product_id DESC";
    }

    $where_sql = "";
    if (!empty($conditions)) {
        $where_sql = "WHERE " . implode(" AND ", $conditions);
    }

    $sql = "SELECT * FROM products $where_sql $order_sql";
    $products = $db->query($sql);
}

// ============================
// ADD TO CART LOGIC
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
    echo "<script>alert('Added to Cart');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Digital Tech Hub</title>

    <link rel="stylesheet" href="user_dashboard.css?v=<?php echo time(); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* [NEW STYLE] Notification Badge */
        .notification-bell {
            position: relative;
            text-decoration: none;
        }

        .badge-counter {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444; /* Red color */
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            animation: popIn 0.3s ease-out;
        }

        @keyframes popIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>

    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon">
                    <img src="https://shorturl.at/nNkXM" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                </div>
                <span>Digital Tech Hub</span>
            </div>

            <div class="user-profile-widget">
                <div class="avatar-circle">
                    <?php 
                    $u_res = $db->query("SELECT profile_image FROM users WHERE user_id=$user_id");
                    if ($u_res) {
                        $u_row = $u_res->fetch_assoc();
                        if (!empty($u_row['profile_image']) && file_exists('uploads/profiles/' . $u_row['profile_image'])) {
                            echo "<img src='uploads/profiles/{$u_row['profile_image']}' style='width:100%; height:100%; object-fit:cover; border-radius:50%;'>";
                        } else {
                            echo strtoupper(substr($_SESSION['name'], 0, 1)); 
                        }
                    } else {
                        echo strtoupper(substr($_SESSION['name'], 0, 1)); 
                    }
                    ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                    <span class="badge">Customer</span>
                </div>
            </div>

            <nav class="side-nav">
                <ul>
                    <li>
                        <a href="?view=products" class="<?php echo (!isset($_GET['view']) || $_GET['view'] == 'products') ? 'active' : ''; ?>">
                            <i class="fa fa-store"></i> Products
                        </a>
                    </li>
                    <li>
                        <a href="cart_view.php">
                            <i class="fa fa-cart-shopping"></i> My Cart
                        </a>
                    </li>
                    <li>
                        <a href="?view=orders" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'orders') ? 'active' : ''; ?>">
                            <i class="fa fa-box-open"></i> Orders
                        </a>
                    </li>
                    
                    <li>
                        <a href="profile_settings.php">
                            <i class="fa fa-user-cog"></i> Profile Settings
                        </a>
                    </li>

                    <li class="logout-item"><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">

            <header class="topbar">
                <div class="welcome-text">
                    <?php if (isset($_GET['view']) && $_GET['view'] == 'orders'): ?>
                        <h2>My Orders</h2>
                        <p>Track your purchase history</p>
                    <?php elseif (isset($_GET['view']) && $_GET['view'] == 'notifications'): ?>
                        <h2>Notifications</h2>
                        <p>Latest updates on your account</p>
                    <?php else: ?>
                        <h2>Find your gadget</h2>
                        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="top-actions">
                    <?php if (!isset($_GET['view']) || $_GET['view'] == 'products'): ?>
                    <form class="global-search" method="GET">
                        <i class="fa fa-search"></i>
                        <input type="text" name="search" placeholder="Search...">
                    </form>
                    <?php endif; ?>
                    
                    <a href="?view=notifications" class="notification-bell">
                        <i class="fa-regular fa-bell"></i>
                        
                        <?php if ($notif_count > 0): ?>
                            <span class="badge-counter">
                                <?php echo ($notif_count > 9) ? '9+' : $notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                </div>
            </header>

            <div class="scrollable-content">

                <?php if (!isset($_GET['view']) || $_GET['view'] == 'products'): ?>
                    
                    <section class="control-panel">
                        <form method="GET" class="filter-form">
                            <input type="hidden" name="view" value="products">

                            <div class="filter-group">
                                <label>Filter by Name</label>
                                <div class="input-wrapper">
                                    <i class="fa fa-filter"></i>
                                    <input type="text" name="search" placeholder="e.g. Xiaomi..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                </div>
                            </div>

                            <div class="filter-group">
                                <label>Category</label>
                                <select name="category" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php 
                                    if ($categories) {
                                        $categories->data_seek(0);
                                        while($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $cat['category_id']; ?>" 
                                            <?php if(isset($_GET['category']) && $_GET['category'] == $cat['category_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Sort Price</label>
                                <select name="sort" onchange="this.form.submit()">
                                    <option value="new" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'new') echo 'selected'; ?>>Newest Arrivals</option>
                                    <option value="low" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'low') echo 'selected'; ?>>Price: Low to High</option>
                                    <option value="high" <?php if (isset($_GET['sort']) && $_GET['sort'] == 'high') echo 'selected'; ?>>Price: High to Low</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-refresh"><i class="fa fa-sync"></i></button>
                        </form>
                    </section>

                    <section class="products-grid">
                        <?php if ($products && $products->num_rows > 0): ?>
                            <?php while ($row = $products->fetch_assoc()) {
                                $productObj = new BasicProduct($row['name'], $row['price']);
                                if (isset($row['has_warranty']) && $row['has_warranty'] == 1) {
                                    $productObj = new WarrantyDecorator($productObj);
                                } 
                                ?>
                                <div class="product-card">
                                    <a href="Productdetails.php?id=<?php echo $row['product_id']; ?>" class="image-box-link">
                                        <div class="image-box">
                                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                        </div>
                                    </a>

                                    <div class="card-details">
                                        <h4 class="product-title">
                                            <a href="Productdetails.php?id=<?php echo $row['product_id']; ?>" style="text-decoration:none; color: inherit;">
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </a>
                                        </h4>
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

                <?php elseif (isset($_GET['view']) && $_GET['view'] == 'orders'): ?>
                    
                    <div class="panel-section">
                        <h3>Order History</h3>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Payment Status</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $o_sql = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC";
                                    $orders = $db->query($o_sql);

                                    if ($orders && $orders->num_rows > 0) {
                                        while ($ord = $orders->fetch_assoc()) {
                                            ?>
                                            <tr>
                                                <td>#<?php echo $ord['order_id']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($ord['order_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($ord['payment_method']); ?></td>
                                                <td>
                                                    <?php 
                                                        $p_status = $ord['payment_status'];
                                                        $p_class = ($p_status == 'Paid') ? 'badge-success' : 'badge-warning';
                                                        echo "<span class='status-badge $p_class'>$p_status</span>";
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge-light"><?php echo htmlspecialchars($ord['order_status']); ?></span>
                                                </td>
                                                <td><strong>৳<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>You have no orders yet.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif (isset($_GET['view']) && $_GET['view'] == 'notifications'): ?>
                    
                    <div class="panel-section">
                        <h3><i class="fa fa-bell"></i> Your Notifications</h3>
                        
                        <div class="notification-list">
                            <?php
                            $n_sql = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
                            $n_res = $db->query($n_sql);

                            if ($n_res && $n_res->num_rows > 0) {
                                while ($notif = $n_res->fetch_assoc()) {
                                    ?>
                                    <div style="background: white; padding: 15px; margin-bottom: 10px; border-left: 4px solid var(--primary-color); border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <p style="margin: 0; font-weight: 500; color: #333;">
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </p>
                                        <small style="color: #888;">
                                            <i class="fa fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<p class='text-center' style='padding: 20px; color: #666;'>No new notifications.</p>";
                            }
                            ?>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>