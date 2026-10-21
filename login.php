<?php
session_start();
include "koneksi.php";

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM users WHERE username = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $passwordBenar =
            md5($password) === $user['password'] ||
            password_verify($password, $user['password']) ||
            $password === $user['password'];

        if ($passwordBenar) {
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['level'] = strtolower($user['level']);

            if ($_SESSION['level'] == 'admin') {
                header("Location: admin/index.php");
                exit;
            } elseif ($_SESSION['level'] == 'petugas') {
                header("Location: petugas/index.php");
                exit;
            } elseif ($_SESSION['level'] == 'peminjam') {
                header("Location: user/index.php");
                exit;
            } else {
                $error = "Level akun tidak valid!";
            }
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SIPRAS SMKN 3 Kendal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #edf4ff, #f8fbff);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 950px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px #183b651a;
        }

        .login-info {
            background: linear-gradient(145deg, #103b73, #1768b4);
            color: white;
            padding: 55px 40px;
            height: 100%;
        }

        .logo {
            width: 58px;
            height: 58px;
            border-radius: 17px;
            background: #ffffff24;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 28px;
        }

        .login-form {
            padding: 50px 42px;
        }

        .form-control {
            padding: 13px 15px;
            border-radius: 11px;
            background: #f8faff;
        }

        .form-control:focus {
            border-color: #2879c7;
            box-shadow: 0 0 0 3px #2879c720;
        }

        .btn-login {
            padding: 13px;
            border: none;
            border-radius: 11px;
            background: #1768b4;
            color: white;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #103b73;
            color: white;
        }

        .small-text {
            color: #7c899b;
            font-size: 13px;
        }

        @media (max-width: 767px) {
            .login-info {
                padding: 30px;
            }

            .login-form {
                padding: 32px 25px;
            }
        }
    </style>
</head>

<body>
<div class="login-card">
    <div class="row g-0">

        <div class="col-md-5">
            <div class="login-info d-flex flex-column justify-content-center">
                <div class="logo">🏫</div>

                <h2 class="fw-bold">SIPRAS</h2>
                <h5 class="mb-3">SMK Negeri 3 Kendal</h5>

                <p class="text-white-50">
                    Sistem Informasi Peminjaman Sarana dan Prasarana Sekolah.
                </p>

                <hr class="border-light opacity-25">

                <p class="small mb-2">✓ Informasi alat dan fasilitas sekolah</p>
                <p class="small mb-2">✓ Peminjaman barang dan ruangan</p>
                <p class="small">✓ Pengelolaan yang mudah dan teratur</p>
            </div>
        </div>

        <div class="col-md-7">
            <div class="login-form">

                <h3 class="fw-bold mb-2">Selamat Datang!</h3>
                <p class="small-text mb-4">
                    Silakan login untuk melanjutkan.
                </p>

                <?php if ($error != ""): ?>
                    <div class="alert alert-danger py-2">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        name="login"
                        class="btn btn-login w-100"
                    >
                        Masuk ke SIPRAS →
                    </button>
                </form>

                <p class="small-text text-center mt-4 mb-0">
                    SIPRAS · SMK Negeri 3 Kendal
                </p>

            </div>
        </div>

    </div>
</div>
</body>
</html>