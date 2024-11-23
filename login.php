<?php
session_start();
require_once 'connection.php';
   
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->openConnection();

    try {
        $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $query->execute([$username]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

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
    <title>LOGGGIN KUAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gradient-speed: 20s;
            --typing-speed: 3s;
            --phrase-1-color: #6bcc33;
            --phrase-2-color: #89d8f0;
            --phrase-3-color: #BD10E0;
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

        .typewriter {
            color: white;
            margin-bottom: 30px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            width: 100%;
        }

        .typewriter h1 {
            font-size: 3.8em;
            margin: 0;
            white-space: nowrap;
            overflow: visible;
            position: relative;
            transition: color 1s ease;
            z-index: 2;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }

        .typing-container {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 800px;
            padding: 0 20px;
        }

        .cursor {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: currentColor;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
            position: relative;
            top: -2px;
            opacity: 0.8;
            transition: background-color 1s ease;
        }

        @media (max-width: 768px) {
            .typewriter h1 {
                font-size: 2.8em;
            }
            .cursor {
                width: 30px;
                height: 30px;
            }
        }

        @media (max-width: 480px) {
            .typewriter h1 {
                font-size: 2em;
            }
            .cursor {
                width: 25px;
                height: 25px;
            }
        }

        .login-container {
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 25px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: transparent;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 12px 40px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .register-text {
            margin-top: 20px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .register-link {
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .register-link:hover {
            transform: scale(1.05);
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .error {
            color: #ff6b6b;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop playsinline class="video-background">
        <source src="product_images/Creative Commons - Minecraft Parkour Gameplay.mp4" type="video/mp4">
    </video>
    <div class="login-container">
        <div class="typewriter">
            <div class="typing-container">
                <h1></h1><span class="cursor"></span>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <span class="register-text">Already have an account? <a href="register.php" class="register-link">Register here</a></span>
    </div>

    <script>
        // Video control
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.video-background');
            if (video) {
                video.addEventListener('loadedmetadata', function() {
                    // Start from middle of the video
                    video.currentTime = video.duration / 2;
                    video.playbackRate = 1.5;
                });
            }
        });

        // Existing typing animation code
        const phrases = [
            { text: "ming gabiibuntaghapon", class: 'phrase-1' },
            { text: "bossing", class: 'phrase-2' },
            { text: "wehehaehhe", class: 'phrase-3' }
        ];

        const typingDelay = 50;  // Faster typing
        const erasingDelay = 25; // Faster erasing
        const newTextDelay = 500; // Shorter pause between phrases
        const colorTransitionDelay = 699; // Color change speed

        let textArrayIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        const typewriterElement = document.querySelector('.typewriter h1');
        const cursorElement = document.querySelector('.cursor');
        const bodyElement = document.body;

        function type() {
            const currentPhrase = phrases[textArrayIndex];
            const text = currentPhrase.text;

            // Apply current phrase styles with a slight delay for smoother transition
            setTimeout(() => {
                bodyElement.className = currentPhrase.class;
            }, colorTransitionDelay / 2);
            
            typewriterElement.style.color = getComputedStyle(document.documentElement)
                .getPropertyValue(`--${currentPhrase.class}-color`);
            cursorElement.style.color = getComputedStyle(document.documentElement)
                .getPropertyValue(`--${currentPhrase.class}-color`);

            if (isDeleting) {
                charIndex--;
            } else {
                charIndex++;
            }

            typewriterElement.textContent = text.substring(0, charIndex);

            if (!isDeleting && charIndex === text.length) {
                setTimeout(() => {
                    isDeleting = true;
                }, newTextDelay);
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                textArrayIndex = (textArrayIndex + 1) % phrases.length;
            }

            const delay = isDeleting ? erasingDelay : typingDelay;
            setTimeout(type, delay);
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(type, newTextDelay);
        });
    </script>
</body>
</html>
