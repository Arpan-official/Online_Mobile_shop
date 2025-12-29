<?php


// load PDO connection in case included files (like menu.php) use $pdo
require_once 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>About Us - Mobile Store</title>
    <link rel="stylesheet" href="css/aboutus.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Include the menu -->
    <?php include 'menu.php'; ?>

    <!-- Hero Section with Video Background -->
    <section class="about-banner">
        <div class="video-container">
            <video autoplay muted loop playsinline>
                <source src="banner.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay">
                <div class="video-content">
                    <h1>Discover Our Journey</h1>
                    <p>Innovation, Quality, and Service at Mobile Store</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="about-section">
        <div class="container">
            <h2>Who We Are</h2>
            <p>We are committed to providing our customers with the best mobile devices and accessories at competitive prices. Our journey started with a passion for innovation and a desire to connect people worldwide.</p>

            <div class="mission">
                <h3>Our Mission</h3>
                <p>Our mission is to keep you connected with the latest technology. We offer a wide selection of mobile phones, tablets, and accessories that are both affordable and high-quality.</p>
            </div>

            <div class="story">
                <h3>Our Story</h3>
                <p>Founded on 24th june 2003, Mobile Store grew from a small business to a trusted brand. With a focus on quality and customer service, we aim to provide the best tech experience to our customers globally.</p>
            </div>

            <div class="why-choose-us">
                <h3>Why Choose Us?</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Wide range of the latest smartphones and accessories</li>
                    <li><i class="fas fa-check-circle"></i> Competitive pricing and special deals</li>
                    <li><i class="fas fa-check-circle"></i> Fast and reliable shipping worldwide</li>
                    <li><i class="fas fa-check-circle"></i> Dedicated 24/7 customer support</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Mobile Store. All rights reserved.</p>
        </div>
    </footer>

    <!-- Include JavaScript (optional) -->
    <script src="js/script.js"></script>
</body>
</html>
