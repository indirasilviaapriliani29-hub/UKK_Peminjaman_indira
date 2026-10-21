<?php
include "../koneksi.php";

$error = "";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: alat.php");
    exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM alat WHERE id_alat = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$alat = mysqli_fetch_assoc($result);

if (!$alat) {
    header("Location: alat.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $id_kategori = !empty($_POST['id_kategori'])
        ? (int)$_POST['id_kategori'] : null;

    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $kode_alat = trim($_POST['kode_alat'] ?? '');
    $stok = filter_var($_POST['stok'] ?? '', FILTER_VALIDATE_INT);
    $kondisi = $_POST['kondisi'] ?? '';
    $lokasi = trim($_POST['lokasi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $foto = $alat['foto'] ?? '';

    if ($nama_alat === '' || $stok === false || $stok === null || $stok < 0) {
        $error = "Nama alat wajib diisi dan stok harus berupa angka minimal 0.";
    } elseif (!in_array($kondisi, ['Baik', 'Perlu Perbaikan', 'Rusak'], true)) {
        $error = "Kondisi alat tidak valid.";
    } elseif (strlen($nama_alat) > 100 || strlen($kode_alat) > 30 || strlen($lokasi) > 100) {
        $error = "Nama, kode, atau lokasi terlalu panjang.";
    } else {
        $folder = "../assets/img/";
        $foto_baru = "";
        $upload_valid = true;

        if (!empty($_FILES['foto']['name'])) {
            $ekstensi = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ekstensi, $ekstensi_valid, true)) {
                $error = "Foto harus berformat JPG, JPEG, PNG, atau WEBP.";
                $upload_valid = false;
            } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $error = "Foto gagal diunggah.";
                $upload_valid = false;
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 2 MB.";
                $upload_valid = false;
            } elseif (@getimagesize($_FILES['foto']['tmp_name']) === false) {
                $error = "File yang diunggah bukan gambar yang valid.";
                $upload_valid = false;
            } else {
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                $foto_baru = uniqid('alat_') . '.' . $ekstensi;

                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $foto_baru)) {
                    $error = "Foto gagal disimpan.";
                    $upload_valid = false;
                } else {
                    $foto = $foto_baru;
                }
            }
        }

        if ($upload_valid && $error === "") {
            $stmt = mysqli_prepare($koneksi, "
                UPDATE alat SET
                    id_kategori = ?,
                    nama_alat = ?,
                    kode_alat = ?,
                    stok = ?,
                    kondisi = ?,
                    lokasi = ?,
                    foto = ?,
                    keterangan = ?
                WHERE id_alat = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ississssi",
                $id_kategori,
                $nama_alat,
                $kode_alat,
                $stok,
                $kondisi,
                $lokasi,
                $foto,
                $keterangan,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {
                if ($foto_baru !== "" && !empty($alat['foto'])) {
                    $foto_lama = $folder . basename($alat['foto']);

                    if (is_file($foto_lama)) {
                        unlink($foto_lama);
                    }
                }

                header("Location: alat.php?pesan=edit");
                exit;
            } else {
                if ($foto_baru !== "" && is_file($folder . $foto_baru)) {
                    unlink($folder . $foto_baru);
                }

                $error = "Data gagal diperbarui: " . mysqli_error($koneksi);
            }
        }
    }

    // Pertahankan input jika validasi gagal.
    $alat['id_kategori'] = $id_kategori;
    $alat['nama_alat'] = $nama_alat;
    $alat['kode_alat'] = $kode_alat;
    $alat['stok'] = $_POST['stok'];
    $alat['kondisi'] = $kondisi;
    $alat['lokasi'] = $lokasi;
    $alat['keterangan'] = $keterangan;

    if (isset($foto_baru) && $foto_baru !== '' && $error !== '') {
        $alat['foto'] = $foto_baru;
    }
}

$kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Alat | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f4f7fc;
            color: #203653;
            font-family: 'Poppins', sans-serif;
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
            overflow-y: auto;
        }

        .brand {
            padding: 0 12px;
            margin-bottom: 43px;
            color: white;
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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
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

        .profile small { color: #8090a8; }

        .profile-icon {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #e7f1ff;
            color: #2878c9;
            font-size: 17px;
        }

        .page-title {
            font-size: 23px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .form-card {
            background: white;
            border: 1px solid #e6edf7;
            border-radius: 17px;
            padding: 27px;
            box-shadow: 0 5px 15px rgba(31, 58, 92, .04);
        }

        .form-label {
            font-weight: 600;
            color: #304966;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            font-size: 12px;
            padding: 11px 13px;
            border-color: #dfe7f1;
            border-radius: 9px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2878c9;
            box-shadow: 0 0 0 3px rgba(40, 120, 201, .1);
        }

        .btn-primary {
            background: #2878c9;
            border-color: #2878c9;
        }

        .preview {
            width: 120px;
            height: 95px;
            object-fit: cover;
            border: 1px solid #e0e7f0;
            border-radius: 10px;
            margin-top: 12px;
        }

        footer {
            text-align: center;
            color: #8190a6;
            margin-top: 30px;
            font-size: 11px;
        }

        @media(max-width: 900px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 20px; }
        }

        @media(max-width: 600px) {
            .sidebar { position: relative; width: 100%; height: auto; }
            .main { margin-left: 0; padding: 15px; }
            .form-card { padding: 18px; }
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
    <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>

    <div class="menu-title">DATA MASTER</div>
    <a href="kategori.php"><i class="fa-solid fa-layer-group"></i> Kategori</a>
    <a href="alat.php" class="active"><i class="fa-solid fa-toolbox"></i> Data Alat</a>
    <a href="ruangan.php"><i class="fa-solid fa-door-open"></i> Data Ruangan</a>
    <a href="pengguna.php"><i class="fa-solid fa-users"></i> Data Pengguna</a>

    <div class="menu-title">TRANSAKSI</div>
    <a href="peminjaman.php"><i class="fa-solid fa-clipboard-list"></i> Peminjaman</a>
    <a href="pengembalian.php"><i class="fa-solid fa-rotate-left"></i> Pengembalian</a>

    <div class="menu-title">AKUN</div>
    <a href="../logout.php" onclick="return confirm('Yakin ingin logout?')">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h4>Edit Alat</h4>
            <p class="subtitle">Sistem Informasi Sarana dan Prasarana</p>
        </div>

        <div class="profile">
            <div>
                <strong>admin</strong><br>
                <small>Administrator</small>
            </div>
            <div class="profile-icon">
                <i class="fa-solid fa-user-gear"></i>
            </div>
        </div>
    </header>

    <div class="mb-4">
        <h3 class="page-title">Edit Data Alat</h3>
        <p class="subtitle">Perbarui informasi inventaris sarana dan prasarana sekolah.</p>
    </div>

    <div class="form-card">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:45px;height:45px;border-radius:12px;background:#fff0d8;color:#b77912;display:grid;place-items:center;font-size:18px">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Informasi Alat</h6>
                <p class="subtitle">Ubah data sesuai kondisi inventaris terbaru.</p>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">

                <div class="col-md-6">
                    <label class="form-label">Nama Alat *</label>
                    <input type="text" name="nama_alat" class="form-control"
                           maxlength="100" required
                           value="<?= htmlspecialchars($alat['nama_alat']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kode Alat</label>
                    <input type="text" name="kode_alat" class="form-control"
                           maxlength="30"
                           value="<?= htmlspecialchars($alat['kode_alat'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select">
                        <option value="">-- Pilih Kategori --</option>

                        <?php while ($kat = mysqli_fetch_assoc($kategori)): ?>
                            <option value="<?= $kat['id_kategori'] ?>"
                                <?= ((string)($alat['id_kategori'] ?? '') === (string)$kat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Stok *</label>
                    <input type="number" name="stok" class="form-control"
                           min="0" required
                           value="<?= htmlspecialchars((string)$alat['stok']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kondisi</label>
                    <select name="kondisi" class="form-select" required>
                        <?php foreach (['Baik', 'Perlu Perbaikan', 'Rusak'] as $k): ?>
                            <option value="<?= $k ?>"
                                <?= $alat['kondisi'] === $k ? 'selected' : '' ?>>
                                <?= $k ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control"
                           maxlength="100"
                           value="<?= htmlspecialchars($alat['lokasi'] ?? '') ?>"
                           placeholder="Contoh: Lab RPL">
                </div>

                <div class="col-12">
                    <label class="form-label">Foto Alat</label>
                    <input type="file" name="foto" id="foto" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp">

                    <small class="text-secondary">Kosongkan jika tidak ingin mengganti foto. Maksimal 2 MB.</small>

                    <div>
                        <img
                            src="<?= !empty($alat['foto']) ? '../assets/img/' . rawurlencode(basename($alat['foto'])) : '' ?>"
                            id="preview"
                            class="preview"
                            alt="Foto alat"
                            style="<?= !empty($alat['foto']) ? '' : 'display:none' ?>"
                            onerror="this.style.display='none'">
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"
                              placeholder="Tambahkan keterangan alat..."><?= htmlspecialchars($alat['keterangan'] ?? '') ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="alat.php" class="btn btn-light border px-4">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>

                <button type="submit" name="simpan" class="btn btn-primary px-4">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <footer>© 2026 SIPRAS · SMK Negeri 3 Kendal</footer>

</main>

<script>
document.getElementById('foto').addEventListener('change', function () {
    const preview = document.getElementById('preview');

    if (this.files && this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.style.display = 'block';
    }
});
</script>

</body>
</html>