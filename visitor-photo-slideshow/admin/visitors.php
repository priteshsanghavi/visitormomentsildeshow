
<?php
session_start();

require_once "../config/database.php";
require_once "../config/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// ========================================
// SEARCH VISITORS
// ========================================

$search = trim($_GET["search"] ?? "");

$selectedVisitor = null;
$visitorPhotos = [];

if (isset($_GET["view"])) {

    $visitorId = (int)$_GET["view"];

    $stmt = $conn->prepare(
        "SELECT * FROM visitors WHERE id = ? LIMIT 1"
    );

    $stmt->bind_param("i", $visitorId);
    $stmt->execute();

    $selectedVisitor = $stmt->get_result()->fetch_assoc();

    if ($selectedVisitor) {

        $stmt = $conn->prepare(
            "SELECT * FROM photos
             WHERE visitor_id = ?
             ORDER BY uploaded_at DESC"
        );

        $stmt->bind_param("i", $visitorId);
        $stmt->execute();

        $visitorPhotos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ========================================
// VISITOR LIST
// ========================================

$sql = "
    SELECT
        v.*,
        COUNT(p.id) AS total_photos,
        SUM(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) AS approved_photos,
        SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) AS pending_photos,
        SUM(CASE WHEN p.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_photos
    FROM visitors v
    LEFT JOIN photos p ON v.id = p.visitor_id
";

if ($search !== "") {

    $sql .= "
        WHERE v.name LIKE ?
        OR v.email LIKE ?
        OR v.phone LIKE ?
    ";
}

$sql .= "
    GROUP BY v.id
    ORDER BY v.created_at DESC
";

$stmt = $conn->prepare($sql);

if ($search !== "") {

    $searchValue = "%" . $search . "%";

    $stmt->bind_param(
        "sss",
        $searchValue,
        $searchValue,
        $searchValue
    );
}

$stmt->execute();

$visitors = $stmt->get_result();

// ========================================
// DASHBOARD COUNTS
// ========================================

$totalVisitors = $conn->query(
    "SELECT COUNT(*) AS total FROM visitors"
)->fetch_assoc()["total"];

$totalPhotos = $conn->query(
    "SELECT COUNT(*) AS total FROM photos"
)->fetch_assoc()["total"];

$todayVisitors = $conn->query(
    "SELECT COUNT(*) AS total FROM visitors
     WHERE DATE(created_at) = CURDATE()"
)->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visitors Management | Visitor Moments</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

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
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #11152b;
            padding: 28px 18px;
            color: white;
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
            text-decoration: none;
            color: #c6c9dc;
            font-size: 14px;
            transition: .3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #6d55d9;
            color: white;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top-header h2 {
            font-weight: 800;
            font-size: 27px;
        }

        .top-header p {
            color: #85899e;
            margin-top: 6px;
        }

        .admin-badge {
            background: white;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.04);
            font-size: 14px;
            font-weight: 600;
        }

        /* STAT CARDS */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .stat-icon {
            width: 53px;
            height: 53px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 23px;
        }

        .purple {
            background: #eee9ff;
            color: #7054d7;
        }

        .blue {
            background: #e6f2ff;
            color: #2785df;
        }

        .green {
            background: #e3f9ed;
            color: #1baf69;
        }

        .stat-card h3 {
            font-size: 25px;
            font-weight: 800;
            margin: 0;
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #9295a8;
            font-size: 13px;
        }

        /* CONTENT CARD */

        .content-card {
            background: white;
            padding: 25px;
            border-radius: 17px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
        }

        .content-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .content-heading h4 {
            font-weight: 800;
            margin: 0;
        }

        .search-form {
            display: flex;
            gap: 8px;
        }

        .search-form input {
            width: 260px;
            border: 1px solid #e5e6ef;
            border-radius: 9px;
            padding: 11px 13px;
            outline: none;
        }

        .search-btn {
            background: #6d55d9;
            color: white;
            border: none;
            padding: 10px 17px;
            border-radius: 9px;
        }

        .search-btn:hover {
            background: #5740bf;
        }

        /* TABLE */

        .table {
            vertical-align: middle;
            margin-bottom: 0;
        }

        .table thead th {
            background: #f7f7fc;
            color: #777b91;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 15px;
            border: none;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 17px 15px;
            border-bottom: 1px solid #f0f0f5;
            font-size: 13px;
        }

        .visitor-name {
            font-weight: 700;
            color: #252943;
        }

        .visitor-id {
            color: #9295a8;
            font-size: 11px;
            margin-top: 4px;
        }

        .count-badge {
            background: #eee9ff;
            color: #7054d7;
            padding: 6px 10px;
            border-radius: 7px;
            font-weight: 700;
        }

        .approved {
            color: #15945a;
            font-weight: 700;
        }

        .pending {
            color: #d18b16;
            font-weight: 700;
        }

        .rejected {
            color: #df4b5c;
            font-weight: 700;
        }

        .view-btn {
            display: inline-block;
            background: #eee9ff;
            color: #7054d7;
            padding: 9px 13px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
        }

        .view-btn:hover {
            background: #6d55d9;
            color: white;
        }

        /* DETAILS */

        .details-card {
            background: white;
            padding: 28px;
            border-radius: 17px;
            margin-bottom: 25px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
        }

        .detail-item {
            padding: 13px;
            background: #f7f7fc;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .detail-item small {
            display: block;
            color: #9295a8;
            margin-bottom: 5px;
        }

        .detail-item strong {
            overflow-wrap: anywhere;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
        }

        .photo-card {
            border: 1px solid #eeeef4;
            border-radius: 13px;
            overflow: hidden;
            background: white;
        }

        .photo-card img {
            width: 100%;
            height: 155px;
            object-fit: cover;
            display: block;
        }

        .photo-info {
            padding: 12px;
            font-size: 13px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-approved {
            background: #e3f9ed;
            color: #15945a;
        }

        .status-pending {
            background: #fff4d9;
            color: #b47a10;
        }

        .status-rejected {
            background: #ffe8eb;
            color: #df4b5c;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #6d55d9;
            text-decoration: none;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 45px 15px;
            color: #9295a8;
        }

        /* RESPONSIVE */

        @media(max-width: 1000px) {

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .main {
                padding: 25px;
            }
        }

        @media(max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .sidebar h3 {
                margin-bottom: 20px;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-header {
                align-items: flex-start;
                gap: 12px;
                flex-direction: column;
            }

            .search-form {
                width: 100%;
            }

            .search-form input {
                width: 100%;
                min-width: 0;
            }
        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <h3>✦ Visitor Moments</h3>

    <a href="dashboard.php">
        <i class="bi bi-grid"></i>
        Dashboard
    </a>

    <a href="visitors.php" class="active">
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

</aside>


<!-- MAIN CONTENT -->

<main class="main">

    <div class="top-header">

        <div>
            <h2>Visitors Management</h2>
            <p>Manage and monitor all registered visitors.</p>
        </div>

        <div class="admin-badge">
            <i class="bi bi-person-circle"></i>
            Admin Panel
        </div>

    </div>


    <!-- STATISTICS -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon purple">
                <i class="bi bi-people-fill"></i>
            </div>

            <div>
                <h3><?= $totalVisitors ?></h3>
                <p>Total Visitors</p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon blue">
                <i class="bi bi-images"></i>
            </div>

            <div>
                <h3><?= $totalPhotos ?></h3>
                <p>Total Uploaded Photos</p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon green">
                <i class="bi bi-person-plus-fill"></i>
            </div>

            <div>
                <h3><?= $todayVisitors ?></h3>
                <p>Today's Visitors</p>
            </div>

        </div>

    </div>


    <?php if ($selectedVisitor): ?>

        <!-- VISITOR DETAILS -->

        <a href="visitors.php" class="back-btn">
            <i class="bi bi-arrow-left"></i>
            Back to Visitors
        </a>

        <div class="details-card">

            <h4 class="fw-bold mb-4">
                <i class="bi bi-person-circle"></i>
                Visitor Details
            </h4>

            <div class="row">

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Visitor Name</small>
                        <strong>
                            <?= htmlspecialchars($selectedVisitor["name"]) ?>
                        </strong>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Email Address</small>
                        <strong>
                            <?= htmlspecialchars($selectedVisitor["email"] ?: "Not provided") ?>
                        </strong>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Mobile Number</small>
                        <strong>
                            <?= htmlspecialchars($selectedVisitor["phone"] ?: "Not provided") ?>
                        </strong>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Registration Date</small>
                        <strong>
                            <?= htmlspecialchars($selectedVisitor["created_at"]) ?>
                        </strong>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Photo Display Consent</small>
                        <strong>
                            <?= $selectedVisitor["consent"] ? "Given" : "Not Given" ?>
                        </strong>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="detail-item">
                        <small>Total Uploaded Photos</small>
                        <strong>
                            <?= count($visitorPhotos) ?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <!-- VISITOR PHOTOS -->

        <div class="content-card">

            <h4 class="fw-bold mb-4">
                <i class="bi bi-images"></i>
                Uploaded Photos
            </h4>

            <?php if (count($visitorPhotos) > 0): ?>

                <div class="photo-grid">

                    <?php foreach ($visitorPhotos as $photo): ?>

                        <div class="photo-card">

                            <a
                                href="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                                target="_blank"
                            >

                                <img
                                    src="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                                    alt="Visitor Photo"
                                >

                            </a>

                            <div class="photo-info">

                                <span class="status status-<?= htmlspecialchars($photo["status"]) ?>">
                                    <?= htmlspecialchars($photo["status"]) ?>
                                </span>

                                <div class="text-secondary mt-2">
                                    <?= htmlspecialchars($photo["uploaded_at"]) ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty">
                    <i class="bi bi-image fs-1"></i>
                    <p class="mt-3">No photos uploaded by this visitor.</p>
                </div>

            <?php endif; ?>

        </div>


    <?php else: ?>


        <!-- VISITORS TABLE -->

        <div class="content-card">

            <div class="content-heading">

                <div>
                    <h4>All Visitors</h4>
                    <small class="text-secondary">
                        Registered visitor information
                    </small>
                </div>

                <form method="GET" class="search-form">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search name, email or mobile..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i>
                        Search
                    </button>

                </form>

            </div>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Visitor</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Total Photos</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($visitors->num_rows > 0): ?>

                            <?php while ($visitor = $visitors->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <div class="visitor-name">
                                            <?= htmlspecialchars($visitor["name"]) ?>
                                        </div>

                                        <div class="visitor-id">
                                            ID: #<?= (int)$visitor["id"] ?>
                                        </div>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($visitor["email"] ?: "-") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($visitor["phone"] ?: "-") ?>
                                    </td>

                                    <td>
                                        <span class="count-badge">
                                            <?= (int)$visitor["total_photos"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="approved">
                                            <?= (int)$visitor["approved_photos"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="pending">
                                            <?= (int)$visitor["pending_photos"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="rejected">
                                            <?= (int)$visitor["rejected_photos"] ?>
                                        </span>
                                    </td>

                                    <td>

                                        <a
                                            href="visitors.php?view=<?= (int)$visitor["id"] ?>"
                                            class="view-btn"
                                        >
                                            <i class="bi bi-eye"></i>
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8">

                                    <div class="empty">

                                        <i class="bi bi-people fs-1"></i>

                                        <p class="mt-3">
                                            No visitors found.
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
