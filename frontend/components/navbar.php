<?php
if (!isset($user_data)) {
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = :user_id LIMIT 1");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $_SESSION['user_id'];
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $user_data = ['username' => 'Guest', 'email' => '', 'role' => 0];
            $user_id = 0;
        }
    } else {
        $user_data = ['username' => 'Guest', 'email' => '', 'role' => 0];
        $user_id = 0;
    }
}

function isMenuActive($page) {
    $current_file = basename($_SERVER['PHP_SELF']);
    if ($page === 'dashboard.php' && $current_file === 'index.php') {
        return 'active';
    }
    return $current_file === $page ? 'active' : '';
}

 $notifications = [];
 $notification_count = 0;

if ($user_id > 0) {
    try {
        if (!class_exists('UploadModel')) {
            require_once __DIR__ . '/../models/UploadModel.php';
        }
        
        $uploadModel = new UploadModel($pdo);
        
        // Get user documents with status updates
        try {
            $userDocuments = $uploadModel->getUserDocuments($user_id);
            if ($userDocuments) {
                foreach ($userDocuments as $doc) {
                    $time_ago = getTimeAgo($doc['tgl_unggah']);
                    
                    $status_name = isset($doc['nama_status']) ? $doc['nama_status'] : 'Unknown';
                    
                    $icon_type = 'info';
                    $icon_class = 'bi-info-circle-fill';
                    $title = 'Status Diperbarui';
                    $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" berstatus: <strong>" . htmlspecialchars($status_name, ENT_QUOTES, 'UTF-8') . "</strong>";
                    
                    // Standardized status mapping and messages
                    // status_id meanings used across the UI:
                    // 1 = Menunggu (waiting/submitted)
                    // 2 = Dalam Review
                    // 3 = Menunggu Publikasi
                    // 4 = Ditolak
                    // 5 = Diterbitkan (published)

                    if ($doc['status_id'] == 5) {
                        // Published / public
                        $icon_type = 'success';
                        $icon_class = 'bi-check-circle-fill';
                        $title = 'Dokumen Diterbitkan';
                        $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" <strong>telah diterbitkan</strong> dan tersedia untuk umum";
                    } elseif ($doc['status_id'] == 1) {
                        // Waiting/submitted
                        $icon_type = 'info';
                        $icon_class = 'bi-clock-fill';
                        $title = 'Menunggu Persetujuan';
                        $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" sedang <strong>menunggu persetujuan</strong> dari reviewer/admin";
                    } elseif ($doc['status_id'] == 4) {
                        $icon_type = 'danger';
                        $icon_class = 'bi-x-circle-fill';
                        $title = 'Dokumen Ditolak';
                        $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" <strong>ditolak</strong>. Silakan periksa kembali dokumen Anda";
                    } elseif ($doc['status_id'] == 2) {
                        // Under review (active review process)
                        $icon_type = 'warning';
                        $icon_class = 'bi-hourglass-split';
                        $title = 'Sedang Direview';
                        $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" sedang <strong>direview</strong> oleh tim";
                    } elseif ($doc['status_id'] == 3) {
                        $icon_type = 'secondary';
                        $icon_class = 'bi-file-earmark-text-fill';
                        $title = 'Menunggu Publikasi';
                        $message = "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" masih dalam status <strong>Menunggu Publikasi</strong>";
                    }

                    $notifications[] = [
                        'title' => $title,
                        'message' => $message,
                        'time' => $time_ago,
                        'icon_type' => $icon_type,
                        'icon_class' => $icon_class,
                        'doc_id' => $doc['dokumen_id'],
                        'judul' => $doc['judul'],
                        'status_id' => $doc['status_id'],
                        'nama_status' => $status_name,
                        'type' => 'document_status',
                        'timestamp' => strtotime($doc['tgl_unggah'])
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching user documents: " . $e->getMessage());
        }
        
        // Get new documents from other users (hanya untuk admin)
        if ($user_data['role'] == 1) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        d.dokumen_id,
                        d.judul,
                        d.tgl_unggah,
                        d.uploader_id,
                        u.username as uploader_name
                    FROM dokumen d
                    LEFT JOIN users u ON d.uploader_id = u.id_user
                    WHERE d.uploader_id != :user_id 
                    AND d.tgl_unggah > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY d.tgl_unggah DESC 
                    LIMIT 10
                ");
                $stmt->execute(['user_id' => $user_id]);
                $newDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($newDocs) {
                    foreach ($newDocs as $doc) {
                        $time_ago = getTimeAgo($doc['tgl_unggah']);
                        $uploader_name = isset($doc['uploader_name']) ? $doc['uploader_name'] : 'Unknown';
                        $notifications[] = [
                            'title' => 'Dokumen Baru',
                            'message' => "<strong>" . htmlspecialchars($uploader_name, ENT_QUOTES, 'UTF-8') . "</strong> mengunggah dokumen: \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\"",
                            'time' => $time_ago,
                            'icon_type' => 'info',
                            'icon_class' => 'bi-file-earmark-plus',
                            'doc_id' => $doc['dokumen_id'],
                            'judul' => $doc['judul'],
                            'uploader_name' => $uploader_name,
                            'type' => 'new_document',
                            'timestamp' => strtotime($doc['tgl_unggah'])
                        ];
                    }
                }
            } catch (PDOException $e) {
                error_log("Error fetching new documents: " . $e->getMessage());
            }
        }
        
        // Get download history
        try {
            $cekDownload = $pdo->query("SHOW TABLES LIKE 'download_history'")->fetch();
            if ($cekDownload) {
                $stmt = $pdo->prepare("
                    SELECT 
                        dh.tanggal,
                        dh.user_id as downloader_id,
                        d.dokumen_id,
                        d.judul,
                        COALESCE(u.username, 'Unknown') as downloader_name
                    FROM download_history dh
                    JOIN dokumen d ON dh.dokumen_id = d.dokumen_id
                    LEFT JOIN users u ON dh.user_id = u.id_user
                    WHERE d.uploader_id = :user_id 
                    AND dh.tanggal > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY dh.tanggal DESC
                    LIMIT 10
                ");
                $stmt->execute(['user_id' => $user_id]);
                $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($downloads) {
                    foreach ($downloads as $download) {
                        $time_ago = getTimeAgo($download['tanggal']);
                        $notifications[] = [
                            'title' => 'Dokumen Diunduh',
                            'message' => "<strong>" . htmlspecialchars($download['downloader_name'], ENT_QUOTES, 'UTF-8') . "</strong> mengunduh dokumen: \"" . htmlspecialchars($download['judul'], ENT_QUOTES, 'UTF-8') . "\"",
                            'time' => $time_ago,
                            'icon_type' => 'primary',
                            'icon_class' => 'bi-download',
                            'doc_id' => $download['dokumen_id'],
                            'judul' => $download['judul'],
                            'downloader_name' => $download['downloader_name'],
                            'type' => 'download',
                            'timestamp' => strtotime($download['tanggal'])
                        ];
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error fetching download history: " . $e->getMessage());
        }
        
        // Get pending documents for admins
        if ($user_data['role'] == 1) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        d.dokumen_id,
                        d.judul,
                        d.tgl_unggah,
                        d.uploader_id,
                        COALESCE(u.username, 'Unknown') as uploader_name
                    FROM dokumen d
                    LEFT JOIN users u ON d.uploader_id = u.id_user
                    WHERE d.status_id = 2
                    ORDER BY d.tgl_unggah DESC
                    LIMIT 5
                ");
                $stmt->execute();
                $pendingDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($pendingDocs) {
                    foreach ($pendingDocs as $doc) {
                        $time_ago = getTimeAgo($doc['tgl_unggah']);
                        $notifications[] = [
                            'title' => 'Dokumen Menunggu Review',
                            'message' => "Dokumen \"" . htmlspecialchars($doc['judul'], ENT_QUOTES, 'UTF-8') . "\" dari <strong>" . htmlspecialchars($doc['uploader_name'], ENT_QUOTES, 'UTF-8') . "</strong> menunggu review",
                            'time' => $time_ago,
                            'icon_type' => 'warning',
                            'icon_class' => 'bi-eye',
                            'doc_id' => $doc['dokumen_id'],
                            'judul' => $doc['judul'],
                            'uploader_name' => $doc['uploader_name'],
                            'type' => 'pending_review',
                            'timestamp' => strtotime($doc['tgl_unggah'])
                        ];
                    }
                }
            } catch (PDOException $e) {
                error_log("Error fetching pending documents: " . $e->getMessage());
            }
        }
        
        // Sort notifications by timestamp (newest first)
        usort($notifications, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Get only the latest 20 notifications
        $notifications = array_slice($notifications, 0, 20);
        $notification_count = count($notifications);

    } catch (Exception $e) {
        error_log("Error in notification system: " . $e->getMessage());
        $notifications = [];
        $notification_count = 0;
    }
}

function getTimeAgo($datetime) {
    if (!$datetime) return 'Waktu tidak diketahui';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 2629743) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return date('d M Y', $time);
    }
}
?>
<style>
:root {
    --primary-color: #0058e4;
    --primary-dark: #0047c2;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #17a2b8;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
    --white: #ffffff;
    --gray-100: #f8f9fa;
    --gray-200: #e9ecef;
    --gray-300: #dee2e6;
    --gray-400: #ced4da;
    --gray-500: #adb5bd;
    --gray-600: #6c757d;
    --gray-700: #495057;
    --gray-800: #343a40;
    --gray-900: #212529;
    --border-radius: 8px;
    --box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.6;
    color: var(--gray-800);
    opacity: 0;
    animation: fadeIn 0.5s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

a {
    transition: var(--transition);
}

nav {
    background-color: var(--white);
    box-shadow: var(--box-shadow);
    padding: 0 1.5rem;
    position: sticky;
    top: 0;
    z-index: 1030;
    backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.95);
    animation: slideDown 0.5s ease;
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition);
}

.brand:hover {
    transform: translateY(-2px);
}

.brand img {
    height: 40px;
    width: auto;
    border-radius: 6px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.nav-links a {
    color: var(--gray-600);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    position: relative;
    transition: var(--transition);
    padding: 0.5rem 0;
}

.nav-links a:hover, .nav-links a.active {
    color: var(--primary-color);
}

.nav-links a.active::after {
    content: '';
    position: absolute;
    bottom: -21px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: var(--primary-color);
    border-radius: 3px 3px 0 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.user-avatar-container {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0, 88, 228, 0.2);
}

.user-avatar-container:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 88, 228, 0.3);
}

.user-avatar-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.notification-icon {
    position: relative;
    cursor: pointer;
    color: var(--gray-600);
    font-size: 1.3rem;
    transition: var(--transition);
    padding: 0.5rem;
    border-radius: 50%;
}

.notification-icon:hover {
    color: var(--primary-color);
    background-color: rgba(0, 88, 228, 0.1);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: linear-gradient(135deg, var(--danger-color), #c82333);
    color: var(--white);
    border-radius: 50%;
    width: 12px;
    height: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    width: 400px;
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    max-height: 480px;
    overflow: hidden;
    border: 1px solid var(--gray-200);
    animation: slideDown 0.3s ease;
}

.notification-dropdown.show {
    display: flex;
}

.notification-header {
    padding: 1.2rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    background-color: var(--gray-50);
}

.notification-header h5 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--gray-800);
}

.notification-header a {
    font-size: 0.85rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    background-color: rgba(0, 88, 228, 0.1);
}

.notification-header a:hover {
    background-color: var(--primary-color);
    color: var(--white);
}

.notification-list {
    flex-grow: 1;
    overflow-y: auto;
}

.notification-item {
    padding: 1.2rem;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    gap: 1rem;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
}

.notification-item:hover {
    background-color: var(--gray-50);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background-color: rgba(0, 88, 228, 0.05);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, var(--primary-color), var(--primary-dark));
}

.notification-item.read {
    opacity: 0.7;
}

.notification-content {
    display: flex;
    gap: 0.9rem;
    width: 100%;
}

.notification-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.notification-icon-wrapper.success { 
    background: linear-gradient(135deg, #d1f7c4, #b8e6b1); 
    color: var(--success-color);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.notification-icon-wrapper.danger { 
    background: linear-gradient(135deg, #f8d7da, #f1b0b7); 
    color: var(--danger-color);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
}

.notification-icon-wrapper.warning { 
    background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
    color: var(--warning-color);
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

.notification-icon-wrapper.info { 
    background: linear-gradient(135deg, #cfe2ff, #b8daff); 
    color: var(--info-color);
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.2);
}

.notification-icon-wrapper.secondary { 
    background: linear-gradient(135deg, #e9ecef, #dee2e6); 
    color: var(--gray-600);
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.2);
}

.notification-icon-wrapper.primary { 
    background: linear-gradient(135deg, #cfe2ff, #a6c8ff); 
    color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0, 88, 228, 0.2);
}

.notification-text {
    flex-grow: 1;
}

.notification-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.4rem;
    color: var(--gray-800);
}

.notification-message {
    font-size: 0.85rem;
    color: var(--gray-600);
    line-height: 1.5;
}

.notification-time {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.notification-time::before {
    content: '•';
    color: var(--gray-400);
}

.notification-empty {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--gray-500);
}

.notification-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
    color: var(--gray-300);
}

.notification-empty p {
    font-size: 0.95rem;
    margin: 0;
}

.notification-footer {
    padding: 1rem 1.2rem;
    border-top: 1px solid var(--gray-200);
    text-align: center;
    background-color: var(--gray-50);
}

.notification-footer a {
    font-size: 0.9rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-footer a:hover {
    color: var(--primary-dark);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    width: 260px;
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--gray-200);
    animation: slideDown 0.3s ease;
}

.user-dropdown.show {
    display: flex;
}

.user-dropdown-header {
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    border-bottom: 1px solid var(--gray-200);
    background: linear-gradient(135deg, var(--gray-50), var(--white));
}

.user-dropdown-header .user-avatar-container {
    width: 48px;
    height: 48px;
}

.user-dropdown-header .name {
    font-weight: 600;
    font-size: 1rem;
    color: var(--gray-800);
}

.user-dropdown-header .role {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-top: 0.2rem;
}

.user-dropdown-item {
    padding: 0.9rem 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    color: var(--gray-700);
    text-decoration: none;
    transition: var(--transition);
}

.user-dropdown-item:hover {
    background-color: var(--gray-50);
    color: var(--primary-color);
}

.user-dropdown-item i {
    font-size: 1.1rem;
    color: var(--gray-500);
    width: 20px;
    text-align: center;
}

.user-dropdown-item:hover i {
    color: var(--primary-color);
}

.user-dropdown-divider {
    height: 1px;
    background-color: var(--gray-200);
    margin: 0.3rem 0;
}

.user-dropdown-logout {
    color: var(--danger-color);
}

.user-dropdown-logout i {
    color: var(--danger-color);
}

.user-dropdown-logout:hover {
    background-color: rgba(220, 53, 69, 0.1);
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.6rem;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
}

.mobile-menu-btn:hover {
    background-color: rgba(0, 88, 228, 0.1);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1060;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-dialog {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    animation: slideUp 0.4s ease;
    display: flex;
    flex-direction: column;
}

.modal-dialog.large {
    max-width: 800px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, var(--gray-50), var(--white));
}

.modal-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.3rem;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--gray-200);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-edit-avatar {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    border: 3px solid var(--white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0, 88, 228, 0.3);
}

.btn-edit-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 88, 228, 0.4);
}

#cameraVideo {
    background-color: var(--gray-900);
}

canvas {
    display: none;
}

.profile-info h4 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--gray-800);
}

.profile-info p {
    margin-bottom: 0.2rem;
    color: var(--gray-600);
    font-size: 0.95rem;
}

.profile-details {
    margin-bottom: 1.5rem;
}

.profile-details h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.profile-detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.profile-detail-item:last-child {
    border-bottom: none;
}

.profile-detail-label {
    font-weight: 500;
    color: var(--gray-600);
    font-size: 0.95rem;
}

.profile-detail-value {
    font-weight: 400;
    color: var(--gray-800);
    font-size: 0.95rem;
}

.notification-detail-header {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.notification-detail-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.notification-detail-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--gray-800);
}

.notification-detail-time {
    font-size: 0.85rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.notification-detail-time::before {
    content: '•';
    color: var(--gray-400);
}

.notification-detail-message {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    color: var(--gray-700);
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
}

.notification-detail-actions {
    display: flex;
    gap: 0.8rem;
    margin-top: 1.5rem;
}

.all-notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.all-notifications-header h5 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.all-notifications-header button {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0, 88, 228, 0.3);
}

.all-notifications-header button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 88, 228, 0.4);
}

.all-notifications-list {
    max-height: 450px;
    overflow-y: auto;
}

.all-notifications-empty {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--gray-500);
}

.all-notifications-empty i {
    font-size: 3.5rem;
    margin-bottom: 1.5rem;
    display: block;
    color: var(--gray-300);
}

.all-notifications-empty p {
    font-size: 1rem;
    margin: 0;
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    box-shadow: 0 2px 8px rgba(0, 88, 228, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 88, 228, 0.4);
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
    transform: translateY(-1px);
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    transition: var(--transition);
    background-color: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.1);
}

.mb-3 {
    margin-bottom: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}

.accordion {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.accordion-item {
    border: 1px solid var(--gray-200);
    margin-bottom: -1px;
    background-color: var(--white);
    transition: var(--transition);
}

.accordion-item:first-child {
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.accordion-item:last-child {
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.accordion-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.accordion-header {
    margin: 0;
}

.accordion-button {
    width: 100%;
    padding: 1.2rem 1.5rem;
    background-color: var(--white);
    border: none;
    text-align: left;
    font-weight: 600;
    font-size: 1rem;
    color: var(--gray-800);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--transition);
    position: relative;
}

.accordion-button:hover {
    background-color: var(--gray-50);
}

.accordion-button::after {
    content: '\f078';
    font-family: 'Bootstrap Icons';
    font-weight: 900;
    transition: var(--transition);
    font-size: 0.8rem;
    color: var(--primary-color);
}

.accordion-button:not(.collapsed)::after {
    transform: rotate(180deg);
}

.accordion-button:not(.collapsed) {
    background-color: var(--gray-50);
    color: var(--primary-color);
}

.accordion-collapse {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.accordion-collapse.show {
    max-height: 500px;
}

.accordion-body {
    padding: 0 1.5rem 1.2rem;
    color: var(--gray-700);
    line-height: 1.6;
}

.accordion-body ol, .accordion-body ul {
    margin-left: 1.5rem;
    margin-top: 0.5rem;
}

.accordion-body li {
    margin-bottom: 0.5rem;
}

@media (max-width: 992px) {
    .nav-links {
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .nav-links {
        position: fixed;
        top: 70px;
        left: 0;
        width: 100%;
        height: calc(100vh - 70px);
        background-color: var(--white);
        flex-direction: column;
        padding: 2rem;
        gap: 1rem;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        box-shadow: var(--box-shadow);
        z-index: 1020;
    }

    .nav-links.show {
        transform: translateX(0);
    }
    
    .nav-links a.active::after {
        bottom: auto;
        top: 0;
        left: -2rem;
        width: 3px;
        height: 100%;
    }

    .mobile-menu-btn {
        display: block;
    }

    .notification-dropdown {
        width: calc(100vw - 2rem);
        right: -1rem;
    }
    
    .modal-dialog.large {
        width: 95%;
        max-width: none;
    }
    
    .user-dropdown {
        width: calc(100vw - 2rem);
        right: -1rem;
    }
}

@media (max-width: 480px) {
    .nav-container {
        padding: 0 1rem;
    }
    
    .brand {
        font-size: 1.3rem;
    }
    
    .brand img {
        height: 35px;
    }
    
    .notification-dropdown {
        width: calc(100vw - 1rem);
        right: -0.5rem;
    }
    
    .modal-dialog {
        width: 95%;
        margin: 1rem;
    }
}
</style>

<nav>
  <div class="nav-container">
    <a href="dashboard.php" class="brand">
      <img src="assets/logo.png" alt="Logo Polije">
         </a>
    <button class="mobile-menu-btn" id="mobileMenuBtn">
      <i class="bi bi-list"></i>
    </button>
    <div class="nav-links" id="navLinks">
      <a href="dashboard.php" class="<?php echo isMenuActive('dashboard.php'); ?>">Beranda</a>
      <a href="upload.php" class="<?php echo isMenuActive('upload.php'); ?>">Unggah</a>
      <a href="browser.php" class="<?php echo isMenuActive('browser.php'); ?> ">Jelajahi</a>
      <a href="search.php" class="<?php echo isMenuActive('search.php'); ?>">Pencarian</a>
    </div>
    <div class="user-info">
      <div class="notification-icon" id="notificationIcon">
        <i class="bi bi-bell-fill"></i>
        <?php if ($notification_count > 0): ?>
        <span class="notification-badge" id="notificationCount"></span>
        <?php endif; ?>
        
        <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <h5>Terbaru</h5>
            <?php if ($notification_count > 0): ?>
            <a href="#" onclick="markAllAsRead()">Tandai semua dibaca</a>
            <?php endif; ?>
          </div>
          <div class="notification-list" id="notificationList">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $index => $notif): ?>
                <div class="notification-item unread" data-index="<?php echo $index; ?>" onclick="showNotificationDetail(<?php echo $index; ?>)">
                  <div class="notification-content">
                    <div class="notification-icon-wrapper <?php echo $notif['icon_type']; ?>">
                      <i class="bi <?php echo $notif['icon_class']; ?>"></i>
                    </div>
                    <div class="notification-text">
                      <div class="notification-title"><?php echo htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="notification-message"><?php echo $notif['message']; ?></div>
                      <div class="notification-time"><?php echo $notif['time']; ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="notification-empty">
                <i class="bi bi-bell-slash"></i>
                <p>Tidak ada notifikasi baru.</p>
              </div>
            <?php endif; ?>
          </div>
          <div class="notification-footer">
            <a href="#" onclick="showAllNotifications()">
              <i class="bi bi-list-ul"></i>
              Lihat Semua Notifikasi
            </a>
          </div>
        </div>
      </div>

      <div id="userAvatarContainer" class="user-avatar-container">
        <?php 
        if (hasProfilePhoto($user_id)) {
            echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" id="userAvatar">';
        } else {
            echo getInitialsHtml($user_data['username'], 'small');
        }
        ?>
      </div>
      
      <div class="user-dropdown" id="userDropdown">
        <div class="user-dropdown-header">
          <div id="dropdownAvatarContainer">
            <?php 
            if (hasProfilePhoto($user_id)) {
                echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
            } else {
                echo getInitialsHtml($user_data['username'], 'small');
            }
            ?>
          </div>
          <div>
            <div class="name"><?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="role"><?php echo getRoleName($user_data['role']); ?></div>
          </div>
        </div>
        <a href="#" class="user-dropdown-item" onclick="openProfileModal()">
          <i class="bi bi-person"></i>
          <span>Profil Saya</span>
        </a>
        <a href="#" class="user-dropdown-item" onclick="openHelpModal()">
          <i class="bi bi-question-circle"></i>
          <span>Bantuan</span>
        </a>
        <div class="user-dropdown-divider"></div>
        <a href="?logout=true" class="user-dropdown-item user-dropdown-logout">
          <i class="bi bi-box-arrow-right"></i>
          <span>Keluar</span>
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Profil Pengguna</h5>
                <button type="button" class="modal-close" onclick="closeModal('profileModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="profile-header">
                    <div id="modalAvatarContainer" style="position: relative;">
                        <?php 
                        if (hasProfilePhoto($user_id)) {
                            echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" class="profile-avatar" id="profileAvatarImg">';
                        } else {
                            echo getInitialsHtml($user_data['username'], 'large');
                        }
                        ?>
                        <button class="btn-edit-avatar" onclick="openChangeProfileModal()" title="Ubah Foto Profil">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                    </div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p><?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo getRoleName($user_data['role']); ?></p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <h5>Informasi Pribadi</h5>
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Username</span>
                        <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Email</span>
                        <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-detail-item">
                        <span class="profile-detail-label">Role</span>
                        <span class="profile-detail-value"><?php echo getRoleName($user_data['role']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bantuan</h5>
                <button type="button" class="modal-close" onclick="closeModal('helpModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" onclick="toggleAccordion(this)">
                                Cara Mengunggah Dokumen
                            </button>
                        </h2>
                        <div class="accordion-collapse show">
                            <div class="accordion-body">
                                <ol>
                                    <li>Klik menu <strong>Unggah</strong> di bilah navigasi.</li>
                                    <li>Isi semua informasi yang diperlukan seperti judul, abstrak, penulis, dll.</li>
                                    <li>Pilih file dokumen yang ingin diunggah (format PDF, DOC, DOCX).</li>
                                    <li>Klik tombol <strong>Unggah Dokumen</strong> untuk memproses.</li>
                                    <li>Dokumen akan melalui proses review oleh admin sebelum diterbitkan.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" onclick="toggleAccordion(this)">
                                Cara Mencari Dokumen
                            </button>
                        </h2>
                        <div class="accordion-collapse">
                            <div class="accordion-body">
                                <ol>
                                    <li>Buka halaman <strong>Pencarian</strong> dari menu navigasi.</li>
                                    <li>Masukkan kata kunci terkait dokumen yang dicari (judul, abstrak, atau kata kunci).</li>
                                    <li>Klik tombol <strong>Cari</strong> atau tekan Enter.</li>
                                    <li>Gunakan filter di sebelah kiri untuk mempersempit hasil pencarian.</li>
                                    <li>Klik pada kartu dokumen untuk melihat detail dan pratinjau.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" onclick="toggleAccordion(this)">
                                Cara Mengunduh Dokumen
                            </button>
                        </h2>
                        <div class="accordion-collapse">
                            <div class="accordion-body">
                                <p>Ada dua cara untuk mengunduh dokumen:</p>
                                <ol>
                                    <li><strong>Dari Halaman Jelajahi/Unduhan:</strong> Klik ikon unduh (<i class="bi bi-download"></i>) pada kartu dokumen.</li>
                                    <li><strong>Dari Modal Detail Dokumen:</strong> Buka detail dokumen dengan mengklik kartunya, lalu klik tombol <strong>Unduh Dokumen</strong> di bagian bawah.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" onclick="toggleAccordion(this)">
                                Memahami Status Dokumen
                            </button>
                        </h2>
                        <div class="accordion-collapse">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Menunggu Publikasi:</strong> Dokumen yang telah disimpan / diajukan tetapi belum dipublikasikan.</li>
                                    <li><strong>Review:</strong> Dokumen sedang dalam proses review oleh admin.</li>
                                    <li><strong>Disetujui:</strong> Dokumen telah disetujui dan menunggu untuk diterbitkan.</li>
                                    <li><strong>Diterbitkan:</strong> Dokumen telah disetujui dan tersedia untuk umum.</li>
                                    <li><strong>Ditolak:</strong> Dokumen tidak disetujui. Anda dapat melihat alasan penolakan dan mengedit ulang dokumen.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="notificationDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Notifikasi</h5>
                <button type="button" class="modal-close" onclick="closeModal('notificationDetailModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="notification-detail-header">
                    <div class="notification-detail-icon" id="notifDetailIcon"></div>
                    <div>
                        <div class="notification-detail-title" id="notifDetailTitle"></div>
                        <div class="notification-detail-time" id="notifDetailTime"></div>
                    </div>
                </div>
                <div class="notification-detail-message" id="notifDetailMessage"></div>
                <div class="notification-detail-actions">
                    <button class="btn btn-secondary" onclick="closeModal('notificationDetailModal')">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changeProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Foto Profil</h5>
                <button type="button" class="modal-close" onclick="closeModal('changeProfileModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <button class="btn btn-primary" onclick="startCamera()" style="flex: 1;">
                        <i class="bi bi-camera-video"></i>
                        Ambil Foto dari Kamera
                    </button>
                    <button class="btn btn-secondary" onclick="document.getElementById('fileInput').click()" style="flex: 1;">
                        <i class="bi bi-upload"></i>
                        Upload Foto
                    </button>
                </div>
                
                <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
                
                <div id="cameraContainer" style="display: none; margin-bottom: 1.5rem;">
                    <video id="cameraVideo" style="width: 100%; border-radius: 8px; margin-bottom: 1rem;"></video>
                    <div style="display: flex; gap: 0.8rem;">
                        <button class="btn btn-primary" onclick="capturePhoto()" style="flex: 1;">
                            <i class="bi bi-check-circle"></i>
                            Ambil Foto
                        </button>
                        <button class="btn btn-secondary" onclick="stopCamera()" style="flex: 1;">
                            <i class="bi bi-x-circle"></i>
                            Batal
                        </button>
                    </div>
                </div>
                
                <div id="previewContainer" style="display: none;">
                    <p style="font-weight: 600; margin-bottom: 1rem; color: var(--gray-800);">Pratinjau Foto:</p>
                    <img id="photoPreview" style="width: 100%; border-radius: 8px; margin-bottom: 1rem; object-fit: cover; max-height: 300px;">
                    <div style="display: flex; gap: 0.8rem;">
                        <button class="btn btn-primary" onclick="uploadPhoto()" style="flex: 1;">
                            <i class="bi bi-cloud-upload"></i>
                            Simpan Foto
                        </button>
                        <button class="btn btn-secondary" onclick="resetPreview()" style="flex: 1;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Ulang
                        </button>
                    </div>
                </div>
                
                <div id="uploadStatus" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: 8px; text-align: center;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="allNotificationsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered large">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Semua Notifikasi</h5>
                <button type="button" class="modal-close" onclick="closeModal('allNotificationsModal')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="all-notifications-header">
                    <h5>Terbaru</h5>
                    <?php if ($notification_count > 0): ?>
                    <button onclick="clearAllNotifications()">
                        <i class="bi bi-trash3"></i>
                        Hapus Semua
                    </button>
                    <?php endif; ?>
                </div>
                <div class="all-notifications-list" id="allNotificationsList">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $index => $notif): ?>
                            <div class="notification-item unread" data-index="<?php echo $index; ?>" onclick="showNotificationDetail(<?php echo $index; ?>)">
                                <div class="notification-content">
                                    <div class="notification-icon-wrapper <?php echo $notif['icon_type']; ?>">
                                        <i class="bi <?php echo $notif['icon_class']; ?>"></i>
                                    </div>
                                    <div class="notification-text">
                                        <div class="notification-title"><?php echo htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="notification-message"><?php echo $notif['message']; ?></div>
                                        <div class="notification-time"><?php echo $notif['time']; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="all-notifications-empty">
                            <i class="bi bi-bell-slash"></i>
                            <p>Tidak ada notifikasi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const notificationsData = <?php echo json_encode($notifications); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // dismissedNotifications stores unique keys for notifications that were removed/read.
    // key format: <type>_<doc_id>_<status_id>_<timestamp>
    let dismissedNotifications = [];
    try {
        dismissedNotifications = JSON.parse(localStorage.getItem('dismissedNotifications') || '[]');
    } catch (e) {
        dismissedNotifications = [];
    }

    function getNotifKey(notif) {
        const type = notif.type || 'unknown';
        const docId = notif.doc_id || notif.dokumen_id || '';
        const statusId = (typeof notif.status_id !== 'undefined') ? notif.status_id : '';
        const ts = notif.timestamp || notif.tanggal || notif.tgl_unggah || '';
        return `${type}_${docId}_${statusId}_${ts}`;
    }

    // Function to update notification UI based on dismissed status
    function updateNotificationUI() {
        // Update regular notification dropdown
        const notificationItems = document.querySelectorAll('#notificationList .notification-item');
        notificationItems.forEach(item => {
            const idx = parseInt(item.getAttribute('data-index'));
            const notif = notificationsData[idx];
            if (notif) {
                const key = getNotifKey(notif);
                if (dismissedNotifications.includes(key)) {
                    item.classList.remove('unread');
                    item.classList.add('read');
                }
            }
        });

        // Update all notifications modal
        const allNotificationItems = document.querySelectorAll('#allNotificationsList .notification-item');
        allNotificationItems.forEach(item => {
            const idx = parseInt(item.getAttribute('data-index'));
            const notif = notificationsData[idx];
            if (notif) {
                const key = getNotifKey(notif);
                if (dismissedNotifications.includes(key)) {
                    item.classList.remove('unread');
                    item.classList.add('read');
                }
            }
        });

        // Update badge count
        updateNotificationBadge();
    }

    // Update badge/count after filtering dismissed items
    function updateNotificationBadge() {
        const unreadItems = document.querySelectorAll('#notificationList .notification-item.unread');
        const count = unreadItems.length;
        const badge = document.getElementById('notificationCount');
        if (badge) {
            if (count > 0) {
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // Initialize UI
    updateNotificationUI();

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    mobileMenuBtn.addEventListener('click', function() {
        navLinks.classList.toggle('show');
    });

    const notificationIcon = document.getElementById('notificationIcon');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    notificationIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        closeAllDropdowns();
        notificationDropdown.classList.toggle('show');
    });

    const userAvatarContainer = document.getElementById('userAvatarContainer');
    const userDropdown = document.getElementById('userDropdown');
    
    userAvatarContainer.addEventListener('click', function(e) {
        e.stopPropagation();
        closeAllDropdowns();
        userDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function() {
        closeAllDropdowns();
    });

    function closeAllDropdowns() {
        if (notificationDropdown) notificationDropdown.classList.remove('show');
        if (userDropdown) userDropdown.classList.remove('show');
        if (navLinks) navLinks.classList.remove('show');
    }

    window.openProfileModal = function() {
        closeModal('helpModal');
        document.getElementById('profileModal').classList.add('show');
        closeAllDropdowns();
    };

    window.openChangeProfileModal = function() {
        resetPreview();
        closeModal('profileModal');
        document.getElementById('changeProfileModal').classList.add('show');
    };

    let cameraStream = null;
    let photoData = null;

    window.startCamera = function() {
        const cameraContainer = document.getElementById('cameraContainer');
        const previewContainer = document.getElementById('previewContainer');
        
        cameraContainer.style.display = 'block';
        previewContainer.style.display = 'none';
        
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                cameraStream = stream;
                const video = document.getElementById('cameraVideo');
                video.srcObject = stream;
                video.play();
            })
            .catch(err => {
                console.error('Error accessing camera:', err);
                showModalNotification('Gagal mengakses kamera. Pastikan Anda telah memberikan izin akses.', 'error');
                cameraContainer.style.display = 'none';
            });
    };

    window.stopCamera = function() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        document.getElementById('cameraContainer').style.display = 'none';
    };

    window.capturePhoto = function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0);
        
        photoData = canvas.toDataURL('image/jpeg', 0.95);
        
        const previewImg = document.getElementById('photoPreview');
        previewImg.src = photoData;
        
        document.getElementById('cameraContainer').style.display = 'none';
        document.getElementById('previewContainer').style.display = 'block';
        
        stopCamera();
    };

    window.handleFileSelect = function(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            showModalNotification('Silakan pilih file gambar yang valid.', 'error');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            showModalNotification('Ukuran file tidak boleh lebih dari 5MB.', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            photoData = e.target.result;
            
            const previewImg = document.getElementById('photoPreview');
            previewImg.src = photoData;
            
            document.getElementById('cameraContainer').style.display = 'none';
            document.getElementById('previewContainer').style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    window.uploadPhoto = function() {
        if (!photoData) {
            showModalNotification('Silakan ambil atau pilih foto terlebih dahulu.', 'error');
            return;
        }
        
        const uploadStatus = document.getElementById('uploadStatus');
        uploadStatus.style.display = 'block';
        uploadStatus.style.backgroundColor = 'var(--info-color)';
        uploadStatus.style.color = 'var(--white)';
        uploadStatus.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengunggah foto...';
        
        fetch('upload_profile_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ photo: photoData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadStatus.style.backgroundColor = 'var(--success-color)';
                uploadStatus.innerHTML = '<i class="bi bi-check-circle"></i> Foto profil berhasil diubah!';
                
                setTimeout(() => {
                    // Reload profile images
                    location.reload();
                }, 1500);
            } else {
                uploadStatus.style.backgroundColor = 'var(--danger-color)';
                uploadStatus.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (data.message || 'Gagal mengunggah foto.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            uploadStatus.style.backgroundColor = 'var(--danger-color)';
            uploadStatus.innerHTML = '<i class="bi bi-exclamation-circle"></i> Terjadi kesalahan saat mengunggah.';
        });
    };

    window.resetPreview = function() {
        photoData = null;
        document.getElementById('photoPreview').src = '';
        document.getElementById('fileInput').value = '';
        document.getElementById('previewContainer').style.display = 'none';
        document.getElementById('cameraContainer').style.display = 'none';
        document.getElementById('uploadStatus').style.display = 'none';
        stopCamera();
    };

    function showModalNotification(message, type = 'success') {
        const modal = document.getElementById('changeProfileModal');
        const uploadStatus = document.getElementById('uploadStatus');
        
        if (modal && modal.classList.contains('show')) {
            uploadStatus.style.display = 'block';
            uploadStatus.style.backgroundColor = type === 'error' ? 'var(--danger-color)' : 'var(--success-color)';
            uploadStatus.style.color = 'var(--white)';
            uploadStatus.innerHTML = `<i class="bi bi-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
            
            if (type !== 'error') {
                setTimeout(() => {
                    uploadStatus.style.display = 'none';
                }, 3000);
            }
        }
    }

    window.openHelpModal = function() {
        closeModal('profileModal');
        document.getElementById('helpModal').classList.add('show');
        closeAllDropdowns();
    };

    window.closeModal = function(modalId) {
        document.getElementById(modalId).classList.remove('show');
    };

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    function clearNotificationsUI() {
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => item.remove());

        const notificationBadge = document.getElementById('notificationCount');
        if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }

        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.innerHTML = '<div class="notification-empty"><i class="bi bi-bell-slash"></i><p>Tidak ada notifikasi baru.</p></div>';
        }

        const allNotificationsList = document.getElementById('allNotificationsList');
        if (allNotificationsList) {
            allNotificationsList.innerHTML = '<div class="all-notifications-empty"><i class="bi bi-bell-slash"></i><p>Tidak ada notifikasi.</p></div>';
        }

        const clearButton = document.querySelector('.all-notifications-header button');
        if (clearButton) {
            clearButton.style.display = 'none';
        }

        const markAllLink = document.querySelector('.notification-header a');
        if (markAllLink) {
            markAllLink.style.display = 'none';
        }
    }

    window.clearAllNotifications = function() {
        // Clear the dismissed list from localStorage to reset the state
        try {
            localStorage.setItem('dismissedNotifications', JSON.stringify([]));
            dismissedNotifications = []; // Also update the in-memory variable
        } catch (e) {
            console.error("Failed to clear dismissed notifications from localStorage.", e);
        }

        // Clear the UI
        clearNotificationsUI();

        // Show a more descriptive message
        showNotificationMessage('Daftar notifikasi telah dibersihkan. Notifikasi baru akan muncul saat ada aktivitas terbaru.');
    };

    window.markAllAsRead = function() {
        // Mark currently visible notifications as dismissed/read
        try {
            // We only need to mark the ones in the main dropdown, not the "all" modal
            const visible = Array.from(document.querySelectorAll('#notificationList .notification-item'));
            visible.forEach(item => {
                const idx = parseInt(item.getAttribute('data-index'));
                const notif = notificationsData[idx];
                if (notif) {
                    const key = getNotifKey(notif);
                    if (!dismissedNotifications.includes(key)) {
                        dismissedNotifications.push(key);
                    }
                }
            });
            localStorage.setItem('dismissedNotifications', JSON.stringify(dismissedNotifications));
        } catch (e) {
            console.error("Failed to mark all as read.", e);
            localStorage.setItem('dismissedNotifications', JSON.stringify(dismissedNotifications));
        }

        // Update the UI to reflect the read status
        updateNotificationUI();

        showNotificationMessage('Semua notifikasi telah ditandai sebagai dibaca');
    };

    function showNotificationMessage(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        
        const bgColor = type === 'error' ? 
            'linear-gradient(135deg, #dc3545, #c82333)' : 
            'linear-gradient(135deg, #28a745, #20c997)';
        
        const icon = type === 'error' ? 
            '<i class="bi bi-exclamation-circle-fill"></i>' : 
            '<i class="bi bi-check-circle-fill"></i>';
        
        toast.innerHTML = `
            ${icon}
            <span>${message}</span>
        `;
        
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        `;
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    function markNotificationAsRead(index) {
        const notif = notificationsData[index];
        if (notif) {
            const key = getNotifKey(notif);
            if (!dismissedNotifications.includes(key)) {
                dismissedNotifications.push(key);
                localStorage.setItem('dismissedNotifications', JSON.stringify(dismissedNotifications));
            }
        }
        // Call updateNotificationUI to refresh the look of all notifications
        updateNotificationUI();
    }

    window.showAllNotifications = function() {
        closeModal('profileModal');
        closeModal('helpModal');
        closeModal('notificationDetailModal');
        document.getElementById('allNotificationsModal').classList.add('show');
        closeAllDropdowns();
        
        // Update UI when opening the modal
        updateNotificationUI();
    };

    window.showNotificationDetail = function(index) {
        const notif = notificationsData[index];
        
        if (!notif) {
            console.error('Notification not found at index:', index);
            return;
        }
        
        // Set modal content
        document.getElementById('notifDetailIcon').className = 'notification-detail-icon notification-icon-wrapper ' + notif.icon_type;
        document.getElementById('notifDetailIcon').innerHTML = '<i class="bi ' + notif.icon_class + '"></i>';
        document.getElementById('notifDetailTitle').textContent = notif.title;
        document.getElementById('notifDetailTime').textContent = notif.time;
        document.getElementById('notifDetailMessage').innerHTML = notif.message;
        
        // Open modal
        closeModal('profileModal');
        closeModal('helpModal');
        closeModal('allNotificationsModal');
        document.getElementById('notificationDetailModal').classList.add('show');
        
        // Mark this notification as read/dismissed
        markNotificationAsRead(index);
    };

    window.toggleAccordion = function(button) {
        const accordionItem = button.parentElement.parentElement;
        const collapse = accordionItem.querySelector('.accordion-collapse');
        const isExpanded = collapse.classList.contains('show');
        
        document.querySelectorAll('.accordion-collapse').forEach(item => {
            item.classList.remove('show');
        });
        
        document.querySelectorAll('.accordion-button').forEach(btn => {
            btn.classList.add('collapsed');
        });
        
        if (!isExpanded) {
            collapse.classList.add('show');
            button.classList.remove('collapsed');
        }
    };

    document.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.hostname === window.location.hostname && !this.getAttribute('onclick')) {
                e.preventDefault();
                document.body.style.opacity = '0';
                setTimeout(() => {
                    window.location.href = this.href;
                }, 300);
            }
        });
    });
});
</script>