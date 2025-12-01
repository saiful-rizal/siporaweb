<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

 $date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';

try {
    $uploadModel = new UploadModel($pdo);
    $upload_history = $uploadModel->getUploadHistory($_SESSION['user_id']);
    
    // Filter by date
    $filtered_history = $upload_history;
    if ($date_filter !== 'all' && !empty($upload_history)) {
        $today = date('Y-m-d');
        switch($date_filter) {
            case 'today':
                $filtered_history = array_filter($upload_history, function($item) use ($today) {
                    return date('Y-m-d', strtotime($item['upload_date'])) === $today;
                });
                break;
            case 'week':
                $week_ago = date('Y-m-d', strtotime('-7 days'));
                $filtered_history = array_filter($upload_history, function($item) use ($week_ago) {
                    return date('Y-m-d', strtotime($item['upload_date'])) >= $week_ago;
                });
                break;
            case 'month':
                $month_ago = date('Y-m-d', strtotime('-30 days'));
                $filtered_history = array_filter($upload_history, function($item) use ($month_ago) {
                    return date('Y-m-d', strtotime($item['upload_date'])) >= $month_ago;
                });
                break;
        }
        // Re-index array
        $filtered_history = array_values($filtered_history);
    }
    
    // Export to CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="riwayat_upload_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, [
        'Tanggal Upload',
        'Waktu Upload',
        'Judul Dokumen',
        'Tema',
        'Tahun',
        'Status',
        'Skor Turnitin',
        'File Path'
    ]);

    // Data
    foreach ($filtered_history as $item) {
        fputcsv($output, [
            date('d/m/Y', strtotime($item['upload_date'])),
            date('H:i:s', strtotime($item['upload_date'])),
            isset($item['judul']) ? $item['judul'] : 'Unknown',
            isset($item['nama_tema']) ? $item['nama_tema'] : 'Unknown',
            isset($item['year_id']) ? $item['year_id'] : 'Unknown',
            isset($item['nama_status']) ? $item['nama_status'] : 'Unknown',
            (isset($item['turnitin']) ? $item['turnitin'] : 0) . '%',
            isset($item['file_path']) ? $item['file_path'] : 'Unknown'
        ]);
    }

    fclose($output);
    
} catch (Exception $e) {
    error_log("Error exporting history: " . $e->getMessage());
    header('Content-Type: text/plain');
    echo "Error exporting data. Please try again.";
}
?>