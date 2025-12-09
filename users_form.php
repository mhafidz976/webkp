<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
require_role(['admin']);

$id       = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nama     = '';
$email    = '';
$roleId   = '';
$errors   = [];

$roles = get_roles();

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $nama   = $row['nama'];
            $email  = $row['email'];
            $roleId = $row['role_id'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $roleId   = (int)($_POST['role_id'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '') {
        $errors[] = 'Nama dan email wajib diisi.';
    }
    if ($roleId <= 0) {
        $errors[] = 'Role wajib dipilih.';
    }
    if ($id === 0 && $password === '') {
        $errors[] = 'Password wajib diisi untuk user baru.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql  = 'UPDATE users SET nama = ?, email = ?, role_id = ?, password_hash = ? WHERE id = ?';
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssisi', $nama, $email, $roleId, $hash, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $sql  = 'UPDATE users SET nama = ?, email = ?, role_id = ? WHERE id = ?';
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ssii', $nama, $email, $roleId, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            log_activity('update_user', 'Update user ID ' . $id);
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql  = 'INSERT INTO users (nama, email, password_hash, role_id) VALUES (?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sssi', $nama, $email, $hash, $roleId);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('create_user', 'Tambah user baru ' . $email);
        }

        header('Location: users_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> User</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($nama); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo (int)$roleId === (int)$r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['nama']); ?> (<?php echo htmlspecialchars($r['role_key']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <?php echo $id > 0 ? '(kosongkan jika tidak diubah)' : ''; ?></label>
                    <input type="password" name="password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                <a href="users_index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
