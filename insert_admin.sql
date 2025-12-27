-- Insert data admin user ke database
-- Password: admin123 (hash dengan PASSWORD_DEFAULT)

USE db_lab;

INSERT INTO users (nama, email, password_hash, role_id) VALUES
('Administrator', 'admin@webkp.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE 
nama = VALUES(nama),
password_hash = VALUES(password_hash),
role_id = VALUES(role_id);

-- Verifikasi data yang dimasukkan
SELECT u.id, u.nama, u.email, r.nama as role, u.created_at 
FROM users u 
JOIN roles r ON u.role_id = r.id 
WHERE u.email = 'admin@webkp.local';
