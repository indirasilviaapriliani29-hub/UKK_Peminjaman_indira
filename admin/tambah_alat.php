<?php
include "../koneksi.php";

$error = "";

$kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");

if (isset($_POST['simpan'])) {
    $id_kategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
    $nama_alat = trim($_POST['nama_alat']);
    $kode_alat = trim($_POST['kode_alat']);
    $stok = (int)$_POST['stok'];
    $kondisi = $_POST['kondisi'];
    $lokasi = trim($_POST['lokasi']);
    $keterangan = trim($_POST['keterangan']);
    $foto = "";

    if ($nama_alat === "" || $stok < 0) {
        $error = "Nama alat wajib diisi dan stok tidak boleh kurang dari 0.";
    } elseif (!in_array($kondisi, ['Baik', 'Perlu Perbaikan', 'Rusak'])) {
        $error = "Kondisi alat tidak valid.";
    } else {
        if (!empty($_FILES['foto']['name'])) {
            $folder = "../assets/img/";

            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $ekstensi = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ekstensi, $ekstensi_valid)) {
                $error = "Foto harus berformat JPG, JPEG, PNG, atau WEBP.";
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 2 MB.";
            } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $error = "Foto gagal diunggah.";
            } else {
                $foto = uniqid('alat_') . '.' . $ekstensi;

                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $foto)) {
                    $foto = "";
                    $error = "Foto gagal disimpan.";
                }
            }
        }

        if ($error === "") {
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO alat
                (id_kategori, nama_alat, kode_alat, stok, kondisi, lokasi, foto, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ississss",
                $id_kategori,
                $nama_alat,
                $kode_alat,
                $stok,
                $kondisi,
                $lokasi,
                $foto,
                $keterangan
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: alat.php?pesan=tambah");
                exit;
            } else {
                $error = "Data gagal disimpan: " . mysqli_error($koneksi);
                if ($foto !== "" && file_exists("../assets/img/" . $foto)) {
                    unlink("../assets/img/" . $foto);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Alat | SIPRAS</title>

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

        .profile small {
            color: #8090a8;
        }

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

        .form-control,
        .form-select {
            font-size: 12px;
            padding: 11px 13px;
            border-color: #dfe7f1;
            border-radius: 9px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2878c9;
            box-shadow: 0 0 0 3px rgba(40, 120, 201, .1);
        }

        .btn-primary {
            background: #2878c9;
            border-color: #2878c9;
        }

        .preview {
            width: 110px;
            height: 90px;
            object-fit: cover;
            border: 1px solid #e0e7f0;
            border-radius: 10px;
            display: none;
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
            <h4>Tambah Alat</h4>
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
        <h3 class="page-title">Tambah Data Alat</h3>
        <p class="subtitle">Lengkapi informasi inventaris sarana dan prasarana sekolah.</p>
    </div>

    <div class="form-card">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:45px;height:45px;border-radius:12px;background:#e7f1ff;color:#2878c9;display:grid;place-items:center;font-size:18px">
                <i class="fa-solid fa-toolbox"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Informasi Alat</h6>
                <p class="subtitle">Masukkan data sesuai kondisi inventaris.</p>
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
                    <label class="form-label">Nama Alat <span class="text-danger">*</span></label>
                    <input type="text" name="nama_alat" class="form-control"
                           placeholder="Contoh: Proyektor Epson"
                           value="<?= htmlspecialchars($_POST['nama_alat'] ?? '') ?>"
                           maxlength="100" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kode Alat</label>
                    <input type="text" name="kode_alat" class="form-control"
                           placeholder="Contoh: ALT-001"
                           value="<?= htmlspecialchars($_POST['kode_alat'] ?? '') ?>"
                           maxlength="30">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($kat = mysqli_fetch_assoc($kategori)): ?>
                            <option value="<?= $kat['id_kategori'] ?>"
                                <?= (($_POST['id_kategori'] ?? '') == $kat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Stok <span class="text-danger">*</span></label>
                    <input type="number" name="stok" class="form-control"
                           min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kondisi Alat</label>
                    <select name="kondisi" class="form-select" required>
                        <?php foreach (['Baik', 'Perlu Perbaikan', 'Rusak'] as $k): ?>
                            <option value="<?= $k ?>"
                                <?= (($_POST['kondisi'] ?? 'Baik') === $k) ? 'selected' : '' ?>>
                                <?= $k ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control"
                           placeholder="Contoh: Lab RPL"
                           value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>"
                           maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label">Foto Alat</label>
                    <input type="file" name="foto" id="foto" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-secondary">Format JPG, JPEG, PNG, WEBP. Maksimal 2 MB.</small>
                    <img src="" id="preview" class="preview" alt="Preview foto">
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"
                              placeholder="Tambahkan keterangan alat..."><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="alat.php" class="btn btn-light border px-4">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                </a>

                <button type="submit" name="simpan" class="btn btn-primary px-4">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data
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
    } else {
        preview.style.display = 'none';
    }
});
</script>

</body>
</html>