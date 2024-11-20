<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $address = $_POST['address'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Remove password hashing

    // Hardcode user_type to 2 for regular users
    $userType = 2; // Regular user type

    $db = new Database();
    $conn = $db->openConnection();

    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->execute([$username]);
    $existingUser = $query->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $error = "Username already taken. Please choose another one.";
    } else {
        try {
            // Insert user into the database with hardcoded user_type
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, address, birthdate, gender, username, password, user_type, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$firstName, $lastName, $address, $birthdate, $gender, $username, $password, $userType]);

            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    $db->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Comic+Sans+MS:wght@700&display=swap');
        
        body {
            font-family: 'Comic Sans MS', sans-serif;
            background: linear-gradient(135deg, #ff9800, #f44336, #2196f3);
            background-size: 300% 300%;
            animation: bgAnimation 5s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        @keyframes bgAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .register-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0px 15px 25px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 350px;
            text-align: center;
        }

        .register-container h2 {
            font-size: 2rem;
            color: #ff5722;
            margin-bottom: 20px;
        }

        .register-container label {
            display: block;
            margin-bottom: 5px;
            color: #2196f3;
            font-size: 1.1rem;
            text-align: left;
        }

        .register-container input,
        .register-container select {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 2px solid #ffeb3b;
            border-radius: 10px;
            background: #e0f7fa;
            color: #333;
            font-size: 1rem;
            text-align: center;
            transition: border-color 0.3s;
        }

        .register-container button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(45deg, #ff5722, #ff9800);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.4s;
        }

        .register-container button:hover {
            background: linear-gradient(45deg, #ff9800, #ff5722);
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" id="first_name" required>
            
            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" required>

            <label for="address">Address:</label>
            <input type="text" name="address" id="address" required>

            <label for="birthdate">Birthdate:</label>
            <input type="date" name="birthdate" id="birthdate" required>

            <label for="gender">Gender:</label>
            <select name="gender" id="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <!-- Removed the user_type dropdown -->
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>