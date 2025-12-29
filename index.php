<?php

require_once("db.php");
include("menu.php");

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_form'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    try {
        $stmt = $pdo->prepare("INSERT INTO contact (name, email, message) VALUES (:name, :email, :message)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':message' => $message
        ]);
        $contact_message = "Message sent successfully!";
    } catch (PDOException $e) {
        $contact_message = "Error: " . $e->getMessage();
    }
}

// Fetch random products
try {
    $productQuery = "SELECT * FROM products ORDER BY RAND() LIMIT 3";
    $stmt = $pdo->query($productQuery);
    $products = $stmt->fetchAll();
    $hasProducts = count($products) > 0;
} catch (PDOException $e) {
    $products = [];
    $hasProducts = false;
    $contact_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mobile Store</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Slideshow specific styles (keeps existing css intact) --- */
        .video-banner {
            position: relative;
            width: 100%;
            height: 100vh;
            overflow: hidden;
        }

        .slideshow {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        .slide {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .slide.active {
            display: flex;
        }

        .slide video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .video-content {
            position: relative;
            color: white;
            text-align: center;
            z-index: 3;
        }


        /* Controls (prev/next + direct toggles) */
        .slideshow-controls {
            position: absolute;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 4;
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        .toggle-btn:focus {
            outline: 2px solid #ffcc00;
        }


        .prev-next {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateY(-50%);
            z-index: 4;
        }

        .prev {
            margin-left: 18px;
        }

        .next {
            margin-right: 18px;
        }

        .prev-next .toggle-btn {
            padding: 12px 14px;
        }


        /* small dot indicators */
        .dots {
            display: flex;
            gap: 8px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.35);
            border: none;
            cursor: pointer;
        }

        .dot.active {
            background: #ffcc00;
        }


        /* Responsive tweaks so overlay content remains readable */
        @media (max-width: 768px) {
            .video-content h2 {
                font-size: 2rem;
            }

            .video-content p {
                font-size: 1rem;
            }
        }
    </style>
</head>



<body>
    <!-- Hero Section with Video Slideshow Banner -->
    <section class="video-banner">
        <div class="slideshow" id="slideshow">
            <!-- Slide 1 -->
            <div class="slide active" data-index="0">
                <video playsinline muted loop preload="metadata">
                    <source src="banner3.mp4" type="video/mp4">
                </video>
            </div>


            <!-- Slide 2 -->
            <div class="slide" data-index="1">
                <video playsinline muted loop preload="metadata">
                    <source src="banner2.mp4" type="video/mp4">
                </video>
            </div>


            <!-- Slide 3 -->
            <div class="slide" data-index="2">
                <video playsinline muted loop preload="metadata">
                    <source src="banner.mp4" type="video/mp4">
                </video>
            </div>
        </div>


        <div class="video-overlay" aria-hidden="false">
            <div class="video-content" role="region" aria-label="Hero banner">
                <h2>Explore the Best Mobile Deals</h2>
                <p>Get your hands on the latest smartphones with incredible discounts.</p>
                <a href="shop.php" class="btn">Shop Now</a>
            </div>
        </div>


        <!-- Prev / Next buttons -->
        <div class="prev-next">
            <button class="toggle-btn prev" id="prevBtn" aria-label="Previous slide"><i
                    class="fas fa-chevron-left"></i></button>
            <button class="toggle-btn next" id="nextBtn" aria-label="Next slide"><i
                    class="fas fa-chevron-right"></i></button>
        </div>


        <!-- Dots + Direct toggle buttons -->
        <div class="slideshow-controls" id="slideshowControls" aria-hidden="false">
            <div class="dots" id="dotsContainer">
                <button class="dot active" data-index="0" aria-label="Go to slide 1"></button>
                <button class="dot" data-index="1" aria-label="Go to slide 2"></button>
                <button class="dot" data-index="2" aria-label="Go to slide 3"></button>
            </div>
        </div>
    </section>
    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-mobile-alt"></i>
        <h2 class="section-title-large">Our Top-Selling Phones</h2>
    </div>

    <!-- Products Section -->
    <section class="products" id="products">
        <div class="container">
            <div class="product-grid">
                <?php if ($hasProducts): ?>
                    <?php foreach ($products as $row): ?>
                        <div class="card">
                            <img src="images/<?php echo htmlspecialchars($row['image']); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p>Rs. <?php echo htmlspecialchars(number_format($row['price'], 2)); ?></p>
                            <a href="product.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn">Buy Now</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products available for sale at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-question-circle"></i>
        <h2 class="section-title-large">Frequently Asked Questions</h2>
    </div>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="faq-item">
                <h3>What is the return policy?</h3>
                <p>You can return the product within 30 days of purchase with a valid receipt.</p>
            </div>
            <div class="faq-item">
                <h3>Do you offer free shipping?</h3>
                <p>Yes, free shipping is available for orders over $500.</p>
            </div>
            <div class="faq-item">
                <h3>Can I buy in installments?</h3>
                <p>Yes, we offer installment plans with select payment options.</p>
            </div>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-envelope"></i>
        <h2 class="section-title-large">Contact Us</h2>
    </div>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <form class="contact-form" action="" method="POST">
                <input type="hidden" name="contact_form" value="1">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="5" required></textarea>

                <button type="submit" class="btn">Send Message</button>
            </form>
            <?php if (isset($contact_message)): ?>
                <p><?php echo htmlspecialchars($contact_message); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Separator -->
    <div class="section-separator">
        <i class="fas fa-map-marker-alt"></i>
        <h2 class="section-title-large">Our Store Location</h2>
    </div>

    <!-- Google Maps Section -->
    <section class="location">
        <div class="container">
            <div id="map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4425.40854386986!2d88.4190377569126!3d22.517054786134295!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a0273f58b9feec5%3A0x30f8067b73c45d8!2sHeritage%20Institute%20of%20Technology%2C%20Kolkata!5e0!3m2!1sen!2sin!4v1762948165785!5m2!1sen!2sin"
                    width="100%" height="450" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0">
                </iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2024 Mobile Store. All rights reserved.</p>
        </div>
    </footer>
</body>


<script>
    // Slideshow logic: prev/next and direct toggles
    (function () {
        const slides = Array.from(document.querySelectorAll('.slide'));
        const dots = Array.from(document.querySelectorAll('.dot'));
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        let current = 0;


        function showSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;


            // pause and reset videos on all slides
            slides.forEach((s, i) => {
                s.classList.toggle('active', i === index);
                const v = s.querySelector('video');
                if (v) {
                    try {
                        v.pause();
                        v.currentTime = 0;
                    } catch (e) { }
                }
            });


            // play current slide video (muted autoplay)
            const activeVideo = slides[index].querySelector('video');
            if (activeVideo) {
                // play returns a promise in some browsers
                activeVideo.muted = true;
                const p = activeVideo.play();
                if (p && p.catch) p.catch(() => { });
            }


            // update dots
            dots.forEach((d, i) => d.classList.toggle('active', i === index));


            current = index;
        }


        // wire buttons
        prevBtn.addEventListener('click', () => showSlide(current - 1));
        nextBtn.addEventListener('click', () => showSlide(current + 1));


        // wire direct dot buttons
        dots.forEach(d => d.addEventListener('click', (e) => {
            const idx = parseInt(e.currentTarget.getAttribute('data-index'), 10);
            showSlide(idx);
        }));


        // Optional: keyboard navigation (left/right)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') showSlide(current - 1);
            if (e.key === 'ArrowRight') showSlide(current + 1);
        });


        // Ensure first slide's video plays on load (if allowed)
        window.addEventListener('load', () => showSlide(0));


    })();
</script>

</html>