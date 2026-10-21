
<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['id']) ||
    !is_numeric($_POST['id'])) {
    header("Location: pengguna.php");
    exit;
}

$id = (int) $_POST['id'];

// Mencegah akun yang sedang login dihapus
if (isset($_SESSION['id_user']) &&
    (int) $_SESSION['id_user'] === $id) {
    header("Location: pengguna.php?pesan=akun_sendiri");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM users WHERE id_user = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        header("Location: pengguna.php?pesan=hapus");
        exit;
    }

    header("Location: pengguna.php?pesan=tidak_ditemukan");
    exit;
}

$error = mysqli_stmt_error($stmt);

echo "<script>
    alert(" . json_encode("Gagal menghapus pengguna: " . $error) . ");
    window.location.href = 'pengguna.php';
</script>";
exit;
?>
