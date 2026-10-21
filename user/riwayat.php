
<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Peminjam';

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? '-'), ENT_QUOTES, 'UTF-8');
}

function warnaStatus($status) {
    switch ($status) {
        case 'Menunggu': return 'warning';
        case 'Disetujui': return 'success';
        case 'Ditolak': return 'danger';
        case 'Dipinjam': return 'primary';
        case 'Selesai': return 'secondary';
        default: return 'light';
    }
}

$sql = "SELECT p.*, a.nama_alat, r.nama_ruangan
        FROM peminjaman p
        LEFT JOIN alat a ON p.id_alat = a.id_alat
        LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
        WHERE p.id_user = ?
        ORDER BY p.id_peminjaman DESC";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die("Query riwayat gagal: " . aman(mysqli_error($koneksi)));
}

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$data_peminjaman = mysqli_stmt_get_result($stmt);

$daftar = [];
$total = 0;
$menunggu = 0;
$disetujui = 0;
$ditolak = 0;
$selesai = 0;

while ($p = mysqli_fetch_assoc($data_peminjaman)) {
    $daftar[] = $p;
    $total++;

    if ($p['status'] === 'Menunggu') $menunggu++;
    if ($p['status'] === 'Disetujui') $disetujui++;
    if ($p['status'] === 'Ditolak') $ditolak++;
    if ($p['status'] === 'Selesai') $selesai++;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Peminjaman | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f7fd;
            font-family: 'Poppins', sans-serif;
            color: #183b68;
        }

        .topbar {
            height: 68px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #15569b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 23px;
            font-weight: 700;
        }

        .brand i {
            font-size: 29px;
        }

        .brand small {
            display: block;
            font-size: 11px;
            font-weight: 400;
        }

        .user-info {
            font-size: 12px;
        }

        .sidebar {
            position: fixed;
            top: 68px;
            bottom: 0;
            left: 0;
            width: 250px;
            background: #174d88;
            padding: 22px 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: white;
            text-decoration: none;
            padding: 17px 20px;
            border-radius: 12px;
            margin-bottom: 7px;
            font-size: 14px;
            transition: .2s;
        }

        .sidebar a i {
            width: 22px;
            font-size: 19px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2671c8;
        }

        .main {
            margin-left: 250px;
            padding: 95px 30px 35px;
            min-height: 100vh;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .page-desc {
            color: #8192aa;
            font-size: 12px;
            margin-bottom: 25px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            padding: 18px;
            border-radius: 15px;
            box-shadow: 0 5px 18px #234f790b;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 12px;
            font-size: 17px;
        }

        .blue { background: #e1edff; color: #1767bd; }
        .orange { background: #fff0d7; color: #d88b12; }
        .green { background: #dff7e9; color: #21965b; }
        .red { background: #ffe5e8; color: #dc3545; }
        .purple { background: #efe5ff; color: #8351c9; }

        .stat-card small {
            font-size: 10px;
            color: #8294aa;
        }

        .stat-card h4 {
            font-size: 24px;
            font-weight: 700;
            margin: 4px 0 0;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px #234f790b;
            overflow: hidden;
        }

        .content-header {
            padding: 22px;
            border-bottom: 1px solid #edf1f6;
        }

        .content-header h5 {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 5px;
        }

        .content-header p {
            font-size: 11px;
            color: #8294aa;
            margin: 0;
        }

        .table {
            margin: 0;
            min-width: 1000px;
            font-size: 11px;
        }

        .table thead th {
            background: #f5f8fd;
            color: #61728a;
            font-size: 10px;
            text-transform: uppercase;
            padding: 15px;
            white-space: nowrap;
            border-bottom: 1px solid #e8edf5;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #edf1f6;
            color: #536174;
        }

        .table tbody tr:hover {
            background: #f8fbff;
        }

        .nomor {
            color: #1767bd;
            font-weight: 600;
        }

        .nama-item {
            color: #183b68;
            font-weight: 600;
        }

        .jenis-label {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 7px;
            background: #e8f2ff;
            color: #1767bd;
            font-size: 10px;
            font-weight: 600;
        }

        .badge {
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #8294aa;
        }

        .empty-state i {
            display: block;
            font-size: 43px;
            color: #a6bfdf;
            margin-bottom: 16px;
        }

        .empty-state h5 {
            font-size: 16px;
            font-weight: 700;
            color: #183b68;
        }

        .empty-state p {
            font-size: 12px;
            margin: 8px 0 0;
        }

        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 65px;
                padding: 15px 6px;
            }

            .sidebar a {
                justify-content: center;
                padding: 16px 5px;
            }

            .sidebar a span {
                display: none;
            }

            .main {
                margin-left: 65px;
                padding: 90px 15px 25px;
            }

            .topbar {
                padding: 0 15px;
            }

            .brand {
                font-size: 18px;
            }
        }

        @media (max-width: 520px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stat-card {
                padding: 13px;
            }

            .page-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <i class="fa-solid fa-school"></i>
        <div>
            SIPRAS
            <small>Peminjaman Sarpras Sekolah</small>
        </div>
    </div>

    <div class="user-info">
        <i class="fa-solid fa-circle-user me-2 fs-5"></i>
        Halo, <?= aman($nama) ?>
    </div>
</header>

<aside class="sidebar">
    <a href="index.php">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
    </a>

    <a href="riwayat.php" class="active">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>Riwayat Peminjaman</span>
    </a>

    <a href="../logout.php"
       onclick="return confirm('Yakin ingin keluar dari akun?')">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
    </a>
</aside>

<main class="main">

    <h3 class="page-title">Riwayat Peminjaman</h3>
    <p class="page-desc">
        Pantau pengajuan serta status peminjaman alat dan ruangan sekolah.
    </p>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <small>Total Pengajuan</small>
            <h4><?= $total ?></h4>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <small>Menunggu</small>
            <h4><?= $menunggu ?></h4>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <small>Disetujui</small>
            <h4><?= $disetujui ?></h4>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <small>Ditolak</small>
            <h4><?= $ditolak ?></h4>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fa-solid fa-flag-checkered"></i>
            </div>
            <small>Selesai</small>
            <h4><?= $selesai ?></h4>
        </div>
    </section>

    <section class="content-card">
        <div class="content-header">
            <h5>
                <i class="fa-solid fa-list-check text-primary me-2"></i>
                Daftar Riwayat Peminjaman
            </h5>
            <p>Semua pengajuan yang pernah kamu kirimkan.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Jenis</th>
                        <th>Nama Alat / Ruangan</th>
                        <th>Jumlah</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th>Catatan Petugas</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($daftar) > 0): ?>

                    <?php foreach ($daftar as $i => $p): ?>
                        <tr>
                            <td class="nomor"><?= $i + 1 ?></td>

                            <td>
                                <span class="jenis-label">
                                    <?= aman(ucfirst($p['jenis_peminjaman'])) ?>
                                </span>
                            </td>

                            <td class="nama-item">
                                <?php
                                if ($p['jenis_peminjaman'] === 'alat') {
                                    echo aman($p['nama_alat'] ?? 'Alat tidak ditemukan');
                                } else {
                                    echo aman($p['nama_ruangan'] ?? 'Ruangan tidak ditemukan');
                                }
                                ?>
                            </td>

                            <td>
                                <?= $p['jenis_peminjaman'] === 'alat'
                                    ? (int)($p['jumlah'] ?? 1)
                                    : '-' ?>
                            </td>

                            <td>
                                <?= !empty($p['tanggal_mulai'])
                                    ? aman(date('d-m-Y H:i', strtotime($p['tanggal_mulai'])))
                                    : '-' ?>
                            </td>

                            <td>
                                <?= !empty($p['tanggal_selesai'])
                                    ? aman(date('d-m-Y H:i', strtotime($p['tanggal_selesai'])))
                                    : '-' ?>
                            </td>

                            <td><?= aman($p['keperluan'] ?? '-') ?></td>

                            <td>
                                <span class="badge text-bg-<?= warnaStatus($p['status'] ?? '') ?>">
                                    <?= aman($p['status'] ?? '-') ?>
                                </span>
                            </td>

                            <td><?= aman($p['catatan_petugas'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-solid fa-folder-open"></i>
                                <h5>Belum Ada Peminjaman</h5>
                                <p>
                                    Riwayat pengajuanmu akan tampil di sini
                                    setelah mengajukan peminjaman melalui Dashboard.
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="text-secondary text-center mt-4" style="font-size:10px">
        &copy; <?= date('Y') ?> SIPRAS - SMK Negeri 3 Kendal
    </p>

</main>

</body>
</html>
