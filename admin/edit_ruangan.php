<?php
include "../koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ruangan.php");
    exit;
}

$id = (int) $_GET['id'];
$error = "";

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM ruangan WHERE id_ruangan = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: ruangan.php");
    exit;
}

$nama = $data['nama_ruangan'];
$kode = $data['kode_ruangan'] ?? "";
$lokasi = $data['lokasi'] ?? "";
$kapasitas = $data['kapasitas'] ?? 0;
$kondisi = $data['kondisi'] ?? "Baik";
$keterangan = $data['keterangan'] ?? "";
$foto_lama = $data['foto'] ?? "";

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_ruangan']);
    $kode = trim($_POST['kode_ruangan']);
    $lokasi = trim($_POST['lokasi']);
    $kapasitas = (int) $_POST['kapasitas'];
    $kondisi = $_POST['kondisi'];
    $keterangan = trim($_POST['keterangan']);

    $foto = $foto_lama;
    $foto_baru = "";

    if ($nama == "") {
        $error = "Nama ruangan wajib diisi!";
    } elseif ($kapasitas < 0) {
        $error = "Kapasitas tidak boleh kurang dari 0!";
    } elseif (!in_array($kondisi, ['Baik', 'Perlu Perbaikan', 'Rusak'])) {
        $error = "Kondisi ruangan tidak valid!";
    }

    if ($error == "" && !empty($_FILES['foto']['name'])) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $error = "Foto gagal diunggah!";
        } else {
            $ekstensi = strtolower(
                pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = "Foto harus JPG, JPEG, PNG, atau WEBP!";
            } else {
                $folder = "../assets/img/";

                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                $foto_baru = uniqid('ruangan_', true) . '.' . $ekstensi;

                if (move_uploaded_file(
                    $_FILES['foto']['tmp_name'],
                    $folder . $foto_baru
                )) {
                    $foto = $foto_baru;
                } else {
                    $error = "Foto gagal disimpan!";
                }
            }
        }
    }

    if ($error == "") {
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE ruangan SET
                nama_ruangan = ?,
                kode_ruangan = NULLIF(?, ''),
                lokasi = NULLIF(?, ''),
                kapasitas = ?,
                kondisi = ?,
                foto = NULLIF(?, ''),
                keterangan = NULLIF(?, '')
             WHERE id_ruangan = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssisssi",
            $nama,
            $kode,
            $lokasi,
            $kapasitas,
            $kondisi,
            $foto,
            $keterangan,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            if (
                $foto_baru != "" &&
                $foto_lama != "" &&
                is_file("../assets/img/" . basename($foto_lama))
            ) {
                unlink("../assets/img/" . basename($foto_lama));
            }

            header("Location: ruangan.php?pesan=edit");
            exit;
        } else {
            if ($foto_baru != "" && is_file("../assets/img/" . $foto_baru)) {
                unlink("../assets/img/" . $foto_baru);
            }

            $error = "Data ruangan gagal diperbarui!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ruangan | SIPRAS</title>

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

        .foto-lama {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body>
<div class="container container-form">
    <h3 class="fw-bold">Edit Ruangan</h3>
    <p class="text-secondary">Perbarui data ruangan sekolah.</p>

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
                    <input type="text" name="nama_ruangan" class="form-control"
                        maxlength="100" required
                        value="<?= htmlspecialchars($nama); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Ruangan</label>
                    <input type="text" name="kode_ruangan" class="form-control"
                        maxlength="30"
                        value="<?= htmlspecialchars($kode); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($lokasi); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Kapasitas (orang)</label>
                    <input type="number" name="kapasitas" class="form-control"
                        min="0" value="<?= htmlspecialchars((string) $kapasitas); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Kondisi</label>
                    <select name="kondisi" class="form-select" required>
                        <?php foreach (['Baik', 'Perlu Perbaikan', 'Rusak'] as $pilih) { ?>
                            <option value="<?= $pilih; ?>"
                                <?= $kondisi == $pilih ? 'selected' : ''; ?>>
                                <?= $pilih; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto Ruangan</label><br>

                    <?php if ($foto_lama != "") { ?>
                        <img
                            src="../assets/img/<?= htmlspecialchars(basename($foto_lama)); ?>"
                            class="foto-lama mb-2"
                            alt="Foto ruangan"
                        >
                        <br>
                    <?php } ?>

                    <input type="file" name="foto" class="form-control"
                        accept=".jpg,.jpeg,.png,.webp">

                    <small class="text-secondary">
                        Kosongkan jika tidak ingin mengganti foto.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control"
                        rows="4"><?= htmlspecialchars($keterangan); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="ruangan.php" class="btn btn-secondary">Kembali</a>

            </form>
        </div>
    </div>
</div>
</body>
</html>