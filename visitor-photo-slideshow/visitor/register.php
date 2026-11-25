
<?php
require_once "../config/config.php";
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $phone = trim($_POST["phone"] ?? "");
    $consent = isset($_POST["consent"]) ? 1 : 0;

    if ($name === "" || ($email === "" && $phone === "")) {
        $error = "Name and email or mobile are required.";
    } elseif (!preg_match("/^[a-zA-Z ]{2,150}$/", $name)) {
        $error = "Enter a valid name.";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email.";
    } elseif ($phone !== "" && !preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Enter a valid mobile number.";
    } elseif (!$consent) {
        $error = "Please accept photo display consent.";
    } else {

        $sql = "SELECT id FROM visitors
                WHERE (email = ? AND ? <> '')
                OR (phone = ? AND ? <> '')
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $email, $email, $phone, $phone);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $visitor = $result->fetch_assoc();
            $visitor_id = $visitor["id"];

            $update = $conn->prepare(
                "UPDATE visitors
                 SET name=?, email=?, phone=?,
                 consent=1, consent_at=NOW()
                 WHERE id=?"
            );

            $update->bind_param("sssi", $name, $email, $phone, $visitor_id);

            if (!$update->execute()) {
                $error = "Unable to update visitor details.";
            }

        } else {

            $insert = $conn->prepare(
                "INSERT INTO visitors
                (name,email,phone,consent,consent_at)
                VALUES (?,?,?,1,NOW())"
            );

            $insert->bind_param("sss", $name, $email, $phone);

            if ($insert->execute()) {
                $visitor_id = $conn->insert_id;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }

        if ($error === "") {
            header("Location: upload.php?visitor_id=" . $visitor_id);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visitor Registration</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css?v=3">

</head>

<body class="register-body">

<div class="register-wrapper">

    <div class="register-left">

        <div class="register-brand">
            <span class="brand-icon">V</span>
            Visitor Moments
        </div>

        <div class="register-left-content">

            <span class="section-label">CREATE YOUR MEMORIES</span>

            <h1>Every picture tells a story.</h1>

            <p>
                Join our visitor gallery and let your
                beautiful moments become part of our story.
            </p>

            <div class="register-highlight">
                ✦ Your memories deserve to be celebrated.
            </div>

        </div>

        <div class="register-left-footer">
            © <?= date("Y") ?> Visitor Moments
        </div>

    </div>

    <div class="register-right">

        <div class="form-card">

            <a href="../index.php" class="back-link">
                ← Back to Home
            </a>

            <div class="form-heading">

                <span class="section-label">WELCOME VISITOR</span>

                <h2>Let's get started!</h2>

                <p>Enter your details to share your photos.</p>

            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group-custom">
                    <label>Full Name *</label>
                    <input type="text"
                           name="name"
                           placeholder="Enter your full name"
                           maxlength="150"
                           value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                           required>
                </div>

                <div class="form-group-custom">
                    <label>Email Address</label>
                    <input type="email"
                           name="email"
                           placeholder="example@email.com"
                           value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
                </div>

                <div class="form-group-custom">
                    <label>Mobile Number</label>
                    <input type="tel"
                           name="phone"
                           placeholder="Enter mobile number"
                           maxlength="15"
                           value="<?= htmlspecialchars($_POST["phone"] ?? "") ?>">
                </div>

                <div class="consent-box">

                    <input type="checkbox"
                           name="consent"
                           id="consent"
                           required
                           <?= isset($_POST["consent"]) ? "checked" : "" ?>>

                    <label for="consent">
                        I agree that my uploaded photos can be
                        displayed publicly on the visitor slideshow.
                        I understand that I can request their removal.
                    </label>

                </div>

                <button type="submit" class="submit-btn">
                    Continue to Photo Upload →
                </button>

            </form>

            <p class="privacy-note">
                🔒 Your information is handled securely.
            </p>

        </div>

    </div>

</div>

</body>
</html>
