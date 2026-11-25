
<?php
session_start();

require_once "../config/database.php";
require_once "../config/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// ========================================
// DELETE PHOTO
// ========================================

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $photoId = (int)($_POST["photo_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($photoId > 0 && $action === "delete") {

        $stmt = $conn->prepare(
            "SELECT image_path FROM photos WHERE id = ? LIMIT 1"
        );

        $stmt->bind_param("i", $photoId);
        $stmt->execute();

        $photo = $stmt->get_result()->fetch_assoc();

        if ($photo) {

            $delete = $conn->prepare(
                "DELETE FROM photos WHERE id = ?"
            );

            $delete->bind_param("i", $photoId);

            if ($delete->execute()) {

                $imagePath = "../" . $photo["image_path"];

                if (is_file($imagePath)) {
                    unlink($imagePath);
                }

                $message = "Photo deleted successfully!";
            }
        }
    }

    // ========================================
    // APPROVE / REJECT PHOTO
    // ========================================

    elseif (
        $photoId > 0 &&
        in_array($action, ["approved", "rejected"], true)
    ) {

        $stmt = $conn->prepare(
            "UPDATE photos SET status = ? WHERE id = ?"
        );

        $stmt->bind_param("si", $action, $photoId);

        if ($stmt->execute()) {
            $message = "Photo status updated successfully!";
        }
    }
}

// ========================================
// FILTER PHOTOS
// ========================================

$filter = $_GET["status"] ?? "all";

$allowedFilters = ["all", "pending", "approved", "rejected"];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = "all";
}

// ========================================
// GET COUNTS
// ========================================

$totalPhotos = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM photos"
)->fetch_assoc()["total"];

$pendingPhotos = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM photos WHERE status='pending'"
)->fetch_assoc()["total"];

$approvedPhotos = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM photos WHERE status='approved'"
)->fetch_assoc()["total"];

$rejectedPhotos = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM photos WHERE status='rejected'"
)->fetch_assoc()["total"];

// ========================================
// PHOTO QUERY
// ========================================

$sql = "
    SELECT
        p.id,
        p.image_path,
        p.status,
        p.uploaded_at,
        v.name,
        v.email,
        v.phone
    FROM photos p
    INNER JOIN visitors v ON p.visitor_id = v.id
";

if ($filter !== "all") {
    $sql .= " WHERE p.status = ?";
}

$sql .= " ORDER BY p.uploaded_at DESC";

$stmt = $conn->prepare($sql);

if ($filter !== "all") {
    $stmt->bind_param("s", $filter);
}

$stmt->execute();

$photos = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Photo Management | Visitor Moments</title>

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
            color: #20233a;
            font-family: Arial, sans-serif;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 28px 18px;
            background: #11152b;
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

        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .top-header h2 {
            font-size: 27px;
            font-weight: 800;
        }

        .top-header p {
            color: #85899e;
            margin: 6px 0 0;
        }

        .admin-badge {
            background: white;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,.04);
            font-size: 14px;
            font-weight: 600;
        }

        /* STATISTICS */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            padding: 23px;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(20,25,60,.04);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .purple {
            background: #eee9ff;
            color: #7054d7;
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
            font-size: 24px;
            font-weight: 800;
            margin: 0;
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #9295a8;
            font-size: 12px;
        }

        /* CONTENT */

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
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .content-heading h4 {
            font-weight: 800;
            margin: 0;
        }

        /* FILTERS */

        .filter-tabs {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filter-tabs a {
            padding: 10px 17px;
            border-radius: 9px;
            background: #f4f4fa;
            color: #74788f;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .filter-tabs a.active {
            background: #6d55d9;
            color: white;
        }

        /* PHOTO GRID */

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .photo-card {
            background: white;
            border: 1px solid #eeeef4;
            border-radius: 15px;
            overflow: hidden;
            transition: .3s;
        }

        .photo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(30,30,70,.09);
        }

        .photo-image {
            height: 220px;
            background: #f1f1f6;
            overflow: hidden;
        }

        .photo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: .4s;
        }

        .photo-card:hover .photo-image img {
            transform: scale(1.05);
        }

        .photo-details {
            padding: 17px;
        }

        .visitor-name {
            font-size: 15px;
            font-weight: 800;
            color: #252943;
        }

        .visitor-email {
            font-size: 12px;
            color: #9295a8;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .photo-date {
            font-size: 11px;
            color: #9295a8;
            margin-top: 12px;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
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

        /* ACTIONS */

        .photo-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .action-btn {
            flex: 1;
            min-width: 75px;
            border: none;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
        }

        .approve-btn {
            background: #e3f9ed;
            color: #15945a;
        }

        .reject-btn {
            background: #fff4d9;
            color: #b47a10;
        }

        .delete-btn {
            background: #ffe8eb;
            color: #df4b5c;
        }

        .preview-btn {
            display: block;
            margin-top: 10px;
            text-align: center;
            padding: 9px;
            border-radius: 8px;
            background: #eee9ff;
            color: #7054d7;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .action-btn:hover,
        .preview-btn:hover {
            opacity: .8;
        }

        .empty {
            text-align: center;
            padding: 55px 15px;
            color: #9295a8;
        }

        .empty i {
            font-size: 45px;
        }

        /* RESPONSIVE */

        @media(max-width: 1200px) {
            .photo-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .photo-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
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

    <a href="visitors.php">
        <i class="bi bi-people"></i>
        Visitors
    </a>

    <a href="photos.php" class="active">
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
            <h2>Photo Management</h2>
            <p>Review, approve, reject and manage visitor photos.</p>
        </div>

        <div class="admin-badge">
            <i class="bi bi-person-circle"></i>
            Admin Panel
        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle-fill"></i>

            <?= htmlspecialchars($message) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- STATISTICS -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon purple">
                <i class="bi bi-images"></i>
            </div>

            <div>
                <h3><?= $totalPhotos ?></h3>
                <p>Total Photos</p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>

            <div>
                <h3><?= $pendingPhotos ?></h3>
                <p>Pending Photos</p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>

            <div>
                <h3><?= $approvedPhotos ?></h3>
                <p>Approved Photos</p>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon red">
                <i class="bi bi-x-circle"></i>
            </div>

            <div>
                <h3><?= $rejectedPhotos ?></h3>
                <p>Rejected Photos</p>
            </div>

        </div>

    </div>


    <!-- PHOTO LIST -->

    <div class="content-card">

        <div class="content-heading">

            <div>
                <h4>Visitor Photos</h4>
                <small class="text-secondary">
                    Manage uploaded images
                </small>
            </div>

        </div>


        <!-- FILTER TABS -->

        <div class="filter-tabs">

            <a
                href="photos.php?status=all"
                class="<?= $filter === 'all' ? 'active' : '' ?>"
            >
                All Photos
            </a>

            <a
                href="photos.php?status=pending"
                class="<?= $filter === 'pending' ? 'active' : '' ?>"
            >
                Pending
            </a>

            <a
                href="photos.php?status=approved"
                class="<?= $filter === 'approved' ? 'active' : '' ?>"
            >
                Approved
            </a>

            <a
                href="photos.php?status=rejected"
                class="<?= $filter === 'rejected' ? 'active' : '' ?>"
            >
                Rejected
            </a>

        </div>


        <!-- PHOTO CARDS -->

        <?php if ($photos->num_rows > 0): ?>

            <div class="photo-grid">

                <?php while ($photo = $photos->fetch_assoc()): ?>

                    <div class="photo-card">

                        <div class="photo-image">

                            <img
                                src="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                                alt="Visitor Photo"
                                loading="lazy"
                            >

                        </div>


                        <div class="photo-details">

                            <div class="d-flex justify-content-between align-items-center gap-2">

                                <div class="visitor-name">
                                    <?= htmlspecialchars($photo["name"]) ?>
                                </div>

                                <span class="status status-<?= htmlspecialchars($photo["status"]) ?>">
                                    <?= htmlspecialchars($photo["status"]) ?>
                                </span>

                            </div>


                            <div class="visitor-email">
                                <?= htmlspecialchars($photo["email"] ?: "No email provided") ?>
                            </div>

                            <div class="visitor-email">
                                <i class="bi bi-telephone"></i>
                                <?= htmlspecialchars($photo["phone"] ?: "No phone provided") ?>
                            </div>

                            <div class="photo-date">
                                <i class="bi bi-calendar3"></i>
                                <?= htmlspecialchars($photo["uploaded_at"]) ?>
                            </div>


                            <!-- PREVIEW -->

                            <a
                                href="../<?= htmlspecialchars($photo["image_path"], ENT_QUOTES, "UTF-8") ?>"
                                target="_blank"
                                class="preview-btn"
                            >
                                <i class="bi bi-arrows-fullscreen"></i>
                                View Full Size
                            </a>


                            <!-- ACTIONS -->

                            <div class="photo-actions">

                                <?php if ($photo["status"] !== "approved"): ?>

                                    <form method="POST" class="flex-fill">

                                        <input
                                            type="hidden"
                                            name="photo_id"
                                            value="<?= (int)$photo["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approved"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn approve-btn w-100"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                            Approve
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <?php if ($photo["status"] !== "rejected"): ?>

                                    <form method="POST" class="flex-fill">

                                        <input
                                            type="hidden"
                                            name="photo_id"
                                            value="<?= (int)$photo["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="rejected"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn reject-btn w-100"
                                        >
                                            <i class="bi bi-x-lg"></i>
                                            Reject
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <form
                                    method="POST"
                                    class="flex-fill"
                                    onsubmit="return confirm('Are you sure you want to permanently delete this photo?');"
                                >

                                    <input
                                        type="hidden"
                                        name="photo_id"
                                        value="<?= (int)$photo["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <button
                                        type="submit"
                                        class="action-btn delete-btn w-100"
                                    >
                                        <i class="bi bi-trash"></i>
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                <i class="bi bi-images"></i>

                <h5 class="mt-3 fw-bold">
                    No Photos Found
                </h5>

                <p>
                    There are no photos available in this category.
                </p>

            </div>

        <?php endif; ?>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
