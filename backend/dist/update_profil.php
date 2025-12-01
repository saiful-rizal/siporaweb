<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth.php");
    exit;
}

$kolomUser = "users";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_user = $_POST['id_user'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $nim = $_POST['nim'];
    $username = $_POST['username'];

    // Jika POST dari konfirmasi YES
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {

        $stmt = $pdo->prepare("UPDATE users SET nama_lengkap=?, email=?, nim=?, username=? WHERE id_user=?");
        $stmt->execute([$nama_lengkap, $email, $nim, $username, $id_user]);

        header("Location: profil.php?msg=profil_updated");
        exit;
    }

    // Tahap awal: tampilkan popup konfirmasi
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Konfirmasi Update Profil</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: "Yakin ingin memperbarui profil?",
            text: "Data profil akan diperbarui sesuai input.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, perbarui!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "update_profil.php";

                form.innerHTML = `
                    <input type="hidden" name="confirm" value="yes">
                    <input type="hidden" name="id_user" value="<?= $id_user ?>">
                    <input type="hidden" name="nama_lengkap" value="<?= htmlspecialchars($nama_lengkap, ENT_QUOTES) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>">
                    <input type="hidden" name="nim" value="<?= htmlspecialchars($nim, ENT_QUOTES) ?>">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES) ?>">
                `;

                document.body.appendChild(form);
                form.submit();
            } else {
                window.location.href = "profil.php?msg=update_cancelled";
            }
        });
    </script>
    </body>
    </html>
    <?php
    exit;
}
?>
