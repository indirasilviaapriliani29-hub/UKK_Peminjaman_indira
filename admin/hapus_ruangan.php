<?php
include "../koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ruangan.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT foto FROM ruangan WHERE id_ruangan = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: ruangan.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM ruangan WHERE id_ruangan = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (!empty($data['foto'])) {
        $foto = "../assets/img/" . basename($data['foto']);

        if (is_file($foto)) {
            unlink($foto);
        }
    }

    header("Location: ruangan.php?pesan=hapus");
    exit;
} else {
    echo "<script>
        alert('Ruangan gagal dihapus! Data ruangan mungkin masih digunakan.');
        window.location.href = 'ruangan.php';
    </script>";
    exit;
}
?>