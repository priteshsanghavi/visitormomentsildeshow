
<?php
session_start();

require_once "../config/database.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, name, email, password
         FROM admins WHERE email = ? LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin["password"])) {

        session_regenerate_id(true);

        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_name"] = $admin["name"];
        $_SESSION["admin_email"] = $admin["email"];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Admin Login | Visitor Moments</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=5">

<style>
body {
    min-height:100vh;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#151426,#30205d);
    padding:20px;
}
.login-card {
    width:100%;
    max-width:430px;
    background:white;
    padding:40px;
    border-radius:22px;
    box-shadow:0 20px 70px #0003;
}
.login-card h2 {
    font-weight:800;
    margin:20px 0 8px;
}
.login-card input {
    padding:14px;
    border-radius:10px;
}
.login-btn {
    width:100%;
    padding:14px;
    background:linear-gradient(120deg,#9275ff,#6544e5);
    color:white;
    border:0;
    border-radius:10px;
    font-weight:700;
}
</style>
</head>

<body>

<div class="login-card">

    <div class="brand-icon">V</div>

    <h2>Admin Login</h2>

    <p class="text-secondary">Welcome back! Sign in to manage your gallery.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email"
                   class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password"
                   class="form-control" required>
        </div>

        <button class="login-btn">Sign In →</button>

    </form>

</div>

</body>
</html>
