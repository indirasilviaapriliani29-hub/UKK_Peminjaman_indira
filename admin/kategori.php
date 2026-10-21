<?php
include "../koneksi.php";

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY id_kategori DESC"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #f4f7fc;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            background: #0d47a1;
            color: white;
            padding: 25px 15px;
        }

        .sidebar h4 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 7px;
            border-radius: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #42a5f5;
        }

        .content {
            margin-left: 240px;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .table th {
            background: #eaf2ff;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 190px;
            }

            .content {
                margin-left: 190px;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h4><i class="fa-solid fa-school"></i> SIPRAS</h4>

    <a href="index.php">
        <i class="fa-solid fa-house me-2"></i> Dashboard
    </a>

    <a href="kategori.php" class="active">
        <i class="fa-solid fa-list me-2"></i> Kategori
    </a>

    <a href="alat.php">
        <i class="fa-solid fa-toolbox me-2"></i> Data Alat
    </a>

    <a href="ruangan.php">
        <i class="fa-solid fa-door-open me-2"></i> Data Ruangan
    </a>

    <a href="pengguna.php">
        <i class="fa-solid fa-users me-2"></i> Data Pengguna
    </a>

    <a href="peminjaman.php">
        <i class="fa-solid fa-hand-holding me-2"></i> Peminjaman
    </a>

    <a href="pengembalian.php">
        <i class="fa-solid fa-rotate-left me-2"></i> Pengembalian
    </a>

    <a href="../logout.php" onclick="return confirm('Yakin ingin logout?')">
        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
    </a>
</div>

<div class="content">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Data Kategori</h3>
            <p class="text-secondary mb-0">
                Kelola kategori sarana dan prasarana sekolah.
            </p>
        </div>

        <a href="tambah_kategori.php" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Kategori
        </a>
    </div>

    <?php if (isset($_GET['pesan'])) { ?>
        <?php if ($_GET['pesan'] == 'tambah') { ?>
            <div class="alert alert-success">Kategori berhasil ditambahkan.</div>
        <?php } elseif ($_GET['pesan'] == 'edit') { ?>
            <div class="alert alert-success">Kategori berhasil diperbarui.</div>
        <?php } elseif ($_GET['pesan'] == 'hapus') { ?>
            <div class="alert alert-success">Kategori berhasil dihapus.</div>
        <?php } ?>
    <?php } ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="70">No.</th>
                            <th>Nama Kategori</th>
                            <th>Keterangan</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;

                        if (mysqli_num_rows($query) > 0) {
                            while ($data = mysqli_fetch_assoc($query)) {
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>

                                <td>
                                    <?= htmlspecialchars($data['nama_kategori']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($data['keterangan'] ?? '-'); ?>
                                </td>

                                <td>
                                    <a href="edit_kategori.php?id=<?= $data['id_kategori']; ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </a>

                                    <a href="hapus_kategori.php?id=<?= $data['id_kategori']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">
                                    Belum ada data kategori.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>