<?php
session_start();
require_once 'Database.php';



$logo = "https://raw.githubusercontent.com/Rokib-Hasan-Oli/Digital_Tech_Hub_--CSE327_Sec-6/Rokib-Hasan-Oli/Relevant%20documents%20and%20FIle/Logo/2.png";
$msg = "";
$msg_type = ""; // 'success' or 'error'

$db = Database::getInstance()->getConnection();

// ==========================================
// 1. HANDLE REGISTER
// ==========================================
if (isset($_POST['register'])) {
    $name = $db->real_escape_string($_POST['name']);
    $email = $db->real_escape_string($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (full_name, email, password) VALUES ('$name', '$email', '$pass')";
    if ($db->query($sql)) {
        $msg = "Registration Successful! Please Login.";
        $msg_type = "success";
    } else {
        $msg = "Error: " . $db->error;
        $msg_type = "error";
    }
}

// ==========================================
// 2. HANDLE LOGIN
// ==========================================
if (isset($_POST['login'])) {
    $email = $db->real_escape_string($_POST['email']);
    $pass = $_POST['password'];
    $role = $_POST['role'];

    if ($role == 'admin') {
        $res = $db->query("SELECT * FROM admins WHERE email='$email'");
        $row = $res->fetch_assoc();
        
        // Check password (supports both plain text sample data & hashed real data)
        if ($row && ($pass === $row['password'] || password_verify($pass, $row['password']))) {
            $_SESSION['role'] = 'admin';
            $_SESSION['user_id'] = $row['admin_id'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $msg = "Invalid Admin Credentials";
            $msg_type = "error";
        }
    } else {
        $res = $db->query("SELECT * FROM users WHERE email='$email'");
        $row = $res->fetch_assoc();
        
        if ($row && password_verify($pass, $row['password'])) {
            if ($row['status'] == 'Blocked') {
                $msg = "Access Denied: Your account is blocked.";
                $msg_type = "error";
            } else {
                $_SESSION['role'] = 'user';
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['full_name'];
                $_SESSION['photo'] = $row['profile_image'];
                header("Location: user_dashboard.php");
                exit();
            }
        } else {
            $msg = "Invalid Email or Password";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Hub - Digital Tech Hub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #0056b3;
            --primary-dark: #004494;
            --accent: #ff6600;
            --bg-body: #eef2f6;
            --white: #ffffff;
            --gray: #636e72;
            --text: #2d3436;
            --radius: 12px;
            --shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-body);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            padding: 20px;
        }

        /* Main Container */
        .container {
            width: 100%;
            max-width: 900px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            display: flex;
            overflow: hidden;
            min-height: 550px;
            position: relative;
        }

        /* Left Side: Visual */
        .visual-side {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        /* Abstract circles background */
        .visual-side::before, .visual-side::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
        }
        .visual-side::before { width: 300px; height: 300px; top: -50px; left: -50px; }
        .visual-side::after { width: 200px; height: 200px; bottom: -20px; right: -20px; }

        .logo-box {
            background: rgba(255,255,255,0.9);
            padding: 15px;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 2;
        }

        .logo-box img { width: 80px; }

        .visual-side h1 { font-size: 24px; margin-bottom: 10px; z-index: 2; }
        .visual-side p { font-size: 14px; opacity: 0.8; z-index: 2; line-height: 1.6; }

        /* Right Side: Forms */
        .form-side {
            flex: 1.2;
            padding: 50px;
            position: relative;
            background: var(--white);
            display: flex;
            align-items: center;
        }

        .form-container {
            width: 100%;
            transition: all 0.4s ease-in-out;
            /* Simple fade animation handling via JS display toggle */
        }
        
        .form-header { margin-bottom: 30px; }
        .form-header h2 { color: var(--text); font-size: 28px; font-weight: 600; margin-bottom: 5px; }
        .form-header p { color: var(--gray); font-size: 14px; }

        /* Input Styling with Icons */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 16px;
        }

        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Space for icon */
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            transition: 0.3s;
            background: #fafafa;
            appearance: none; /* Removes default arrow for select */
        }

        .input-group select {
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%230056b3%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 12px auto;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.1);
            outline: none;
            background: #fff;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0,86,179,0.2);
        }

        .btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .btn-register { background: #27ae60; box-shadow: 0 4px 10px rgba(39, 174, 96, 0.2); }
        .btn-register:hover { background: #219150; }

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #b91c1c; }
        .alert.success { background: #dcfce7; color: #15803d; border-left: 4px solid #15803d; }

        /* Toggle Link */
        .toggle-box {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray);
        }
        .toggle-box span {
            color: var(--accent);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .toggle-box span:hover { text-decoration: underline; color: #d94e00; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { flex-direction: column; min-height: auto; }
            .visual-side { padding: 30px; min-height: 200px; }
            .form-side { padding: 30px 20px; }
            .logo-box { width: 80px; height: 80px; }
            .logo-box img { width: 50px; }
        }
        
        /* Utility to hide/show */
        .hidden { display: none; }
        .fade-in { animation: fadeIn 0.5s ease; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="visual-side">
            <div class="logo-box">
                <img src="<?php echo $logo; ?>" alt="Logo">
            </div>
            <h1>Digital Tech Hub</h1>
            <p>Empowering your digital life with the best hardware solutions.</p>
        </div>

        <div class="form-side">
            
            <?php if(!empty($msg)): ?>
                <div style="width: 100%; position: absolute; top: 20px; left: 0; padding: 0 50px;">
                    <div class="alert <?php echo $msg_type; ?>">
                        <i class="fas <?php echo ($msg_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo $msg; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-container fade-in" id="login-box">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Enter your details to access your account.</p>
                </div>
                
                <form method="post">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-user-tag"></i>
                        <select name="role">
                            <option value="user">Customer Account</option>
                            <option value="admin">Admin Access</option>
                        </select>
                    </div>

                    <button type="submit" name="login" class="btn">Sign In</button>
                    
                    <div class="toggle-box">
                        New to Tech Hub? <span onclick="switchForm('register')">Create Account</span>
                    </div>
                </form>
            </div>

            <div class="form-container fade-in hidden" id="register-box">
                <div class="form-header">
                    <h2>Get Started</h2>
                    <p>Create your account to start shopping.</p>
                </div>

                <form method="post">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Full Name" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" placeholder="Create Password" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-register">Register</button>
                    
                    <div class="toggle-box">
                        Already have an account? <span onclick="switchForm('login')">Sign In</span>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function switchForm(target) {
            const loginBox = document.getElementById('login-box');
            const registerBox = document.getElementById('register-box');

            if (target === 'register') {
                loginBox.classList.add('hidden');
                registerBox.classList.remove('hidden');
            } else {
                registerBox.classList.add('hidden');
                loginBox.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>