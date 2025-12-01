<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nim = trim($_POST['nim']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password_hash = password_hash($_POST['password_hash'], PASSWORD_DEFAULT);
    $role = 'admin';
    $status = 'approved';

    try {
        // Cek duplikat username/email
        $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $cek->execute([':username' => $username, ':email' => $email]);

        if ($cek->fetchColumn() > 0) {
            header("Location: form_admin.php?error=duplicate");
            exit;
        }

        // Insert data admin
        $stmt = $pdo->prepare("
            INSERT INTO users (nama_lengkap, nim, email, username, password_hash, role, status)
            VALUES (:nama_lengkap, :nim, :email, :username, :password_hash, :role, :status)
        ");

        $stmt->execute([
            ':nama_lengkap' => $nama_lengkap,
            ':nim' => $nim,
            ':email' => $email,
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':role' => $role,
            ':status' => $status
        ]);

        header("Location: form_admin.php?success=1");
        exit;
    } catch (PDOException $e) {
        header("Location: form_admin.php?error=db");
        exit;
    }
} else {
    header("Location: form_admin.php");
    exit;
}
