
<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['level']) && $_SESSION['level'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? '-'), ENT_QUOTES, 'UTF-8');
}

$cari = trim($_GET['cari'] ?? '');
$nama_petugas = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas';

// Periksa kolom tabel ruangan
$kolom_ruangan = [];
$hasil_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM ruangan");

if ($hasil_kolom) {
    while ($kolom = mysqli_fetch_assoc($hasil_kolom)) {
        $kolom_ruangan[] = $kolom['Field'];
    }
}

$ada_foto = in_array('foto', $kolom_ruangan, true);
$ada_keterangan = in_array('keterangan', $kolom_ruangan, true);
$ada_lokasi = in_array('lokasi', $kolom_ruangan, true);
$ada_kapasitas = in_array('kapasitas', $kolom_ruangan, true);

$sql = "SELECT r.* FROM ruangan r WHERE 1=1";

if ($cari !== '') {
    $sql .= " AND r.nama_ruangan LIKE ?";
}

$sql .= " ORDER BY r.id_ruangan DESC";

$stmt = mysqli_prepare($koneksi, $sql);
$data_ruangan = false;

if ($stmt) {
    if ($cari !== '') {
        $kata = "%" . $cari . "%";
        mysqli_stmt_bind_param($stmt, "s", $kata);
    }

    mysqli_stmt_execute($stmt);
    $data_ruangan = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Ruangan | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f4f7fc;
            font-family: 'Poppins', sans-serif;
            color: #263449;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #0d47a1;
            color: white;
            padding: 25px 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 30px;
        }

        .brand-icon {
            background: white;
            color: #0d47a1;
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 21px;
        }

        .brand h5 { font-weight: 700; margin: 0; }
        .brand small { color: #d9e7ff; font-size: 11px; }

        .menu-label {
            font-size: 11px;
            color: #c1d7fb;
            margin: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #e7efff;
            padding: 13px 14px;
            margin-bottom: 7px;
            border-radius: 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background: #2874d9;
        }

        .nav-link i { width: 20px; text-align: center; }

        .main { margin-left: 250px; padding: 30px; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .topbar h4 { font-size: 22px; font-weight: 700; margin-bottom: 5px; }
        .muted { color: #7b8798; font-size: 12px; }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 10px 15px;
            border-radius: 12px;
        }

        .avatar {
            background: #e2edff;
            color: #0d47a1;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 23px;
            box-shadow: 0 5px 22px #18365b08;
        }

        .section-title { font-size: 16px; font-weight: 700; }

        .form-control {
            font-size: 12px;
            border-color: #e1e7f0;
            padding: 10px 12px;
            border-radius: 9px;
        }

        .btn-primary {
            background: #0d47a1;
            border-color: #0d47a1;
            border-radius: 9px;
            font-size: 12px;
            padding: 10px 14px;
        }

        .ruangan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 22px;
        }

        .ruangan-card {
            border: 1px solid #edf1f7;
            border-radius: 14px;
            overflow: hidden;
            background: white;
            transition: .2s;
        }

        .ruangan-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px #18365b12;
        }

        .ruangan-foto {
            width: 100%;
            height: 165px;
            object-fit: cover;
            background: #edf3fc;
        }

        .foto-kosong {
            width: 100%;
            height: 165px;
            display: grid;
            place-items: center;
            background: #edf3fc;
            color: #8ba4c8;
            font-size: 42px;
        }

        .ruangan-body { padding: 16px; }

        .ruangan-nama {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .ruangan-info {
            font-size: 11px;
            color: #7b8798;
            margin-bottom: 8px;
        }

        .info-badge {
            display: inline-block;
            padding: 6px 9px;
            background: #e6efff;
            color: #1e63c5;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 600;
        }

        .empty {
            padding: 45px 10px;
            text-align: center;
            color: #8a96a8;
            grid-column: 1 / -1;
        }

        @media (max-width: 900px) {
            .sidebar { width: 72px; padding: 20px 10px; }
            .brand { padding: 0 4px 30px; }
            .brand-text, .menu-label, .nav-text { display: none; }
            .nav-link { justify-content: center; padding: 14px 5px; }
            .main { margin-left: 72px; padding: 20px 14px; }
        }

        @media (max-width: 550px) {
            .topbar h4 { font-size: 18px; }
            .profile { padding: 8px; }
            .profile-name { display: none; }
            .content-card { padding: 15px; }
            .ruangan-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-school"></i></div>
        <div class="brand-text">
            <h5>SIPRAS</h5>
            <small>SMK Negeri 3 Kendal</small>
        </div>
    </div>

    <div class="menu-label">Menu Utama</div>

    <a href="index.php" class="nav-link">
        <i class="fa-solid fa-house"></i>
        <span class="nav-text">Dashboard</span>
    </a>

    <a href="peminjaman.php" class="nav-link">
        <i class="fa-solid fa-clipboard-check"></i>
        <span class="nav-text">Data Peminjaman</span>
    </a>

    <a href="alat.php" class="nav-link">
        <i class="fa-solid fa-box"></i>
        <span class="nav-text">Data Alat</span>
    </a>

    <a href="ruangan.php" class="nav-link active">
        <i class="fa-solid fa-door-open"></i>
        <span class="nav-text">Data Ruangan</span>
    </a>

    <div class="menu-label mt-4">Akun</div>

    <a href="../logout.php" class="nav-link" onclick="return confirm('Yakin ingin keluar?')">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="nav-text">Logout</span>
    </a>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h4>Data Ruangan</h4>
            <div class="muted">Daftar ruangan sekolah yang tersedia untuk dipinjam.</div>
        </div>

        <div class="profile">
            <div class="avatar"><i class="fa-solid fa-user-check"></i></div>
            <div class="profile-name">
                <div style="font-size:12px;font-weight:600;"><?= aman($nama_petugas); ?></div>
                <div class="muted">Petugas</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <h5 class="section-title mb-1">Katalog Ruangan Sekolah</h5>
        <div class="muted">Informasi ruangan yang tercatat di sistem SIPRAS.</div>

        <form method="GET" class="row g-2 mt-3 mb-2">
            <div class="col-12 col-md-8">
                <input type="text" name="cari" class="form-control"
                       placeholder="Cari nama ruangan..."
                       value="<?= aman($cari); ?>">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>
                <a href="ruangan.php" class="btn btn-light border" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        <div class="ruangan-grid">
            <?php if ($data_ruangan && mysqli_num_rows($data_ruangan) > 0): ?>
                <?php while ($r = mysqli_fetch_assoc($data_ruangan)): ?>
                    <?php
                    $foto = '';

                    if ($ada_foto && !empty($r['foto'])) {
                        $nama_foto = basename($r['foto']);

                        if (is_file(__DIR__ . "/../assets/img/" . $nama_foto)) {
                            $foto = "../assets/img/" . $nama_foto;
                        } elseif (is_file(__DIR__ . "/../uploads/" . $nama_foto)) {
                            $foto = "../uploads/" . $nama_foto;
                        }
                    }
                    ?>
                    <div class="ruangan-card">
                        <?php if ($foto !== ''): ?>
                            <img src="<?= aman($foto); ?>" class="ruangan-foto"
                                 alt="<?= aman($r['nama_ruangan'] ?? 'Foto ruangan'); ?>">
                        <?php else: ?>
                            <div class="foto-kosong">
                                <i class="fa-solid fa-door-open"></i>
                            </div>
                        <?php endif; ?>

                        <div class="ruangan-body">
                            <div class="ruangan-nama">
                                <?= aman($r['nama_ruangan'] ?? 'Ruangan'); ?>
                            </div>

                            <?php if ($ada_lokasi && !empty($r['lokasi'])): ?>
                                <div class="ruangan-info">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?= aman($r['lokasi']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($ada_kapasitas && isset($r['kapasitas'])): ?>
                                <div class="ruangan-info">
                                    <i class="fa-solid fa-users me-1"></i>
                                    Kapasitas: <?= aman($r['kapasitas']); ?> orang
                                </div>
                            <?php endif; ?>

                            <?php if ($ada_keterangan && !empty($r['keterangan'])): ?>
                                <div class="ruangan-info">
                                    <?= aman($r['keterangan']); ?>
                                </div>
                            <?php endif; ?>

                            <span class="info-badge mt-2">
                                <i class="fa-solid fa-building me-1"></i>
                                Sarana Ruangan
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-solid fa-door-open fa-2x mb-3"></i>
                    <div>Data ruangan belum tersedia atau tidak ditemukan.</div>
                    <div class="muted mt-2">Pastikan data ruangan sudah ditambahkan melalui halaman admin.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center muted mt-4">
        SIPRAS &copy; <?= date('Y'); ?> · SMK Negeri 3 Kendal
    </div>
</main>

</body>
</html>
