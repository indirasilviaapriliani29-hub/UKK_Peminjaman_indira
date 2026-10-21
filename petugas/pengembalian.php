<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['level'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";
$error = "";

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['mulai'])) {
    $id = (int) $_POST['id_peminjaman'];

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE peminjaman
         SET status = 'Dipinjam'
         WHERE id_peminjaman = ? AND status = 'Disetujui'"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $pesan = "Peminjaman berhasil ditandai sedang dipinjam.";
    } else {
        $error = "Status peminjaman tidak dapat diubah.";
    }
}

if (isset($_POST['kembali'])) {
    $id = (int) $_POST['id_peminjaman'];
    $kondisi = $_POST['kondisi_kembali'] ?? 'Baik';
    $denda = (float) ($_POST['denda'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');
    $petugas = (int) $_SESSION['id_user'];

    if (!in_array($kondisi, ['Baik', 'Rusak', 'Hilang'], true) || $denda < 0) {
        $error = "Kondisi atau denda tidak valid.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT status FROM peminjaman
                 WHERE id_peminjaman = ? FOR UPDATE"
            );
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($hasil);

            if (!$data || $data['status'] !== 'Dipinjam') {
                throw new Exception("Peminjaman harus berstatus Dipinjam.");
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT id_pengembalian FROM pengembalian
                 WHERE id_peminjaman = ? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "i", $id);
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
                $id, $kondisi, $denda, $catatan, $petugas
            );
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE peminjaman SET status = 'Selesai'
                 WHERE id_peminjaman = ? AND status = 'Dipinjam'"
            );
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) < 1) {
                throw new Exception("Status peminjaman gagal diperbarui.");
            }

            mysqli_commit($koneksi);
            $pesan = "Pengembalian berhasil dicatat.";
        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }
}

$query = mysqli_query(
    $koneksi,
    "SELECT p.*, u.nama,
            a.nama_alat, r.nama_ruangan,
            pg.tanggal_pengembalian, pg.kondisi_kembali,
            pg.denda
     FROM peminjaman p
     LEFT JOIN users u ON p.id_user = u.id_user
     LEFT JOIN alat a ON p.id_alat = a.id_alat
     LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
     LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
     ORDER BY p.id_peminjaman DESC"
);

if (!$query) {
    die("Gagal mengambil data: " . aman(mysqli_error($koneksi)));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengembalian | SIPRAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7fb;
            font-family: Arial, sans-serif;
            color: #26364a;
        }
        .container {
            max-width: 1200px;
        }
        .judul {
            font-weight: 700;
            color: #0d47a1;
        }
        .card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 3px 15px #0000000a;
        }
        .table th {
            background: #0d47a1;
            color: white;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-primary {
            background: #0d47a1;
        }
        .form-select, .form-control {
            min-width: 100px;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="judul mb-1">Pengembalian Sarana & Prasarana</h3>
            <p class="text-secondary mb-0">Kelola peminjaman dan pencatatan pengembalian.</p>
        </div>
        <a href="index.php" class="btn btn-outline-primary">Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-success"><?= aman($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= aman($error) ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Nama Sarana/Prasarana</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th>Pengembalian</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <?php
                        $namaBarang = $row['jenis_peminjaman'] === 'alat'
                            ? ($row['nama_alat'] ?? '-')
                            : ($row['nama_ruangan'] ?? '-');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= aman($row['nama'] ?? '-') ?></td>
                            <td><?= aman(ucfirst($row['jenis_peminjaman'])) ?></td>
                            <td><?= aman($namaBarang) ?></td>
                            <td><?= aman($row['tanggal_mulai']) ?></td>
                            <td><?= aman($row['tanggal_selesai']) ?></td>
                            <td>
                                <?php
                                $warna = [
                                    'Menunggu' => 'warning',
                                    'Disetujui' => 'primary',
                                    'Ditolak' => 'danger',
                                    'Dipinjam' => 'info',
                                    'Selesai' => 'success'
                                ];
                                $status = $row['status'];
                                ?>
                                <span class="badge text-bg-<?= $warna[$status] ?? 'secondary' ?>">
                                    <?= aman($status) ?>
                                </span>
                            </td>
                            <td style="min-width:230px">
                                <?php if ($row['status'] === 'Disetujui'): ?>
                                    <form method="post" onsubmit="return confirm('Tandai peminjaman ini sebagai Dipinjam?')">
                                        <input type="hidden" name="id_peminjaman" value="<?= (int)$row['id_peminjaman'] ?>">
                                        <button type="submit" name="mulai" class="btn btn-primary btn-sm">
                                            Mulai Dipinjam
                                        </button>
                                    </form>

                                <?php elseif ($row['status'] === 'Dipinjam'): ?>
                                    <form method="post" onsubmit="return confirm('Simpan pengembalian ini?')">
                                        <input type="hidden" name="id_peminjaman" value="<?= (int)$row['id_peminjaman'] ?>">

                                        <label class="form-label mt-1">Kondisi kembali</label>
                                        <select name="kondisi_kembali" class="form-select form-select-sm mb-2" required>
                                            <option value="Baik">Baik</option>
                                            <option value="Rusak">Rusak</option>
                                            <option value="Hilang">Hilang</option>
                                        </select>

                                        <label class="form-label">Denda (Rp)</label>
                                        <input type="number" name="denda" class="form-control form-control-sm mb-2"
                                               min="0" step="1000" value="0" required>

                                        <label class="form-label">Catatan</label>
                                        <textarea name="catatan" class="form-control form-control-sm mb-2"
                                                  rows="2" placeholder="Catatan jika ada"></textarea>

                                        <button type="submit" name="kembali" class="btn btn-success btn-sm w-100">
                                            Catat Pengembalian
                                        </button>
                                    </form>

                                <?php elseif ($row['status'] === 'Selesai'): ?>
                                    <span class="text-success">Sudah dikembalikan</span>
                                    <div class="small text-secondary">
                                        Kondisi: <?= aman($row['kondisi_kembali'] ?? '-') ?><br>
                                        Denda: Rp<?= number_format((float)($row['denda'] ?? 0), 0, ',', '.') ?>
                                    </div>

                                <?php else: ?>
                                    <span class="text-secondary small">Menunggu keputusan peminjaman</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">Belum ada data peminjaman.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>