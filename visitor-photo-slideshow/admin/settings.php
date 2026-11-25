
<?php
session_start();

require_once "../config/database.php";
require_once "../config/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $duration = (int)($_POST["slide_duration"] ?? 5);
    $enabled = isset($_POST["slideshow_enabled"]) ? "1" : "0";

    if ($duration < 2 || $duration > 60) {
        $message = "Duration must be between 2 and 60 seconds.";
    } else {

        $stmt = $conn->prepare(
            "UPDATE settings SET setting_value = ?
             WHERE setting_key = ?"
        );

        $key = "slide_duration";
        $value = (string)$duration;
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();

        $key = "slideshow_enabled";
        $stmt->bind_param("ss", $enabled, $key);
        $stmt->execute();

        $message = "Settings saved successfully!";
    }
}

$result = $conn->query(
    "SELECT setting_key, setting_value FROM settings"
);

$settings = [];

while ($row = $result->fetch_assoc()) {
    $settings[$row["setting_key"]] = $row["setting_value"];
}

$duration = $settings["slide_duration"] ?? "5";
$enabled = $settings["slideshow_enabled"] ?? "1";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slideshow Settings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f3f4fa;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #11152b;
            color: white;
            position: fixed;
            padding: 30px 18px;
        }

        .sidebar h3 {
            font-size: 21px;
            margin: 0 0 35px;
            color: #c4b5fd;
        }

        .sidebar a {
            display: block;
            color: #c7c9d8;
            text-decoration: none;
            padding: 14px 15px;
            border-radius: 9px;
            margin-bottom: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #6d55d9;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 40px;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h2 {
            font-weight: 700;
            color: #191d35;
        }

        .settings-card {
            max-width: 750px;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(20,25,60,.07);
        }

        .form-label {
            font-weight: 600;
            color: #34384d;
        }

        .form-control {
            padding: 13px;
            border-radius: 10px;
        }

        .form-check-input {
            width: 45px;
            height: 23px;
            cursor: pointer;
        }

        .save-btn {
            background: #6d55d9;
            border: none;
            color: white;
            padding: 13px 25px;
            border-radius: 9px;
            font-weight: 600;
        }

        .save-btn:hover {
            background: #5740bf;
            color: white;
        }

        .info {
            background: #f2efff;
            color: #5945b4;
            padding: 15px;
            border-radius: 10px;
            margin-top: 22px;
        }

        @media(max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h3>✦ Visitor Moments</h3>

    <a href="dashboard.php">Dashboard</a>
    <a href="visitors.php">Visitors</a>
    <a href="photos.php">Photo Approval</a>
    <a href="settings.php" class="active">Settings</a>
    <a href="logout.php">Logout</a>
</aside>

<main class="main">

    <div class="heading">
        <h2>Slideshow Settings</h2>
        <p class="text-secondary">Manage your live TV display preferences.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="settings-card">

        <form method="POST">

            <div class="mb-4">
                <label class="form-label">Photo Change Interval</label>

                <div class="input-group">
                    <input
                        type="number"
                        name="slide_duration"
                        class="form-control"
                        min="2"
                        max="60"
                        value="<?= htmlspecialchars($duration) ?>"
                        required
                    >
                    <span class="input-group-text">Seconds</span>
                </div>

                <small class="text-secondary">
                    Choose how many seconds each photo should appear.
                </small>
            </div>

            <div class="mb-4">
                <label class="form-label d-block">Live Slideshow Status</label>

                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="slideshow_enabled"
                        id="slideshow_enabled"
                        <?= $enabled === "1" ? "checked" : "" ?>
                    >

                    <label class="form-check-label ms-2" for="slideshow_enabled">
                        Enable Live Slideshow
                    </label>
                </div>
            </div>

            <button type="submit" class="save-btn">
                Save Settings
            </button>

        </form>

        <div class="info">
            <strong>Note:</strong> Only approved visitor photos are displayed on the TV screen.
        </div>

    </div>

</main>

</body>
</html>
