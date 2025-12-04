<?php
//
// =======================================
// USER AVATAR (Warna, Initials, Foto)
// =======================================
//

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

    $luminance = (0.299*$r + 0.587*$g + 0.114*$b) / 255;
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/../uploads/profile/profile_' . $user_id . '.jpg';
    return file_exists($photo_path);
}

function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/../uploads/profile/profile_' . $user_id . '.jpg';

    if (file_exists($photo_path)) {
        return 'uploads/profile/profile_' . $user_id . '.jpg?t=' . time();
    }

    return 'profile_image.php?id=' . $user_id 
        . '&email=' . urlencode($email) 
        . '&name=' . urlencode($username) 
        . '&t=' . time();
}

function getInitialsHtml($username, $size = 'normal') {
    $parts = explode('_', $username);
    if (count($parts) > 1) {
        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    } else {
        $initials = strtoupper(substr($username, 0, 2));
    }

    $bg = getInitialsBackgroundColor($username);
    $text = getContrastColor($bg);

    switch($size) {
        case 'small':
            $style = "width:40px;height:40px;font-size:16px;";
            break;
        case 'large':
            $style = "width:100px;height:100px;font-size:36px;";
            break;
        case 'normal':
        default:
            $style = "width:68px;height:68px;font-size:24px;";
            break;
    }

    return "<div class='user-initials' style='background:$bg;color:$text;$style'>$initials</div>";
}

//
// =======================================
// ROLE DAN STATUS DOKUMEN
// =======================================
//

function getRoleName($role) {
    switch($role) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
}

// STATUS FINAL (Sudah Konsisten)
// 1 = Diterbitkan
// 2 = Review
// 3 = Menunggu Publikasi (draf yang menunggu untuk dipublikasikan)
// 4 = Ditolak
// 5 = Disetujui

function getStatusName($status_id) {
    switch($status_id) {
        case 1: return 'Diterbitkan';
        case 2: return 'Review';
        case 3: return 'Menunggu Publikasi';
        case 4: return 'Ditolak';
        case 5: return 'Disetujui';
        default: return 'Unknown';
    }
}

function getStatusBadge($status_id) {
    switch($status_id) {
        case 1: return 'badge-success';
        case 2: return 'badge-warning';
        case 3: return 'badge-info';
        case 4: return 'badge-danger';
        case 5: return 'badge-primary';
        default: return 'badge-secondary';
    }
}

?>
