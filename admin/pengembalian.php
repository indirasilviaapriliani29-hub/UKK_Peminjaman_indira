<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_petugas = (int) $_SESSION['id_user'];
$pesan = "";
$tipe_pesan = "success";

/* PROSES PENGEMBALIAN */
if (isset($_POST['kembalikan'])) {
    $id_peminjaman = (int) $_POST['id_peminjaman'];
    $kondisi = $_POST['kondisi_kembali'] ?? '';
    $denda = (float) ($_POST['denda'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if (!in_array($kondisi, ['Baik', 'Rusak', 'Hilang'], true) || $denda < 0) {
        $pesan = "Data pengembalian tidak valid.";
        $tipe_pesan = "danger";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT status FROM peminjaman
                 WHERE id_peminjaman = ? FOR UPDATE"
            );
            mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);
            $pinjaman = mysqli_fetch_assoc($hasil);

            if (!$pinjaman || $pinjaman['status'] != 'Dipinjam') {
                throw new Exception("Peminjaman tidak ditemukan atau belum berstatus Dipinjam.");
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT id_pengembalian FROM pengembalian
                 WHERE id_peminjaman = ? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
            mysqli_stmt_execute($stmt);

            if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
                throw new Exception("Pengembalian sudah pernah dicatat.");
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO pengembalian
                (id_peminjaman, tanggal_pengembalian, kondisi_kembali,
                 denda, catatan, id_petugas)
                VALUES (?, NOW(), ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt, "isdsi",
                $id_peminjaman, $kondisi, $denda, $catatan, $id_petugas
            );
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE peminjaman SET status = 'Selesai'
                 WHERE id_peminjaman = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
            mysqli_stmt_execute($stmt);

            mysqli_commit($koneksi);
            header("Location: pengembalian.php?berhasil=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $pesan = $e->getMessage();
            $tipe_pesan = "danger";
        }
    }
}

if (isset($_GET['berhasil'])) {
    $pesan = "Pengembalian berhasil disimpan!";
}

/* DAFTAR PEMINJAMAN AKTIF */
$query = mysqli_query(
    $koneksi,
    "SELECT p.id_peminjaman, p.id_user, p.jenis_peminjaman,
            p.id_ruangan, p.tanggal_mulai, p.tanggal_selesai,
            p.keperluan, p.status, u.nama
     FROM peminjaman p
     LEFT JOIN users u ON p.id_user = u.id_user
     WHERE p.status = 'Dipinjam'
     ORDER BY p.id_peminjaman DESC"
);

if (!$query) {
    die("Gagal mengambil data peminjaman: " . mysqli_error($koneksi));
}

/* RIWAYAT PENGEMBALIAN */
$riwayat = mysqli_query(
    $koneksi,
    "SELECT pg.id_pengembalian, pg.id_peminjaman,
            pg.tanggal_pengembalian, pg.kondisi_kembali,
            pg.denda, pg.catatan, u.nama
     FROM pengembalian pg
     LEFT JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman
     LEFT JOIN users u ON p.id_user = u.id_user
     ORDER BY pg.id_pengembalian DESC"
);

if (!$riwayat) {
    die("Gagal mengambil riwayat: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian | SIPRAS</title>

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
            transition: .2s;
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

        .btn-primary:hover {
            background: #2675c9;
            border-color: #2675c9;
        }

        .form-label {
            font-weight: 500;
            font-size: 12px;
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
    <a href="peminjaman.php">
        <i class="fa-solid fa-hand-holding"></i><span>Peminjaman</span>
    </a>
    <a href="pengembalian.php" class="active">
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
            <i class="fa-solid fa-rotate-left me-2"></i>Pengembalian
        </h4>
        <p class="page-subtitle mb-0">
            Kelola pengembalian sarana dan prasarana sekolah.
        </p>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipe_pesan) ?> alert-dismissible fade show">
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- PEMINJAMAN AKTIF -->
    <div class="panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="panel-title mb-0">
                <i class="fa-solid fa-list-check me-2"></i>Peminjaman Aktif
            </h6>
            <span class="badge text-bg-primary">
                <?= mysqli_num_rows($query) ?> data
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Keperluan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama'] ?? 'Pengguna') ?></td>
                            <td>
                                <?= htmlspecialchars(ucfirst($row['jenis_peminjaman'])) ?>
                            </td>
                            <td><?= htmlspecialchars($row['tanggal_mulai']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal_selesai']) ?></td>
                            <td><?= htmlspecialchars($row['keperluan']) ?></td>
                            <td>
                                <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalKembali"
                                    data-id="<?= (int) $row['id_peminjaman'] ?>">
                                    <i class="fa-solid fa-check me-1"></i>Kembalikan
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            <i class="fa-solid fa-inbox me-2"></i>
                            Belum ada peminjaman aktif.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIWAYAT -->
    <div class="panel">
        <h6 class="panel-title mb-3">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Pengembalian
        </h6>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>ID Peminjaman</th>
                        <th>Peminjam</th>
                        <th>Tanggal Pengembalian</th>
                        <th>Kondisi</th>
                        <th>Denda</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($riwayat) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>#<?= (int) $r['id_peminjaman'] ?></td>
                            <td><?= htmlspecialchars($r['nama'] ?? 'Pengguna') ?></td>
                            <td><?= htmlspecialchars($r['tanggal_pengembalian'] ?? '-') ?></td>
                            <td>
                                <?php
                                $warna = 'success';
                                if ($r['kondisi_kembali'] === 'Rusak') {
                                    $warna = 'warning';
                                } elseif ($r['kondisi_kembali'] === 'Hilang') {
                                    $warna = 'danger';
                                }
                                ?>
                                <span class="badge text-bg-<?= $warna ?>">
                                    <?= htmlspecialchars($r['kondisi_kembali'] ?? '-') ?>
                                </span>
                            </td>
                            <td>Rp <?= number_format((float) $r['denda'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($r['catatan'] ?: '-') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Belum ada riwayat pengembalian.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL PENGEMBALIAN -->
<div class="modal fade" id="modalKembali" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <i class="fa-solid fa-rotate-left me-2"></i>Form Pengembalian
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id_peminjaman" id="id_peminjaman">

                <div class="mb-3">
                    <label class="form-label">Kondisi Saat Dikembalikan</label>
                    <select name="kondisi_kembali" class="form-select" required>
                        <option value="Baik">Baik</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Hilang">Hilang</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Denda (Rp)</label>
                    <input type="number" name="denda" class="form-control"
                        min="0" step="1000" value="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="3"
                        placeholder="Catatan tambahan jika ada"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="submit" name="kembalikan" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Pengembalian
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('modalKembali').addEventListener('show.bs.modal', function (event) {
    const tombol = event.relatedTarget;
    document.getElementById('id_peminjaman').value =
        tombol.getAttribute('data-id');
});
</script>

</body>
</html>