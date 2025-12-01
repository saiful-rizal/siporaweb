<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/config.php';

$response = ['success' => false, 'exists' => false, 'fields' => [], 'message' => ''];

try {
    $data = $_POST;
    if (empty($data)) {
        $body = file_get_contents('php://input');
        $json = json_decode($body, true);
        if (is_array($json)) $data = $json;
    }

    $email = isset($data['email']) ? strtolower(trim($data['email'])) : null;
    $username = isset($data['username']) ? strtolower(trim($data['username'])) : null;
    $nama_lengkap = isset($data['nama_lengkap']) ? strtolower(trim($data['nama_lengkap'])) : null;

    if (!$email && !$username && !$nama_lengkap) {
        $response['message'] = 'Harap berikan parameter email atau username.';
        echo json_encode($response);
        exit;
    }

    $clauses = [];
    $params = [];
    if ($email) {
        $clauses[] = 'LOWER(email) = :email';
        $params['email'] = $email;
    }
    if ($username) {
        $clauses[] = 'LOWER(username) = :username';
        $params['username'] = $username;
    }
    if ($nama_lengkap) {
        $clauses[] = 'LOWER(nama_lengkap) = :nama_lengkap';
        $params['nama_lengkap'] = $nama_lengkap;
    }

    $sql = 'SELECT email, username FROM users WHERE ' . implode(' OR ', $clauses) . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($found) {
        $response['success'] = true;
        $response['exists'] = true;

        if ($email && isset($found['email']) && strtolower(trim($found['email'])) === $email) {
            $response['fields'][] = 'email';
        }
        if ($username && isset($found['username']) && strtolower(trim($found['username'])) === $username) {
            $response['fields'][] = 'username';
        }
        if ($nama_lengkap && isset($found['nama_lengkap']) && strtolower(trim($found['nama_lengkap'])) === $nama_lengkap) {
            $response['fields'][] = 'nama_lengkap';
        }

        if (empty($response['fields'])) {
            // entry found but didn't match exactly - still treat as exists
            $response['fields'] = ['email_or_username'];
        }
        $response['message'] = 'Sudah terdaftar';
    } else {
        $response['success'] = true;
        $response['exists'] = false;
        $response['message'] = 'Belum terdaftar';
    }

} catch (PDOException $e) {
    error_log('check_user_exists error: ' . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Terjadi kesalahan pada server';
}

echo json_encode($response);
