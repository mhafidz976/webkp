<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/google_config.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $sql  = 'SELECT u.id, u.nama, u.email, u.password_hash, r.id AS role_id, r.nama AS role_name, r.role_key
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE u.email = ? LIMIT 1';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'nama'     => $user['nama'],
                    'email'    => $user['email'],
                    'role_id'  => $user['role_id'],
                    'role'     => $user['role_name'],
                    'role_key' => $user['role_key'],
                ];

                header('Location: dashboard.php');
                exit;
            }

            $error = 'Email atau password salah.';
        } else {
            $error = 'Terjadi kesalahan pada server.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Penjadwalan Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
    <style>
        body.bg-light {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-radius: 1.2rem;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-card h4 {
            color: #1a202c;
            font-weight: 700;
        }
        .btn-google {
            background: #4285f4;
            color: white;
            border: none;
            font-weight: 500;
        }
        .btn-google:hover {
            background: #357ae8;
            color: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card login-card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3 text-center">Login Penjadwalan Lab</h4>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2 small mb-3"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-2">Masuk</button>
                        <a href="<?php echo GOOGLE_AUTH_URL . '?' . http_build_query([
                            'client_id' => GOOGLE_CLIENT_ID,
                            'redirect_uri' => GOOGLE_REDIRECT_URI,
                            'response_type' => 'code',
                            'scope' => GOOGLE_SCOPES,
                            'state' => bin2hex(random_bytes(8))
                        ]); ?>" class="btn btn-google w-100 d-flex align-items-center justify-content-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google" viewBox="0 0 16 16">
                                <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.759 7.759 0 0 1 5.902 2.57l-.378.378A6.949 6.949 0 0 0 8 1c-3.86 0-7 3.14-7 7s3.14 7 7 7 7-3.14 7-7c0-.789-.125-1.548-.357-2.26l-.024-.057z"/>
                                <path d="M8 13.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zm0-1.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                            </svg>
                            Sign in with Google
                        </a>
                    </form>

                    <p class="mt-3 mb-0 small text-muted">
                        Gunakan akun demo yang tertera di README setelah tabel `users` dibuat dan data diisi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
