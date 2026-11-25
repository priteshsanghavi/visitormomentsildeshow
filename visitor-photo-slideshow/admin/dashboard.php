
<?php
session_start();

require_once "../config/database.php";
require_once "../config/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// ========================================
// DASHBOARD STATISTICS
// ========================================

function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if ($result) {
        return (int)$result->fetch_assoc()["total"];
    }

    return 0;
}

$totalVisitors = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM visitors"
);

$totalPhotos = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM photos"
);

$pendingPhotos = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM photos WHERE status='pending'"
);

$approvedPhotos = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM photos WHERE status='approved'"
);

$rejectedPhotos = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM photos WHERE status='rejected'"
);

$todayVisitors = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM visitors
     WHERE DATE(created_at) = CURDATE()"
);

$todayPhotos = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM photos
     WHERE DATE(uploaded_at) = CURDATE()"
);

// ========================================
// MONTHLY CHART DATA - LAST 6 MONTHS
// ========================================

$chartLabels = [];
$visitorChartData = [];
$photoChartData = [];

$visitorMonthly = [];
$photoMonthly = [];

$visitorResult = $conn->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        COUNT(*) AS total
    FROM visitors
    WHERE created_at >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01'
    )
    GROUP BY month_key
");

if ($visitorResult) {
    while ($row = $visitorResult->fetch_assoc()) {
        $visitorMonthly[$row["month_key"]] = (int)$row["total"];
    }
}

$photoResult = $conn->query("
    SELECT
        DATE_FORMAT(uploaded_at, '%Y-%m') AS month_key,
        COUNT(*) AS total
    FROM photos
    WHERE uploaded_at >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01'
    )
    GROUP BY month_key
");

if ($photoResult) {
    while ($row = $photoResult->fetch_assoc()) {
        $photoMonthly[$row["month_key"]] = (int)$row["total"];
    }
}

for ($i = 5; $i >= 0; $i--) {

    $monthDate = new DateTime("first day of this month");
    $monthDate->modify("-" . $i . " months");

    $monthKey = $monthDate->format("Y-m");

    $chartLabels[] = $monthDate->format("M Y");

    $visitorChartData[] = $visitorMonthly[$monthKey] ?? 0;
    $photoChartData[] = $photoMonthly[$monthKey] ?? 0;
}

// ========================================
// RECENT PHOTOS
// ========================================

$recentPhotos = $conn->query("
    SELECT
        p.id,
        p.image_path,
        p.status,
        p.uploaded_at,
        v.name
    FROM photos p
    INNER JOIN visitors v ON p.visitor_id = v.id
    ORDER BY p.uploaded_at DESC
    LIMIT 6
");

// ========================================
// RECENT VISITORS
// ========================================

$recentVisitors = $conn->query("
    SELECT
        v.id,
        v.name,
        v.email,
        v.phone,
        v.created_at,
        COUNT(p.id) AS total_photos
    FROM visitors v
    LEFT JOIN photos p ON v.id = p.visitor_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Visitor Moments</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f5fb;
            font-family: Arial, sans-serif;
            color: #20233a;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #11152b;
            padding: 28px 18px;
            color: white;
            z-index: 100;
        }

        .sidebar h3 {
            color: #c4b5fd;
            font-size: 21px;
            font-weight: 800;
            margin-bottom: 38px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px 15px;
            margin-bottom: 8px;
            border-radius: 10px;
            color: #c6c9dc;
            text-decoration: none;
            font-size: 14px;
            transition: .3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #6d55d9;
            color: white;
        }

        .sidebar a i {
            font-size: 17px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
            padding: 14px;
            border-top: 1px solid #292d47;
            color: #a8abc1;
            font-size: 12px;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 32px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
        }

        .top-header p {
            color: #85899e;
            margin: 7px 0 0;
            font-size: 14px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.04);
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee9ff;
            color: #7054d7;
            border-radius: 50%;
            font-size: 20px;
        }

        .profile-text strong {
            display: block;
            font-size: 13px;
        }

        .profile-text small {
            color: #9295a8;
            font-size: 11px;
        }

        /* WELCOME BANNER */

        .welcome-banner {
            position: relative;
            overflow: hidden;
            padding: 30px 32px;
            border-radius: 18px;
            color: white;
            margin-bottom: 28px;
            background:
                linear-gradient(120deg, #5141b6, #8263e9);
        }

        .welcome-banner h3 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .welcome-banner p {
            color: #e7e0ff;
            margin: 0;
            font-size: 14px;
        }

        .welcome-icon {
            position: absolute;
            right: 55px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 90px;
            opacity: .16;
        }

        /* STAT CARDS */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            padding: 21px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
            transition: .3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(20,25,60,.08);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .stat-icon {
            width: 47px;
            height: 47px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            font-size: 21px;
        }

        .purple {
            background: #eee9ff;
            color: #7054d7;
        }

        .blue {
            background: #e6f2ff;
            color: #2785df;
        }

        .orange {
            background: #fff1dc;
            color: #db8b1c;
        }

        .green {
            background: #e3f9ed;
            color: #1baf69;
        }

        .red {
            background: #ffe8eb;
            color: #df4b5c;
        }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
        }

        .stat-card p {
            color: #9295a8;
            font-size: 13px;
            margin: 6px 0 0;
        }

        /* QUICK ACTIVITY */

        .activity-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .activity-card {
            background: white;
            padding: 20px 22px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
        }

        .activity-card strong {
            font-size: 23px;
            display: block;
        }

        .activity-card small {
            color: #9295a8;
        }

        /* CHARTS */

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .panel {
            background: white;
            padding: 23px;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
            min-width: 0;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            gap: 10px;
        }

        .panel-heading h4 {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .panel-heading small {
            color: #9295a8;
        }

        .chart-container {
            position: relative;
            height: 270px;
            width: 100%;
        }

        /* RECENT TABLE */

        .recent-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .table {
            vertical-align: middle;
            margin: 0;
        }

        .table thead th {
            background: #f7f7fc;
            border: none;
            padding: 13px 10px;
            color: #85899e;
            font-size: 11px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px 10px;
            border-bottom: 1px solid #f0f0f5;
            font-size: 12px;
        }

        .visitor-name {
            font-weight: 700;
            color: #252943;
        }

        .visitor-email {
            color: #9295a8;
            font-size: 11px;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .photo-thumb {
            width: 47px;
            height: 43px;
            object-fit: cover;
            border-radius: 8px;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff4d9;
            color: #b47a10;
        }

        .status-approved {
            background: #e3f9ed;
            color: #15945a;
        }

        .status-rejected {
            background: #ffe8eb;
            color: #df4b5c;
        }

        .view-all {
            color: #7054d7;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .empty {
            text-align: center;
            padding: 30px 10px;
            color: #9295a8;
            font-size: 13px;
        }

        /* QUICK LINKS */

        .quick-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 18px;
            background: white;
            border-radius: 13px;
            text-decoration: none;
            color: #252943;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
            transition: .3s;
        }

        .quick-link:hover {
            transform: translateY(-3px);
            color: #7054d7;
        }

        .quick-link i {
            font-size: 23px;
            color: #7054d7;
        }

        .quick-link strong {
            font-size: 13px;
            display: block;
        }

        .quick-link small {
            color: #9295a8;
            font-size: 11px;
        }

        .footer {
            text-align: center;
            padding: 15px;
            color: #9295a8;
            font-size: 12px;
        }

        /* RESPONSIVE */

        @media(max-width: 1250px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .recent-grid {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 900px) {

            .charts-grid {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .sidebar-footer {
                display: none;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .top-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }

            .welcome-banner {
                padding: 25px 20px;
            }

            .welcome-icon {
                right: 15px;
                font-size: 65px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 480px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .activity-row {
                grid-template-columns: 1fr;
            }

            .top-header h2 {
                font-size: 23px;
            }

        }

    </style>

</head>

<body>

<!-- =====================================
     SIDEBAR
===================================== -->

<aside class="sidebar">

    <h3>✦ Visitor Moments</h3>

    <a href="dashboard.php" class="active">
        <i class="bi bi-grid"></i>
        Dashboard
    </a>

    <a href="visitors.php">
        <i class="bi bi-people"></i>
        Visitors
    </a>

    <a href="photos.php">
        <i class="bi bi-images"></i>
        Photo Approval
    </a>

    <a href="settings.php">
        <i class="bi bi-gear"></i>
        Settings
    </a>

    <a href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

    <div class="sidebar-footer">
        <i class="bi bi-shield-check"></i>
        Secure Admin Panel
        <br>
        © 2026 Visitor Moments
    </div>

</aside>


<!-- =====================================
     MAIN DASHBOARD
===================================== -->

<main class="main">

    <!-- HEADER -->

    <div class="top-header">

        <div>
            <h2>Dashboard Overview</h2>
            <p>Welcome back! Here's what's happening today.</p>
        </div>

        <div class="admin-profile">

            <div class="profile-icon">
                <i class="bi bi-person-fill"></i>
            </div>

            <div class="profile-text">

                <strong>
                    <?= htmlspecialchars($_SESSION["admin_name"] ?? "Administrator") ?>
                </strong>

                <small>Administrator</small>

            </div>

        </div>

    </div>


    <!-- WELCOME BANNER -->

    <section class="welcome-banner">

        <div>

            <h3>Welcome to Visitor Moments! ✨</h3>

            <p>
                Manage visitors, review photos and control your live slideshow
                from one place.
            </p>

        </div>

        <div class="welcome-icon">
            <i class="bi bi-camera-fill"></i>
        </div>

    </section>


    <!-- STATISTICS -->

    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon purple">
                    <i class="bi bi-people-fill"></i>
                </div>

                <i class="bi bi-arrow-up-right text-secondary"></i>

            </div>

            <h3><?= $totalVisitors ?></h3>

            <p>Total Registered Visitors</p>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon blue">
                    <i class="bi bi-images"></i>
                </div>

                <i class="bi bi-arrow-up-right text-secondary"></i>

            </div>

            <h3><?= $totalPhotos ?></h3>

            <p>Total Uploaded Photos</p>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon orange">
                    <i class="bi bi-clock-history"></i>
                </div>

                <i class="bi bi-hourglass-split text-secondary"></i>

            </div>

            <h3><?= $pendingPhotos ?></h3>

            <p>Pending Photo Approvals</p>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-icon green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <i class="bi bi-check2-all text-secondary"></i>

            </div>

            <h3><?= $approvedPhotos ?></h3>

            <p>Approved Photos</p>

        </div>

    </section>


    <!-- TODAY ACTIVITY -->

    <section class="activity-row">

        <div class="activity-card">

            <div class="stat-icon purple">
                <i class="bi bi-person-plus-fill"></i>
            </div>

            <div>
                <strong><?= $todayVisitors ?></strong>
                <small>New Visitors Today</small>
            </div>

        </div>


        <div class="activity-card">

            <div class="stat-icon blue">
                <i class="bi bi-cloud-arrow-up-fill"></i>
            </div>

            <div>
                <strong><?= $todayPhotos ?></strong>
                <small>Photos Uploaded Today</small>
            </div>

        </div>

    </section>


    <!-- CHARTS -->

    <section class="charts-grid">

        <div class="panel">

            <div class="panel-heading">

                <div>
                    <h4>Visitor Registration</h4>
                    <small>Last 6 months</small>
                </div>

                <i class="bi bi-people text-secondary fs-5"></i>

            </div>

            <div class="chart-container">

                <canvas id="visitorChart"></canvas>

            </div>

        </div>


        <div class="panel">

            <div class="panel-heading">

                <div>
                    <h4>Photo Upload Activity</h4>
                    <small>Last 6 months</small>
                </div>

                <i class="bi bi-images text-secondary fs-5"></i>

            </div>

            <div class="chart-container">

                <canvas id="photoChart"></canvas>

            </div>

        </div>

    </section>


    <!-- RECENT ACTIVITY -->

    <section class="recent-grid">

        <!-- RECENT PHOTOS -->

        <div class="panel">

            <div class="panel-heading">

                <h4>Recent Uploaded Photos</h4>

                <a href="photos.php" class="view-all">
                    View All
                    <i class="bi bi-arrow-right"></i>
                </a>

            </div>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Photo</th>
                            <th>Visitor</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($recentPhotos && $recentPhotos->num_rows > 0): ?>

                            <?php while ($photo = $recentPhotos->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <img
                                            src="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                                            class="photo-thumb"
                                            alt="Visitor photo"
                                        >

                                    </td>

                                    <td>
                                        <div class="visitor-name">
                                            <?= htmlspecialchars($photo["name"]) ?>
                                        </div>

                                        <div class="visitor-email">
                                            <?= htmlspecialchars($photo["uploaded_at"]) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="status status-<?= htmlspecialchars($photo["status"]) ?>">
                                            <?= htmlspecialchars($photo["status"]) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3">
                                    <div class="empty">
                                        No uploaded photos yet.
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- RECENT VISITORS -->

        <div class="panel">

            <div class="panel-heading">

                <h4>Recent Visitors</h4>

                <a href="visitors.php" class="view-all">
                    View All
                    <i class="bi bi-arrow-right"></i>
                </a>

            </div>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Visitor</th>
                            <th>Photos</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($recentVisitors && $recentVisitors->num_rows > 0): ?>

                            <?php while ($visitor = $recentVisitors->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <div class="visitor-name">
                                            <?= htmlspecialchars($visitor["name"]) ?>
                                        </div>

                                        <div class="visitor-email">
                                            <?= htmlspecialchars($visitor["email"] ?: $visitor["phone"] ?: "No contact") ?>
                                        </div>

                                    </td>

                                    <td>

                                        <strong>
                                            <?= (int)$visitor["total_photos"] ?>
                                        </strong>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="2">
                                    <div class="empty">
                                        No visitors registered yet.
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>


    <!-- QUICK LINKS -->

    <h5 class="fw-bold mb-3">Quick Actions</h5>

    <section class="quick-links">

        <a href="visitors.php" class="quick-link">

            <i class="bi bi-person-lines-fill"></i>

            <div>
                <strong>Manage Visitors</strong>
                <small>View registered visitors</small>
            </div>

        </a>


        <a href="photos.php?status=pending" class="quick-link">

            <i class="bi bi-images"></i>

            <div>
                <strong>Review Photos</strong>
                <small>Check pending approvals</small>
            </div>

        </a>


        <a href="settings.php" class="quick-link">

            <i class="bi bi-sliders"></i>

            <div>
                <strong>Slideshow Settings</strong>
                <small>Control live display</small>
            </div>

        </a>

    </section>


    <div class="footer">
        © 2026 Visitor Moments. All Rights Reserved.
    </div>

</main>


<!-- =====================================
     CHART.JS
===================================== -->

<script>

    const chartLabels = <?= json_encode($chartLabels) ?>;

    const visitorData = <?= json_encode($visitorChartData) ?>;

    const photoData = <?= json_encode($photoChartData) ?>;


    // VISITOR CHART

    new Chart(
        document.getElementById("visitorChart"),
        {
            type: "line",

            data: {
                labels: chartLabels,

                datasets: [{
                    label: "Visitors",

                    data: visitorData,

                    borderColor: "#7054d7",

                    backgroundColor: "rgba(112,84,215,0.12)",

                    borderWidth: 3,

                    fill: true,

                    tension: 0.4,

                    pointRadius: 4,

                    pointHoverRadius: 7
                }]
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );


    // PHOTO CHART

    new Chart(
        document.getElementById("photoChart"),
        {
            type: "bar",

            data: {
                labels: chartLabels,

                datasets: [{
                    label: "Photos",

                    data: photoData,

                    backgroundColor: "#42a5df",

                    borderRadius: 7,

                    maxBarThickness: 35
                }]
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );

</script>

</body>
</html>
