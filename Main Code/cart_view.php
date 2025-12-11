<?php
session_start();
require_once 'DiscountStrategy.php'; 

$rawTotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $rawTotal += ($item['price'] * $item['qty']);
    }
}

// Strategy Pattern Usage
$context = new DiscountContext();
$msg = "";

if (isset($_POST['apply_coupon'])) {
    if ($_POST['code'] == 'HOLIDAY10') {
        $context->setStrategy(new HolidayDiscount());
        $msg = "Coupon Applied: 10% Off";
    }
}

$finalTotal = $context->getFinalPrice($rawTotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Digital Tech Hub</title>
    
    <link rel="stylesheet" href="user_dashboard.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* Cart Panel Container */
        .cart-panel {
            background: var(--bg-white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }

        /* Styled Table */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .cart-table th {
            text-align: left;
            padding: 15px;
            color: var(--text-gray);
            font-weight: 500;
            border-bottom: 2px solid #f3f4f6;
        }

        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: var(--text-dark);
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .product-name-cell {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Summary Section */
        .cart-summary {
            background: #f9fafb;
            padding: 25px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .total-display h3 {
            font-size: 1.1rem;
            color: var(--text-gray);
            margin-bottom: 5px;
        }
        
        .total-display .final-price {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Forms and Inputs */
        .coupon-form {
            display: flex;
            gap: 10px;
        }

        .input-styled {
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
        }

        .select-styled {
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            background: white;
            cursor: pointer;
            min-width: 150px;
        }

        /* Buttons */
        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .btn-secondary {
            background: white;
            border: 1px solid #e5e7eb;
            color: var(--text-dark);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #f3f4f6;
        }

        .msg-text {
            color: #059669; /* Green for success */
            font-size: 0.9rem;
            margin-left: 10px;
            font-weight: 500;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: var(--text-gray);
        }
    </style>
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
                <?php echo isset($_SESSION['name']) ? strtoupper(substr($_SESSION['name'], 0, 1)) : 'U'; ?>
            </div>
            <div class="user-info">
                <h3><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?></h3>
                <span class="badge">Customer</span>
            </div>
        </div>

        <nav class="side-nav">
            <ul>
                <li><a href="user_dashboard.php"><i class="fa fa-store"></i> Products</a></li>
                <li><a href="#" class="active"><i class="fa fa-cart-shopping"></i> My Cart</a></li>
                <li><a href="checkout_process.php?history=true"><i class="fa fa-box-open"></i> Orders</a></li>
                <li class="logout-item"><a href="login_register.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="welcome-text">
                <h2>Shopping Cart</h2>
                <p>Review your selected items</p>
            </div>

        </header>

        <div class="scrollable-content">

            <section class="cart-panel">
                
                <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $i): ?>
                                <tr>
                                    <td class="product-name-cell"><?php echo htmlspecialchars($i['name']); ?></td>
                                    <td>৳<?php echo number_format($i['price']); ?></td>
                                    <td><?php echo $i['qty']; ?></td>
                                    <td>৳<?php echo number_format($i['price'] * $i['qty']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-summary">
                        
                        <div class="summary-row" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 20px;">
                            <form method="post" class="coupon-form">
                                <input type="text" name="code" placeholder="Enter Coupon Code" class="input-styled"> 
                                <button type="submit" name="apply_coupon" class="btn-primary">Apply</button>
                            </form>
                            <?php if($msg): ?>
                                <span class="msg-text"><i class="fa fa-check-circle"></i> <?php echo $msg; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="summary-row">
                            <div class="total-display">
                                <h3>Total Amount: ৳<?php echo number_format($rawTotal); ?></h3>
                                <div class="final-price">Payable: ৳<?php echo number_format($finalTotal); ?></div>
                            </div>

                            <form action="checkout_process.php" method="post" style="display: flex; gap: 15px; align-items: center;">
                                <input type="hidden" name="pay_amount" value="<?php echo $finalTotal; ?>">
                                
                                <div>
                                    <select name="method" class="select-styled">
                                        <option value="COD">Cash On Delivery</option>
                                        <option value="Bkash">Bkash</option>
                                        <option value="Nagad">Nagad</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="checkout" class="btn-primary">
                                    Proceed to Checkout <i class="fa fa-arrow-right"></i>
                                </button>
                            </form>
                        </div>

                    </div>

                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fa fa-cart-arrow-down" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3>Your cart is empty</h3>
                        <p>Looks like you haven't added anything yet.</p>
                        <br>
                        <a href="user_dashboard.php" class="btn-primary">Start Shopping</a>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px;">
                    <a href="user_dashboard.php" class="btn-secondary"><i class="fa fa-arrow-left"></i> Continue Shopping</a>
                </div>

            </section>

        </div>
    </main>
</div>

</body>
</html>