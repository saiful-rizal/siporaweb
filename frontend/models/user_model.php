<?php
// models/user_model.php
require_once __DIR__ . '/../includes/config.php';

function getUserData($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

function getUserProfileData($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND status_id = 5) AS uploaded_docs,
                (SELECT COUNT(*) FROM download_history WHERE user_id = u.id_user) AS downloaded_docs,
                (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND status_id = 5 AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE)) AS monthly_uploads
            FROM users u
            WHERE u.id_user = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getInitialsBackgroundColor($username) {
    $colors = [
        '#4285F4', '#1E88E5', '#039BE5', '#00ACC1', '#00BCD4', '#26C6DA', 
        '#26A69A', '#42A5F5', '#5C6BC0', '#7E57C2', '#9575CD', '#64B5F6'
    ];
    
    $index = 0;
    for ($i = 0; $i < strlen($username); $i++) {
        $index += ord($username[$i]);
    }
    
    return $colors[$index % count($colors)];
}

function getContrastColor($hexColor) {
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/../uploads/profile_photos/' . $user_id . '.jpg';
    return file_exists($photo_path);
}

function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/../uploads/profile_photos/' . $user_id . '.jpg';
    if (file_exists($photo_path)) {
        return 'uploads/profile_photos/' . $user_id . '.jpg?t=' . time();
    } else {
        return 'profile_image.php?id=' . $user_id . '&email=' . urlencode($email) . '&name=' . urlencode($username) . '&t=' . time();
    }
}

function getInitialsHtml($username, $size = 'normal') {
    $username_parts = explode('_', $username);
    if (count($username_parts) > 1) {
        $initials = strtoupper(substr($username_parts[0], 0, 1) . substr(end($username_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($username, 0, 2));
    }
    
    $bgColor = getInitialsBackgroundColor($username);
    $textColor = getContrastColor($bgColor);
    
    $sizeClass = '';
    $style = '';
    
    switch($size) {
        case 'small':
            $sizeClass = 'initials-small';
            $style = "width: 40px; height: 40px; font-size: 16px;";
            break;
        case 'large':
            $sizeClass = 'initials-large';
            $style = "width: 100px; height: 100px; font-size: 36px;";
            break;
        case 'normal':
        default:
            $sizeClass = 'initials-normal';
            $style = "width: 68px; height: 68px; font-size: 24px;";
            break;
    }
    
    return "<div class='user-initials {$sizeClass}' style='background-color: {$bgColor}; color: {$textColor}; {$style}'>{$initials}</div>";
}

function getRoleName($role) {
    switch($role) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
}

function getStatusName($status_id) {
    switch($status_id) {
        case 1: return 'Diterbitkan';
        case 2: return 'Review';
        case 3: return 'Menunggu Publikasi';
        default: return 'Unknown';
    }
}

function getStatusBadge($status_id) {
    switch($status_id) {
        case 1: return 'badge-success';
        case 2: return 'badge-warning';
        case 3: return 'badge-info';
        default: return 'badge-secondary';
    }
}