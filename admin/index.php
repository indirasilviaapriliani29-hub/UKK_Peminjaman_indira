<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['level'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

function totalData($koneksi, $tabel) {
    $hasil = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$tabel`");
    return mysqli_fetch_assoc($hasil)['total'] ?? 0;
}

$totalAlat = totalData($koneksi, 'alat');
$totalRuangan = totalData($koneksi, 'ruangan');
$totalKategori = totalData($koneksi, 'kategori');
$totalPeminjaman = totalData($koneksi, 'peminjaman');

$nama = htmlspecialchars($_SESSION['nama']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin | SIPRAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{font-family:Poppins,sans-serif}
body{background:#f4f7fc;color:#24344d;font-size:14px}
.sidebar{width:250px;position:fixed;inset:0 auto 0 0;background:#123766;color:white;padding:25px 16px}
.brand{font-size:20px;font-weight:700;padding:0 12px 30px}
.brand small{display:block;font-size:11px;font-weight:400;color:#b9cee9;margin-top:5px}
.menu-title{font-size:10px;color:#9eb6d5;margin:18px 12px 10px;letter-spacing:1.4px}
.nav-link{color:#cbd9eb;padding:12px 14px;border-radius:10px;margin-bottom:5px}
.nav-link:hover,.nav-link.active{background:#2675c9;color:white}
.nav-link i{width:25px}
.main{margin-left:250px;padding:30px}
.topbar,.panel,.stat{background:white;border:1px solid #e9eef5;border-radius:16px}
.topbar{padding:17px 22px;display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.stat{padding:22px;height:100%;transition:.2s}
.stat:hover{transform:translateY(-3px);box-shadow:0 10px 25px #17385d0c}
.stat-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:13px;font-size:19px}
.blue{background:#e6f1ff;color:#2473ca}
.green{background:#e4f8ed;color:#24965c}
.orange{background:#fff0df;color:#dc8b2b}
.purple{background:#f0eaff;color:#7954ce}
.stat-number{font-size:29px;font-weight:700;margin-top:18px}
.stat-label{color:#7b899d;font-size:12px}
.panel{padding:23px}
.quick-link{display:flex;align-items:center;gap:14px;padding:15px;border:1px solid #edf0f6;border-radius:12px;color:#24344d;text-decoration:none;height:100%}
.quick-link:hover{border-color:#2675c9;background:#f7faff;color:#1768b4}
.quick-icon{width:43px;height:43px;border-radius:12px;background:#e6f1ff;color:#2675c9;display:flex;align-items:center;justify-content:center;font-size:18px}
.muted{color:#8491a4}
@media(max-width:800px){.sidebar{width:72px;padding:20px 9px}.brand{font-size:0;padding:8px}.brand:before{content:"S";font-size:23px}.brand small,.nav-link span,.menu-title{display:none}.nav-link{text-align:center;padding:13px 5px}.nav-link i{width:auto}.main{margin-left:72px;padding:16px}.topbar{gap:10px;flex-wrap:wrap}}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><i class="fa-solid fa-school me-2"></i>SIPRAS
        <small>SMK Negeri 3 Kendal</small>
    </div>
    <div class="menu-title">MENU UTAMA</div>
    <a href="index.php" class="nav-link active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
    <div class="menu-title">DATA MASTER</div>
    <a href="kategori.php" class="nav-link"><i class="fa-solid fa-layer-group"></i><span>Kategori</span></a>
    <a href="alat.php" class="nav-link"><i class="fa-solid fa-toolbox"></i><span>Data Alat</span></a>
    <a href="ruangan.php" class="nav-link"><i class="fa-solid fa-door-open"></i><span>Data Ruangan</span></a>
    <a href="users.php" class="nav-link"><i class="fa-solid fa-users"></i><span>Data Pengguna</span></a>
    <div class="menu-title">TRANSAKSI</div>
    <a href="peminjaman.php" class="nav-link"><i class="fa-solid fa-clipboard-list"></i><span>Peminjaman</span></a>
    <a href="pengembalian.php" class="nav-link"><i class="fa-solid fa-rotate-left"></i><span>Pengembalian</span></a>
    <div class="mt-4">
        <a href="../logout.php" class="nav-link" onclick="return confirm('Yakin ingin keluar?')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h5 class="fw-bold mb-1">Dashboard Admin</h5>
            <div class="muted small">Sistem Informasi Sarana dan Prasarana</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-semibold"><?= $nama ?></div>
                <div class="muted small">Administrator</div>
            </div>
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:#e6f1ff;color:#2675c9">
                <i class="fa-solid fa-user-shield"></i>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h4 class="fw-bold">Halo, <?= $nama ?>! 👋</h4>
        <p class="muted">Selamat datang di panel pengelolaan SIPRAS sekolah.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="stat-icon blue"><i class="fa-solid fa-toolbox"></i></div>
                <div class="stat-number"><?= $totalAlat ?></div>
                <div class="stat-label">Total Alat</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="stat-icon green"><i class="fa-solid fa-door-open"></i></div>
                <div class="stat-number"><?= $totalRuangan ?></div>
                <div class="stat-label">Total Ruangan</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="stat-icon orange"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-number"><?= $totalKategori ?></div>
                <div class="stat-label">Kategori</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="stat-icon purple"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="stat-number"><?= $totalPeminjaman ?></div>
                <div class="stat-label">Total Peminjaman</div>
            </div>
        </div>
    </div>

    <div class="panel mb-4">
        <h6 class="fw-bold mb-1">Menu Cepat</h6>
        <p class="muted small mb-3">Pilih menu untuk mulai mengelola data sekolah.</p>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <a href="alat.php" class="quick-link">
                    <div class="quick-icon"><i class="fa-solid fa-toolbox"></i></div>
                    <div><div class="fw-semibold">Data Alat</div><div class="muted small">Kelola barang dan foto</div></div>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="ruangan.php" class="quick-link">
                    <div class="quick-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div><div class="fw-semibold">Data Ruangan</div><div class="muted small">Kelola fasilitas sekolah</div></div>
                </a>
            </div>
            <div class="col-md-6 col-xl-4">
                <a href="peminjaman.php" class="quick-link">
                    <div class="quick-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div><div class="fw-semibold">Peminjaman</div><div class="muted small">Periksa permohonan</div></div>
                </a>
            </div>
        </div>
    </div>

    <div class="text-center muted small py-2">
        © <?= date('Y') ?> SIPRAS · SMK Negeri 3 Kendal
    </div>
</main>
</body>
</html>