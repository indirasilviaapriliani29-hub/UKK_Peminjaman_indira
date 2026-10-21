<?php
include "../koneksi.php";

$error = "";

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_ruangan']);
    $kode = trim($_POST['kode_ruangan']);
    $lokasi = trim($_POST['lokasi']);
    $kapasitas = (int) $_POST['kapasitas'];
    $kondisi = $_POST['kondisi'];
    $keterangan = trim($_POST['keterangan']);
    $foto = "";

    if ($nama == "") {
        $error = "Nama ruangan wajib diisi!";
    } elseif ($kapasitas < 0) {
        $error = "Kapasitas tidak boleh kurang dari 0!";
    } elseif (!in_array($kondisi, ['Baik', 'Perlu Perbaikan', 'Rusak'])) {
        $error = "Kondisi ruangan tidak valid!";
    } else {
        if (!empty($_FILES['foto']['name'])) {
            $folder = "../assets/img/";

            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $ekstensi = strtolower(
                pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)
            );

            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ekstensi, $ekstensi_valid)) {
                $error = "Foto harus berformat JPG, JPEG, PNG, atau WEBP!";
            } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $error = "Foto gagal diunggah!";
            } else {
                $foto = uniqid('ruangan_', true) . '.' . $ekstensi;

                if (!move_uploaded_file(
                    $_FILES['foto']['tmp_name'],
                    $folder . $foto
                )) {
                    $error = "Foto gagal disimpan!";
                    $foto = "";
                }
            }
        }

        if ($error == "") {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO ruangan
                (nama_ruangan, kode_ruangan, lokasi, kapasitas, kondisi, foto, keterangan)
                VALUES (?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssisss",
                $nama,
                $kode,
                $lokasi,
                $kapasitas,
                $kondisi,
                $foto,
                $keterangan
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: ruangan.php?pesan=tambah");
                exit;
            } else {
                if ($foto != "" && is_file("../assets/img/" . $foto)) {
                    unlink("../assets/img/" . $foto);
                }

                $error = "Data ruangan gagal disimpan!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ruangan | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fc;
            font-family: Arial, sans-serif;
        }

        .container-form {
            max-width: 750px;
            margin: 35px auto;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }
    </style>
</head>

<body>
<div class="container container-form">
    <h3 class="fw-bold">Tambah Ruangan</h3>
    <p class="text-secondary">Masukkan data ruangan sekolah.</p>

    <div class="card">
        <div class="card-body p-4">

            <?php if ($error != "") { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Nama Ruangan</label>
                    <input
                        type="text"
                        name="nama_ruangan"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['nama_ruangan'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Ruangan</label>
                    <input
                        type="text"
                        name="kode_ruangan"
                        class="form-control"
                        maxlength="30"
                        value="<?= htmlspecialchars($_POST['kode_ruangan'] ?? ''); ?>"
                        placeholder="Contoh: R-001"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <input
                        type="text"
                        name="lokasi"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['lokasi'] ?? ''); ?>"
                        placeholder="Contoh: Lantai 1"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Kapasitas (orang)</label>
                    <input
                        type="number"
                        name="kapasitas"
                        class="form-control"
                        min="0"
                        value="<?= htmlspecialchars($_POST['kapasitas'] ?? '0'); ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Kondisi</label>
                    <select name="kondisi" class="form-select" required>
                        <?php
                        $pilihan = ['Baik', 'Perlu Perbaikan', 'Rusak'];
                        $kondisi_terpilih = $_POST['kondisi'] ?? 'Baik';

                        foreach ($pilihan as $pilih) {
                            $selected = ($kondisi_terpilih == $pilih) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($pilih) . '" ' . $selected . '>'
                                . htmlspecialchars($pilih) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto Ruangan</label>
                    <input
                        type="file"
                        name="foto"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >
                    <small class="text-secondary">Format JPG, JPEG, PNG, atau WEBP.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                </button>

                <a href="ruangan.php" class="btn btn-secondary">Kembali</a>

            </form>
        </div>
    </div>
</div>
</body>
</html>