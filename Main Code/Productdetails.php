<?php
session_start();
require_once 'Database.php';
require_once 'Product.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login_register.php");
    exit();
}

$db = Database::getInstance()->getConnection();
// Logo Link
$logo = "https://ln.run/WlP6-"; 

// Get Product ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch Product Details
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.product_id = $id";

$result = $db->query($sql);

if ($result->num_rows == 0) {
    die("Product not found");
}

$product = $result->fetch_assoc();

// Fetch Related Products
$cat_id = $product['category_id'];
$related_sql = "SELECT * FROM products 
                WHERE category_id = '$cat_id' 
                AND product_id != '$id' 
                LIMIT 4";
$related_result = $db->query($related_sql);

// Handle Add to Cart
if (isset($_POST['add_cart_details'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $basePrice = $product['price'];
    $finalPrice = $basePrice;
    $nameSuffix = "";

    // Check warranty
    if (isset($_POST['has_warranty']) && $_POST['has_warranty'] == 'yes') {
        $prodObj = new BasicProduct($product['name'], $basePrice);
        $warrantyObj = new WarrantyDecorator($prodObj);
        $finalPrice = $warrantyObj->getPrice();
        $nameSuffix = " (Warranty Included)";
    }

    $_SESSION['cart'][] = [
        'id'    => $product['product_id'],
        'name'  => $product['name'] . $nameSuffix,
        'price' => $finalPrice,
        'qty'   => (int)$_POST['qty']
    ];

    echo "<script>
        alert('Item added to cart!'); 
        window.location.href='user_dashboard.php';
    </script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | Digital Tech Hub</title>
    
    <link rel="stylesheet" href="user_dashboard.css">
    <link rel="stylesheet" href="Productdetails.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon" style="width: 50px; height: 50px; border-radius: 12px; overflow: hidden; background: white; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                    <img src="<?php echo $logo; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
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
                    <li><a href="user_dashboard.php" class="active"><i class="fa fa-store"></i> Shop</a></li>
                    <li><a href="cart_view.php"><i class="fa fa-cart-shopping"></i> My Cart</a></li>
                    <li><a href="checkout_process.php?history=true"><i class="fa fa-clock-rotate-left"></i> Order History</a></li>
                    <li class="logout-item"><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            
            <header class="topbar">
                <div class="welcome-text">
                    <h2><i class="fas fa-box-open"></i> Product Details</h2>
                    <p>Specification & Customization</p>
                </div>
                <div class="top-actions">
                    <a href="user_dashboard.php" class="back-link">
                        <i class="fa fa-arrow-left"></i> Back to Shop
                    </a>
                    <div class="notification-bell">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                </div>
            </header>

            <div class="scrollable-content">
                
                <div class="product-showcase-card">
                    
                    <div class="showcase-image">
                        <div class="main-img-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                    </div>

                    <div class="showcase-info">
                        
                        <div class="meta-tags">
                            <span class="tag-category"><i class="fa fa-layer-group"></i> <?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php if($product['stock_quantity'] > 0): ?>
                                <span class="tag-stock in"><i class="fa fa-check"></i> In Stock</span>
                            <?php else: ?>
                                <span class="tag-stock out"><i class="fa fa-times"></i> Sold Out</span>
                            <?php endif; ?>
                        </div>

                        <h1 class="product-heading"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <div class="price-block">
                            <span class="currency">৳</span>
                            <span class="amount" id="displayPrice"><?php echo number_format($product['price']); ?></span>
                        </div>

                        <div class="desc-block">
                            <h4>Description</h4>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>

                        <form method="POST" action="" class="purchase-form">
                            
                            <?php if ($product['has_warranty']) { ?>
                            <div class="option-card">
                                <label class="custom-toggle-row">
                                    <div class="toggle-info">
                                        <span class="toggle-title"><i class="fa fa-shield-alt"></i> Official Warranty</span>
                                        <span class="toggle-desc">1 Year Coverage (+৳2,000)</span>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="has_warranty" id="warrantyCheck" value="yes">
                                        <span class="slider round"></span>
                                    </div>
                                </label>
                            </div>
                            <?php } ?>

                            <div class="action-footer">
                                <div class="qty-control">
                                    <button type="button" onclick="adjustQty(-1)"><i class="fa fa-minus"></i></button>
                                    <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" readonly>
                                    <button type="button" onclick="adjustQty(1)"><i class="fa fa-plus"></i></button>
                                </div>
                                
                                <button type="submit" name="add_cart_details" class="btn-add-cart" 
                                    <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fa fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <?php if ($related_result && $related_result->num_rows > 0): ?>
                <div class="related-section">
                    <h3>Similar Products</h3>
                    <div class="related-grid">
                        <?php while ($rel = $related_result->fetch_assoc()): ?>
                            <a href="Productdetails.php?id=<?php echo $rel['product_id']; ?>" class="mini-card">
                                <div class="mini-img">
                                    <img src="uploads/<?php echo htmlspecialchars($rel['product_image']); ?>" alt="Related">
                                </div>
                                <div class="mini-info">
                                    <h5><?php echo htmlspecialchars($rel['name']); ?></h5>
                                    <span>৳<?php echo number_format($rel['price']); ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
        const basePrice = <?php echo $product['price']; ?>;
        const warrantyCost = 2000;
        const displayEl = document.getElementById('displayPrice');
        const checkboxEl = document.getElementById('warrantyCheck');
        const qtyInput = document.getElementById('qtyInput');
        const maxQty = <?php echo $product['stock_quantity']; ?>;

        if(checkboxEl) {
            checkboxEl.addEventListener('change', function() {
                const final = this.checked ? basePrice + warrantyCost : basePrice;
                displayEl.style.opacity = '0.5';
                setTimeout(() => {
                    displayEl.innerText = final.toLocaleString();
                    displayEl.style.opacity = '1';
                }, 150);
            });
        }

        function adjustQty(amount) {
            let current = parseInt(qtyInput.value);
            let next = current + amount;
            if(next >= 1 && next <= maxQty) {
                qtyInput.value = next;
            }
        }
    </script>
</body>
</html>