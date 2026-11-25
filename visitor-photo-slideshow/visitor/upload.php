
<?php
require_once "../config/config.php";
require_once "../config/database.php";

$error = "";

$visitor_id = filter_input(
    INPUT_GET,
    "visitor_id",
    FILTER_VALIDATE_INT
);

if (!$visitor_id || $visitor_id <= 0) {
    die("Invalid visitor ID.");
}

// Verify visitor and consent
$stmt = $conn->prepare(
    "SELECT id, name FROM visitors
     WHERE id = ? AND consent = 1"
);

$stmt->bind_param("i", $visitor_id);
$stmt->execute();

$visitor = $stmt->get_result()->fetch_assoc();

if (!$visitor) {
    die("Visitor not found or consent not available.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_FILES["photos"])) {
        $error = "Please select photos.";
    } else {

        $files = $_FILES["photos"];
        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $maxSize = 5 * 1024 * 1024;
        $total = count($files["name"]);

        if ($total > 10) {
            $error = "Maximum 10 photos allowed at once.";
        } elseif ($total < 1) {
            $error = "Please select at least one photo.";
        } else {

            $uploadDir = "../uploads/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $saved = 0;
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $insert = $conn->prepare(
                "INSERT INTO photos
                (visitor_id, image_path, status)
                VALUES (?, ?, 'pending')"
            );

            for ($i = 0; $i < $total; $i++) {

                if ($files["error"][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $files["tmp_name"][$i];

                if (!is_uploaded_file($tmpName)) {
                    continue;
                }

                if ($files["size"][$i] > $maxSize) {
                    continue;
                }

                $mime = $finfo->file($tmpName);

                if (!in_array($mime, $allowedTypes, true)) {
                    continue;
                }

                $imageInfo = getimagesize($tmpName);

                if ($imageInfo === false) {
                    continue;
                }

                $extension = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ][$mime];

                $fileName = bin2hex(random_bytes(16))
                          . "." . $extension;

                $destination = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $destination)) {

                    $dbPath = "uploads/" . $fileName;

                    $insert->bind_param(
                        "is",
                        $visitor_id,
                        $dbPath
                    );

                    if ($insert->execute()) {
                        $saved++;
                    } else {
                        unlink($destination);
                    }
                }
            }

            if ($saved > 0) {

                header(
                    "Location: success.php?visitor_id=" . $visitor_id
                );
                exit;

            } else {
                $error = "No valid photos uploaded. Check image type and size.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Upload Photos | Visitor Moments</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/style.css?v=4">

<style>
.upload-container {
    min-height: 100vh;
    background: #f5f3fc;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 15px;
}

.upload-card {
    background: white;
    width: 100%;
    max-width: 650px;
    padding: 40px;
    border-radius: 22px;
    box-shadow: 0 15px 50px #25204715;
}

.upload-icon {
    width: 75px;
    height: 75px;
    background: #eee9ff;
    color: #7958ed;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    font-size: 35px;
    margin-bottom: 20px;
}

.drop-zone {
    border: 2px dashed #b7a7ef;
    padding: 45px 20px;
    border-radius: 16px;
    background: #faf9ff;
    text-align: center;
    cursor: pointer;
    transition: .3s;
}

.drop-zone:hover {
    background: #f0edff;
    border-color: #7958ed;
}

.drop-zone h4 {
    font-weight: 700;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 20px;
}

.preview-grid img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    border-radius: 12px;
}

.upload-submit {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(120deg, #9275ff, #6544e5);
    color: white;
    font-weight: 700;
    margin-top: 25px;
}

@media(max-width:500px) {
    .upload-card {
        padding: 25px 18px;
    }
    .preview-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

</head>

<body>

<div class="upload-container">

<div class="upload-card">

    <div class="upload-icon">📸</div>

    <span class="section-label">SHARE YOUR MEMORIES</span>

    <h2 class="mt-2">Hello, <?= htmlspecialchars($visitor["name"]) ?>!</h2>

    <p class="text-secondary">
        Upload your beautiful photos to our visitor gallery.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          enctype="multipart/form-data">

        <label class="drop-zone w-100">

            <div style="font-size:40px;">☁️</div>

            <h4>Choose Your Photos</h4>

            <p class="text-secondary mb-0">
                JPG, PNG, WEBP · Maximum 5MB each · Up to 10 photos
            </p>

            <input type="file"
                   name="photos[]"
                   id="photos"
                   accept=".jpg,.jpeg,.png,.webp"
                   multiple
                   hidden
                   required>

        </label>

        <div id="preview" class="preview-grid"></div>

        <button type="submit" class="upload-submit">
            Upload My Photos →
        </button>

    </form>

</div>
</div>

<script>
const input = document.getElementById("photos");
const preview = document.getElementById("preview");

input.addEventListener("change", function() {

    preview.innerHTML = "";

    const files = Array.from(this.files);

    if (files.length > 10) {
        alert("Maximum 10 photos allowed.");
        this.value = "";
        return;
    }

    files.forEach(file => {

        if (!file.type.startsWith("image/")) return;

        const reader = new FileReader();

        reader.onload = function(e) {

            const img = document.createElement("img");
            img.src = e.target.result;

            preview.appendChild(img);
        };

        reader.readAsDataURL(file);
    });
});
</script>

</body>
</html>
