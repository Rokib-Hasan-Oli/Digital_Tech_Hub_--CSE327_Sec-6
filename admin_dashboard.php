<?php
session_start();
require_once 'AdminProxy.php';

// 1. PROXY PATTERN: Security Check
$proxy = new AdminProxy();
$proxy->render(); 

$db = Database::getInstance()->getConnection();
$logo = "https://raw.githubusercontent.com/Rokib-Hasan-Oli/Digital_Tech_Hub_--CSE327_Sec-6/Rokib-Hasan-Oli/Relevant%20documents%20and%20FIle/Logo/1.png";
$msg = "";

// ====================================================
// HANDLE ACTIONS (PHP LOGIC)
// ====================================================

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

if (isset($_GET['block_user'])) {
    $uid = intval($_GET['block_user']);
    $db->query("UPDATE users SET status='Blocked' WHERE user_id=$uid");
    $msg = "User Blocked.";
}

// 2. Add Category (POST)
if (isset($_POST['add_cat'])) {
    $name = $db->real_escape_string($_POST['cat_name']);
    $desc = $db->real_escape_string($_POST['cat_desc']);
    if ($db->query("INSERT INTO categories (name, description) VALUES ('$name', '$desc')")) {
        $msg = "Category Added Successfully.";
    } else {
        $msg = "Error: " . $db->error;
    }
}

// 3. Add Product (POST)
if (isset($_POST['add_product'])) {
    $name = $db->real_escape_string($_POST['name']);
    $desc = $db->real_escape_string($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $cat_id = $_POST['category_id'];
    
    // Image Upload Handling
    $image = "default_product.png";
    if (isset($_FILES['product_image']['name']) && $_FILES['product_image']['name'] != "") {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $image = basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $image;
        move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file);
    }

    $sql = "INSERT INTO products (category_id, name, description, price, stock_quantity, product_image) 
            VALUES ('$cat_id', '$name', '$desc', '$price', '$stock', '$image')";
            
    if ($db->query($sql)) {
        $msg = "Product Added Successfully.";
    } else {
        $msg = "Error: " . $db->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Digital Tech Hub</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; }
        .sidebar { width: 250px; background: #333; color: white; height: 100vh; position: fixed; padding: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; margin-bottom: 5px; }
        .sidebar a:hover { background: #575757; }
        .content { margin-left: 270px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .alert { padding: 10px; background-color: #4CAF50; color: white; margin-bottom: 15px; }
        .btn-del { background: red; color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .form-group { margin-bottom: 10px; }
        label { display: block; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="sidebar">
        <center><img src="<?php echo $logo; ?>" width="120" style="background:white; border-radius:5px;"></center>
        <h3>Admin Panel</h3>
        <hr>
        <a href="?view=products">Manage Products</a>
        <a href="?view=categories">Manage Categories</a>
        <a href="?view=users">Manage Users</a>
        <a href="?view=orders">Manage Orders</a>
        <br>
        <a href="logout.php" style="color: #ff6b6b;">Logout</a>
    </div>

    <div class="content">
        <?php if($msg) echo "<div class='alert'>$msg</div>"; ?>

        <!-- ========================================== -->
        <!-- VIEW: PRODUCTS -->
        <!-- ========================================== -->
        <?php if (!isset($_GET['view']) || $_GET['view'] == 'products') { 
            // Check if categories exist
            $cat_check = $db->query("SELECT * FROM categories");
            $has_cats = $cat_check->num_rows > 0;
        ?>
            <h2>Manage Products</h2>
            
            <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
                <h3>Add New Product</h3>
                <?php if (!$has_cats) { ?>
                    <p style="color:red;"><b>Warning:</b> You must add a Category first before adding products.</p>
                    <a href="?view=categories">Go to Categories</a>
                <?php } else { ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Product Name:</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Category:</label>
                            <select name="category_id" required>
                                <?php 
                                $cat_check->data_seek(0); // Reset pointer
                                while($c = $cat_check->fetch_assoc()) {
                                    echo "<option value='{$c['category_id']}'>{$c['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (Tk):</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity:</label>
                            <input type="number" name="stock" required>
                        </div>
                        <div class="form-group">
                            <label>Product Image:</label>
                            <input type="file" name="product_image" accept="image/*">
                        </div>
                        <button type="submit" name="add_product" style="padding:10px 20px; background:blue; color:white; border:none;">Add Product</button>
                    </form>
                <?php } ?>
            </div>

            <h3>Product List</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Action</th>
                </tr>
                <?php
                $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id DESC";
                $res = $db->query($sql);
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['product_id']}</td>
                            <td><img src='uploads/{$row['product_image']}' width='50'></td>
                            <td>{$row['name']}</td>
                            <td>{$row['cat_name']}</td>
                            <td>{$row['price']}</td>
                            <td>{$row['stock_quantity']}</td>
                            <td><a href='?view=products&delete_prod={$row['product_id']}' class='btn-del' onclick='return confirm(\"Are you sure?\")'>Delete</a></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No products found.</td></tr>";
                }
                ?>
            </table>
        <?php } ?>


        <!-- ========================================== -->
        <!-- VIEW: CATEGORIES -->
        <!-- ========================================== -->
        <?php if (isset($_GET['view']) && $_GET['view'] == 'categories') { ?>
            <h2>Manage Categories</h2>
            <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px;">
                <h3>Add Category</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Category Name:</label>
                        <input type="text" name="cat_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="cat_desc"></textarea>
                    </div>
                    <button type="submit" name="add_cat" style="padding:10px 20px; background:green; color:white; border:none;">Add Category</button>
                </form>
            </div>

            <h3>Existing Categories</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                <?php
                $res = $db->query("SELECT * FROM categories");
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['category_id']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['description']}</td>
                            <td><a href='?view=categories&delete_cat={$row['category_id']}' class='btn-del' onclick='return confirm(\"Delete this category?\")'>Delete</a></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No categories found.</td></tr>";
                }
                ?>
            </table>
        <?php } ?>


        <!-- ========================================== -->
        <!-- VIEW: USERS -->
        <!-- ========================================== -->
        <?php if (isset($_GET['view']) && $_GET['view'] == 'users') { ?>
            <h2>Manage Users</h2>
            <table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
                <?php
                $res = $db->query("SELECT * FROM users");
                while ($row = $res->fetch_assoc()) {
                    $statusColor = ($row['status'] == 'Blocked') ? 'red' : 'green';
                    echo "<tr>
                        <td>{$row['user_id']}</td>
                        <td>{$row['full_name']}</td>
                        <td>{$row['email']}</td>
                        <td style='color:$statusColor'><b>{$row['status']}</b></td>
                        <td>";
                        if($row['status'] != 'Blocked') {
                            echo "<a href='?view=users&block_user={$row['user_id']}' class='btn-del'>Block</a>";
                        }
                    echo "</td></tr>";
                }
                ?>
            </table>
        <?php } ?>


        <!-- ========================================== -->
        <!-- VIEW: ORDERS -->
        <!-- ========================================== -->
        <?php if (isset($_GET['view']) && $_GET['view'] == 'orders') { ?>
            <h2>All Orders</h2>
            <table>
                <tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Status</th><th>Action</th></tr>
                <?php
                $res = $db->query("SELECT * FROM orders ORDER BY order_id DESC");
                while ($row = $res->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['order_id']}</td>
                        <td>{$row['user_id']}</td>
                        <td>{$row['total_amount']} Tk</td>
                        <td>{$row['order_status']}</td>
                        <td><button onclick='alert(\"Generating PDF for Order #{$row['order_id']}...\")'>Generate PDF</button></td>
                    </tr>";
                }
                ?>
            </table>
        <?php } ?>

    </div>

</body>
</html>