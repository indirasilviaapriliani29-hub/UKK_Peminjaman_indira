
<?php
include "../koneksi.php";

$error = "";

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $level = $_POST['level'];
    $no_hp = trim($_POST['no_hp']);

    if ($nama == "" || $username == "" || $password == "") {
        $error = "Semua data wajib diisi kecuali nomor HP.";
    } elseif (!in_array($level, ['admin', 'petugas', 'peminjam'])) {
        $error = "Role tidak valid.";
    } else {
        $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE username=?");
        mysqli_stmt_bind_param($cek, "s", $username);
        mysqli_stmt_execute($cek);

        if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO users (nama, username, password, level, no_hp)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sssss", $nama, $username, $hash, $level, $no_hp);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: pengguna.php?pesan=tambah");
                exit;
            } else {
                $error = "Gagal menyimpan pengguna.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Pengguna | SIPRAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f4f7fc;color:#26344a}
.sidebar{width:250px;height:100vh;position:fixed;background:#0d47a1;color:white;padding:25px 15px}
.brand{text-align:center;font-size:25px;font-weight:bold;padding-bottom:30px}
.sidebar a{display:block;color:#e8efff;text-decoration:none;padding:13px 15px;margin-bottom:7px;border-radius:8px;font-size:14px}
.sidebar a i{width:25px}
.sidebar a:hover,.sidebar a.active{background:#42a5f5;color:white}
.main{margin-left:250px;padding:30px}
.content-card{background:white;padding:25px;border-radius:12px;box-shadow:0 3px 15px #0000000d;max-width:800px;margin-top:25px}
.btn-primary{background:#0d47a1;border-color:#0d47a1}
@media(max-width:768px){.sidebar{width:190px}.main{margin-left:190px;padding:18px}}
</style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="fa-solid fa-boxes-stacked"></i> SIPRAS</div>
    <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="kategori.php"><i class="fa-solid fa-layer-group"></i> Kategori</a>
    <a href="alat.php"><i class="fa-solid fa-toolbox"></i> Data Alat</a>
    <a href="ruangan.php"><i class="fa-solid fa-door-open"></i> Data Ruangan</a>
    <a href="pengguna.php" class="active"><i class="fa-solid fa-users"></i> Data Pengguna</a>
    <a href="peminjaman.php"><i class="fa-solid fa-hand-holding"></i> Peminjaman</a>
    <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <h4 class="fw-bold">Tambah Pengguna</h4>
    <p class="text-secondary">Tambahkan akun pengguna SIPRAS.</p>

    <div class="content-card">
        <?php if ($error != ""): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="level" class="form-select" required>
                    <option value="admin">Admin</option>
                    <option value="petugas">Petugas Sekolah</option>
                    <option value="peminjam" selected>Peminjam</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>

            <button type="submit" name="simpan" class="btn btn-primary">
                <i class="fa fa-save me-1"></i> Simpan
            </button>
            <a href="pengguna.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
</body>
</html>
