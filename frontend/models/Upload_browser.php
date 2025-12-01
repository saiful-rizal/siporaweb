<?php
// models/UploadModel.php

class UploadModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all master data for dropdowns
     */
    public function getMasterData() {
        $data = [];
        
        try {
            // Get divisions
            $stmt = $this->pdo->query("SELECT id_divisi, nama_divisi FROM master_divisi ORDER BY nama_divisi");
            $data['divisi'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $data['divisi'] = [];
        }

        try {
            // Get jurusan
            $stmt = $this->pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan");
            $data['jurusan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $data['jurusan'] = [];
        }

        try {
            // Get prodi
            $stmt = $this->pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi");
            $data['prodi'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $data['prodi'] = [];
        }

        try {
            // Get tema
            $stmt = $this->pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema");
            $data['tema'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $data['tema'] = [];
        }

        try {
            // Get tahun - PERBAIKAN: Mengambil kolom 'tahun' juga
            $stmt = $this->pdo->query("SELECT year_id, tahun FROM master_tahun ORDER BY tahun DESC");
            $data['tahun'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data['tahun'])) {
                $current_year = date('Y');
                $this->ensureYearExists($current_year);
                $data['tahun'] = [['year_id' => $current_year, 'tahun' => $current_year]];
            }
        } catch (PDOException $e) {
            $data['tahun'] = [['year_id' => date('Y'), 'tahun' => date('Y')]];
        }

        return $data;
    }

    /**
     * Get documents uploaded by a specific user
     */
    public function getUserDocuments($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, 
                       (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
                       (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
                       (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
                       (SELECT tahun FROM master_tahun WHERE year_id = d.year_id) AS tahun
                FROM dokumen d 
                WHERE d.uploader_id = :user_id 
                ORDER BY d.tgl_unggah DESC
            ");
            $stmt->execute(['user_id' => $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get all documents with optional filters (for browser page)
     */
    public function getDocuments($filter_jurusan = '', $filter_prodi = '', $filter_tema = '', $filter_tahun = '') {
        $query = "
            SELECT 
                d.*,
                u.username as uploader_name,
                (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
                (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
                (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
                (SELECT tahun FROM master_tahun WHERE year_id = d.year_id) AS tahun
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            WHERE d.status_id = 5
        ";
        
        $params = [];
        if (!empty($filter_jurusan)) {
            $query .= " AND d.id_jurusan = :jurusan";
            $params['jurusan'] = $filter_jurusan;
        }
        if (!empty($filter_prodi)) {
            $query .= " AND d.id_prodi = :prodi";
            $params['prodi'] = $filter_prodi;
        }
        if (!empty($filter_tahun)) {
            $query .= " AND d.year_id = :tahun";
            $params['tahun'] = $filter_tahun;
        }
        if (!empty($filter_tema)) {
            $query .= " AND d.id_tema = :tema";
            $params['tema'] = $filter_tema;
        }

        $query .= " ORDER BY d.tgl_unggah DESC";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Return empty array on error
            return [];
        }
    }

    /**
     * Get a single document by ID
     */
    public function getDocumentById($id) {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.username as uploader_name, u.email as uploader_email,
                   j.nama_jurusan, p.nama_prodi, t.nama_tema, th.tahun
            FROM dokumen d
            LEFT JOIN users u ON d.uploader_id = u.id_user
            LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
            LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN master_tema t ON d.id_tema = t.id_tema
            LEFT JOIN master_tahun th ON d.year_id = th.year_id
            WHERE d.id_dokumen = :id
        ");
    
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Upload a new document
     */
    public function uploadDocument($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO dokumen (
                judul, abstrak, kata_kunci, id_divisi, id_jurusan, id_prodi, 
                id_tema, year_id, file_path, uploader_id, status_id, turnitin, tgl_unggah
            ) VALUES (
                :judul, :abstrak, :kata_kunci, :id_divisi, :id_jurusan, :id_prodi, 
                :id_tema, :year_id, :file_path, :uploader_id, :status_id, :turnitin, NOW()
            )
        ");
        
        return $stmt->execute([
            'judul' => $data['judul'],
            'abstrak' => $data['abstrak'],
            'kata_kunci' => $data['kata_kunci'],
            'id_divisi' => $data['id_divisi'],
            'id_jurusan' => $data['id_jurusan'],
            'id_prodi' => $data['id_prodi'],
            'id_tema' => $data['id_tema'],
            'year_id' => $data['year_id'],
            'file_path' => $data['file_path'],
            'uploader_id' => $data['uploader_id'],
            'status_id' => $data['status_id'],
            'turnitin' => $data['turnitin']
        ]);
    }

    /**
     * Ensure a year exists in master_tahun table
     */
    public function ensureYearExists($year_id) {
        // Use INSERT IGNORE to prevent errors if year already exists
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO master_tahun (year_id, tahun) VALUES (:year_id, :tahun)");
        return $stmt->execute(['year_id' => $year_id, 'tahun' => $year_id]);
    }

    /**
     * Get documents based on user role
     */
    public function getDocumentsByUser($user_id, $user_role = '') {
        try {
            // Jika user adalah admin, ambil semua dokumen
            if ($user_role === 'admin') {
                $stmt = $this->pdo->prepare("
                    SELECT d.*, u.username as uploader_name
                    FROM dokumen d
                    LEFT JOIN users u ON d.uploader_id = u.id_user
                    ORDER BY d.tgl_unggah DESC
                ");
                $stmt->execute();
            } else {
                // Jika bukan admin, hanya ambil dokumen milik user tersebut
                $stmt = $this->pdo->prepare("
                    SELECT d.*, u.username as uploader_name
                    FROM dokumen d
                    LEFT JOIN users u ON d.uploader_id = u.id_user
                    WHERE d.uploader_id = :user_id
                    ORDER BY d.tgl_unggah DESC
                ");
                $stmt->execute(['user_id' => $user_id]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get statistics based on user role
     */
    public function getStatisticsByUser($user_id, $user_role = '') {
        try {
            // Jika user adalah admin, ambil statistik global
            if ($user_role === 'admin') {
                return $this->getStatistics();
            } else {
                // Jika bukan admin, hanya ambil statistik untuk user tersebut
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN MONTH(tgl_unggah) = MONTH(CURRENT_DATE()) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month,
                        (SELECT COUNT(*) FROM download_history dl JOIN dokumen d ON dl.id_dokumen = d.id_dokumen 
                         WHERE d.uploader_id = :user_id AND MONTH(dl.tanggal) = MONTH(CURRENT_DATE()) AND YEAR(dl.tanggal) = YEAR(CURRENT_DATE())) as downloads_this_month,
                        1 as active_users
                    FROM dokumen
                    WHERE uploader_id = :user_id
                ");
                $stmt->execute(['user_id' => $user_id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return [
                'total' => 0,
                'this_month' => 0,
                'downloads_this_month' => 0,
                'active_users' => 0
            ];
        }
    }
    
    /**
     * Get various statistics for dashboard
     */
    public function getStatistics() {
        $stats = [
            'total' => 0,
            'this_month' => 0,
            'downloads_this_month' => 0,
            'active_users' => 0
        ];

        try {
            // Total published documents
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM dokumen WHERE status_id = 5");
            $stats['total'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Keep default value
        }

        try {
            // New uploads this month
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM dokumen WHERE status_id = 5 AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE()) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE())");
            $stats['this_month'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Keep default value
        }

        // Check if download_history table exists before querying
        try {
            $cekDownload = $this->pdo->query("SHOW TABLES LIKE 'download_history'")->fetch();
            if ($cekDownload) {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM download_history WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
                $stats['downloads_this_month'] = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // Keep default value
        }
        
        // Check if riwayat_login table exists before querying
        try {
            $cekLogin = $this->pdo->query("SHOW TABLES LIKE 'riwayat_login'")->fetch();
            if ($cekLogin) {
                $stmt = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM riwayat_login WHERE MONTH(tanggal_login) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_login) = YEAR(CURRENT_DATE())");
                $stats['active_users'] = $stmt->fetchColumn();
            }
            
        } catch (PDOException $e) {
            // Keep default value
        }
        

        return $stats;
    }
}