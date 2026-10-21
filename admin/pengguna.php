<?php
include "../koneksi.php";

$query = mysqli_query(
    $koneksi,
    "SELECT id_user, nama, username, level, no_hp, created_at
     FROM users
     ORDER BY id_user DESC"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pengguna | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #26344a;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #0d47a1;
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .brand {
            font-size: 25px;
            font-weight: bold;
            text-align: center;
            padding-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: #e8efff;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 7px;
            border-radius: 8px;
            font-size: 14px;
        }

        .sidebar a i {
            width: 25px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #42a5f5;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .page-title {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #7b8798;
            font-size: 14px;
        }

        .content-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .05);
            margin-top: 25px;
        }

        .btn-primary {
            background: #0d47a1;
            border-color: #0d47a1;
        }

        .btn-primary:hover {
            background: #09377c;
            border-color: #09377c;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #eaf2ff;
            color: #174783;
            font-size: 13px;
            white-space: nowrap;
            padding: 14px;
        }

        .table tbody td {
            padding: 13px;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-boxes-stacked"></i> SIPRAS
    </div>

    <a href="index.php">
        <i class="fa-solid fa-gauge"></i> Dashboard
    </a>

    <a href="kategori.php">
        <i class="fa-solid fa-layer-group"></i> Kategori
    </a>

    <a href="alat.php">
        <i class="fa-solid fa-toolbox"></i> Data Alat
    </a>

    <a href="ruangan.php">
        <i class="fa-solid fa-door-open"></i> Data Ruangan
    </a>

    <a href="pengguna.php" class="active">
        <i class="fa-solid fa-users"></i> Data Pengguna
    </a>

    <a href="peminjaman.php">
        <i class="fa-solid fa-hand-holding"></i> Peminjaman
    </a>

    <a href="pengembalian.php">
        <i class="fa-solid fa-rotate-left"></i> Pengembalian
    </a>

    <a href="../logout.php"
       onclick="return confirm('Yakin ingin logout?')">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<div class="main">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="page-title">Data Pengguna</h4>
            <div class="subtitle">
                Kelola data akun pengguna sarana dan prasarana sekolah.
            </div>
        </div>

        <a href="tambah_pengguna.php" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Pengguna
        </a>
    </div>

    <?php
    $pesan = $_GET['pesan'] ?? '';

    $daftarPesan = [
        'tambah' => ['success', 'Pengguna berhasil ditambahkan.'],
        'edit' => ['success', 'Data pengguna berhasil diperbarui.'],
        'hapus' => ['success', 'Pengguna berhasil dihapus.'],
        'akun_sendiri' => ['warning', 'Akun yang sedang digunakan tidak dapat dihapus.'],
        'tidak_ditemukan' => ['warning', 'Data pengguna tidak ditemukan.'],
        'gagal' => ['danger', 'Pengguna gagal dihapus. Periksa relasi database.']
    ];

    if (isset($daftarPesan[$pesan])):
        $info = $daftarPesan[$pesan];
    ?>
        <div class="alert alert-<?= $info[0] ?> mt-4 mb-0">
            <?= htmlspecialchars($info[1]) ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">Daftar Pengguna</h6>

            <span class="text-muted small">
                Total: <?= mysqli_num_rows($query) ?> pengguna
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>No. HP</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>

                    <?php while ($data = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td class="fw-semibold">
                                <?= htmlspecialchars($data['nama'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['username'] ?? '-') ?>
                            </td>

                            <td>
                                <?php
                                $warna = 'success';

                                if ($data['level'] == 'admin') {
                                    $warna = 'danger';
                                } elseif ($data['level'] == 'petugas') {
                                    $warna = 'primary';
                                }
                                ?>

                                <span class="badge bg-<?= $warna ?>">
                                    <?= htmlspecialchars(ucfirst($data['level'] ?? '-')) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['no_hp'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['created_at'] ?? '-') ?>
                            </td>

                            <td class="text-nowrap">
                                <a href="edit_pengguna.php?id=<?= (int)$data['id_user'] ?>"
                                   class="btn btn-warning btn-sm"
                                   title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form action="hapus_pengguna.php"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$data['id_user'] ?>">

                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fa-solid fa-users fa-2x mb-2"></i>
                            <br>Belum ada data pengguna.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>