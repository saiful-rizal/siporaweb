<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Anda belum login.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['photo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data foto tidak ditemukan.']);
    exit;
}

$photo_data = $input['photo'];

// Validate base64 data
if (strpos($photo_data, 'data:image/') !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format foto tidak valid.']);
    exit;
}

try {
    // Extract base64 string
    $photo_base64 = explode(',', $photo_data)[1];
    $photo_binary = base64_decode($photo_base64);
    
    // Create directory if it doesn't exist
    $upload_dir = __DIR__ . '/uploads/profile/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate filename with user_id
    $filename = 'profile_' . $user_id . '.jpg';
    $filepath = $upload_dir . $filename;
    
    // Delete old profile photo for this user if exists
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Save the file
    if (file_put_contents($filepath, $photo_binary) === false) {
        throw new Exception('Gagal menyimpan file foto.');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Foto profil berhasil diubah.',
        'filename' => $filename
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
