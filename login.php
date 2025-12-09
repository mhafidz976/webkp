<?php
require_once __DIR__ . '/koneksi.php';
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
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
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
                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
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
