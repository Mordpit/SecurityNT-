<?php
// Test file upload functionality
echo "<h2>Debug Information</h2>";

// Check uploads directory
echo "<h3>1. Uploads Directory Check</h3>";
if (file_exists('uploads')) {
    echo "✓ uploads/ folder exists<br>";
    if (is_writable('uploads')) {
        echo "✓ uploads/ is writable<br>";
    } else {
        echo "❌ uploads/ is NOT writable<br>";
    }
} else {
    echo "❌ uploads/ folder does not exist<br>";
}

// Check file upload settings
echo "<h3>2. PHP Upload Settings</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "<br>";

// Test upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>3. Upload Test Result</h3>";
    
    if (isset($_FILES['test_image'])) {
        echo "File info:<br>";
        echo "Name: " . $_FILES['test_image']['name'] . "<br>";
        echo "Type: " . $_FILES['test_image']['type'] . "<br>";
        echo "Size: " . $_FILES['test_image']['size'] . " bytes<br>";
        echo "Error: " . $_FILES['test_image']['error'] . "<br>";
        
        if ($_FILES['test_image']['error'] === 0) {
            $filename = uniqid() . '_' . $_FILES['test_image']['name'];
            $upload_path = 'uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['test_image']['tmp_name'], $upload_path)) {
                echo "<br>✓ <strong>SUCCESS!</strong> File uploaded to: " . $upload_path . "<br>";
            } else {
                echo "<br>❌ <strong>FAILED</strong> to move uploaded file<br>";
            }
        } else {
            echo "<br>❌ Upload error code: " . $_FILES['test_image']['error'];
        }
    } else {
        echo "❌ No file uploaded<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Test Upload</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #333; }
        h3 { color: #666; margin-top: 20px; }
        form { margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 8px; }
    </style>
</head>
<body>
    <h2>🔬 Upload Test Page</h2>
    
    <form method="POST" enctype="multipart/form-data">
        <label>เลือกรูปภาพเพื่อทดสอบ:</label><br>
        <input type="file" name="test_image" accept="image/*" required><br><br>
        <button type="submit">ทดสอบอัพโหลด</button>
    </form>
</body>
</html>
