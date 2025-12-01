<?php 
session_start();
require_once __DIR__ . '/../config/db.php';

$kolomPassword = "password_hash";

// Jika POST datang dari konfirmasi YES
if (isset($_POST['confirm']) && $_POST['confirm'] === "yes") {

    $id_user = $_POST['id_user'];
    $password_baru = $_POST['password_baru'];

    // Hash password baru
    $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);

    // Update password
    $update = $pdo->prepare("UPDATE users SET $kolomPassword = ? WHERE id_user = ?");
    $update->execute([$password_baru_hash, $id_user]);

    header("Location: profil.php?msg=password_updated");
    exit;
}


// Tahap awal: user submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['confirm'])) {

    $id_user = $_POST['id_user'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    if ($password_baru !== $konfirmasi) {
        header("Location: profil.php?msg=confirm_failed");
        exit;
    }

    // Ambil hash di database
    $stmt = $pdo->prepare("SELECT $kolomPassword FROM users WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: profil.php?msg=user_not_found");
        exit;
    }

    if (!password_verify($password_lama, $user[$kolomPassword])) {
        header("Location: profil.php?msg=wrong_old_password");
        exit;
    }

    // Jika lolos semua, tampilkan POPUP konfirmasi — *tanpa pindah halaman*
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Konfirmasi</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>

    <script>
        Swal.fire({
            title: "Yakin ingin mengubah password?",
            text: "Password baru akan langsung menggantikan password lama.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, ubah!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                
                // Buat form POST kedua untuk melakukan update password
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "ubah_password.php";

                form.innerHTML = `
                    <input type="hidden" name="confirm" value="yes">
                    <input type="hidden" name="id_user" value="<?= $id_user ?>">
                    <input type="hidden" name="password_baru" value="<?= $password_baru ?>">
                `;

                document.body.appendChild(form);
                form.submit();

            } else {
                window.location.href = "profil.php?msg=password_cancelled";
            }
        });
    </script>

    </body>
    </html>

    <?php
    exit;
}
?>
