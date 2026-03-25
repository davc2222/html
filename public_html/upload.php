<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {

        $uploadDir = "uploads/";
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            echo "File uploaded successfully!";
        } else {
            echo "Upload failed.";
        }

    } else {
        echo "No file selected or error occurred.";
    }
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>Upload File</h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>

</body>
</html>cd ..
