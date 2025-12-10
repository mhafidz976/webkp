<?php
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/koneksi.php';
session_start();

// Jika tidak ada code, redirect ke login
if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}

// Tukar authorization code dengan access token
$code = $_GET['code'];
$data = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$response = file_get_contents(GOOGLE_TOKEN_URL, false, $context);
if ($response === false) {
    die('Gagal mendapatkan access token.');
}
$tokenData = json_decode($response, true);
if (!isset($tokenData['access_token'])) {
    die('Invalid token response.');
}

// Ambil info user
$accessToken = $tokenData['access_token'];
$options = [
    'http' => [
        'header' => "Authorization: Bearer $accessToken\r\n"
    ]
];
$context = stream_context_create($options);
$userInfo = file_get_contents(GOOGLE_USERINFO_URL, false, $context);
if ($userInfo === false) {
    die('Gagal mengambil info user.');
}
$profile = json_decode($userInfo, true);

$email = filter_var($profile['email'], FILTER_SANITIZE_EMAIL);
$googleId = $profile['id'];
$name = $profile['name'] ?? $email;

// Cek user berdasarkan google_id
$stmt = $conn->prepare('SELECT * FROM users WHERE google_id = ?');
if ($stmt) {
    $stmt->bind_param('s', $googleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    $user = false;
}

if ($user) {
    // Login langsung
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['role_key']= $user['role_key'];
    header('Location: dashboard.php');
    exit;
} else {
    // Cek apakah email sudah terdaftar manual
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
    } else {
        $existing = false;
    }

    if ($existing) {
        // Email sudah terdaftar, update google_id
        $stmt = $conn->prepare('UPDATE users SET google_id = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $googleId, $existing['id']);
            $stmt->execute();
            $stmt->close();
        }
        $_SESSION['user_id'] = $existing['id'];
        $_SESSION['email']   = $existing['email'];
        $_SESSION['role']    = $existing['role'];
        $_SESSION['role_key']= $existing['role_key'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Auto-register sebagai 'mahasiswa'
        $defaultRole = 'mahasiswa';
        $stmt = $conn->prepare('INSERT INTO users (nama, email, password, role, role_key, google_id) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $hashed = password_hash(uniqid(), PASSWORD_DEFAULT); // dummy password
            $stmt->bind_param('ssssss', $name, $email, $hashed, $defaultRole, $defaultRole, $googleId);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            $_SESSION['user_id'] = $newId;
            $_SESSION['email']   = $email;
            $_SESSION['role']    = $defaultRole;
            $_SESSION['role_key']= $defaultRole;
            header('Location: dashboard.php');
            exit;
        } else {
            die('Gagal mendaftarkan user baru.');
        }
    }
}
?>
