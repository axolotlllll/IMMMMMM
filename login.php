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
                SELECT c.*, p.quantity as available_stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.product_id 
                WHERE c.user_id = ? AND (c.status = 'active' OR c.status = 'saved')
            ");
            $cartQuery->execute([$user['id']]);
            $cartItems = $cartQuery->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore product quantities for saved items and update cart status
            if (!empty($cartItems)) {
                $conn->beginTransaction();
                try {
                    foreach ($cartItems as $item) {
                        if ($item['status'] === 'saved') {
                            // Restore product quantity
                            $updateProduct = $conn->prepare("
                                UPDATE products 
                                SET quantity = quantity + ? 
                                WHERE product_id = ?
                            ");
                            $updateProduct->execute([$item['quantity'], $item['product_id']]);
                            

                            // Update cart status to active
                            $updateCart = $conn->prepare("
                                UPDATE cart 
                                SET status = 'active' 
                                WHERE id = ?
                            ");
                            $updateCart->execute([$item['id']]);
                        }
                    }
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Error restoring cart: " . $e->getMessage());
                }
            }
            
            // Store cart items in session
            $_SESSION['cart'] = $cartItems;
            
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
    } catch (Exception $e) {
        $error = "An error occurred during login.";
        error_log("Login error: " . $e->getMessage());
    } finally {
        $db->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@600&family=Righteous&display=swap');

        body {
            font-family: 'Baloo 2', cursive;
            background: linear-gradient(135deg, #ff4081, #7c4dff, #ffeb3b);
            background-size: 300% 300%;
            animation: bgAnimation 5s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        @keyframes bgAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0px 15px 25px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 350px;
            text-align: center;
        }

        .login-container h2 {
            font-family: 'Righteous', sans-serif;
            font-size: 2rem;
            color: #ff4081;
            margin-bottom: 20px;
        }

        .login-container label {
            display: block;
            margin-bottom: 5px;
            color: #7c4dff;
            font-size: 1.1rem;
            text-align: left;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 90%;
            padding: 12px;
            margin-bottom: 15px;
            border: 2px dashed #ffeb3b;
            border-radius: 10px;
            background: #ffccff;
            color: #333;
            text-align: center;
            font-family: 'Baloo 2', sans-serif;
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus {
            border-color: #ff4081;
            outline: none;
            transform: scale(1.05);
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(45deg, #ff4081, #ffeb3b);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.4s ease, transform 0.2s ease;
            font-family: 'Righteous', sans-serif;
        }

        .login-container button:hover {
            background: linear-gradient(45deg, #ffeb3b, #ff4081);
            transform: scale(1.1);
            box-shadow: 0px 5px 15px rgba(255, 192, 203, 0.6);
        }

        .error-message {
            color: #ff4081;
            margin: 10px 0;
            font-weight: bold;
            font-size: 0.9rem;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }

        .login-container p {
            font-size: 0.9rem;
            color: #7c4dff;
        }

        .login-container a {
            color: #ff4081;
            font-weight: bold;
            text-decoration: underline;
        }

        .login-container a:hover {
            color: #ffeb3b;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back!</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Login</button>
        </form>
        <p>DONT HAVE AN ACCOUNT??????????????????<a href="register.php">PISLITA NI !!!!!!!!!!!!!</a></p>
    </div>
</body>
</html>
