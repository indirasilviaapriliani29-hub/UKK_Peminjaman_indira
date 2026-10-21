<?php
include "../koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kategori.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM kategori WHERE id_kategori = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: kategori.php?pesan=hapus");
    exit;
} else {
    echo "<script>
        alert('Kategori gagal dihapus! Kategori mungkin masih digunakan oleh data alat.');
        window.location.href = 'kategori.php';
    </script>";
    exit;
}
?>