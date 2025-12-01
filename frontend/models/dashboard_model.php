<?php
// models/dashboard_model.php
require_once __DIR__ . '/../includes/config.php';

function getDashboardStats() {
    global $pdo;
    $stats = [
        'totalDokumen' => 0,
        'uploadBaru' => 0,
        'downloadBulanIni' => 0,
        'penggunaAktif' => 0
    ];
    
    try {
        // Total dokumen yang dipublikasi
        $stats['totalDokumen'] = $pdo->query("SELECT COUNT(*) FROM dokumen WHERE status_id = 5")->fetchColumn();

        // Upload baru bulan ini yang dipublikasi
        $stats['uploadBaru'] = $pdo->query("SELECT COUNT(*) FROM dokumen WHERE status_id = 5 AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE())")->fetchColumn();

        // Download bulan ini (jika tabel ada)
        $cekDownload = $pdo->query("SHOW TABLES LIKE 'download_history'")->fetch();
        if ($cekDownload) {
            $stats['downloadBulanIni'] = $pdo->query("SELECT COUNT(*) FROM download_history WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())")->fetchColumn();
        }

        // Pengguna aktif bulan ini (jika tabel ada)
        $cekLogin = $pdo->query("SHOW TABLES LIKE 'riwayat_login'")->fetch();
        if ($cekLogin) {
            $stats['penggunaAktif'] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM riwayat_login WHERE MONTH(tanggal_login) = MONTH(CURRENT_DATE())")->fetchColumn();
        }
    } catch (PDOException $e) {
        // Jika query error, tetap lanjut tanpa menghentikan halaman
    }
    
    return $stats;
}

function getRecentDocuments() {
    global $pdo;
    $dokumenTerbaru = [];
    
    try {
        // Query untuk dokumen terbaru dalam seminggu dengan status dipublikasi
        $query = "
            SELECT 
                d.dokumen_id, 
                d.judul, 
                d.abstrak,
                d.kata_kunci,
                d.file_path,
                d.tgl_unggah,
                d.status_id,
                d.turnitin,
                u.username as uploader_name,
                (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count,
                (SELECT nama_divisi FROM master_divisi WHERE id_divisi = d.id_divisi) AS nama_divisi,
                (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
                (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
                (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
                (SELECT tahun FROM master_tahun WHERE year_id = d.year_id) AS tahun -- PERUBAHAN: ambil kolom 'tahun'
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            WHERE d.status_id = 5
            AND d.tgl_unggah >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            ORDER BY d.tgl_unggah DESC
            LIMIT 6
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $dokumenTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // jika tabel relasi belum ada, tampilkan dokumen dasar saja
        try {
            $stmt = $pdo->prepare("
                SELECT dokumen_id, judul, abstrak, kata_kunci, file_path, tgl_unggah, status_id, turnitin
                FROM dokumen 
                WHERE status_id = 5
                AND tgl_unggah >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                ORDER BY tgl_unggah DESC 
                LIMIT 6
            ");
            $stmt->execute();
            $dokumenTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $dokumenTerbaru = [];
        }
    }
    
    return $dokumenTerbaru;
}

function getMasterData() {
    global $pdo;
    $master_data = [
        'divisi_data' => [],
        'jurusan_data' => [],
        'prodi_data' => [],
        'tema_data' => [],
        'tahun_data' => []
    ];
    
    try {
        // Get divisi data from master_divisi table
        $master_data['divisi_data'] = $pdo->query("SELECT id_divisi, nama_divisi FROM master_divisi ORDER BY nama_divisi")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get jurusan data from master_jurusan table
        $master_data['jurusan_data'] = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get prodi data from master_prodi table
        $master_data['prodi_data'] = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tema data from master_tema table
        $master_data['tema_data'] = $pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tahun data from master_tahun table -- PERUBAHAN: ambil kolom 'tahun' saja
        $master_data['tahun_data'] = $pdo->query("SELECT tahun FROM master_tahun ORDER BY tahun DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        // If no years in master_tahun, add current year
        if (empty($master_data['tahun_data'])) {
            $current_year = date('Y');
            try {
                $stmt = $pdo->prepare("INSERT INTO master_tahun (year_id, tahun) VALUES (:year_id, :tahun)");
                $stmt->execute(['year_id' => $current_year, 'tahun' => $current_year]);
                // PERUBAHAN: struktur array fallback menggunakan 'tahun'
                $master_data['tahun_data'] = [['tahun' => $current_year]];
            } catch (PDOException $e) {
                // If insert fails, use fallback
                $master_data['tahun_data'] = [['tahun' => $current_year]];
            }
        }
    } catch (PDOException $e) {
        // Fallback to current year if there's an error
        $current_year = date('Y');
        // PERUBAHAN: struktur array fallback menggunakan 'tahun'
        $master_data['tahun_data'] = [['tahun' => $current_year]];
    }
    
    return $master_data;
}

function getFilteredDocuments($filter_jurusan = '', $filter_prodi = '', $filter_tahun = '', $filter_tema = '') {
    global $pdo;
    $documents_data = [];
    
    // Query yang disesuaikan dengan struktur database, menampilkan SEMUA dokumen yang dipublikasi
    // TANPA batasan waktu, agar filter bisa menampilkan semua dokumen yang sesuai
    $query = "
        SELECT 
            d.dokumen_id, 
            d.judul, 
            d.abstrak,
            d.kata_kunci,
            d.file_path,
            d.tgl_unggah,
            d.status_id,
            d.turnitin,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count,
            (SELECT nama_divisi FROM master_divisi WHERE id_divisi = d.id_divisi) AS nama_divisi,
            (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
            (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
            (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
            (SELECT tahun FROM master_tahun WHERE year_id = d.year_id) AS tahun -- PERUBAHAN: ambil kolom 'tahun'
        FROM dokumen d
        LEFT JOIN users u ON d.uploader_id = u.id_user
        WHERE d.status_id = 5
    ";

    $params = [];
    
    // Filter berdasarkan jurusan
    if (!empty($filter_jurusan)) {
        $query .= " AND d.id_jurusan = :filter_jurusan";
        $params[':filter_jurusan'] = $filter_jurusan;
    }
    
    // Filter berdasarkan prodi
    if (!empty($filter_prodi)) {
        $query .= " AND d.id_prodi = :filter_prodi";
        $params[':filter_prodi'] = $filter_prodi;
    }
    
    // Filter berdasarkan tahun -- PERUBAHAN: filter berdasarkan nilai 'tahun', bukan 'year_id'
    if (!empty($filter_tahun)) {
        $query .= " AND d.year_id = (SELECT year_id FROM master_tahun WHERE tahun = :filter_tahun)";
        $params[':filter_tahun'] = $filter_tahun;
    }
    
    // Filter berdasarkan tema
    if (!empty($filter_tema)) {
        $query .= " AND d.id_tema = :filter_tema";
        $params[':filter_tema'] = $filter_tema;
    }

    // Urutkan berdasarkan tanggal unggah terbaru
    $query .= " ORDER BY d.tgl_unggah DESC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $documents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $documents_data = [];
        // Tampilkan error untuk debugging
        error_log("Error fetching filtered documents: " . $e->getMessage());
    }
    
    return $documents_data;
}

function getDocumentById($dokumen_id) {
    global $pdo;
    if (empty($dokumen_id)) {
        return null;
    }

    try {
        $query = "
            SELECT 
                d.dokumen_id, 
                d.judul, 
                d.abstrak,
                d.kata_kunci,
                d.file_path,
                d.tgl_unggah,
                d.status_id,
                d.turnitin,
                d.id_divisi,
                d.id_jurusan,
                d.id_prodi,
                d.id_tema,
                d.year_id,
                u.username as uploader_name,
                (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count,
                (SELECT nama_divisi FROM master_divisi WHERE id_divisi = d.id_divisi) AS nama_divisi,
                (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
                (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
                (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
                (SELECT tahun FROM master_tahun WHERE year_id = d.year_id) AS tahun 
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            WHERE d.dokumen_id = :id AND d.status_id = 5
            LIMIT 1
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $dokumen_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching document by ID: " . $e->getMessage());
        return null;
    }
}