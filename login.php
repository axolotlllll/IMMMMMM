<?php
session_start();
require_once 'connection.php';
   
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->openConnection();

    try {
        // Fetch user by username
        $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $query->execute([$username]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        // Verify the password and check user type
        if ($user && $password === $user['password']) {
            // Store user ID, username, and user_type in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Load both active and saved cart items
            $cartQuery = $conn->prepare("
                SELECT c.*, p.product_name, p.price, p.quantity as available_stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.product_id 
                WHERE c.user_id = ? AND (c.status = 'active' OR c.status = 'saved')
            ");
            $cartQuery->execute([$user['id']]);
            $cartItems = $cartQuery->fetchAll(PDO::FETCH_ASSOC);
            
            // Update saved items to active and store in session
            if (!empty($cartItems)) {
                $conn->beginTransaction();
                try {
                    foreach ($cartItems as &$item) {
                        if ($item['status'] === 'saved') {
                            // Update cart status to active
                            $updateCart = $conn->prepare("
                                UPDATE cart 
                                SET status = 'active' 
                                WHERE id = ?
                            ");
                            $updateCart->execute([$item['id']]);
                            $item['status'] = 'active';
                        }
                    }
                    $conn->commit();
                    $_SESSION['cart'] = $cartItems;
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Error restoring cart: " . $e->getMessage());
                }
            } else {
                $_SESSION['cart'] = array();
            }
            
            // Redirect based on user type
            if ($user['user_type'] == 1) {
                header("Location: index.php");
            } elseif ($user['user_type'] == 2) {
                header("Location: user_home.php");
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
    
    $db->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Celebrity Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            background-size: 300% 300%;
            animation: color 12s ease-in-out infinite;
            padding: 2rem;
        }

        @keyframes color {
            0% {
                background-position: 0 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0 50%;
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.47);
        }

        h1 {
            color: white;
            text-align: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
        }

        input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1rem;
        }

        button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .error {
            background: rgba(255, 87, 87, 0.1);
            border: 1px solid rgba(255, 87, 87, 0.2);
            color: #fff;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            backdrop-filter: blur(5px);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: white;
        }

        .register-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }

            h1 {
                font-size: 1.75rem;
            }

            input, button {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Welcome Back!</h1>
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
