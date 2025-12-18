<?php
session_start();
require_once 'AdminProxy.php';

// 1. PROXY PATTERN: Security Check
$proxy = new AdminProxy();
$proxy->render(); 

$db = Database::getInstance()->getConnection();
$logo = "https://raw.githubusercontent.com/Rokib-Hasan-Oli/Digital_Tech_Hub_--CSE327_Sec-6/Rokib-Hasan-Oli/Relevant%20documents%20and%20FIle/Logo/2.png";
$msg = "";

// ====================================================
// HANDLE ACTIONS (PHP LOGIC)
// ====================================================

// HELPER: Function to send notification (Simulating Observer)
function send_user_notification($db, $order_id, $message) {
    // 1. Get User ID from Order
    $u_check = $db->query("SELECT user_id FROM orders WHERE order_id = $order_id");
    if ($u_check && $u_check->num_rows > 0) {
        $uid = $u_check->fetch_assoc()['user_id'];
        
        // 2. Insert Notification
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("is", $uid, $message);
            $stmt->execute();
        }
    }
}

// 1. Delete Actions (GET)
if (isset($_GET['delete_cat'])) {
    $id = intval($_GET['delete_cat']);
    $db->query("DELETE FROM categories WHERE category_id=$id");
    $msg = "Category Deleted.";
}

if (isset($_GET['delete_prod'])) {
    $id = intval($_GET['delete_prod']);
    $db->query("DELETE FROM products WHERE product_id=$id");
    $msg = "Product Deleted.";
}

// 2. User Management
if (isset($_GET['block_user'])) {
    $uid = intval($_GET['block_user']);
    $db->query("UPDATE users SET status='Blocked' WHERE user_id=$uid");
    $msg = "User Blocked.";
}

if (isset($_GET['unblock_user'])) {
    $uid = intval($_GET['unblock_user']);
    $db->query("UPDATE users SET status='Active' WHERE user_id=$uid");
    $msg = "User Unblocked and set to Active.";
}

// 3. Add Category (POST)
if (isset($_POST['add_cat'])) {
    $name = $db->real_escape_string($_POST['cat_name']);
    $desc = $db->real_escape_string($_POST['cat_desc']);
    if ($db->query("INSERT INTO categories (name, description) VALUES ('$name', '$desc')")) {
        $msg = "Category Added Successfully.";
    } else {
        $msg = "Error: " . $db->error;
    }
}

// 4. Add Product (POST)
if (isset($_POST['add_product'])) {
    $name = $db->real_escape_string($_POST['name']);
    $desc = $db->real_escape_string($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $cat_id = $_POST['category_id'];
    $has_warranty = isset($_POST['has_warranty']) ? 1 : 0;
    
    // Image Upload Handling
    $image = "default_product.png";
    if (isset($_FILES['product_image']['name']) && $_FILES['product_image']['name'] != "") {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $image = basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $image;
        move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file);
    }

    $sql = "INSERT INTO products (category_id, name, description, price, stock_quantity, product_image, has_warranty) 
            VALUES ('$cat_id', '$name', '$desc', '$price', '$stock', '$image', '$has_warranty')";
            
    if ($db->query($sql)) {
        $msg = "Product Added Successfully.";
    } else {
        $msg = "Error: " . $db->error;
    }
}

// 5. Update Order Status (Action A)
if (isset($_GET['update_order_status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = $db->real_escape_string($_GET['update_order_status']);
    
    // Update DB
    $db->query("UPDATE orders SET order_status='$new_status' WHERE order_id=$order_id");
    
    // Notify User
    $msg_text = "Your Order #$order_id status has been updated to: $new_status";
    send_user_notification($db, $order_id, $msg_text);

    $msg = "Order #$order_id status updated to **$new_status** & User Notified.";
}

// 6. Update Payment Status (Action B)
if (isset($_GET['update_payment_status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $new_pay_status = $db->real_escape_string($_GET['update_payment_status']);
    
    // Update DB
    $db->query("UPDATE orders SET payment_status='$new_pay_status' WHERE order_id=$order_id");
    
    // Notify User
    $msg_text = "Payment update for Order #$order_id: Status is now $new_pay_status";
    send_user_notification($db, $order_id, $msg_text);

    $msg = "Order #$order_id Payment Status set to **$new_pay_status** & User Notified.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Digital Tech Hub</title>
    
    <link rel="stylesheet" href="admin_dashboard.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo-icon">
                <img src="<?php echo $logo; ?>" alt="Admin Logo">
            </div>
            <span>Digital Tech Hub</span>
        </div>

        <div class="user-profile-widget">
            <div class="avatar-circle">A</div>
            <div class="user-info">
                <h3>Administrator</h3>
                <span class="badge">System Control</span>
            </div>
        </div>

        <nav class="side-nav">
            <ul>
                <li><a href="?view=products" class="<?php echo (!isset($_GET['view']) || $_GET['view'] == 'products') ? 'active' : ''; ?>"><i class="fa fa-box"></i> Products</a></li>
                <li><a href="?view=categories" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'categories') ? 'active' : ''; ?>"><i class="fa fa-list"></i> Categories</a></li>
                <li><a href="?view=users" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'users') ? 'active' : ''; ?>"><i class="fa fa-users"></i> Users</a></li>
                <li><a href="?view=orders" class="<?php echo (isset($_GET['view']) && $_GET['view'] == 'orders') ? 'active' : ''; ?>"><i class="fa fa-shopping-cart"></i> Orders</a></li>
                <li class="logout-item"><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="welcome-text">
                <h2>Admin Panel</h2>
                <p>Manage your digital inventory and users.</p>
            </div>
            <div class="top-actions">
                <div class="notification-bell">
                    <i class="fa-regular fa-bell"></i>
                </div>
            </div>
        </header>

        <div class="scrollable-content">

            <?php if($msg): ?>
                <div class="alert-box">
                    <i class="fa fa-info-circle"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_GET['view']) || $_GET['view'] == 'products') { 
                $cat_check = $db->query("SELECT * FROM categories");
                $has_cats = $cat_check->num_rows > 0;
            ?>
                <div class="panel-section">
                    <div class="section-header">
                        <h3><i class="fa fa-plus-circle"></i> Add New Product</h3>
                    </div>

                    <?php if (!$has_cats) { ?>
                        <div class="warning-box">
                            <i class="fa fa-exclamation-triangle"></i>
                            <p>You must add a Category first before adding products.</p>
                            <a href="?view=categories" class="btn-link">Go to Categories</a>
                        </div>
                    <?php } else { ?>
                        <form method="post" enctype="multipart/form-data" class="admin-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Product Name</label>
                                    <input type="text" name="name" required placeholder="e.g. iPhone 15">
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category_id" required>
                                        <?php 
                                        $cat_check->data_seek(0);
                                        while($c = $cat_check->fetch_assoc()) {
                                            echo "<option value='{$c['category_id']}'>{$c['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Price (Tk)</label>
                                    <input type="number" name="price" step="0.01" required placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Stock Quantity</label>
                                    <input type="number" name="stock" required placeholder="0">
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="description" required rows="3" placeholder="Product details..."></textarea>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" name="has_warranty" value="1" id="warranty">
                                <label for="warranty">Include 1 Year Official Warranty (+2000 Tk)</label>
                            </div>

                            <div class="form-group full-width">
                                <label>Product Image</label>
                                <input type="file" name="product_image" accept="image/*" class="file-input">
                            </div>

                            <button type="submit" name="add_product" class="btn-primary">Add Product</button>
                        </form>
                    <?php } ?>
                </div>

                <div class="panel-section">
                    <h3>Product List</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id DESC";
                                $res = $db->query($sql);
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        echo "<tr>
                                            <td>#{$row['product_id']}</td>
                                            <td><div class='img-preview'><img src='uploads/{$row['product_image']}' alt='img'></div></td>
                                            <td><strong>{$row['name']}</strong></td>
                                            <td><span class='badge-light'>{$row['cat_name']}</span></td>
                                            <td>{$row['price']}</td>
                                            <td>{$row['stock_quantity']}</td>
                                            <td><a href='?view=products&delete_prod={$row['product_id']}' class='btn-danger' onclick='return confirm(\"Are you sure?\")'><i class='fa fa-trash'></i></a></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No products found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <?php if (isset($_GET['view']) && $_GET['view'] == 'categories') { ?>
                <div class="panel-section">
                    <h3><i class="fa fa-folder-plus"></i> Add Category</h3>
                    <form method="post" class="admin-form">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="cat_name" required placeholder="e.g. Laptops">
                        </div>
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="cat_desc" rows="2" placeholder="Category details..."></textarea>
                        </div>
                        <button type="submit" name="add_cat" class="btn-success">Add Category</button>
                    </form>
                </div>

                <div class="panel-section">
                    <h3>Existing Categories</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $db->query("SELECT * FROM categories");
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        echo "<tr>
                                            <td>#{$row['category_id']}</td>
                                            <td><strong>{$row['name']}</strong></td>
                                            <td>{$row['description']}</td>
                                            <td><a href='?view=categories&delete_cat={$row['category_id']}' class='btn-danger' onclick='return confirm(\"Delete this category?\")'><i class='fa fa-trash'></i> Delete</a></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No categories found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <?php if (isset($_GET['view']) && $_GET['view'] == 'users') { ?>
                <div class="panel-section">
                    <h3>Manage Users</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $db->query("SELECT * FROM users");
                                while ($row = $res->fetch_assoc()) {
                                    $statusClass = ($row['status'] == 'Blocked') ? 'status-blocked' : 'status-active';
                                    echo "<tr>
                                        <td>#{$row['user_id']}</td>
                                        <td>{$row['full_name']}</td>
                                        <td>{$row['email']}</td>
                                        <td><span class='status-badge $statusClass'>{$row['status']}</span></td>
                                        <td>";
                                        if($row['status'] == 'Blocked') {
                                            echo "<a href='?view=users&unblock_user={$row['user_id']}' class='btn-success btn-sm'>Unblock</a>";
                                        } else {
                                            echo "<a href='?view=users&block_user={$row['user_id']}' class='btn-danger btn-sm'>Block</a>";
                                        }
                                    echo "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <?php if (isset($_GET['view']) && $_GET['view'] == 'orders') { ?>
                <div class="panel-section">
                    <h3>All Orders</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>User ID</th>
                                    <th>Total</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $db->query("SELECT * FROM orders ORDER BY order_id DESC");
                                
                                $order_statuses = ["Pending", "Processing", "Shipped", "Delivered", "Cancelled"];
                                $pay_statuses   = ["Unpaid", "Paid", "Refunded"];
                            
                                while ($row = $res->fetch_assoc()) {
                                    echo "<tr>
                                        <td>#{$row['order_id']}</td>
                                        <td>User #{$row['user_id']}</td>
                                        <td>{$row['total_amount']} Tk</td>
                                        
                                        <td>
                                            <select 
                                                class='status-select' 
                                                style='border-color: #3b82f6;'
                                                onchange='window.location.href = \"?view=orders&order_id={$row['order_id']}&update_order_status=\" + this.value'
                                            >
                                            ";
                                            foreach ($order_statuses as $status) {
                                                $selected = ($row['order_status'] == $status) ? 'selected' : '';
                                                echo "<option value='$status' $selected>$status</option>";
                                            }
                                    echo    "</select>
                                        </td>
                            
                                        <td>
                                            <select 
                                                class='status-select' 
                                                style='border-color: #10b981;'
                                                onchange='window.location.href = \"?view=orders&order_id={$row['order_id']}&update_payment_status=\" + this.value'
                                            >
                                            ";
                                            foreach ($pay_statuses as $p_status) {
                                                $p_selected = ($row['payment_status'] == $p_status) ? 'selected' : '';
                                                echo "<option value='$p_status' $p_selected>$p_status</option>";
                                            }
                                    echo    "</select>
                                        </td>
                            
                                        <td>
                                            <a href='generate_pdf.php?order_id={$row['order_id']}' target='_blank' class='btn-primary btn-sm'><i class='fa fa-file-pdf'></i> PDF</a>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

        </div>
    </main>
</div>

</body>
</html>