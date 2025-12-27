<?php
require_once 'koneksi.php';

// Data admin yang akan dimasukkan
$admin_data = [
    'nama' => 'Administrator',
    'email' => 'admin@webkp.local',
    'password' => 'admin123',
    'role_key' => 'admin'
];

// Cek apakah admin sudah ada
$check_sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("s", $admin_data['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Admin dengan email " . $admin_data['email'] . " sudah ada.\n";
    
    // Update password jika diperlukan
    $update_sql = "UPDATE users SET password_hash = ? WHERE email = ?";
    $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $password_hash, $admin_data['email']);
    $stmt->execute();
    echo "Password admin telah diperbarui.\n";
} else {
    // Dapatkan role_id untuk admin
    $role_sql = "SELECT id FROM roles WHERE role_key = ?";
    $stmt = $conn->prepare($role_sql);
    $stmt->bind_param("s", $admin_data['role_key']);
    $stmt->execute();
    $role_result = $stmt->get_result();
    
    if ($role_result->num_rows === 0) {
        die("Role 'admin' tidak ditemukan dalam database!\n");
    }
    
    $role = $role_result->fetch_assoc();
    $role_id = $role['id'];
    
    // Insert admin baru
    $insert_sql = "INSERT INTO users (nama, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
    $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssi", $admin_data['nama'], $admin_data['email'], $password_hash, $role_id);
    
    if ($stmt->execute()) {
        echo "Admin berhasil dibuat!\n";
        echo "Email: " . $admin_data['email'] . "\n";
        echo "Password: " . $admin_data['password'] . "\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

// Verifikasi data
$verify_sql = "SELECT u.id, u.nama, u.email, r.nama as role, u.created_at 
               FROM users u 
               JOIN roles r ON u.role_id = r.id 
               WHERE u.email = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("s", $admin_data['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "\nData admin yang tersimpan:\n";
    echo "ID: " . $admin['id'] . "\n";
    echo "Nama: " . $admin['nama'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Role: " . $admin['role'] . "\n";
    echo "Dibuat: " . $admin['created_at'] . "\n";
}

$stmt->close();
$conn->close();
?>
