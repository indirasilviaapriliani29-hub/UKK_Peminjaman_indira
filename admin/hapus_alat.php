<?php
include "../koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: alat.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT foto FROM alat WHERE id_alat = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$alat = mysqli_fetch_assoc($result);

if (!$alat) {
    header("Location: alat.php?pesan=tidak_ditemukan");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM alat WHERE id_alat = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (!empty($alat['foto'])) {
        $foto = "../assets/img/" . basename($alat['foto']);

        if (is_file($foto)) {
            unlink($foto);
        }
    }

    header("Location: alat.php?pesan=hapus");
    exit;
}

echo "Data alat gagal dihapus. Pastikan alat tersebut tidak sedang digunakan dalam transaksi peminjaman.";
echo '<br><a href="alat.php">Kembali ke Data Alat</a>';
?>