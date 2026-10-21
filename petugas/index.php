
<?php
session_start();
include "../koneksi.php";

// Pastikan pengguna sudah login sebagai petugas
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

function hitung($koneksi, $status) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total FROM peminjaman WHERE status = ?"
    );

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    return (int)($data['total'] ?? 0);
}

$menunggu = hitung($koneksi, 'Menunggu');
$disetujui = hitung($koneksi, 'Disetujui');
$ditolak = hitung($koneksi, 'Ditolak');

$total_pengajuan = $menunggu + $disetujui + $ditolak;

// Ambil pengajuan terbaru
$sql = "
    SELECT
        p.id_peminjaman,
        p.jenis_peminjaman,
        p.jumlah,
        p.tanggal_mulai,
        p.tanggal_selesai,
        p.keperluan,
        p.status,
        u.nama AS nama_peminjam,
        a.nama_alat,
        r.nama_ruangan
    FROM peminjaman p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN alat a ON p.id_alat = a.id_alat
    LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
    ORDER BY p.id_peminjaman DESC
    LIMIT 8
";

$pengajuan = mysqli_query($koneksi, $sql);

$nama_petugas = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas | SIPRAS</title>

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

        .brand h5 {
            font-weight: 700;
            margin: 0;
        }

        .brand small {
            color: #d9e7ff;
            font-size: 11px;
        }

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
            text-decoration: none;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: #2874d9;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
        }

        .topbar h4 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .muted {
            color: #7b8798;
            font-size: 12px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 10px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 18px #18365b0a;
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

        .stat-card {
            border: none;
            background: white;
            border-radius: 16px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 5px 22px #18365b08;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            font-size: 18px;
        }

        .blue { background: #e6efff; color: #1e63c5; }
        .orange { background: #fff0d9; color: #d98a12; }
        .green { background: #dcf7e8; color: #1c9858; }
        .red { background: #ffe4e4; color: #d64545; }

        .stat-number {
            font-size: 29px;
            font-weight: 700;
            margin: 0;
        }

        .section-card {
            margin-top: 25px;
            background: white;
            border-radius: 16px;
            padding: 23px;
            box-shadow: 0 5px 22px #18365b08;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .table {
            vertical-align: middle;
            font-size: 12px;
            margin-top: 18px;
        }

        .table thead th {
            color: #77849a;
            font-weight: 600;
            background: #f7f9fd;
            border-bottom: none;
            padding: 14px 12px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px 12px;
            border-color: #edf1f7;
        }

        .badge-status {
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-menunggu { background: #fff0cf; color: #a56a00; }
        .status-disetujui { background: #d9f6e5; color: #18834a; }
        .status-ditolak { background: #ffe0e0; color: #c53636; }
        .status-lain { background: #e8edf5; color: #56657b; }

        .btn-primary {
            background: #0d47a1;
            border-color: #0d47a1;
            border-radius: 9px;
            font-size: 12px;
            padding: 10px 15px;
        }

        .btn-primary:hover {
            background: #09377f;
        }

        .empty {
            padding: 35px 10px;
            text-align: center;
            color: #8a96a8;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 72px;
                padding: 20px 10px;
            }

            .brand { padding: 0 4px 30px; }
            .brand-text, .menu-label, .nav-text { display: none; }
            .nav-link { justify-content: center; padding: 14px 5px; }
            .main { margin-left: 72px; padding: 20px 14px; }
        }

        @media (max-width: 550px) {
            .topbar { align-items: flex-start; }
            .topbar h4 { font-size: 18px; }
            .profile { padding: 8px; }
            .profile-name { display: none; }
            .section-card { padding: 15px; }
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

    <a href="index.php" class="nav-link active">
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

    <!-- MENU RIWAYAT DITAMBAHKAN -->
    <a href="riwayat.php" class="nav-link">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span class="nav-text">Riwayat Peminjaman</span>
    </a>

    <div class="menu-label mt-4">Akun</div>

    <a href="../logout.php" class="nav-link"
       onclick="return confirm('Yakin ingin keluar?')">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="nav-text">Logout</span>
    </a>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h4>Dashboard Petugas</h4>
            <div class="muted">Pantau dan periksa pengajuan peminjaman sarana prasarana.</div>
        </div>

        <div class="profile">
            <div class="avatar"><i class="fa-solid fa-user-check"></i></div>
            <div class="profile-name">
                <div style="font-size:12px;font-weight:600;">
                    <?= aman($nama_petugas); ?>
                </div>
                <div class="muted">Petugas</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="muted">Menunggu Persetujuan</div>
                    <div class="stat-icon orange"><i class="fa-regular fa-clock"></i></div>
                </div>
                <p class="stat-number"><?= $menunggu; ?></p>
                <div class="muted">Pengajuan perlu diperiksa</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="muted">Disetujui</div>
                    <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                </div>
                <p class="stat-number"><?= $disetujui; ?></p>
                <div class="muted">Pengajuan telah disetujui</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="muted">Ditolak</div>
                    <div class="stat-icon red"><i class="fa-solid fa-xmark"></i></div>
                </div>
                <p class="stat-number"><?= $ditolak; ?></p>
                <div class="muted">Pengajuan tidak disetujui</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="muted">Total Pengajuan</div>
                    <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                </div>
                <p class="stat-number"><?= $total_pengajuan; ?></p>
                <div class="muted">Total tiga status utama</div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="section-title">Pengajuan Peminjaman Terbaru</h5>
                <div class="muted mt-1">Daftar pengajuan yang masuk ke sistem SIPRAS.</div>
            </div>

            <a href="peminjaman.php" class="btn btn-primary">
                <i class="fa-solid fa-clipboard-check me-1"></i>
                Periksa Pengajuan
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Barang / Ruangan</th>
                        <th>Tanggal Mulai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($pengajuan && mysqli_num_rows($pengajuan) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($p = mysqli_fetch_assoc($pengajuan)): ?>
                        <?php
                        $jenis = strtolower($p['jenis_peminjaman'] ?? '');
                        $nama_item = ($jenis === 'ruangan')
                            ? ($p['nama_ruangan'] ?? 'Ruangan tidak tersedia')
                            : ($p['nama_alat'] ?? 'Alat tidak tersedia');

                        $status = $p['status'] ?? '';
                        $kelas_status = 'status-lain';

                        if ($status === 'Menunggu') {
                            $kelas_status = 'status-menunggu';
                        } elseif ($status === 'Disetujui') {
                            $kelas_status = 'status-disetujui';
                        } elseif ($status === 'Ditolak') {
                            $kelas_status = 'status-ditolak';
                        }
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= aman($p['nama_peminjam'] ?? '-'); ?></td>
                            <td><?= aman(ucfirst($jenis)); ?></td>
                            <td><?= aman($nama_item); ?></td>
                            <td>
                                <?= !empty($p['tanggal_mulai'])
                                    ? aman(date('d-m-Y', strtotime($p['tanggal_mulai'])))
                                    : '-'; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $kelas_status; ?>">
                                    <?= aman($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <i class="fa-regular fa-folder-open fa-2x mb-3"></i>
                                <div>Belum ada pengajuan peminjaman.</div>
                            </div>
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
