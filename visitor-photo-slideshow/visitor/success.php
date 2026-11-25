
<?php
require_once "../config/config.php";
require_once "../config/database.php";

$visitor_id = filter_input(
    INPUT_GET,
    "visitor_id",
    FILTER_VALIDATE_INT
);

if (!$visitor_id) {
    die("Invalid visitor.");
}

$stmt = $conn->prepare(
    "SELECT name FROM visitors WHERE id = ? AND consent = 1"
);

$stmt->bind_param("i", $visitor_id);
$stmt->execute();

$visitor = $stmt->get_result()->fetch_assoc();

if (!$visitor) {
    die("Visitor not found.");
}

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM photos WHERE visitor_id = ?"
);

$countStmt->bind_param("i", $visitor_id);
$countStmt->execute();

$total = $countStmt->get_result()->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Successful</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css?v=4">

    <style>
        body {
            background: #f4f1fc;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .success-card {
            background: white;
            padding: 50px 35px;
            max-width: 500px;
            width: 100%;
            border-radius: 22px;
            text-align: center;
            box-shadow: 0 20px 60px #30205d15;
        }

        .success-icon {
            font-size: 65px;
            margin-bottom: 20px;
        }

        .success-card h1 {
            font-weight: 800;
            margin-bottom: 15px;
        }

        .success-card p {
            color: #77758b;
            line-height: 1.8;
        }
    </style>
</head>

<body>

<div class="success-card">

    <div class="success-icon">🎉</div>

    <h1>Thank You!</h1>

    <h4>Hello, <?= htmlspecialchars($visitor["name"]) ?>!</h4>

    <p class="mt-3">
        Your <?= (int)$total ?> photo(s) have been uploaded successfully.
        They will appear on the public slideshow after admin approval.
    </p>

    <a href="../index.php" class="primary-btn">
        Back to Home →
    </a>

</div>

</body>
</html>
