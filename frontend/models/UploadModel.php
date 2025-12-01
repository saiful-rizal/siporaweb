<?php
class UploadModel {
    private $pdo;
    private $tableDokumen = 'dokumen';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
   public function getMasterData() {
    $data = [];
    
    // Get divisi data
    try {
        $stmt = $this->pdo->query("SELECT id_divisi, nama_divisi FROM master_divisi ORDER BY nama_divisi");
        $data['divisi'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['divisi'] = [];
    }
    
    // Get jurusan data
    try {
        $stmt = $this->pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan");
        $data['jurusan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['jurusan'] = [];
    }
    
    // Get prodi data
    try {
        $stmt = $this->pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi");
        $data['prodi'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['prodi'] = [];
    }
    
    // Get tema data
    try {
        $stmt = $this->pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema");
        $data['tema'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data['tema'] = [];
    }
    
    // Get tahun data
    try {
        // Perubahan: Mengambil kedua kolom year_id dan tahun
        $stmt = $this->pdo->query("SELECT year_id, tahun FROM master_tahun ORDER BY tahun DESC");
        $data['tahun'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If master_tahun doesn't exist, create default years
        $currentYear = date('Y');
        $years = [];
        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
            // Perubahan: Membuat array dengan kedua kunci 'year_id' dan 'tahun'
            $years[] = ['year_id' => $i, 'tahun' => $i];
        }
        $data['tahun'] = $years;
    }
    
    return $data;
}
    
    public function getUserDocuments($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username as uploader_name, 
                       COALESCE(s.nama_status, 'Unknown') as nama_status, 
                       COALESCE(t.nama_tema, 'Unknown') as nama_tema, 
                       COALESCE(j.nama_jurusan, 'Unknown') as nama_jurusan, 
                       COALESCE(p.nama_prodi, 'Unknown') as nama_prodi, 
                       COALESCE(dv.nama_divisi, 'Unknown') as nama_divisi,
                       COALESCE(mt.year_id, d.year_id) as tahun
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                LEFT JOIN master_tahun mt ON d.year_id = mt.year_id
                WHERE d.uploader_id = :user_id
                ORDER BY d.tgl_unggah DESC
            ");
            $stmt->execute(['user_id' => $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user documents: " . $e->getMessage());
            return [];
        }
    }
    
    public function ensureYearExists($year_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT year_id FROM master_tahun WHERE year_id = :year_id");
            $stmt->execute(['year_id' => $year_id]);
            
            if ($stmt->rowCount() === 0) {
                $stmt = $this->pdo->prepare("INSERT INTO master_tahun (year_id) VALUES (:year_id)");
                $stmt->execute(['year_id' => $year_id]);
            }
        } catch (PDOException $e) {
            // If master_tahun doesn't exist, we'll handle it gracefully
            error_log("Year table check failed: " . $e->getMessage());
        }
    }
    
    public function uploadDocument($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tableDokumen} (
                    judul, abstrak, kata_kunci, id_divisi, id_jurusan, id_prodi, 
                    id_tema, year_id, file_path, turnitin_file, uploader_id, status_id, turnitin, tgl_unggah
                ) VALUES (
                    :judul, :abstrak, :kata_kunci, :id_divisi, :id_jurusan, :id_prodi,
                    :id_tema, :year_id, :file_path, :turnitin_file, :uploader_id, :status_id, :turnitin, NOW()
                )
            ");
            
            $stmt->execute([
                'judul' => $data['judul'],
                'abstrak' => $data['abstrak'],
                'kata_kunci' => $data['kata_kunci'],
                'id_divisi' => $data['id_divisi'],
                'id_jurusan' => $data['id_jurusan'],
                'id_prodi' => $data['id_prodi'],
                'id_tema' => $data['id_tema'],
                'year_id' => $data['year_id'],
                'file_path' => $data['file_path'],
                'turnitin_file' => $data['turnitin_file'] ?? null, // Menambahkan turnitin_file
                'uploader_id' => $data['uploader_id'],
                'status_id' => $data['status_id'],
                'turnitin' => $data['turnitin'] ?? 0
            ]);
            
            return $this->pdo->lastInsertId() > 0;
        } catch (PDOException $e) {
            error_log("Error uploading document: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDocumentById($document_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username as uploader_name, u.email as uploader_email,
                       s.nama_status, t.nama_tema, j.nama_jurusan, p.nama_prodi, dv.nama_divisi,
                       COALESCE(mt.year_id, d.year_id) as tahun
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                LEFT JOIN master_tahun mt ON d.year_id = mt.year_id
                WHERE d.dokumen_id = :document_id
            ");
            $stmt->execute(['document_id' => $document_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting document: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateDocumentStatus($document_id, $status_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableDokumen} 
                SET status_id = :status_id
                WHERE dokumen_id = :document_id
            ");
                $success = $stmt->execute([
                    'status_id' => $status_id,
                    'document_id' => $document_id
                ]);

                if ($success) {
                    try {
                        // Fetch document info to notify uploader
                        $stmtDoc = $this->pdo->prepare("SELECT judul, uploader_id FROM {$this->tableDokumen} WHERE dokumen_id = :document_id LIMIT 1");
                        $stmtDoc->execute(['document_id' => $document_id]);
                        $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

                        if ($doc) {
                            // Get status name if available
                            $stmtStatus = $this->pdo->prepare("SELECT nama_status FROM master_status WHERE id_status = :status_id LIMIT 1");
                            $stmtStatus->execute(['status_id' => $status_id]);
                            $s = $stmtStatus->fetch(PDO::FETCH_ASSOC);
                            $statusName = $s ? $s['nama_status'] : 'Unknown';

                            // Ensure notifications table exists
                            $createSql = "CREATE TABLE IF NOT EXISTS notifications (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT DEFAULT NULL,
                                actor_id INT DEFAULT NULL,
                                doc_id INT DEFAULT NULL,
                                type VARCHAR(50) DEFAULT NULL,
                                title VARCHAR(255) DEFAULT NULL,
                                message TEXT DEFAULT NULL,
                                icon_type VARCHAR(50) DEFAULT NULL,
                                icon_class VARCHAR(255) DEFAULT NULL,
                                status_name VARCHAR(100) DEFAULT NULL,
                                is_read TINYINT(1) DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                            $this->pdo->exec($createSql);

                            // Choose icon based on status (basic mapping)
                            $icon_type = ($status_id == 5) ? 'success' : 'info';
                            $icon_class = ($status_id == 5) ? 'fas fa-check-circle text-success' : 'fas fa-info-circle text-info';

                            $title = 'Perubahan Status Dokumen';
                            $message = "Status dokumen '" . ($doc['judul'] ?? 'Dokumen') . "' telah berubah menjadi " . $statusName . ".";

                            $stmtIns = $this->pdo->prepare("INSERT INTO notifications (user_id, actor_id, doc_id, type, title, message, status_name, icon_type, icon_class, is_read) VALUES (:user_id, :actor_id, :doc_id, :type, :title, :message, :status_name, :icon_type, :icon_class, 0)");
                            $stmtIns->execute([
                                'user_id' => $doc['uploader_id'],
                                'actor_id' => null,
                                'doc_id' => $document_id,
                                'type' => 'status_change',
                                'title' => $title,
                                'message' => $message,
                                'status_name' => $statusName,
                                'icon_type' => $icon_type,
                                'icon_class' => $icon_class
                            ]);
                        }
                    } catch (PDOException $e) {
                        error_log("Error creating notification after status update: " . $e->getMessage());
                    }
                }

                return $success;
        } catch (PDOException $e) {
            error_log("Error updating document status: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteDocument($document_id) {
        try {
            // Get document info first
            $stmt = $this->pdo->prepare("
                SELECT file_path, turnitin_file FROM {$this->tableDokumen} 
                WHERE dokumen_id = :document_id
            ");
            $stmt->execute(['document_id' => $document_id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete physical files
            if ($document) {
                // Delete main document file
                if (file_exists(__DIR__ . '/../uploads/documents/' . $document['file_path'])) {
                    unlink(__DIR__ . '/../uploads/documents/' . $document['file_path']);
                }
                
                // Delete turnitin file if exists
                if (!empty($document['turnitin_file']) && file_exists(__DIR__ . '/../uploads/turnitin/' . $document['turnitin_file'])) {
                    unlink(__DIR__ . '/../uploads/turnitin/' . $document['turnitin_file']);
                }
            }
            
            // Delete from database
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tableDokumen} 
                WHERE dokumen_id = :document_id
            ");
            return $stmt->execute(['document_id' => $document_id]);
        } catch (PDOException $e) {
            error_log("Error deleting document: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllDocuments($limit = 10, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username as uploader_name, 
                       s.nama_status, t.nama_tema, j.nama_jurusan, p.nama_prodi, dv.nama_divisi,
                       COALESCE(mt.year_id, d.year_id) as tahun
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                LEFT JOIN master_tahun mt ON d.year_id = mt.year_id
                ORDER BY d.tgl_unggah DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all documents: " . $e->getMessage());
            return [];
        }
    }

    public function getAllDocumentsWithTurnitin() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username as uploader_name, u.email as uploader_email,
                       s.nama_status, t.nama_tema, j.nama_jurusan, p.nama_prodi, dv.nama_divisi,
                       COALESCE(mt.year_id, d.year_id) as tahun
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                LEFT JOIN master_tahun mt ON d.year_id = mt.year_id
                ORDER BY d.tgl_unggah DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all documents: " . $e->getMessage());
            return [];
        }
    }
    
    public function searchDocuments($keyword, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.username as uploader_name, 
                       s.nama_status, t.nama_tema, j.nama_jurusan, p.nama_prodi, dv.nama_divisi
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                WHERE d.judul LIKE :keyword 
                   OR d.abstrak LIKE :keyword 
                   OR d.kata_kunci LIKE :keyword
                   OR u.username LIKE :keyword
                ORDER BY d.tgl_unggah DESC
                LIMIT :limit OFFSET :offset
            ");
            $searchTerm = '%' . $keyword . '%';
            $stmt->bindValue(':keyword', $searchTerm);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching documents: " . $e->getMessage());
            return [];
        }
    }

    public function getUploadHistory($user_id) {
        try {
            // Cek apakah tabel dokumen ada
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'dokumen'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                error_log("Table 'dokumen' does not exist");
                return [];
            }
            
            // Cek struktur tabel
            $stmt = $this->pdo->prepare("DESCRIBE dokumen");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Query dasar tanpa JOIN untuk menghindari error
            $sql = "
                SELECT 
                    dokumen_id, 
                    judul, 
                    abstrak, 
                    kata_kunci, 
                    id_divisi, 
                    id_jurusan, 
                    id_prodi, 
                    id_tema, 
                    year_id, 
                    file_path, 
                    turnitin_file,
                    uploader_id, 
                    status_id, 
                    turnitin, 
                    tgl_unggah
                FROM dokumen 
                WHERE uploader_id = :user_id 
                ORDER BY tgl_unggah DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data dan tambahkan data dummy untuk menghindari error
            foreach ($results as &$result) {
                $result['upload_date'] = $result['tgl_unggah'];
                $result['uploader_name'] = 'Unknown';
                $result['nama_status'] = 'Unknown';
                $result['nama_tema'] = 'Unknown';
                $result['nama_jurusan'] = 'Unknown';
                $result['nama_prodi'] = 'Unknown';
                $result['nama_divisi'] = 'Unknown';
                // Pastikan ada field 'tahun' yang berisi nilai tahun (bukan id)
                $result['tahun'] = isset($result['year_id']) ? $result['year_id'] : (isset($result['year']) ? $result['year'] : null);
                
                // Coba ambil data dari tabel referensi jika ada
                try {
                    // Ambil username
                    if (in_array('users', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
                        $stmt_user = $this->pdo->prepare("SELECT username FROM users WHERE id_user = :user_id LIMIT 1");
                        $stmt_user->execute(['user_id' => $result['uploader_id']]);
                        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                        if ($user) {
                            $result['uploader_name'] = $user['username'];
                        }
                    }
                    
                    // Ambil status
                    if (in_array('master_status', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
                        $stmt_status = $this->pdo->prepare("SELECT nama_status FROM master_status WHERE id_status = :status_id LIMIT 1");
                        $stmt_status->execute(['status_id' => $result['status_id']]);
                        $status = $stmt_status->fetch(PDO::FETCH_ASSOC);
                        if ($status) {
                            $result['nama_status'] = $status['nama_status'];
                        }
                    }
                    
                    // Ambil tema
                    if (in_array('master_tema', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) && $result['id_tema']) {
                        $stmt_tema = $this->pdo->prepare("SELECT nama_tema FROM master_tema WHERE id_tema = :id_tema LIMIT 1");
                        $stmt_tema->execute(['id_tema' => $result['id_tema']]);
                        $tema = $stmt_tema->fetch(PDO::FETCH_ASSOC);
                        if ($tema) {
                            $result['nama_tema'] = $tema['nama_tema'];
                        }
                    }
                    
                    // Ambil jurusan
                    if (in_array('master_jurusan', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) && $result['id_jurusan']) {
                        $stmt_jurusan = $this->pdo->prepare("SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = :id_jurusan LIMIT 1");
                        $stmt_jurusan->execute(['id_jurusan' => $result['id_jurusan']]);
                        $jurusan = $stmt_jurusan->fetch(PDO::FETCH_ASSOC);
                        if ($jurusan) {
                            $result['nama_jurusan'] = $jurusan['nama_jurusan'];
                        }
                    }
                    
                    // Ambil prodi
                    if (in_array('master_prodi', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) && $result['id_prodi']) {
                        $stmt_prodi = $this->pdo->prepare("SELECT nama_prodi FROM master_prodi WHERE id_prodi = :id_prodi LIMIT 1");
                        $stmt_prodi->execute(['id_prodi' => $result['id_prodi']]);
                        $prodi = $stmt_prodi->fetch(PDO::FETCH_ASSOC);
                        if ($prodi) {
                            $result['nama_prodi'] = $prodi['nama_prodi'];
                        }
                    }
                    
                    // Ambil divisi
                    if (in_array('master_divisi', $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) && $result['id_divisi']) {
                        $stmt_divisi = $this->pdo->prepare("SELECT nama_divisi FROM master_divisi WHERE id_divisi = :id_divisi LIMIT 1");
                        $stmt_divisi->execute(['id_divisi' => $result['id_divisi']]);
                        $divisi = $stmt_divisi->fetch(PDO::FETCH_ASSOC);
                        if ($divisi) {
                            $result['nama_divisi'] = $divisi['nama_divisi'];
                        }
                    }
                } catch (Exception $e) {
                    // Abaikan error dan tetap gunakan data dummy
                    error_log("Error getting reference data: " . $e->getMessage());
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Database error getting upload history: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("General error getting upload history: " . $e->getMessage());
            return [];
        }
    }

    public function getAllDocumentsForBrowser($search = '', $theme_filter = 'all', $year_filter = 'all', $divisi_filter = 'all', $jurusan_filter = 'all', $prodi_filter = 'all', $turnitin_filter = 'all', $status_filter = 'all') {
        try {
            $sql = "
                SELECT d.*, u.username as uploader_name, 
                       COALESCE(s.nama_status, 'Unknown') as nama_status, 
                       COALESCE(t.nama_tema, 'Unknown') as nama_tema, 
                       COALESCE(j.nama_jurusan, 'Unknown') as nama_jurusan, 
                       COALESCE(p.nama_prodi, 'Unknown') as nama_prodi, 
                       COALESCE(dv.nama_divisi, 'Unknown') as nama_divisi,
                       COALESCE(mt.year_id, d.year_id) as tahun
                FROM {$this->tableDokumen} d
                LEFT JOIN users u ON d.uploader_id = u.id_user
                LEFT JOIN master_status s ON d.status_id = s.id_status
                LEFT JOIN master_tema t ON d.id_tema = t.id_tema
                LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
                LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
                LEFT JOIN master_divisi dv ON d.id_divisi = dv.id_divisi
                LEFT JOIN master_tahun mt ON d.year_id = mt.year_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add search condition
            if (!empty($search)) {
                $sql .= " AND (d.judul LIKE :search OR d.abstrak LIKE :search OR d.kata_kunci LIKE :search)";
                $params['search'] = '%' . $search . '%';
            }
            
            // Add theme filter
            if ($theme_filter !== 'all') {
                $sql .= " AND d.id_tema = :theme";
                $params['theme'] = $theme_filter;
            }
            
            // Add year filter
            if ($year_filter !== 'all') {
                $sql .= " AND d.year_id = :year";
                $params['year'] = $year_filter;
            }
            
            // Add divisi filter
            if ($divisi_filter !== 'all') {
                $sql .= " AND d.id_divisi = :divisi";
                $params['divisi'] = $divisi_filter;
            }
            
            // Add jurusan filter
            if ($jurusan_filter !== 'all') {
                $sql .= " AND d.id_jurusan = :jurusan";
                $params['jurusan'] = $jurusan_filter;
            }
            
            // Add prodi filter
            if ($prodi_filter !== 'all') {
                $sql .= " AND d.id_prodi = :prodi";
                $params['prodi'] = $prodi_filter;
            }
            
            // Add turnitin filter
            if ($turnitin_filter !== 'all') {
                switch($turnitin_filter) {
                    case 'none':
                        $sql .= " AND (d.turnitin IS NULL OR d.turnitin = 0)";
                        break;
                    case 'low':
                        $sql .= " AND d.turnitin > 0 AND d.turnitin <= 20";
                        break;
                    case 'medium':
                        $sql .= " AND d.turnitin > 20 AND d.turnitin <= 40";
                        break;
                    case 'high':
                        $sql .= " AND d.turnitin > 40";
                        break;
                }
            }
            
            // Add status filter
            if ($status_filter !== 'all') {
                $sql .= " AND d.status_id = :status";
                $params['status'] = $status_filter;
            }
            
            $sql .= " ORDER BY d.tgl_unggah DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting documents for browser: " . $e->getMessage());
            return [];
        }
    }

    public function getDocuments($filter_jurusan = '', $filter_prodi = '', $filter_tema = '', $filter_tahun = '', $limit = 20, $offset = 0) {
        $query = "
            SELECT 
                d.*,
                u.username as uploader_name,
                u.email as uploader_email,
                (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS nama_jurusan,
                (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS nama_prodi,
                (SELECT nama_tema FROM master_tema WHERE id_tema = d.id_tema) AS nama_tema,
                (SELECT year_id FROM master_tahun WHERE year_id = d.year_id) AS tahun,
                (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count
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

        $query .= " ORDER BY d.tgl_unggah DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting documents: " . $e->getMessage());
            return [];
        }
    }

    public function getDocumentsCount($filter_jurusan = '', $filter_prodi = '', $filter_tema = '', $filter_tahun = '') {
        $query = "
            SELECT COUNT(*) as total
            FROM dokumen d
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

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting documents count: " . $e->getMessage());
            return 0;
        }
    }

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
            error_log("Error getting total documents: " . $e->getMessage());
        }

        try {
            // New uploads this month
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM dokumen WHERE status_id = 5 AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE()) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE())");
            $stats['this_month'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting this month uploads: " . $e->getMessage());
        }

        // Check if download_history table exists before querying
        try {
            $cekDownload = $this->pdo->query("SHOW TABLES LIKE 'download_history'")->fetch();
            if ($cekDownload) {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM download_history WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
                $stats['downloads_this_month'] = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log("Error getting downloads this month: " . $e->getMessage());
        }
        
        // Check if riwayat_login table exists before querying
        try {
            $cekLogin = $this->pdo->query("SHOW TABLES LIKE 'riwayat_login'")->fetch();
            if ($cekLogin) {
                $stmt = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM riwayat_login WHERE MONTH(tanggal_login) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_login) = YEAR(CURRENT_DATE())");
                $stats['active_users'] = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log("Error getting active users: " . $e->getMessage());
        }

        return $stats;
    }

/**
 * Get download history for a specific user
 */
public function getUserDownloadHistory($user_id) {
    try {
        // Check if download_history table exists
        $tableExists = $this->pdo->query("SHOW TABLES LIKE 'download_history'")->fetch();
        
        if (!$tableExists) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                dh.tanggal,
                d.dokumen_id,
                d.judul,
                d.abstrak,
                u.username as uploader_name,
                COALESCE(j.nama_jurusan, 'Unknown') as nama_jurusan,
                COALESCE(p.nama_prodi, 'Unknown') as nama_prodi,
                COALESCE(t.nama_tema, 'Unknown') as nama_tema,
                d.year_id as tahun,
                d.file_path,
                d.status_id
            FROM download_history dh
            JOIN dokumen d ON dh.dokumen_id = d.dokumen_id
            LEFT JOIN users u ON d.uploader_id = u.id_user
            LEFT JOIN master_jurusan j ON d.id_jurusan = j.id_jurusan
            LEFT JOIN master_prodi p ON d.id_prodi = p.id_prodi
            LEFT JOIN master_tema t ON d.id_tema = t.id_tema
            WHERE dh.user_id = :user_id
            ORDER BY dh.tanggal DESC
            LIMIT 50
        ");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting download history: " . $e->getMessage());
        return [];
    }
}
}