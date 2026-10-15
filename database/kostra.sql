CREATE DATABASE IF NOT EXISTS kostra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kostra;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS kos_fasilitas;
DROP TABLE IF EXISTS foto_kos;
DROP TABLE IF EXISTS kos;
DROP TABLE IF EXISTS fasilitas;
DROP TABLE IF EXISTS lokasi;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE lokasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_lokasi VARCHAR(120) NOT NULL,
  deskripsi VARCHAR(255) NOT NULL DEFAULT '',
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lokasi_aktif (status_aktif)
) ENGINE=InnoDB;

CREATE TABLE fasilitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_fasilitas VARCHAR(100) NOT NULL,
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fasilitas_aktif (status_aktif)
) ENGINE=InnoDB;

CREATE TABLE kos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kos VARCHAR(180) NOT NULL,
  lokasi_id INT UNSIGNED NOT NULL,
  harga BIGINT UNSIGNED NOT NULL,
  deskripsi TEXT NOT NULL,
  kontak VARCHAR(25) NOT NULL,
  jumlah_kamar_total INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_kamar_tersedia INT UNSIGNED NOT NULL DEFAULT 0,
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  is_rekomendasi TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kos_lokasi FOREIGN KEY (lokasi_id) REFERENCES lokasi(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_kos_status (status_aktif),
  INDEX idx_kos_lokasi (lokasi_id),
  INDEX idx_kos_harga (harga),
  INDEX idx_kos_rekomendasi (is_rekomendasi),
  INDEX idx_kos_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE kos_fasilitas (
  kos_id INT UNSIGNED NOT NULL,
  fasilitas_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (kos_id, fasilitas_id),
  CONSTRAINT fk_kf_kos FOREIGN KEY (kos_id) REFERENCES kos(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_kf_fasilitas FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE foto_kos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kos_id INT UNSIGNED NOT NULL,
  path_file VARCHAR(255) NOT NULL,
  is_utama TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_foto_kos FOREIGN KEY (kos_id) REFERENCES kos(id) ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_foto_kos_utama (kos_id,is_utama)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  UNIQUE KEY uq_reset_token (token_hash),
  INDEX idx_reset_user (user_id),
  INDEX idx_reset_expiry (expires_at)
) ENGINE=InnoDB;

INSERT INTO lokasi (nama_lokasi, deskripsi) VALUES
('Keputih','Area sekitar kampus dan berbagai kebutuhan mahasiswa.'),
('Gebang','Area hunian mahasiswa di sekitar ITS Raya.'),
('Tegal Mulyorejo Baru (TMB)','Area kos di kawasan Tegal Mulyorejo Baru.'),
('Mulyosari','Area hunian di kawasan Mulyosari.');

INSERT INTO fasilitas (nama_fasilitas) VALUES
('Wi-Fi'),
('Kamar mandi dalam'),
('AC'),
('Lemari'),
('Kasur'),
('Meja belajar'),
('Parkir motor'),
('Kipas angin'),
('Kamar mandi luar'),
('Listrik termasuk harga'),
('Air termasuk harga'),
('Dapur bersama'),
('CCTV 24 Jam');
