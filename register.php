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
    <title>Register - Celebrity Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
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
            background: linear-gradient(45deg, #4ecdc4, #45b7d1, #ff6b6b);
            background-size: 300% 300%;
            animation: color 12s ease-in-out infinite;
            padding: 2rem;
        }

        @keyframes color {
            0% { background-position: 0 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }

        .register-container {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .register-container:hover {
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

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            z-index: 1;
        }

        input, select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        /* Flatpickr Custom Styles */
        .flatpickr-calendar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            border-radius: 15px !important;
            border: none !important;
            backdrop-filter: blur(10px) !important;
        }

        .flatpickr-day {
            border-radius: 10px !important;
            transition: all 0.3s ease !important;
        }

        .flatpickr-day.selected {
            background: #45b7d1 !important;
            border-color: #45b7d1 !important;
        }

        .flatpickr-day:hover {
            background: rgba(69, 183, 209, 0.2) !important;
        }

        .flatpickr-months .flatpickr-month {
            background: #45b7d1 !important;
            color: white !important;
            fill: white !important;
            border-radius: 15px 15px 0 0 !important;
        }

        .flatpickr-current-month {
            color: white !important;
        }

        .flatpickr-monthDropdown-months {
            background: #45b7d1 !important;
            color: white !important;
        }

        .flatpickr-weekdays {
            background: #45b7d1 !important;
        }

        span.flatpickr-weekday {
            background: #45b7d1 !important;
            color: white !important;
        }

        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            fill: white !important;
        }

        select {
            appearance: none;
            padding-right: 2rem;
            cursor: pointer;
        }

        .form-group::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            pointer-events: none;
        }

        input::placeholder, select {
            color: rgba(255, 255, 255, 0.7);
        }

        input:focus, select:focus {
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

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: white;
        }

        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        option {
            background: #2c3e50;
            color: white;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 2rem;
            }

            h1 {
                font-size: 1.75rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            input, select, button {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Create Account</h1>
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <i class="fas fa-calendar"></i>
                    <input type="text" name="birthdate" id="birthdate" placeholder="Select Birthdate" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-venus-mars"></i>
                    <select name="gender" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="address" placeholder="Address" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <i class="fas fa-user-circle"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            
            <button type="submit">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#birthdate", {
            dateFormat: "Y-m-d",
            maxDate: new Date(),
            disableMobile: "true",
            animate: true,
            theme: "material_blue"
        });
    </script>
</body>
</html>