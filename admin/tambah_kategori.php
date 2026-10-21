<?php
include "../koneksi.php";

$error = "";

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_kategori']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama == "") {
        $error = "Nama kategori wajib diisi!";
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO kategori (nama_kategori, keterangan)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $nama,
            $keterangan
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: kategori.php?pesan=tambah");
            exit;
        } else {
            $error = "Kategori gagal disimpan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fc;
            font-family: Arial, sans-serif;
        }

        .container-form {
            max-width: 650px;
            margin: 50px auto;
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
    <h3 class="fw-bold">Tambah Kategori</h3>
    <p class="text-secondary">Tambah kategori sarana dan prasarana sekolah.</p>

    <div class="card">
        <div class="card-body p-4">

            <?php if ($error != "") { ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input
                        type="text"
                        name="nama_kategori"
                        class="form-control"
                        placeholder="Contoh: Alat Laboratorium"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['nama_kategori'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                        placeholder="Masukkan keterangan kategori"
                    ><?= htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan
                </button>

                <a href="kategori.php" class="btn btn-secondary">
                    Kembali
                </a>
            </form>

        </div>
    </div>
</div>
</body>
</html>