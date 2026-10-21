<?php
include "../koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kategori.php");
    exit;
}

$id = (int) $_GET['id'];
$error = "";

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM kategori WHERE id_kategori = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: kategori.php");
    exit;
}

$nama = $data['nama_kategori'];
$keterangan = $data['keterangan'] ?? "";

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_kategori']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama == "") {
        $error = "Nama kategori wajib diisi!";
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE kategori
             SET nama_kategori = ?, keterangan = ?
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $nama,
            $keterangan,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: kategori.php?pesan=edit");
            exit;
        } else {
            $error = "Kategori gagal diperbarui!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori | SIPRAS</title>

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
    <h3 class="fw-bold">Edit Kategori</h3>
    <p class="text-secondary">Ubah data kategori sarana dan prasarana.</p>

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
                        maxlength="100"
                        value="<?= htmlspecialchars($nama); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($keterangan); ?></textarea>
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Perubahan
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