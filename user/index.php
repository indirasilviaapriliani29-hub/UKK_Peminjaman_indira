
<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Peminjam';
$pesan = '';
$berhasil = isset($_GET['berhasil']);

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// Mencari foto di folder project
function cariFoto($foto) {
    $foto = trim((string)($foto ?? ''));

    if ($foto === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $foto)) {
        return $foto;
    }

    $root = dirname(__DIR__);
    $namaFile = basename(str_replace('\\', '/', $foto));

    $daftarPath = [
        $foto,
        'uploads/' . $namaFile,
        'uploads/alat/' . $namaFile,
        'uploads/ruangan/' . $namaFile,
        'assets/img/' . $namaFile,
        'assets/images/' . $namaFile,
        'img/' . $namaFile,
        'images/' . $namaFile,
        'admin/uploads/' . $namaFile
    ];

    foreach (array_unique($daftarPath) as $path) {
        if (is_file($root . '/' . ltrim($path, '/'))) {
            return '../' . ltrim(str_replace('\\', '/', $path), '/');
        }
    }

    return '';
}

// ======================================
// PROSES PENGAJUAN ALAT / RUANGAN
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan'])) {
    $jenis = $_POST['jenis_peminjaman'] ?? '';
    $id_alat = (int)($_POST['id_alat'] ?? 0);
    $id_ruangan = (int)($_POST['id_ruangan'] ?? 0);
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
    $mulai = $_POST['tanggal_mulai'] ?? '';
    $selesai = $_POST['tanggal_selesai'] ?? '';
    $keperluan = trim($_POST['keperluan'] ?? '');

    $idAlatDB = null;
    $idRuanganDB = null;

    if (!in_array($jenis, ['alat', 'ruangan'], true)) {
        $pesan = 'Jenis peminjaman tidak valid.';
    } elseif ($keperluan === '') {
        $pesan = 'Keperluan peminjaman harus diisi.';
    } elseif (
        !$mulai || !$selesai ||
        strtotime($mulai) === false ||
        strtotime($selesai) === false ||
        strtotime($mulai) < time() ||
        strtotime($selesai) <= strtotime($mulai)
    ) {
        $pesan = 'Tanggal peminjaman tidak valid.';
    } else {
        if ($jenis === 'alat') {
            $idAlatDB = $id_alat;

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT stok FROM alat WHERE id_alat = ? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "i", $idAlatDB);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);
            $dataAlat = mysqli_fetch_assoc($hasil);
            mysqli_stmt_close($stmt);

            if (!$dataAlat) {
                $pesan = 'Alat tidak ditemukan.';
            } elseif ((int)$dataAlat['stok'] < 1) {
                $pesan = 'Stok alat sedang kosong.';
            } elseif ($jumlah > (int)$dataAlat['stok']) {
                $pesan = 'Jumlah melebihi stok yang tersedia.';
            }
        } else {
            $idRuanganDB = $id_ruangan;
            $jumlah = 1;

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT id_ruangan FROM ruangan
                 WHERE id_ruangan = ? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "i", $idRuanganDB);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);
            $dataRuangan = mysqli_fetch_assoc($hasil);
            mysqli_stmt_close($stmt);

            if (!$dataRuangan) {
                $pesan = 'Ruangan tidak ditemukan.';
            }
        }
    }

    if ($pesan === '') {
        $sql = "INSERT INTO peminjaman
                (id_user, jenis_peminjaman, id_alat, id_ruangan,
                 jumlah, tanggal_mulai, tanggal_selesai,
                 keperluan, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu')";

        $stmt = mysqli_prepare($koneksi, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "isiiisss",
                $id_user,
                $jenis,
                $idAlatDB,
                $idRuanganDB,
                $jumlah,
                $mulai,
                $selesai,
                $keperluan
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php?berhasil=1");
                exit;
            }

            $pesan = 'Pengajuan gagal disimpan. Periksa kolom tabel peminjaman.';
            mysqli_stmt_close($stmt);
        } else {
            $pesan = 'Query pengajuan gagal. Periksa struktur database.';
        }
    }
}

// ======================================
// AMBIL DATA ALAT
// ======================================
$daftarAlat = [];
$sqlAlat = mysqli_query(
    $koneksi,
    "SELECT a.*, k.nama_kategori
     FROM alat a
     LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
     ORDER BY a.id_alat DESC"
);

if ($sqlAlat) {
    while ($row = mysqli_fetch_assoc($sqlAlat)) {
        $daftarAlat[] = $row;
    }
}

// ======================================
// AMBIL DATA RUANGAN
// ======================================
$daftarRuangan = [];
$sqlRuangan = mysqli_query(
    $koneksi,
    "SELECT * FROM ruangan ORDER BY id_ruangan DESC"
);

if ($sqlRuangan) {
    while ($row = mysqli_fetch_assoc($sqlRuangan)) {
        $daftarRuangan[] = $row;
    }
}

// ======================================
// PENCARIAN DAN FILTER KATALOG
// ======================================
$cari = trim($_GET['cari'] ?? '');
$jenisFilter = $_GET['jenis'] ?? 'semua';

if (!in_array($jenisFilter, ['semua', 'alat', 'ruangan'], true)) {
    $jenisFilter = 'semua';
}

$daftarAlat = array_values(array_filter(
    $daftarAlat,
    function ($item) use ($cari) {
        return $cari === '' ||
            stripos($item['nama_alat'] ?? '', $cari) !== false ||
            stripos($item['nama_kategori'] ?? '', $cari) !== false;
    }
));

$daftarRuangan = array_values(array_filter(
    $daftarRuangan,
    function ($item) use ($cari) {
        return $cari === '' ||
            stripos($item['nama_ruangan'] ?? '', $cari) !== false;
    }
));

$tampilAlat = in_array($jenisFilter, ['semua', 'alat'], true);
$tampilRuangan = in_array($jenisFilter, ['semua', 'ruangan'], true);

// ======================================
// RINGKASAN PENGAJUAN PEMINJAM
// ======================================
$total = 0;
$menunggu = 0;
$disetujui = 0;
$selesai = 0;

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Menunggu') AS menunggu,
        SUM(status = 'Disetujui') AS disetujui,
        SUM(status = 'Selesai') AS selesai
     FROM peminjaman
     WHERE id_user = ?"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $ringkasan = mysqli_fetch_assoc($hasil);

    if ($ringkasan) {
        $total = (int)$ringkasan['total'];
        $menunggu = (int)$ringkasan['menunggu'];
        $disetujui = (int)$ringkasan['disetujui'];
        $selesai = (int)$ringkasan['selesai'];
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Peminjam | SIPRAS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f7fd;font-family:Poppins,sans-serif;color:#183b68}

.topbar{
 position:fixed;top:0;left:0;right:0;height:68px;z-index:1000;
 background:#15569b;color:white;display:flex;align-items:center;
 justify-content:space-between;padding:0 28px
}
.brand{display:flex;align-items:center;gap:12px;font-size:23px;font-weight:700}
.brand>i{font-size:29px}
.brand small{display:block;font-size:11px;font-weight:400}

.sidebar{
 position:fixed;top:68px;bottom:0;left:0;width:250px;
 background:#174d88;padding:22px 10px
}
.sidebar a{
 display:flex;align-items:center;gap:14px;color:white;text-decoration:none;
 padding:17px 20px;border-radius:12px;margin-bottom:7px;font-size:14px
}
.sidebar a i{width:22px;font-size:19px}
.sidebar a:hover,.sidebar a.active{background:#2671c8}

.main{margin-left:250px;padding:95px 30px 45px}
.welcome{
 background:linear-gradient(115deg,#15569b,#287bd2);
 color:white;border-radius:18px;padding:27px 30px;
 display:flex;justify-content:space-between;align-items:center;
 margin-bottom:26px
}
.welcome h2{font-size:23px;font-weight:700;margin-bottom:8px}
.welcome p{font-size:12px;color:#e4efff;margin-bottom:18px}
.welcome>i{font-size:65px;opacity:.8}
.welcome .btn{font-size:12px;font-weight:600;padding:10px 15px}

.section-title{font-size:18px;font-weight:700;margin:26px 0 15px}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
.stat{
 background:white;padding:19px;border-radius:16px;
 display:flex;align-items:center;gap:13px;
 box-shadow:0 5px 18px #234f790b
}
.stat-icon{
 width:48px;height:48px;flex-shrink:0;border-radius:13px;
 display:flex;align-items:center;justify-content:center;font-size:19px
}
.blue{background:#e1edff;color:#1767bd}
.orange{background:#fff0d7;color:#d88b12}
.green{background:#dff7e9;color:#21965b}
.purple{background:#efe5ff;color:#8351c9}
.stat small{font-size:10px;color:#7d91ac}
.stat strong{display:block;font-size:23px;color:#183b68}

.search-panel{
 background:white;border-radius:15px;padding:14px;display:flex;gap:12px;
 margin:22px 0;box-shadow:0 5px 18px #234f790b
}
.search-box{
 display:flex;align-items:center;gap:12px;flex:1;background:#f5f8fd;
 border:1px solid #e4ebf5;border-radius:10px;padding:0 15px
}
.search-box i{color:#6e8db1}
.search-box input{border:0;outline:0;background:transparent;width:100%;padding:13px 0;font-size:12px}
.btn-search,.btn-submit{
 background:#1767bd;color:white;border:0;border-radius:10px;
 padding:12px 20px;font-size:12px;font-weight:600
}
.btn-search:hover,.btn-submit:hover{background:#124f94;color:white}

.filters{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:24px}
.filter-btn{
 display:inline-block;text-decoration:none;border:1px solid #dce7f5;
 background:white;color:#597594;padding:10px 18px;border-radius:10px;
 font-size:12px;font-weight:600
}
.filter-btn.active,.filter-btn:hover{background:#1767bd;color:white;border-color:#1767bd}

.catalog{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.item-card{
 background:white;border-radius:17px;overflow:hidden;
 box-shadow:0 5px 20px #234f7910;display:flex;flex-direction:column;
 min-width:0;transition:.2s
}
.item-card:hover{transform:translateY(-4px);box-shadow:0 10px 25px #234f791a}
.item-photo{
 height:215px;background:#e5eef9;position:relative;
 display:flex;align-items:center;justify-content:center;overflow:hidden
}
.item-photo img{width:100%;height:100%;object-fit:cover}
.no-photo{font-size:48px;color:#8eafd5}
.item-tag{
 position:absolute;top:12px;left:12px;background:white;padding:7px 11px;
 border-radius:9px;font-size:10px;font-weight:600;color:#1767bd
}
.item-content{padding:18px;display:flex;flex-direction:column;flex:1}
.item-content h6{font-size:15px;font-weight:700;margin:0 0 8px}
.item-desc{font-size:12px;color:#8294aa;margin-bottom:16px}
.item-info{display:flex;justify-content:space-between;gap:8px;font-size:11px;color:#7186a2;margin-bottom:17px}
.item-info i{color:#1767bd;margin-right:5px}
.btn-borrow{
 display:block;width:100%;border:0;background:#1767bd;color:white;
 padding:13px;border-radius:10px;font-size:12px;font-weight:600;margin-top:auto
}
.btn-borrow:hover{background:#124f94;color:white}
.btn-borrow:disabled{background:#9aacc1}

.empty{grid-column:1/-1;background:white;border-radius:15px;padding:40px;text-align:center;color:#8294aa}
.empty i{font-size:38px;display:block;margin-bottom:12px;color:#a8bed9}
.alert{font-size:12px;border-radius:10px}
.modal-content{border:0;border-radius:17px;overflow:hidden}
.modal-header{background:#15569b;color:white;padding:20px 24px}
.modal-title{font-size:17px;font-weight:700}
.modal-header .btn-close{filter:brightness(0) invert(1)}
.modal-body{padding:24px}
.form-label{font-size:12px;font-weight:600;color:#365778}
.form-control{font-size:12px;padding:11px;border-color:#dce6f2;border-radius:9px}

@media(max-width:1250px){.catalog{grid-template-columns:repeat(2,minmax(0,1fr))}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:800px){
 .sidebar{width:65px;padding:15px 6px}
 .sidebar a{justify-content:center;padding:16px 5px}
 .sidebar a span{display:none}
 .main{margin-left:65px;padding:90px 15px 25px}
 .topbar{padding:0 15px}
 .brand{font-size:18px}
 .welcome>i{font-size:35px}
}
@media(max-width:560px){
 .catalog{grid-template-columns:1fr}
 .stats{grid-template-columns:1fr 1fr}
 .stat{padding:12px;gap:8px}
 .stat-icon{width:38px;height:38px}
 .stat strong{font-size:19px}
 .welcome>i{display:none}
 .search-panel{flex-wrap:wrap}
 .search-box{flex-basis:100%}
}
</style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <i class="fa-solid fa-school"></i>
        <div>SIPRAS<small>Peminjaman Sarpras Sekolah</small></div>
    </div>
    <div style="font-size:12px">
        <i class="fa-solid fa-circle-user me-2 fs-5"></i>
        Halo, <?= aman($nama) ?>
    </div>
</header>

<aside class="sidebar">
    <a href="index.php" class="active">
        <i class="fa-solid fa-house"></i><span>Dashboard</span>
    </a>
    <a href="riwayat.php">
        <i class="fa-solid fa-clock-rotate-left"></i><span>Riwayat Peminjaman</span>
    </a>
    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
    </a>
</aside>

<main class="main">

    <section class="welcome">
        <div>
            <h2>Halo, <?= aman($nama) ?>! 👋</h2>
            <p>Selamat datang di SIPRAS. Pilih alat atau ruangan untuk mengajukan peminjaman.</p>
            <a href="#katalog" class="btn btn-light text-primary">
                <i class="fa-solid fa-plus me-1"></i>Lihat Katalog
            </a>
        </div>
        <i class="fa-solid fa-school"></i>
    </section>

    <?php if ($berhasil): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check me-2"></i>
            Pengajuan berhasil dikirim. Silakan tunggu persetujuan petugas.
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-danger"><?= aman($pesan) ?></div>
    <?php endif; ?>

    <h5 class="section-title">Ringkasan Peminjaman</h5>

    <section class="stats">
        <div class="stat">
            <div class="stat-icon blue"><i class="fa-solid fa-file-lines"></i></div>
            <div><small>Total Pengajuan</small><strong><?= $total ?></strong></div>
        </div>
        <div class="stat">
            <div class="stat-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
            <div><small>Menunggu</small><strong><?= $menunggu ?></strong></div>
        </div>
        <div class="stat">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div><small>Disetujui</small><strong><?= $disetujui ?></strong></div>
        </div>
        <div class="stat">
            <div class="stat-icon purple"><i class="fa-solid fa-flag-checkered"></i></div>
            <div><small>Selesai</small><strong><?= $selesai ?></strong></div>
        </div>
    </section>

    <h5 class="section-title" id="katalog">Pilih Alat atau Ruangan</h5>

    <form method="GET" class="search-panel">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="cari" value="<?= aman($cari) ?>"
                   placeholder="Cari nama alat atau ruangan...">
        </div>
        <input type="hidden" name="jenis" value="<?= aman($jenisFilter) ?>">
        <button type="submit" class="btn-search">
            <i class="fa-solid fa-search me-1"></i>Cari
        </button>
    </form>

    <div class="filters">
        <a href="index.php#katalog"
           class="filter-btn <?= $jenisFilter === 'semua' ? 'active' : '' ?>">Semua</a>
        <a href="?jenis=alat&cari=<?= urlencode($cari) ?>#katalog"
           class="filter-btn <?= $jenisFilter === 'alat' ? 'active' : '' ?>">Alat</a>
        <a href="?jenis=ruangan&cari=<?= urlencode($cari) ?>#katalog"
           class="filter-btn <?= $jenisFilter === 'ruangan' ? 'active' : '' ?>">Ruangan</a>
    </div>

    <?php if ($tampilAlat): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title m-0">
                <i class="fa-solid fa-box text-primary me-2"></i>Daftar Alat
            </h5>
            <small class="text-secondary"><?= count($daftarAlat) ?> alat</small>
        </div>

        <section class="catalog mb-4">
        <?php foreach ($daftarAlat as $item): ?>
            <?php
            $foto = cariFoto($item['foto'] ?? '');
            $stok = (int)($item['stok'] ?? 0);
            ?>
            <article class="item-card">
                <div class="item-photo">
                    <?php if ($foto !== ''): ?>
                        <img src="<?= aman($foto) ?>" alt="<?= aman($item['nama_alat']) ?>"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="no-photo" style="display:none"><i class="fa-solid fa-box-open"></i></div>
                    <?php else: ?>
                        <div class="no-photo"><i class="fa-solid fa-box-open"></i></div>
                    <?php endif; ?>
                    <span class="item-tag"><i class="fa-solid fa-box me-1"></i>Alat</span>
                </div>

                <div class="item-content">
                    <h6><?= aman($item['nama_alat']) ?></h6>
                    <div class="item-desc"><?= aman($item['nama_kategori'] ?? 'Alat sekolah') ?></div>
                    <div class="item-info">
                        <span><i class="fa-solid fa-layer-group"></i>Stok: <?= $stok ?></span>
                        <span><i class="fa-solid fa-school"></i>SIPRAS</span>
                    </div>

                    <?php if ($stok > 0): ?>
                        <button type="button" class="btn-borrow"
                            data-bs-toggle="modal" data-bs-target="#modalPinjam"
                            data-jenis="alat"
                            data-id="<?= (int)$item['id_alat'] ?>"
                            data-nama="<?= aman($item['nama_alat']) ?>"
                            data-stok="<?= $stok ?>">
                            <i class="fa-solid fa-plus me-2"></i>Ajukan Peminjaman
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-borrow" disabled>Stok Habis</button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$daftarAlat): ?>
            <div class="empty"><i class="fa-solid fa-box-open"></i>Alat tidak ditemukan.</div>
        <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($tampilRuangan): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title m-0">
                <i class="fa-solid fa-door-open text-primary me-2"></i>Daftar Ruangan
            </h5>
            <small class="text-secondary"><?= count($daftarRuangan) ?> ruangan</small>
        </div>

        <section class="catalog">
        <?php foreach ($daftarRuangan as $item): ?>
            <?php $foto = cariFoto($item['foto'] ?? ''); ?>
            <article class="item-card">
                <div class="item-photo">
                    <?php if ($foto !== ''): ?>
                        <img src="<?= aman($foto) ?>" alt="<?= aman($item['nama_ruangan']) ?>"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="no-photo" style="display:none"><i class="fa-solid fa-door-open"></i></div>
                    <?php else: ?>
                        <div class="no-photo"><i class="fa-solid fa-door-open"></i></div>
                    <?php endif; ?>
                    <span class="item-tag"><i class="fa-solid fa-door-open me-1"></i>Ruangan</span>
                </div>

                <div class="item-content">
                    <h6><?= aman($item['nama_ruangan']) ?></h6>
                    <div class="item-desc">Ruangan sarana sekolah</div>
                    <div class="item-info">
                        <span><i class="fa-solid fa-calendar-check"></i>Jadwal peminjaman</span>
                        <span><i class="fa-solid fa-school"></i>SIPRAS</span>
                    </div>
                    <button type="button" class="btn-borrow"
                        data-bs-toggle="modal" data-bs-target="#modalPinjam"
                        data-jenis="ruangan"
                        data-id="<?= (int)$item['id_ruangan'] ?>"
                        data-nama="<?= aman($item['nama_ruangan']) ?>"
                        data-stok="1">
                        <i class="fa-solid fa-plus me-2"></i>Ajukan Peminjaman
                    </button>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$daftarRuangan): ?>
            <div class="empty"><i class="fa-solid fa-door-open"></i>Ruangan tidak ditemukan.</div>
        <?php endif; ?>
        </section>
    <?php endif; ?>

</main>

<!-- SATU FORM UNTUK ALAT DAN RUANGAN -->
<div class="modal fade" id="modalPinjam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-circle-plus me-2"></i>Form Pengajuan Peminjaman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" id="formPinjam">
                <div class="modal-body">
                    <div class="alert alert-primary">
                        <small>Item yang dipilih</small>
                        <div class="fw-bold" id="namaItem">-</div>
                    </div>

                    <input type="hidden" name="jenis_peminjaman" id="jenisInput">
                    <input type="hidden" name="id_alat" id="alatInput" value="0">
                    <input type="hidden" name="id_ruangan" id="ruanganInput" value="0">

                    <div class="mb-3" id="jumlahGroup">
                        <label class="form-label">Jumlah alat</label>
                        <input type="number" name="jumlah" id="jumlahInput"
                               class="form-control" min="1" value="1" required>
                        <small class="text-secondary" id="stokText"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal dan waktu mulai</label>
                        <input type="datetime-local" name="tanggal_mulai"
                               id="tanggalMulai" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal dan waktu selesai</label>
                        <input type="datetime-local" name="tanggal_selesai"
                               id="tanggalSelesai" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keperluan peminjaman</label>
                        <textarea name="keperluan" class="form-control" rows="3"
                                  placeholder="Tuliskan keperluan peminjaman..."
                                  required></textarea>
                    </div>

                    <small class="text-secondary">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Pengajuan akan menunggu persetujuan petugas.
                    </small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ajukan" class="btn-submit">
                        <i class="fa-solid fa-paper-plane me-1"></i>Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const modalPinjam = document.getElementById('modalPinjam');

function formatTanggal(tanggal) {
    const z = n => String(n).padStart(2, '0');
    return tanggal.getFullYear() + '-' +
        z(tanggal.getMonth() + 1) + '-' +
        z(tanggal.getDate()) + 'T' +
        z(tanggal.getHours()) + ':' +
        z(tanggal.getMinutes());
}

modalPinjam.addEventListener('show.bs.modal', function(event) {
    const tombol = event.relatedTarget;
    const jenis = tombol.dataset.jenis;
    const id = tombol.dataset.id;
    const stok = parseInt(tombol.dataset.stok || '1', 10);

    document.getElementById('namaItem').textContent = tombol.dataset.nama;
    document.getElementById('jenisInput').value = jenis;
    document.getElementById('alatInput').value = jenis === 'alat' ? id : '0';
    document.getElementById('ruanganInput').value = jenis === 'ruangan' ? id : '0';

    const group = document.getElementById('jumlahGroup');
    const jumlah = document.getElementById('jumlahInput');

    if (jenis === 'ruangan') {
        group.style.display = 'none';
        jumlah.required = false;
        jumlah.value = '1';
    } else {
        group.style.display = 'block';
        jumlah.required = true;
        jumlah.min = '1';
        jumlah.max = stok;
        jumlah.value = '1';
        document.getElementById('stokText').textContent = 'Stok tersedia: ' + stok;
    }

    const sekarang = new Date();
    sekarang.setMinutes(sekarang.getMinutes() - sekarang.getTimezoneOffset());
    sekarang.setHours(sekarang.getHours() + 1, 0, 0, 0);

    const akhir = new Date(sekarang);
    akhir.setHours(akhir.getHours() + 1);

    document.getElementById('tanggalMulai').value = formatTanggal(sekarang);
    document.getElementById('tanggalSelesai').value = formatTanggal(akhir);
    document.getElementById('tanggalMulai').min = formatTanggal(sekarang);
    document.getElementById('tanggalSelesai').min = formatTanggal(sekarang);
});

document.getElementById('tanggalMulai').addEventListener('change', function() {
    const akhir = document.getElementById('tanggalSelesai');
    akhir.min = this.value;

    if (akhir.value && akhir.value <= this.value) {
        akhir.value = '';
    }
});

document.getElementById('formPinjam').addEventListener('submit', function(event) {
    const jenis = document.getElementById('jenisInput').value;
    const jumlah = document.getElementById('jumlahInput');
    const mulai = document.getElementById('tanggalMulai').value;
    const selesai = document.getElementById('tanggalSelesai').value;

    if (jenis === 'alat' &&
        (parseInt(jumlah.value, 10) < 1 ||
         parseInt(jumlah.value, 10) > parseInt(jumlah.max, 10))) {
        event.preventDefault();
        alert('Jumlah melebihi stok yang tersedia.');
        return;
    }

    if (!mulai || !selesai || selesai <= mulai) {
        event.preventDefault();
        alert('Tanggal selesai harus setelah tanggal mulai.');
    }
});
</script>

</body>
</html>