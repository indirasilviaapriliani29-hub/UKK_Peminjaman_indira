<?php
include "koneksi.php";

$akun = [
    ['Administrator', 'admin', 'admin123', 'admin'],
    ['Petugas Sekolah', 'petugas', 'petugas123', 'petugas'],
    ['Peminjam Demo', 'peminjam', 'peminjam123', 'peminjam']
];

foreach ($akun as $a) {
    $nama = $a[0];
    $username = $a[1];
    $password = password_hash($a[2], PASSWORD_DEFAULT);
    $level = $a[3];

    $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE username = ?");
    mysqli_stmt_bind_param($cek, "s", $username);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) == 0) {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO users (nama, username, password, level)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $nama, $username, $password, $level);
        mysqli_stmt_execute($stmt);
        echo "Akun $username berhasil dibuat.<br>";
        mysqli_stmt_close($stmt);
    } else {
        echo "Akun $username sudah tersedia.<br>";
    }

    mysqli_stmt_close($cek);
}
?>