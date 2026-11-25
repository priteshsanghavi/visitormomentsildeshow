
<?php
require_once "../config/database.php";

$message = "";

$count = $conn->query("SELECT COUNT(*) AS total FROM admins");
$total = $count->fetch_assoc()["total"];

if ($total > 0) {
    die("Admin already exists. Please remove this setup file.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 8) {

        $message = "Enter valid details. Password must be at least 8 characters.";

    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO admins (name, email, password)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $name, $email, $hash);

        if ($stmt->execute()) {
            $message = "Admin account created successfully! Delete create-admin.php now.";
        } else {
            $message = "Unable to create admin account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
<div class="card shadow border-0 mx-auto p-4" style="max-width:450px">

<h3 class="mb-4">Create Admin Account</h3>

<?php if ($message): ?>
<div class="alert alert-info">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ((int)$total === 0): ?>
<form method="POST">

<div class="mb-3">
<label class="form-label">Admin Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" minlength="8" required>
</div>

<button class="btn btn-dark w-100">Create Admin</button>

</form>
<?php endif; ?>

</div>
</div>

</body>
</html>
