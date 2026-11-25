
<?php
require_once "../config/database.php";
require_once "../config/config.php";

// ========================================
// GET SLIDESHOW SETTINGS
// ========================================

function getSetting($conn, $key, $default = "")
{
    $stmt = $conn->prepare(
        "SELECT setting_value FROM settings
         WHERE setting_key = ? LIMIT 1"
    );

    $stmt->bind_param("s", $key);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row["setting_value"];
    }

    return $default;
}

$duration = (int)getSetting($conn, "slide_duration", "5");
$duration = max(2, min(60, $duration));

$slideshowEnabled = getSetting(
    $conn,
    "slideshow_enabled",
    "1"
);

// ========================================
// GET APPROVED PHOTOS
// ========================================

$photos = [];

$sql = "SELECT
            p.id,
            p.image_path,
            v.name
        FROM photos p
        INNER JOIN visitors v
            ON p.visitor_id = v.id
        WHERE p.status = 'approved'
        ORDER BY p.uploaded_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $photos[] = $row;
    }
}

$photoNames = array_column($photos, "name");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta http-equiv="Cache-Control"
          content="no-cache, no-store, must-revalidate">

    <title>Visitor Moments - Live Slideshow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            background: #080b18;
            color: white;
            font-family: Arial, sans-serif;
        }

        .slideshow {
            position: relative;
            width: 100%;
            height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background:
                radial-gradient(
                    circle at center,
                    #25294d,
                    #080b18 75%
                );
        }

        /* TOP HEADER */

        .top-bar {
            position: absolute;
            top: 28px;
            left: 40px;
            right: 40px;

            z-index: 20;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 2px;
            color: white;
        }

        .live-status {
            color: #4ade80;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        /* SLIDES */

        .slide {
            position: absolute;
            inset: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            opacity: 0;
            transform: scale(1.04);

            transition:
                opacity 1s ease,
                transform 1.5s ease;

            pointer-events: none;
        }

        .slide.active {
            opacity: 1;
            transform: scale(1);
        }

        .slide img {
            max-width: 88%;
            max-height: 80%;

            object-fit: contain;

            border-radius: 18px;

            box-shadow:
                0 20px 80px rgba(0,0,0,.6);
        }

        /* BOTTOM DETAILS */

        .bottom-bar {
            position: absolute;
            bottom: 28px;
            left: 0;
            right: 0;

            z-index: 20;

            text-align: center;
        }

        .visitor-name {
            font-size: 23px;
            font-weight: 700;
            color: white;

            text-shadow: 0 2px 15px black;
        }

        .counter {
            margin-top: 9px;
            color: #b4b8d0;
            font-size: 14px;
        }

        /* EMPTY / PAUSED SCREEN */

        .empty-screen {
            text-align: center;
            padding: 30px;
            max-width: 700px;
        }

        .empty-icon {
            font-size: 65px;
            margin-bottom: 20px;
        }

        .empty-screen h1 {
            font-size: 38px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .empty-screen p {
            font-size: 16px;
            color: #b7bad0;
            line-height: 1.7;
        }

        .paused-label {
            color: #fbbf24;
            font-size: 13px;
            letter-spacing: 3px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* RESPONSIVE */

        @media(max-width: 600px) {

            .top-bar {
                top: 18px;
                left: 18px;
                right: 18px;
            }

            .brand {
                font-size: 16px;
            }

            .slide img {
                max-width: 95%;
                max-height: 72%;
            }

            .visitor-name {
                font-size: 18px;
            }

            .empty-screen h1 {
                font-size: 27px;
            }
        }

    </style>

</head>

<body>

<div class="slideshow">

    <!-- TOP HEADER -->

    <div class="top-bar">

        <div class="brand">
            ✦ VISITOR MOMENTS
        </div>

        <div class="live-status" id="displayStatus">
            <?php if ($slideshowEnabled === "1"): ?>
                ● LIVE DISPLAY
            <?php else: ?>
                ● DISPLAY PAUSED
            <?php endif; ?>
        </div>

    </div>


    <!-- SLIDESHOW CONTENT -->

    <?php if ($slideshowEnabled !== "1"): ?>

        <div class="empty-screen">

            <div class="empty-icon">⏸️</div>

            <div class="paused-label">
                DISPLAY TEMPORARILY PAUSED
            </div>

            <h1>Slideshow Temporarily Paused</h1>

            <p>
                The administrator has temporarily disabled
                the live photo display.
            </p>

        </div>


    <?php elseif (count($photos) > 0): ?>

        <?php foreach ($photos as $index => $photo): ?>

            <div class="slide <?= $index === 0 ? 'active' : '' ?>">

                <img
                    src="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                    alt="Visitor Photo"
                >

            </div>

        <?php endforeach; ?>


        <!-- VISITOR DETAILS -->

        <div class="bottom-bar">

            <div class="visitor-name" id="visitorName">
                <?= htmlspecialchars($photos[0]["name"], ENT_QUOTES, "UTF-8") ?>
            </div>

            <div class="counter" id="counter">
                1 / <?= count($photos) ?>
            </div>

        </div>


    <?php else: ?>

        <div class="empty-screen">

            <div class="empty-icon">✨</div>

            <h1>Your Moments. Our Memories.</h1>

            <p>
                No approved photos available yet.
                <br>
                Approved visitor photos will appear here.
            </p>

        </div>

    <?php endif; ?>

</div>


<!-- SLIDESHOW JAVASCRIPT -->

<?php if ($slideshowEnabled === "1" && count($photos) > 1): ?>

<script>

    const slides = document.querySelectorAll(".slide");

    const names = <?= json_encode(
        $photoNames,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const duration = <?= $duration ?> * 1000;

    let current = 0;

    setInterval(() => {

        slides[current].classList.remove("active");

        current = (current + 1) % slides.length;

        slides[current].classList.add("active");

        document.getElementById("visitorName").textContent =
            names[current];

        document.getElementById("counter").textContent =
            (current + 1) + " / " + slides.length;

    }, duration);

</script>

<?php endif; ?>


<!-- AUTOMATIC ON/OFF CHECK -->

<script>

    let previousState = null;

    async function checkDisplayStatus() {

        try {

            const response = await fetch(
                window.location.href + "?check=" + Date.now(),
                {
                    cache: "no-store"
                }
            );

            if (!response.ok) return;

            const html = await response.text();

            const parser = new DOMParser();

            const doc = parser.parseFromString(
                html,
                "text/html"
            );

            const newStatus = doc.getElementById("displayStatus");

            const currentStatus =
                document.getElementById("displayStatus");

            if (!newStatus || !currentStatus) return;

            const newState =
                newStatus.textContent.includes("LIVE DISPLAY")
                ? "ON"
                : "OFF";

            if (previousState === null) {
                previousState =
                    currentStatus.textContent.includes("LIVE DISPLAY")
                    ? "ON"
                    : "OFF";
            }

            if (newState !== previousState) {

                previousState = newState;

                window.location.reload();

            }

        } catch (error) {

            console.log("Display status check failed:", error);

        }

    }

    // Check admin ON/OFF setting every 5 seconds
    setInterval(checkDisplayStatus, 5000);

</script>

</body>
</html>
