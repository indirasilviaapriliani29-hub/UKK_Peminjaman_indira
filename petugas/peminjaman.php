
<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['level']) && $_SESSION['level'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = "";
$jenis_pesan = "success";

// Proses ACC atau penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
    $aksi = $_POST['aksi'];
    $catatan = trim($_POST['catatan'] ?? '');

    if ($id_peminjaman <= 0) {
        $pesan = "Data peminjaman tidak valid.";
        $jenis_pesan = "danger";
    } elseif (!in_array($aksi, ['Disetujui', 'Ditolak'], true)) {
        $pesan = "Aksi tidak valid.";
        $jenis_pesan = "danger";
    } else {
        // Hanya pengajuan Menunggu yang dapat diproses
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE peminjaman
             SET status = ?
             WHERE id_peminjaman = ? AND status = 'Menunggu'"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $aksi, $id_peminjaman);

            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $pesan = "Pengajuan berhasil diperbarui menjadi " . $aksi . ".";

                // Simpan catatan jika kolom catatan_petugas tersedia.
                // Jika kolom belum ada, status tetap berhasil diproses.
                if ($catatan !== '') {
                    $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM peminjaman LIKE 'catatan_petugas'");

                    if ($cek_kolom && mysqli_num_rows($cek_kolom) > 0) {
                        $catatan_stmt = mysqli_prepare(
                            $koneksi,
                            "UPDATE peminjaman SET catatan_petugas = ? WHERE id_peminjaman = ?"
                        );

                        if ($catatan_stmt) {
                            mysqli_stmt_bind_param($catatan_stmt, "si", $catatan, $id_peminjaman);
                            mysqli_stmt_execute($catatan_stmt);
                            mysqli_stmt_close($catatan_stmt);
                        }
                    }
                }
            } else {
                $pesan = "Pengajuan tidak ditemukan atau sudah diproses sebelumnya.";
                $jenis_pesan = "warning";
            }

            mysqli_stmt_close($stmt);
        } else {
            $pesan = "Query gagal. Periksa struktur tabel peminjaman.";
            $jenis_pesan = "danger";
        }
    }
}

$status_filter = $_GET['status'] ?? 'Semua';
$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT
        p.id_peminjaman,
        p.jenis_peminjaman,
        p.jumlah,
        p.tanggal_mulai,
        p.tanggal_selesai,
        p.keperluan,
        p.status,
        u.nama AS nama_peminjam,
        a.nama_alat,
        r.nama_ruangan
    FROM peminjaman p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN alat a ON p.id_alat = a.id_alat
    LEFT JOIN ruangan r ON p.id_ruangan = r.id_ruangan
    WHERE 1=1
";

$tipe = "";
$parameter = [];

if (in_array($status_filter, ['Menunggu', 'Disetujui', 'Ditolak', 'Dipinjam', 'Selesai'], true)) {
    $sql .= " AND p.status = ?";
    $tipe .= "s";
    $parameter[] = $status_filter;
}

if ($cari !== '') {
    $sql .= " AND (u.nama LIKE ? OR a.nama_alat LIKE ? OR r.nama_ruangan LIKE ?)";
    $tipe .= "sss";
    $kata = "%" . $cari . "%";
    $parameter[] = $kata;
    $parameter[] = $kata;
    $parameter[] = $kata;
}

$sql .= " ORDER BY p.id_peminjaman DESC";

$stmt_data = mysqli_prepare($koneksi, $sql);
$data_peminjaman = false;

if ($stmt_data) {
    if ($tipe !== "") {
        mysqli_stmt_bind_param($stmt_data, $tipe, ...$parameter);
    }

    mysqli_stmt_execute($stmt_data);
    $data_peminjaman = mysqli_stmt_get_result($stmt_data);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman | SIPRAS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f4f7fc;
            font-family: 'Poppins', sans-serif;
            color: #263449;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #0d47a1;
            color: white;
            padding: 25px 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 30px;
        }

        .brand-icon {
            background: white;
            color: #0d47a1;
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 21px;
        }

        .brand h5 { font-weight: 700; margin: 0; }
        .brand small { color: #d9e7ff; font-size: 11px; }

        .menu-label {
            font-size: 11px;
            color: #c1d7fb;
            margin: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            color: #e7efff;
            padding: 13px 14px;
            margin-bottom: 7px;
            border-radius: 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background: #2874d9;
        }

        .nav-link i { width: 20px; text-align: center; }

        .main { margin-left: 250px; padding: 30px; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .topbar h4 { font-size: 22px; font-weight: 700; margin-bottom: 5px; }
        .muted { color: #7b8798; font-size: 12px; }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 10px 15px;
            border-radius: 12px;
        }

        .avatar {
            background: #e2edff;
            color: #0d47a1;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 23px;
            box-shadow: 0 5px 22px #18365b08;
        }

        .section-title { font-size: 16px; font-weight: 700; }

        .form-control, .form-select {
            font-size: 12px;
            border-color: #e1e7f0;
            padding: 10px 12px;
            border-radius: 9px;
        }

        .table { vertical-align: middle; font-size: 12px; }

        .table thead th {
            color: #77849a;
            font-weight: 600;
            background: #f7f9fd;
            border-bottom: none;
            padding: 14px 12px;
            white-space: nowrap;
        }

        .table tbody td { padding: 14px 12px; border-color: #edf1f7; }

        .badge-status {
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 10px;
            font-weight: 600;
        }

        .status-menunggu { background: #fff0cf; color: #a56a00; }
        .status-disetujui { background: #d9f6e5; color: #18834a; }
        .status-ditolak { background: #ffe0e0; color: #c53636; }
        .status-lain { background: #e8edf5; color: #56657b; }

        .btn-primary {
            background: #0d47a1;
            border-color: #0d47a1;
            border-radius: 9px;
            font-size: 12px;
            padding: 10px 14px;
        }

        .btn-acc, .btn-tolak {
            border: 0;
            border-radius: 7px;
            padding: 7px 9px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-acc { background: #dcf7e8; color: #18834a; }
        .btn-tolak { background: #ffe4e4; color: #c53636; }

        .btn-acc:hover { background: #bcefd0; }
        .btn-tolak:hover { background: #ffd0d0; }

        .empty { padding: 35px 10px; text-align: center; color: #8a96a8; }

        @media (max-width: 900px) {
            .sidebar { width: 72px; padding: 20px 10px; }
            .brand { padding: 0 4px 30px; }
            .brand-text, .menu-label, .nav-text { display: none; }
            .nav-link { justify-content: center; padding: 14px 5px; }
            .main { margin-left: 72px; padding: 20px 14px; }
        }

        @media (max-width: 550px) {
            .topbar h4 { font-size: 18px; }
            .profile { padding: 8px; }
            .profile-name { display: none; }
            .content-card { padding: 15px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-school"></i></div>
        <div class="brand-text">
            <h5>SIPRAS</h5>
            <small>SMK Negeri 3 Kendal</small>
        </div>
    </div>

    <div class="menu-label">Menu Utama</div>

    <a href="index.php" class="nav-link">
        <i class="fa-solid fa-house"></i>
        <span class="nav-text">Dashboard</span>
    </a>

    <a href="peminjaman.php" class="nav-link active">
        <i class="fa-solid fa-clipboard-check"></i>
        <span class="nav-text">Data Peminjaman</span>
    </a>

    <a href="alat.php" class="nav-link">
        <i class="fa-solid fa-box"></i>
        <span class="nav-text">Data Alat</span>
    </a>

    <a href="ruangan.php" class="nav-link">
        <i class="fa-solid fa-door-open"></i>
        <span class="nav-text">Data Ruangan</span>
    </a>

    <div class="menu-label mt-4">Akun</div>

    <a href="../logout.php" class="nav-link" onclick="return confirm('Yakin ingin keluar?')">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="nav-text">Logout</span>
    </a>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <h4>Data Peminjaman</h4>
            <div class="muted">Periksa pengajuan dan tentukan persetujuan peminjaman.</div>
        </div>

        <div class="profile">
            <div class="avatar"><i class="fa-solid fa-user-check"></i></div>
            <div class="profile-name">
                <div style="font-size:12px;font-weight:600;">
                    <?= aman($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas'); ?>
                </div>
                <div class="muted">Petugas</div>
            </div>
        </div>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= aman($jenis_pesan); ?> alert-dismissible fade show" role="alert">
            <?= aman($pesan); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <div class="mb-4">
            <h5 class="section-title mb-1">Daftar Pengajuan</h5>
            <div class="muted">Pengajuan berstatus Menunggu dapat disetujui atau ditolak.</div>
        </div>

        <form method="GET" class="row g-2 mb-4">
            <div class="col-12 col-md-4">
                <select name="status" class="form-select">
                    <?php
                    $opsi_status = ['Semua', 'Menunggu', 'Disetujui', 'Ditolak', 'Dipinjam', 'Selesai'];
                    foreach ($opsi_status as $opsi):
                    ?>
                        <option value="<?= aman($opsi); ?>" <?= $status_filter === $opsi ? 'selected' : ''; ?>>
                            <?= aman($opsi); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-5">
                <input type="text" name="cari" class="form-control"
                       placeholder="Cari nama peminjam, alat, atau ruangan..."
                       value="<?= aman($cari); ?>">
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                </button>
                <a href="peminjaman.php" class="btn btn-light border" title="Reset filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Alat / Ruangan</th>
                        <th>Jumlah</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($data_peminjaman && mysqli_num_rows($data_peminjaman) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($p = mysqli_fetch_assoc($data_peminjaman)): ?>
                        <?php
                        $jenis = strtolower($p['jenis_peminjaman'] ?? '');
                        $nama_item = ($jenis === 'ruangan')
                            ? ($p['nama_ruangan'] ?? 'Ruangan tidak tersedia')
                            : ($p['nama_alat'] ?? 'Alat tidak tersedia');

                        $status = $p['status'] ?? '';
                        $kelas_status = 'status-lain';

                        if ($status === 'Menunggu') {
                            $kelas_status = 'status-menunggu';
                        } elseif ($status === 'Disetujui') {
                            $kelas_status = 'status-disetujui';
                        } elseif ($status === 'Ditolak') {
                            $kelas_status = 'status-ditolak';
                        }
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= aman($p['nama_peminjam'] ?? '-'); ?></td>
                            <td><?= aman(ucfirst($jenis)); ?></td>
                            <td><?= aman($nama_item); ?></td>
                            <td><?= aman($p['jumlah'] ?? 1); ?></td>
                            <td>
                                <?= !empty($p['tanggal_mulai'])
                                    ? aman(date('d-m-Y', strtotime($p['tanggal_mulai'])))
                                    : '-'; ?>
                            </td>
                            <td>
                                <?= !empty($p['tanggal_selesai'])
                                    ? aman(date('d-m-Y', strtotime($p['tanggal_selesai'])))
                                    : '-'; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $kelas_status; ?>">
                                    <?= aman($status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status === 'Menunggu'): ?>
                                    <button type="button" class="btn-acc mb-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAksi"
                                            data-id="<?= (int)$p['id_peminjaman']; ?>"
                                            data-aksi="Disetujui"
                                            data-item="<?= aman($nama_item); ?>">
                                        <i class="fa-solid fa-check"></i> ACC
                                    </button>

                                    <button type="button" class="btn-tolak mb-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAksi"
                                            data-id="<?= (int)$p['id_peminjaman']; ?>"
                                            data-aksi="Ditolak"
                                            data-item="<?= aman($nama_item); ?>">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                <?php else: ?>
                                    <span class="muted">Sudah diproses</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty">
                                <i class="fa-regular fa-folder-open fa-2x mb-3"></i>
                                <div>Belum ada data pengajuan yang cocok.</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center muted mt-4">
        SIPRAS &copy; <?= date('Y'); ?> · SMK Negeri 3 Kendal
    </div>
</main>

<!-- Modal persetujuan -->
<div class="modal fade" id="modalAksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size:16px;font-weight:700;">Proses Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id_peminjaman" id="id_peminjaman">
                    <input type="hidden" name="aksi" id="aksi">

                    <p class="mb-2" style="font-size:13px;">
                        Kamu akan memproses pengajuan:
                        <strong id="nama_item"></strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:12px;">Catatan petugas (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="3"
                                  placeholder="Contoh: Silakan mengambil alat di ruang sarpras."></textarea>
                    </div>

                    <div class="muted" id="konfirmasi"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="tombolKonfirmasi">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalAksi = document.getElementById('modalAksi');

modalAksi.addEventListener('show.bs.modal', function (event) {
    const tombol = event.relatedTarget;
    const id = tombol.getAttribute('data-id');
    const aksi = tombol.getAttribute('data-aksi');
    const item = tombol.getAttribute('data-item');

    document.getElementById('id_peminjaman').value = id;
    document.getElementById('aksi').value = aksi;
    document.getElementById('nama_item').textContent = item;
    document.getElementById('konfirmasi').textContent =
        'Yakin ingin mengubah status pengajuan menjadi ' + aksi + '?';

    const tombolKonfirmasi = document.getElementById('tombolKonfirmasi');
    tombolKonfirmasi.textContent = aksi;
    tombolKonfirmasi.className = aksi === 'Disetujui'
        ? 'btn btn-success'
        : 'btn btn-danger';
});
</script>
</body>
</html>
