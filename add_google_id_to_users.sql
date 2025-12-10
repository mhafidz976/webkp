-- Tambahkan kolom google_id ke tabel users (jika belum ada)
ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER password_hash;

-- Juga tambahkan kolom role_key untuk backward compatibility (jika belum ada)
ALTER TABLE users ADD COLUMN role_key VARCHAR(50) NULL AFTER role_id;
