<?php
// Simple script to generate password hash for database insertion

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    echo "<h2>Hashed Password:</h2>";
    echo "<pre>" . htmlspecialchars($hashed_password) . "</pre>";
    echo "<p>Copy this hash and use it when inserting into the database.</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        input, button {
            margin: 10px 0;
            padding: 10px;
            width: 100%;
        }
    </style>
</head>
<body>
    <h1>Password Hash Generator</h1>
    <form method="POST">
        <input type="password" name="password" placeholder="Enter password to hash" required>
        <button type="submit">Generate Hash</button>
    </form>
</body>
</html>
