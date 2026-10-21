<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$pesan = "";

/* PROSES SETUJUI / TOLAK */
if (isset($_POST['ubah_status'])) {
    $id = (int) $_POST['id_peminjaman'];
    $status = $_POST['status'];

    if (in_array($status, ['Disetujui', 'Ditolak'], true)) {
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE peminjaman
             SET status = ?
             WHERE id_peminjaman = ?
             AND status = 'Menunggu'"
        );

        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: peminjaman.php?pesan=" . urlencode("Status peminjaman berhasil diperbarui."));
            exit;
        } else {
            $pesan = "Data tidak berubah. Pastikan pengajuan masih berstatus Menunggu.";
        }
    }
}

if (isset($_GET['pesan'])) {
    $pesan = $_GET['pesan'];
}

/* PENCARIAN */
$cari = trim($_GET['cari'] ?? '');
$cari_sql = mysqli_real_escape_string($koneksi, $cari);

$sql = "SELECT
            p.id_peminjaman,
            p.id_user,
            p.jenis_peminjaman,
            p.id_ruangan,
            p.tanggal_pengajuan,
            p.tanggal_mulai,
            p.tanggal_selesai,
            p.keperluan,
            p.status,
            p.catatan_petugas,
            u.nama,
            r.nama_ruangan
        FROM peminjaman p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan";

if ($cari !== '') {
    $sql .= " WHERE u.nama LIKE '%$cari_sql%'
              OR p.jenis_peminjaman LIKE '%$cari_sql%'
              OR p.status LIKE '%$cari_sql%'
              OR r.nama_ruangan LIKE '%$cari_sql%'
              OR p.keperluan LIKE '%$cari_sql%'";
}

$sql .= " ORDER BY p.id_peminjaman DESC";

$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Gagal mengambil data peminjaman: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            background: #f4f7fc;
            color: #26354d;
            font-size: 13px;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #0d47a1;
            color: white;
            padding: 22px 14px;
            overflow-y: auto;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 25px;
            border-bottom: 1px solid #ffffff30;
            margin-bottom: 22px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #42a5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand h5 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
        }

        .brand small {
            font-size: 10px;
            color: #d8e7ff;
        }

        .menu-title {
            color: #b9d4fa;
            font-size: 10px;
            font-weight: 600;
            padding: 12px 12px 8px;
            letter-spacing: 1px;
        }

        .sidebar a {
            color: #e8f1ff;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            margin-bottom: 5px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 12px;
        }

        .sidebar a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #42a5f5;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 28px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: #18365f;
        }

        .page-subtitle {
            color: #7b8798;
            font-size: 12px;
        }

        .panel {
            background: white;
            border-radius: 13px;
            padding: 22px;
            box-shadow: 0 3px 15px #17365d0b;
            margin-bottom: 24px;
        }

        .panel-title {
            font-size: 15px;
            font-weight: 600;
            color: #18365f;
        }

        .table {
            font-size: 12px;
            margin-bottom: 0;
        }

        .table thead th {
            background: #0d47a1;
            color: white;
            font-weight: 500;
            padding: 12px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 11px;
            vertical-align: middle;
        }

        .btn {
            font-size: 12px;
            border-radius: 7px;
        }

        .btn-primary {
            background: #0d47a1;
            border-color: #0d47a1;
        }

        .form-control,
        .form-select {
            font-size: 12px;
            padding: 10px;
            border-radius: 7px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 72px;
                padding: 20px 8px;
            }

            .brand {
                justify-content: center;
                padding: 0 0 20px;
            }

            .brand-text,
            .menu-title,
            .sidebar a span {
                display: none;
            }

            .sidebar a {
                justify-content: center;
                padding: 13px 5px;
            }

            .sidebar a i {
                width: auto;
            }

            .main-content {
                margin-left: 72px;
                padding: 16px;
            }

            .page-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <div class="brand-text">
            <h5>SIPRAS</h5>
            <small>SMK Negeri 3 Kendal</small>
        </div>
    </div>

    <div class="menu-title">MENU UTAMA</div>
    <a href="index.php">
        <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
    </a>

    <div class="menu-title">DATA MASTER</div>
    <a href="kategori.php">
        <i class="fa-solid fa-layer-group"></i><span>Kategori</span>
    </a>
    <a href="alat.php">
        <i class="fa-solid fa-toolbox"></i><span>Data Alat</span>
    </a>
    <a href="ruangan.php">
        <i class="fa-solid fa-door-open"></i><span>Data Ruangan</span>
    </a>
    <a href="pengguna.php">
        <i class="fa-solid fa-users"></i><span>Data Pengguna</span>
    </a>

    <div class="menu-title">TRANSAKSI</div>
    <a href="peminjaman.php" class="active">
        <i class="fa-solid fa-hand-holding"></i><span>Peminjaman</span>
    </a>
    <a href="pengembalian.php">
        <i class="fa-solid fa-rotate-left"></i><span>Pengembalian</span>
    </a>

    <div class="menu-title">AKUN</div>
    <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
    </a>
</div>

<!-- KONTEN -->
<div class="main-content">

    <div class="mb-4">
        <h4 class="page-title mb-1">
            <i class="fa-solid fa-hand-holding me-2"></i>Data Peminjaman
        </h4>
        <p class="page-subtitle mb-0">
            Kelola pengajuan peminjaman sarana dan prasarana sekolah.
        </p>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <h6 class="panel-title mb-0">
                <i class="fa-solid fa-list me-2"></i>Daftar Peminjaman
            </h6>

            <form method="GET" class="d-flex gap-2">
                <input type="text" name="cari" class="form-control"
                    placeholder="Cari peminjaman..."
                    value="<?= htmlspecialchars($cari) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if ($cari !== ''): ?>
                    <a href="peminjaman.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Ruangan</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th>Catatan Petugas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <?php
                        $status = $row['status'] ?? 'Menunggu';
                        $warna = 'secondary';

                        if ($status === 'Menunggu') {
                            $warna = 'warning';
                        } elseif ($status === 'Disetujui') {
                            $warna = 'success';
                        } elseif ($status === 'Ditolak') {
                            $warna = 'danger';
                        } elseif ($status === 'Dipinjam') {
                            $warna = 'primary';
                        } elseif ($status === 'Selesai') {
                            $warna = 'dark';
                        }
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama'] ?? 'Pengguna') ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['jenis_peminjaman'])) ?></td>
                            <td>
                                <?= htmlspecialchars(
                                    $row['jenis_peminjaman'] === 'ruangan'
                                        ? ($row['nama_ruangan'] ?? '-')
                                        : 'Alat'
                                ) ?>
                            </td>
                            <td><?= htmlspecialchars($row['tanggal_pengajuan']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal_mulai']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal_selesai']) ?></td>
                            <td><?= htmlspecialchars($row['keperluan']) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $warna ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['catatan_petugas'] ?? '-') ?></td>
                            <td>
                                <?php if ($status === 'Menunggu'): ?>
                                    <div class="d-flex gap-1">
                                        <form method="POST"
                                            onsubmit="return confirm('Setujui pengajuan ini?')">
                                            <input type="hidden" name="id_peminjaman"
                                                value="<?= (int) $row['id_peminjaman'] ?>">
                                            <input type="hidden" name="status" value="Disetujui">
                                            <button type="submit" name="ubah_status"
                                                class="btn btn-success btn-sm" title="Setujui">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>

                                        <form method="POST"
                                            onsubmit="return confirm('Tolak pengajuan ini?')">
                                            <input type="hidden" name="id_peminjaman"
                                                value="<?= (int) $row['id_peminjaman'] ?>">
                                            <input type="hidden" name="status" value="Ditolak">
                                            <button type="submit" name="ubah_status"
                                                class="btn btn-danger btn-sm" title="Tolak">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-secondary py-4">
                            <i class="fa-solid fa-inbox me-2"></i>
                            Data peminjaman belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>