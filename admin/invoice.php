<?php
include "../koneksi.php";

$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_user <= 0) {
    die("ID pengguna tidak valid. Silakan buka invoice dari menu Data Pengguna.");
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM users WHERE id_user = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    die("Pengguna dengan ID " . $id_user . " tidak ditemukan di database.");
}

$query = mysqli_prepare(
    $koneksi,
    "SELECT p.*, r.nama_ruangan
     FROM peminjaman p
     LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
     WHERE p.id_user = ?
     ORDER BY p.id_peminjaman DESC"
);

mysqli_stmt_bind_param($query, "i", $id_user);
mysqli_stmt_execute($query);

$result = mysqli_stmt_get_result($query);

function aman($data) {
    return htmlspecialchars((string)($data ?? "-"), ENT_QUOTES, "UTF-8");
}
?>