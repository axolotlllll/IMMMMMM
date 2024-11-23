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
    $password = $_POST['password']; 

    $userType = 2; 

    $db = new Database();
    $conn = $db->openConnection();

    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->execute([$username]);
    $existingUser = $query->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $error = "Username already taken. Please choose another one.";
    } else {
        try {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Montserrat', sans-serif;
            background: #000; /* Fallback color */
            position: relative;
            overflow: hidden;
        }

        .video-background {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%) scale(1.02);
            z-index: -1;
            filter: brightness(0.7);
            object-fit: cover;
        }

        .register-container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            z-index: 1;
        }

        .register-container h1 {
            color: white;
            font-size: 2.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            width: 100%;
            max-width: 400px;
            margin-bottom: 15px;
            position: relative;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: transparent;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .form-group input::placeholder,
        .form-group select::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group select {
            appearance: none;
            padding-right: 30px;
            cursor: pointer;
        }

        .form-group select option {
            background: #333;
            color: white;
        }

        .register-btn {
            width: 100%;
            max-width: 400px;
            padding: 12px 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .register-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .login-link {
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
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

        /* Custom styles for flatpickr */
        .flatpickr-calendar {
            background: rgba(0, 0, 0, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 15px !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: rgba(0, 0, 0, 0.8) !important;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months option {
            background: rgba(0, 0, 0, 0.8) !important;
            color: white !important;
        }

        .flatpickr-current-month input.cur-year {
            background: transparent !important;
        }

        .numInputWrapper span {
            border: none !important;
            background: transparent !important;
        }

        .numInputWrapper span:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: rgba(255, 255, 255, 0.8) !important;
        }

        .flatpickr-day {
            color: white !important;
            border-radius: 8px !important;
        }

        .flatpickr-day.selected {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
        }

        .flatpickr-day:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-months .flatpickr-prev-month, 
        .flatpickr-months .flatpickr-next-month {
            color: white !important;
            fill: white !important;
        }

        .flatpickr-weekday {
            color: rgba(255, 255, 255, 0.8) !important;
            background: transparent !important;
        }

        .flatpickr-day.today {
            border-color: white !important;
        }

        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: rgba(255, 255, 255, 0.3) !important;
        }

        .flatpickr-day.disabled {
            color: rgba(255, 255, 255, 0.2) !important;
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 15px;
            }
            .form-group {
                max-width: 100%;
            }
            .register-btn {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop playsinline class="video-background">
        <source src="product_images/Subway Surfers (2024) - Gameplay [4K 16x9] No Copyright.mp4" type="video/mp4">
    </video>
    <div class="register-container">
        <h1>Create Account</h1>
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="first_name" placeholder="First Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
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
            <div class="form-group">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="address" placeholder="Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-user-circle"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        // Video control
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.video-background');
            if (video) {
                video.addEventListener('loadedmetadata', function() {
                    video.currentTime = video.duration / 2;
                    video.playbackRate = 1.5;
                });
            }

            const birthdate = document.getElementById('birthdate');
            if (birthdate) {
                flatpickr(birthdate, {
                    dateFormat: "Y-m-d",
                    maxDate: new Date(),
                    disableMobile: true,
                    animate: true,
                    allowInput: true,
                    clickOpens: true,
                    position: "auto",
                    monthSelectorType: "dropdown",
                    yearSelectorType: "dropdown",
                    onChange: function(selectedDates, dateStr, instance) {
                        if (dateStr) {
                            instance.element.style.color = 'white';
                        }
                    },
                    onOpen: function(selectedDates, dateStr, instance) {
                        instance.element.style.color = 'white';
                    }
                });
            }
        });
    </script>
</body>
</html>