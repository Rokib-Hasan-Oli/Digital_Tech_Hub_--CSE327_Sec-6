<?php
session_start();
require_once 'Database.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login_register.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$logo = "https://ln.run/WlP6-";
$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Fetch current user data
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $db->query($user_sql);
$user_data = $user_result->fetch_assoc();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = $db->real_escape_string($_POST['full_name']);
    $email = $db->real_escape_string($_POST['email']);
    $phone = $db->real_escape_string($_POST['phone'] ?? '');
    $address = $db->real_escape_string($_POST['address'] ?? '');
    
    // Handle profile photo upload
    $profile_image = $user_data['profile_image'] ?? 'default-avatar.png';
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = "uploads/profiles/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image = $new_filename;
            }
        }
    }
    
    // Update database
    $update_sql = "UPDATE users SET 
                   full_name = '$full_name',
                   email = '$email',
                   phone = '$phone',
                   address = '$address',
                   profile_image = '$profile_image'
                   WHERE user_id = $user_id";
    
    if ($db->query($update_sql)) {
        $_SESSION['name'] = $full_name;
        $_SESSION['email'] = $email;
        $success_msg = "Profile updated successfully!";
        
        // Refresh user data
        $user_result = $db->query($user_sql);
        $user_data = $user_result->fetch_assoc();
    } else {
        $error_msg = "Failed to update profile.";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // FIX 1: Use password_verify() to check the hash, not direct comparison
    if (password_verify($current_password, $user_data['password'])) {
        
        if ($new_password == $confirm_password) {
            if (strlen($new_password) >= 6) {
                
                // FIX 2: Hash the NEW password before saving it to the database
                // If you don't do this, you won't be able to login next time!
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_pass = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
                
                if ($db->query($update_pass)) {
                    $success_msg = "Password changed successfully!";
                    
                    // Optional: Update the local user_data so consecutive changes don't fail immediately
                    $user_data['password'] = $hashed_password;
                } else {
                    $error_msg = "Failed to change password.";
                }
            } else {
                $error_msg = "Password must be at least 6 characters.";
            }
        } else {
            $error_msg = "New passwords do not match.";
        }
    } else {
        $error_msg = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Digital Tech Hub</title>
    
    <link rel="stylesheet" href="user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        .profile-settings-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .settings-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        
        .settings-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        /* Profile Photo Section */
        .photo-section {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            padding: 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
        }
        
        .current-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .photo-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        
        .photo-info p {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 16px;
        }
        
        .file-upload-wrapper {
            position: relative;
        }
        
        .file-upload-wrapper input[type="file"] {
            display: none;
        }
        
        .btn-upload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-upload:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-group label i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-dark);
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 71, 161, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 2px solid #86efac;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        
        /* Buttons */
        .btn-primary {
            padding: 12px 28px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(13, 71, 161, 0.2);
        }
        
        .btn-secondary {
            padding: 12px 28px;
            background: #f3f4f6;
            color: var(--text-dark);
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        /* Password Strength Indicator */
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            background: #10b981;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .photo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon">
                    <img src="<?php echo $logo; ?>" alt="Logo">
                </div>
                <span>Digital Tech Hub</span>
            </div>

            <div class="user-profile-widget">
                <div class="avatar-circle">
                    <?php 
                    if (!empty($user_data['profile_image']) && $user_data['profile_image'] != 'default-avatar.png') {
                        echo '<img src="uploads/profiles/' . $user_data['profile_image'] . '" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">';
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
                        <a href="user_dashboard.php">
                            <i class="fa fa-store"></i> <span>Shop</span>
                        </a>
                    </li>
                    <li>
                        <a href="cart_view.php">
                            <i class="fa fa-cart-shopping"></i> <span>My Cart</span>
                        </a>
                    </li>
                    <li>
                        <a href="user_dashboard.php?view=orders">
                            <i class="fa fa-box-open"></i> <span>Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile_settings.php" class="active">
                            <i class="fa fa-user-gear"></i> <span>Profile Settings</span>
                        </a>
                    </li>
                    <li class="logout-item">
                        <a href="logout.php">
                            <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="welcome-text">
                    <h2><i class="fas fa-user-gear"></i> Profile Settings</h2>
                    <p>Manage your account information</p>
                </div>
                <div class="top-actions">
                    <div class="notification-bell">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                </div>
            </header>

            <div class="scrollable-content">
                <div class="profile-settings-container">

                    <?php if ($success_msg): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        <span><?php echo $success_msg; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_msg): ?>
                    <div class="alert alert-error">
                        <i class="fa fa-exclamation-circle"></i>
                        <span><?php echo $error_msg; ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- PROFILE INFORMATION -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="fas fa-user-circle"></i>
                            <h3>Profile Information</h3>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            
                            <!-- Profile Photo -->
                            <div class="photo-section">
                                <?php 
                                $photo_src = (!empty($user_data['profile_image']) && $user_data['profile_image'] != 'default-avatar.png') 
                                    ? 'uploads/profiles/' . $user_data['profile_image'] 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name']) . '&size=120&background=0d47a1&color=fff&bold=true';
                                ?>
                                <img src="<?php echo $photo_src; ?>" alt="Profile Photo" class="current-photo" id="photoPreview">
                                
                                <div class="photo-info">
                                    <h4>Profile Picture</h4>
                                    <p>Upload a new photo (JPG, PNG, GIF - Max 2MB)</p>
                                    <div class="file-upload-wrapper">
                                        <label for="profile_image" class="btn-upload">
                                            <i class="fa fa-camera"></i> Choose Photo
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewPhoto(event)">
                                    </div>
                                </div>
                            </div>

                            <!-- Form Fields -->
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-user"></i> Full Name
                                    </label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="+880 1234-567890">
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-calendar"></i> Member Since
                                    </label>
                                    <input type="text" value="<?php echo date('F Y', strtotime($user_data['created_at'] ?? 'now')); ?>" disabled>
                                </div>

                                <div class="form-group form-group-full">
                                    <label>
                                        <i class="fa fa-location-dot"></i> Address
                                    </label>
                                    <textarea name="address" placeholder="Enter your full address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" name="update_profile" class="btn-primary">
                                    <i class="fa fa-save"></i> Save Changes
                                </button>
                                <a href="user_dashboard.php" class="btn-secondary">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- CHANGE PASSWORD -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <i class="fas fa-lock"></i>
                            <h3>Change Password</h3>
                        </div>

                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group form-group-full">
                                    <label>
                                        <i class="fa fa-key"></i> Current Password
                                    </label>
                                    <input type="password" name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-lock"></i> New Password
                                    </label>
                                    <input type="password" name="new_password" id="newPassword" required minlength="6">
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fa fa-lock"></i> Confirm New Password
                                    </label>
                                    <input type="password" name="confirm_password" required minlength="6">
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" name="change_password" class="btn-primary">
                                    <i class="fa fa-shield-halved"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        // Photo Preview
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        // Password Strength Indicator
        const newPassword = document.getElementById('newPassword');
        const strengthBar = document.getElementById('strengthBar');

        if (newPassword) {
            newPassword.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                if (password.length >= 6) strength += 25;
                if (password.length >= 10) strength += 25;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                if (/\d/.test(password)) strength += 25;

                strengthBar.style.width = strength + '%';
                
                if (strength <= 25) strengthBar.style.background = '#ef4444';
                else if (strength <= 50) strengthBar.style.background = '#f59e0b';
                else if (strength <= 75) strengthBar.style.background = '#3b82f6';
                else strengthBar.style.background = '#10b981';
            });
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'fadeOut 0.5s ease-out';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>

</body>
</html>