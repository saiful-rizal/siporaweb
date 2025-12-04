<?php
/**
 * Setup untuk Fitur Profile Photo
 * File ini hanya untuk membuat direktori uploads/profile jika belum ada
 */

session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Setup Profile Camera Feature</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Setup Fitur Profile Camera</h2>";

try {
    // Create directory if it doesn't exist
    $upload_dir = __DIR__ . '/frontend/uploads/profile/';
    
    if (!is_dir($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            echo "<div class='success'>✓ Direktori 'frontend/uploads/profile/' berhasil dibuat.</div>";
        } else {
            echo "<div class='error'>✗ Gagal membuat direktori 'frontend/uploads/profile/'.</div>";
        }
    } else {
        echo "<div class='success'>✓ Direktori 'frontend/uploads/profile/' sudah ada.</div>";
    }
    
    // Verify directory is writable
    if (is_writable($upload_dir)) {
        echo "<div class='success'>✓ Direktori dapat ditulis (writable).</div>";
    } else {
        echo "<div class='error'>✗ Direktori tidak dapat ditulis. Jalankan: chmod 755 frontend/uploads/profile/</div>";
    }
    
    // Check if frontend/uploads directory exists
    $uploads_dir = __DIR__ . '/frontend/uploads/';
    if (is_dir($uploads_dir)) {
        echo "<div class='info'>ℹ Direktori 'frontend/uploads/' ada.</div>";
    }
    
    echo "<div class='success'><strong>✓ Setup Selesai!</strong><br>
    Fitur Profile Camera siap digunakan.<br>
    Foto profil akan disimpan di: <code>frontend/uploads/profile/profile_USER_ID.jpg</code>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
