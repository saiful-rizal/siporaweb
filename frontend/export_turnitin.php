<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/UploadModel.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

 $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
 $score_filter = isset($_GET['score']) ? $_GET['score'] : 'all';

 $uploadModel = new UploadModel($pdo);
 $all_documents = $uploadModel->getAllDocumentsWithTurnitin();

// Filter documents
 $filtered_documents = $all_documents;
if ($score_filter !== 'all') {
    switch($score_filter) {
        case 'low':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 0 && $doc['turnitin'] <= 20;
            });
            break;
        case 'medium':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 20 && $doc['turnitin'] <= 40;
            });
            break;
        case 'high':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] > 40;
            });
            break;
        case 'none':
            $filtered_documents = array_filter($all_documents, function($doc) {
                return $doc['turnitin'] == 0;
            });
            break;
    }
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="turnitin_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, [
        'Judul Dokumen',
        'Pengunggah',
        'Divisi',
        'Jurusan',
        'Program Studi',
        'Tema',
        'Tahun',
        'Skor Turnitin',
        'Status',
        'Tanggal Unggah'
    ]);
    
    // Data
    foreach ($filtered_documents as $doc) {
        fputcsv($output, [
            $doc['judul'],
            $doc['uploader_name'],
            $doc['nama_divisi'],
            $doc['nama_jurusan'],
            $doc['nama_prodi'],
            $doc['nama_tema'],
            $doc['year_id'],
            $doc['turnitin'] . '%',
            $doc['nama_status'],
            date('d/m/Y H:i', strtotime($doc['tgl_unggah']))
        ]);
    }
    
    fclose($output);
    
} elseif ($format === 'pdf') {
    // Simple PDF export (requires TCPDF or similar library)
    // For now, redirect to CSV
    header('Location: export_turnitin.php?format=csv&score=' . $score_filter);
}
?>