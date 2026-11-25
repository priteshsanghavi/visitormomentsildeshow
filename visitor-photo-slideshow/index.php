
<?php
require_once "config/config.php";
require_once "config/database.php";

// Total Visitors
$totalVisitors = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM visitors");

if ($result) {
    $totalVisitors = (int) $result->fetch_assoc()['total'];
}

// Total Approved Photos
$totalPhotos = 0;
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM photos WHERE status = 'approved'"
);

if ($result) {
    $totalPhotos = (int) $result->fetch_assoc()['total'];
}

// Slideshow Setting
$slideshowEnabled = 1;
$result = $conn->query(
    "SELECT setting_value FROM settings WHERE setting_key = 'slideshow_enabled' LIMIT 1"
);

if ($result && $row = $result->fetch_assoc()) {
    $slideshowEnabled = (int) $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visitor Moments | Every Visit Has a Story</title>

    <meta name="description" content="Share your special moments and become part of our beautiful Visitor Moments digital gallery.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css?v=10">

    <style>
        :root {
            --vm-dark: #101126;
            --vm-purple: #7665e8;
            --vm-light: #f7f7fc;
            --vm-text: #25263b;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            background: var(--vm-light);
            color: var(--vm-text);
        }

        /* NAVBAR */

        .main-navbar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            padding: 22px 0;
            background: transparent;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white !important;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -1px;
            text-decoration: none;
        }

        .navbar-brand span:last-child {
            color: #a99cff;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: white;
            background: linear-gradient(135deg, #a18bff, #6853dc);
            box-shadow: 0 8px 25px rgba(118, 101, 232, .35);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            color: rgba(255,255,255,.82);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: .3s;
        }

        .nav-links a:hover {
            color: white;
        }

        .nav-display {
            padding: 11px 20px;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 30px;
            color: white !important;
            transition: .3s;
        }

        .nav-display:hover {
            background: white;
            color: var(--vm-dark) !important;
        }

        .mobile-menu-btn {
            display: none;
            border: 1px solid rgba(255,255,255,.4);
            color: white;
            background: transparent;
            padding: 8px 13px;
            border-radius: 9px;
            font-size: 20px;
        }

        /* HERO */

        .hero-section {
            min-height: 740px;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            color: white;

            background:
                radial-gradient(circle at 80% 30%, rgba(126, 93, 255, .38), transparent 30%),
                radial-gradient(circle at 10% 90%, rgba(44, 87, 170, .3), transparent 35%),
                linear-gradient(120deg, #101126 0%, #171735 55%, #29204e 100%);
        }

        .hero-section::before {
            content: "";
            position: absolute;
            width: 550px;
            height: 550px;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 50%;
            right: -120px;
            top: 100px;
        }

        .hero-section::after {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 50%;
            right: -40px;
            top: 175px;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(16,17,38,.2), transparent);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding-top: 100px;
            padding-bottom: 65px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 17px;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(255,255,255,.07);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 700;
            color: #d6ceff;
            margin-bottom: 28px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #7df0b2;
            border-radius: 50%;
            box-shadow: 0 0 15px #7df0b2;
        }

        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(48px, 7vw, 82px);
            line-height: 1.12;
            letter-spacing: -2px;
            font-weight: 800;
            margin-bottom: 24px;
        }

        .hero-content h1 span {
            color: #a99aff;
        }

        .hero-content > p {
            max-width: 570px;
            color: rgba(255,255,255,.72);
            font-size: 17px;
            line-height: 1.9;
            margin-bottom: 32px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 15px 25px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: .3s;
        }

        .primary-btn {
            color: white;
            background: linear-gradient(135deg, #927cff, #6853dc);
            box-shadow: 0 12px 30px rgba(118,101,232,.3);
        }

        .primary-btn:hover {
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 17px 35px rgba(118,101,232,.4);
        }

        .secondary-btn {
            color: white;
            border: 1px solid rgba(255,255,255,.3);
            background: rgba(255,255,255,.06);
        }

        .secondary-btn:hover {
            color: var(--vm-dark);
            background: white;
            transform: translateY(-4px);
        }

        .hero-features {
            display: flex;
            flex-wrap: wrap;
            gap: 45px;
            margin-top: 65px;
        }

        .hero-features div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .hero-features strong {
            font-size: 23px;
            color: #a99aff;
        }

        .hero-features span {
            color: rgba(255,255,255,.65);
            font-size: 13px;
        }

        /* STATS */

        .stats-section {
            position: relative;
            z-index: 5;
            margin-top: -48px;
        }

        .stats-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 55px rgba(28,25,65,.09);
        }

        .stat-item {
            text-align: center;
            padding: 12px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #7665e8;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #77788c;
            font-size: 13px;
            font-weight: 600;
        }

        /* WELCOME */

        .welcome-section {
            padding: 110px 0 100px;
            text-align: center;
        }

        .section-label {
            color: #7968e6;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 3px;
        }

        .welcome-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5vw, 48px);
            color: #17182e;
            font-weight: 800;
            margin: 17px 0;
        }

        .welcome-section > .container > p {
            max-width: 610px;
            margin: 0 auto 30px;
            color: #77788c;
            line-height: 1.9;
            font-size: 15px;
        }

        .welcome-cards {
            margin-top: 65px;
            text-align: left;
        }

        .welcome-card {
            height: 100%;
            background: white;
            padding: 30px;
            border: 1px solid #ededf5;
            border-radius: 18px;
            transition: .3s;
        }

        .welcome-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 45px rgba(30,28,70,.08);
        }

        .card-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            color: #7665e8;
            background: #f0edff;
            font-size: 24px;
            margin-bottom: 22px;
        }

        .welcome-card h4 {
            color: #22233a;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .welcome-card p {
            color: #818196;
            font-size: 13px;
            line-height: 1.8;
            margin: 0;
        }

        .dark-btn {
            display: inline-block;
            margin-top: 5px;
            padding: 14px 26px;
            color: white;
            background: #17182e;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            transition: .3s;
        }

        .dark-btn:hover {
            color: white;
            background: #7665e8;
            transform: translateY(-3px);
        }

        /* CTA */

        .cta-section {
            padding: 65px 20px;
            background: linear-gradient(120deg, #191a38, #34245e);
            color: white;
            text-align: center;
        }

        .cta-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            margin-bottom: 15px;
        }

        .cta-section p {
            color: rgba(255,255,255,.7);
            margin-bottom: 25px;
        }

        /* FOOTER */

        .main-footer {
            padding: 25px 15px;
            text-align: center;
            background: #101126;
            color: rgba(255,255,255,.6);
        }

        .main-footer p {
            margin: 0;
            font-size: 13px;
        }

        /* RESPONSIVE */

        @media(max-width: 768px) {
            .main-navbar {
                padding: 15px 0;
            }

            .navbar-brand {
                font-size: 20px;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 75px;
                left: 5%;
                width: 90%;
                padding: 20px;
                flex-direction: column;
                align-items: stretch;
                gap: 18px;
                background: #20203e;
                border: 1px solid rgba(255,255,255,.1);
                border-radius: 15px;
            }

            .nav-links.show {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-section {
                min-height: 720px;
            }

            .hero-content {
                padding-top: 125px;
            }

            .hero-content h1 {
                font-size: 49px;
            }

            .hero-content > p {
                font-size: 15px;
            }

            .hero-features {
                gap: 25px;
                margin-top: 45px;
            }

            .stats-section {
                margin-top: -30px;
            }

            .stats-container {
                padding: 18px;
            }

            .welcome-section {
                padding: 80px 0;
            }

            .cta-section h2 {
                font-size: 30px;
            }
        }

        @media(max-width: 400px) {
            .hero-content h1 {
                font-size: 41px;
            }

            .hero-features {
                gap: 18px;
            }

            .hero-features strong {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->

<nav class="navbar main-navbar">
    <div class="container">

        <a class="navbar-brand" href="index.php">
            <span class="brand-icon">V</span>
            Visitor<span>Moments</span>
        </a>

        <button class="mobile-menu-btn" id="menuButton"
                type="button" aria-label="Toggle navigation"
                aria-expanded="false">
            ☰
        </button>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="visitor/register.php">Share Photos</a>
            <a href="#about">About</a>
            <a href="display/index.php" class="nav-display">
                Live Display →
            </a>
        </div>

    </div>
</nav>

<!-- HERO SECTION -->

<section class="hero-section">

    <div class="hero-overlay"></div>

    <div class="container hero-content">

        <div class="hero-badge">
            <span class="pulse-dot"></span>
            EVERY VISIT HAS A STORY
        </div>

        <h1>
            Your Moments.<br>
            Our <span>Memories.</span>
        </h1>

        <p>
            Every smile deserves a place in our memories.
            Share your special moments and become part
            of our beautiful visitor gallery.
        </p>

        <div class="hero-buttons">

            <a href="visitor/register.php" class="primary-btn">
                Share Your Moments →
            </a>

            <a href="display/index.php" class="secondary-btn">
                View Slideshow
            </a>

        </div>

        <div class="hero-features">

            <div>
                <strong>01</strong>
                <span>Register</span>
            </div>

            <div>
                <strong>02</strong>
                <span>Upload Photos</span>
            </div>

            <div>
                <strong>03</strong>
                <span>Enjoy Memories</span>
            </div>

        </div>

    </div>

</section>

<!-- LIVE STATISTICS -->

<section class="stats-section">
    <div class="container">

        <div class="stats-container">

            <div class="row">

                <div class="col-6 col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?= number_format($totalVisitors) ?>+
                        </div>
                        <div class="stat-label">
                            Registered Visitors
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?= number_format($totalPhotos) ?>+
                        </div>
                        <div class="stat-label">
                            Approved Memories
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="stat-item">
                        <div class="stat-number">
                            <?= $slideshowEnabled ? 'LIVE' : 'OFF' ?>
                        </div>
                        <div class="stat-label">
                            Slideshow Status
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>

<!-- WELCOME SECTION -->

<section class="welcome-section" id="about">

    <div class="container">

        <span class="section-label">
            WELCOME TO OUR GALLERY
        </span>

        <h2>Moments That Stay Forever.</h2>

        <p>
            Your photographs become beautiful memories
            displayed on our digital screen. Register,
            upload your favorite moments and let your memories shine.
        </p>

        <a href="visitor/register.php" class="dark-btn">
            Get Started →
        </a>

        <!-- FEATURES -->

        <div class="row g-4 welcome-cards">

            <div class="col-md-4">
                <div class="welcome-card">

                    <div class="card-icon">✦</div>

                    <h4>Quick Registration</h4>

                    <p>
                        Register with your basic details
                        and become part of our visitor community.
                    </p>

                </div>
            </div>

            <div class="col-md-4">
                <div class="welcome-card">

                    <div class="card-icon">▧</div>

                    <h4>Share Your Photos</h4>

                    <p>
                        Upload your favorite photographs
                        and keep your special moments alive.
                    </p>

                </div>
            </div>

            <div class="col-md-4">
                <div class="welcome-card">

                    <div class="card-icon">♡</div>

                    <h4>Enjoy The Memories</h4>

                    <p>
                        Approved photographs become part
                        of our beautiful digital slideshow.
                    </p>

                </div>
            </div>

        </div>

    </div>

</section>

<!-- CALL TO ACTION -->

<section class="cta-section">

    <div class="container">

        <h2>Be Part of Our Memories.</h2>

        <p>
            Your visit matters. Share a photo and make it memorable.
        </p>

        <a href="visitor/register.php" class="primary-btn">
            Share Your Moments →
        </a>

    </div>

</section>

<!-- FOOTER -->

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Visitor Moments.
        All Rights Reserved.
    </p>

</footer>

<script>
    const menuButton = document.getElementById("menuButton");
    const navLinks = document.getElementById("navLinks");

    menuButton.addEventListener("click", function () {
        navLinks.classList.toggle("show");

        const expanded = navLinks.classList.contains("show");
        menuButton.setAttribute("aria-expanded", expanded);
    });

    navLinks.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navLinks.classList.remove("show");
            menuButton.setAttribute("aria-expanded", "false");
        });
    });
</script>

</body>
</html>
