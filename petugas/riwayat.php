
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
$status_pilihan = $_GET['status'] ?? '';

$sql = "SELECT p.*, u.nama AS nama_peminjam,
        a.nama_alat, r.nama_ruangan
        FROM peminjaman p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN alat a ON p.id_alat = a.id_alat
        LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
        WHERE p.status <> 'Menunggu'";

$tipe = "";
$parameter = [];

if ($status_pilihan !== '') {
    $sql .= " AND p.status = ?";
    $tipe .= "s";
    $parameter[] = $status_pilihan;
}

if ($cari !== '') {
    $sql .= " AND (u.nama LIKE ? OR a.nama_alat LIKE ?
              OR r.nama_ruangan LIKE ? OR p.keperluan LIKE ?)";
    $tipe .= "ssss";
    $kata = "%$cari%";
    array_push($parameter, $kata, $kata, $kata, $kata);
}

$sql .= " ORDER BY p.id_peminjaman DESC";

$stmt = mysqli_prepare($koneksi, $sql);
$data = false;

if ($stmt) {
    if ($tipe !== '') {
        mysqli_stmt_bind_param($stmt, $tipe, ...$parameter);
    }
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
}

$nama_petugas = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas';

// Ringkasan status yang sudah diproses
$ringkasan = [
    'Disetujui' => 0,
    'Ditolak' => 0,
    'Selesai' => 0
];

$hasil_ringkasan = mysqli_query(
    $koneksi,
    "SELECT status, COUNT(*) AS total
     FROM peminjaman
     WHERE status IN ('Disetujui', 'Ditolak', 'Selesai')
     GROUP BY status"
);

if ($hasil_ringkasan) {
    while ($row = mysqli_fetch_assoc($hasil_ringkasan)) {
        $ringkasan[$row['status']] = (int)$row['total'];
    }
}

function warnaStatus($status) {
    switch ($status) {
        case 'Disetujui': return 'success';
        case 'Ditolak': return 'danger';
        case 'Selesai': return 'primary';
        default: return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman | SIPRAS</title>

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
            overflow-y: auto;
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

        .summary-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 5px 22px #18365b08;
        }

        .summary-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #e6efff;
            color: #0d47a1;
            margin-bottom: 14px;
        }

        .summary-number { font-size: 25px; font-weight: 700; }
        .summary-label { color: #7b8798; font-size: 12px; }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 23px;
            box-shadow: 0 5px 22px #18365b08;
        }

        .section-title { font-size: 16px; font-weight: 700; }

        .form-control, .form-select {
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

        .table { font-size: 12px; vertical-align: middle; }
        .table thead th {
            background: #f5f8fd;
            color: #52637b;
            font-weight: 600;
            white-space: nowrap;
            padding: 14px;
        }

        .table tbody td { padding: 14px; }
        .badge { font-weight: 500; padding: 7px 10px; }

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

    <a href="ruangan.php" class="nav-link">
        <i class="fa-solid fa-door-open"></i>
        <span class="nav-text">Data Ruangan</span>
    </a>

    <a href="riwayat.php" class="nav-link active">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span class="nav-text">Riwayat Peminjaman</span>
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
            <h4>Riwayat Peminjaman</h4>
            <div class="muted">Daftar pengajuan yang sudah diproses oleh petugas.</div>
        </div>

        <div class="profile">
            <div class="avatar"><i class="fa-solid fa-user-check"></i></div>
            <div class="profile-name">
                <div style="font-size:12px;font-weight:600;"><?= aman($nama_petugas); ?></div>
                <div class="muted">Petugas</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="summary-card">
                <div class="summary-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="summary-number"><?= $ringkasan['Disetujui']; ?></div>
                <div class="summary-label">Peminjaman Disetujui</div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="summary-card">
                <div class="summary-icon" style="background:#ffe8e8;color:#dc3545">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div class="summary-number"><?= $ringkasan['Ditolak']; ?></div>
                <div class="summary-label">Peminjaman Ditolak</div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="summary-card">
                <div class="summary-icon" style="background:#e3f7ed;color:#198754">
                    <i class="fa-solid fa-flag-checkered"></i>
                </div>
                <div class="summary-number"><?= $ringkasan['Selesai']; ?></div>
                <div class="summary-label">Peminjaman Selesai</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <h5 class="section-title mb-1">Daftar Riwayat</h5>
        <div class="muted">Cari dan filter peminjaman berdasarkan statusnya.</div>

        <form method="GET" class="row g-2 mt-3 mb-3">
            <div class="col-12 col-md-5">
                <input type="text" name="cari" class="form-control"
                       placeholder="Cari nama, alat, ruangan..."
                       value="<?= aman($cari); ?>">
            </div>

            <div class="col-12 col-md-4">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="Disetujui" <?= $status_pilihan === 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                    <option value="Ditolak" <?= $status_pilihan === 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    <option value="Selesai" <?= $status_pilihan === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>
                <a href="riwayat.php" class="btn btn-light border" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Nama Alat / Ruangan</th>
                        <th>Jumlah</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data && mysqli_num_rows($data) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($p = mysqli_fetch_assoc($data)): ?>
                            <?php
                            $jenis = $p['jenis_peminjaman'] ?? '';
                            $nama_item = $jenis === 'ruangan'
                                ? ($p['nama_ruangan'] ?? '-')
                                : ($p['nama_alat'] ?? '-');
                            $status = $p['status'] ?? '';
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= aman($p['nama_peminjam'] ?? '-'); ?></td>
                                <td><?= aman(ucfirst($jenis)); ?></td>
                                <td><?= aman($nama_item); ?></td>
                                <td><?= aman($p['jumlah'] ?? 1); ?></td>
                                <td><?= aman($p['tanggal_mulai'] ?? '-'); ?></td>
                                <td><?= aman($p['tanggal_selesai'] ?? '-'); ?></td>
                                <td><?= aman($p['keperluan'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge text-bg-<?= warnaStatus($status); ?>">
                                        <?= aman($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-clock-rotate-left fa-2x mb-3 d-block"></i>
                                Belum ada riwayat peminjaman yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center muted mt-4">
        SIPRAS &copy; <?= date('Y'); ?> · SMK Negeri 3 Kendal
    </div>
</main>

</body>
</html>
