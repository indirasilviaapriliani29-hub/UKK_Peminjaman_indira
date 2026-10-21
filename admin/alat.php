```php
<?php
include "../koneksi.php";

$cari = trim($_GET['cari'] ?? '');
$cari_sql = mysqli_real_escape_string($koneksi, $cari);

$query = mysqli_query($koneksi, "
    SELECT a.*, k.nama_kategori
    FROM alat a
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
    WHERE a.nama_alat LIKE '%$cari_sql%'
       OR a.kode_alat LIKE '%$cari_sql%'
    ORDER BY a.id_alat DESC
");

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alat | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fc;
            font-family: 'Poppins', sans-serif;
            color: #203653;
            font-size: 13px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 269px;
            height: 100vh;
            padding: 25px 17px;
            background: #173b68;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .brand {
            padding: 0 12px;
            margin-bottom: 43px;
        }

        .brand h4 {
            font-size: 21px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .brand p {
            color: #d4e2f4;
            font-size: 11px;
            margin: 0;
        }

        .menu-title {
            font-size: 10px;
            letter-spacing: 1.5px;
            color: #b9cde7;
            margin: 25px 13px 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 15px;
            margin-bottom: 7px;
            color: #e2ebf8;
            text-decoration: none;
            border-radius: 11px;
            font-size: 13px;
            transition: .2s;
        }

        .sidebar a i {
            width: 17px;
            text-align: center;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2878c9;
            color: white;
        }

        .main {
            margin-left: 269px;
            padding: 30px 32px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            border: 1px solid #e6edf7;
            border-radius: 17px;
            padding: 19px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 27px;
        }

        .topbar h4 {
            font-size: 21px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .subtitle {
            color: #8090a8;
            margin: 0;
            font-size: 12px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 13px;
            text-align: right;
        }

        .profile strong {
            display: block;
            font-size: 13px;
        }

        .profile small {
            color: #8090a8;
        }

        .profile-icon {
            background: #e7f1ff;
            color: #2878c9;
            border-radius: 50%;
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            font-size: 17px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 23px;
        }

        .page-heading h3 {
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #8090a8;
            margin: 0;
            font-size: 12px;
        }

        .btn-primary {
            background: #2878c9;
            border-color: #2878c9;
        }

        .btn-primary:hover {
            background: #1c65ad;
            border-color: #1c65ad;
        }

        .data-card {
            background: white;
            border: 1px solid #e6edf7;
            border-radius: 17px;
            padding: 22px;
            box-shadow: 0 5px 15px rgba(31, 58, 92, .04);
        }

        .search-box {
            max-width: 450px;
        }

        .search-box .form-control {
            border-radius: 9px 0 0 9px;
            padding: 10px 13px;
            font-size: 12px;
            border-color: #e0e7f0;
        }

        .search-box .btn {
            border-radius: 0 9px 9px 0;
            padding: 9px 17px;
            font-size: 12px;
        }

        .table {
            margin-top: 20px;
            font-size: 12px;
            vertical-align: middle;
        }

        .table thead th {
            background: #edf4fc;
            color: #25466e;
            font-weight: 600;
            padding: 13px 12px;
            border-bottom: 1px solid #dce6f3;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 12px;
            border-color: #edf0f5;
        }

        .table tbody tr:hover {
            background: #f8fbff;
        }

        .foto-alat {
            width: 53px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5eaf1;
        }

        .badge-kondisi {
            padding: 6px 9px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
        }

        .baik {
            background: #e1f7eb;
            color: #16834b;
        }

        .perbaikan {
            background: #fff0d8;
            color: #a66b00;
        }

        .rusak {
            background: #ffe4e5;
            color: #c3333b;
        }

        .aksi {
            display: flex;
            gap: 6px;
        }

        .aksi .btn {
            width: 30px;
            height: 30px;
            padding: 0;
            display: grid;
            place-items: center;
            border-radius: 7px;
        }

        .empty-state {
            padding: 35px !important;
            color: #8593a8;
            text-align: center;
        }

        footer {
            text-align: center;
            color: #8190a6;
            margin-top: 30px;
            font-size: 11px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .brand {
                margin-bottom: 20px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .topbar {
                padding: 15px;
            }

            .page-heading {
                align-items: flex-start;
            }

            .page-heading h3 {
                font-size: 19px;
            }

            .data-card {
                padding: 13px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <h4><i class="fa-solid fa-school me-2"></i>SIPRAS</h4>
        <p>SMK Negeri 3 Kendal</p>
    </div>

    <div class="menu-title">MENU UTAMA</div>
    <a href="index.php">
        <i class="fa-solid fa-house"></i> Dashboard
    </a>

    <div class="menu-title">DATA MASTER</div>
    <a href="kategori.php">
        <i class="fa-solid fa-layer-group"></i> Kategori
    </a>
    <a href="alat.php" class="active">
        <i class="fa-solid fa-toolbox"></i> Data Alat
    </a>
    <a href="ruangan.php">
        <i class="fa-solid fa-door-open"></i> Data Ruangan
    </a>
    <a href="pengguna.php">
        <i class="fa-solid fa-users"></i> Data Pengguna
    </a>

    <div class="menu-title">TRANSAKSI</div>
    <a href="peminjaman.php">
        <i class="fa-solid fa-clipboard-list"></i> Peminjaman
    </a>
    <a href="pengembalian.php">
        <i class="fa-solid fa-rotate-left"></i> Pengembalian
    </a>

    <div class="menu-title">AKUN</div>
    <a href="../logout.php"
       onclick="return confirm('Yakin ingin logout?')">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h4>Data Alat</h4>
            <p class="subtitle">Sistem Informasi Sarana dan Prasarana</p>
        </div>

        <div class="profile">
            <div>
                <strong>admin</strong>
                <small>Administrator</small>
            </div>
            <div class="profile-icon">
                <i class="fa-solid fa-user-gear"></i>
            </div>
        </div>
    </header>

    <section class="page-heading">
        <div>
            <h3>Data Alat</h3>
            <p>Kelola inventaris sarana dan prasarana sekolah.</p>
        </div>

        <a href="tambah_alat.php" class="btn btn-primary btn-sm px-3 py-2">
            <i class="fa-solid fa-plus me-1"></i> Tambah Alat
        </a>
    </section>

    <section class="data-card">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1">Daftar Inventaris Alat</h6>
                <p class="subtitle">Data alat yang tersedia di sekolah.</p>
            </div>

            <form method="GET" class="input-group search-box">
                <input type="text" name="cari" class="form-control"
                       placeholder="Cari nama atau kode alat..."
                       value="<?= htmlspecialchars($cari) ?>">

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>

                <?php if ($cari !== ''): ?>
                    <a href="alat.php" class="btn btn-outline-secondary"
                       title="Reset pencarian">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Foto</th>
                        <th>Kode Alat</th>
                        <th>Nama Alat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Kondisi</th>
                        <th>Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?php if (!empty($row['foto'])): ?>
                                    <img class="foto-alat"
                                         src="../assets/img/<?= htmlspecialchars($row['foto']) ?>"
                                         alt="Foto alat"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="foto-alat d-grid place-items-center"
                                         style="display:grid!important;place-items:center;background:#f0f5fc;color:#91a4bd;">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['kode_alat'] ?: '-') ?>
                            </td>

                            <td class="fw-semibold">
                                <?= htmlspecialchars($row['nama_alat']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama_kategori'] ?? '-') ?>
                            </td>

                            <td>
                                <span class="fw-semibold">
                                    <?= (int)$row['stok'] ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                $kondisi = $row['kondisi'] ?? 'Baik';
                                $kelas = 'baik';

                                if ($kondisi === 'Perlu Perbaikan') {
                                    $kelas = 'perbaikan';
                                } elseif ($kondisi === 'Rusak') {
                                    $kelas = 'rusak';
                                }
                                ?>
                                <span class="badge-kondisi <?= $kelas ?>">
                                    <?= htmlspecialchars($kondisi) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['lokasi'] ?? '-') ?>
                            </td>

                            <td>
                                <div class="aksi">
                                    <a href="edit_alat.php?id=<?= (int)$row['id_alat'] ?>"
                                       class="btn btn-warning btn-sm"
                                       title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <a href="hapus_alat.php?id=<?= (int)$row['id_alat'] ?>"
                                       class="btn btn-danger btn-sm"
                                       title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus alat ini?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-box-open fa-2x mb-3 d-block"></i>
                            <strong>Data alat belum tersedia</strong>
                            <div class="mt-1">
                                <?= $cari !== ''
                                    ? 'Tidak ada alat yang cocok dengan pencarian.'
                                    : 'Silakan tambahkan data alat terlebih dahulu.' ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-secondary mt-3" style="font-size:11px">
            Total data ditampilkan: <?= mysqli_num_rows($query) ?> alat
        </div>
    </section>

    <footer>
        © 2026 SIPRAS · SMK Negeri 3 Kendal
    </footer>

</main>

</body>
</html>
```