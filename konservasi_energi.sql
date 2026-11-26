CREATE DATABASE IF NOT EXISTS konservasi_energi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE konservasi_energi;

-- Skema mengikuti ERD: siswa, users, perangkat, checklist, detail_checklist.
-- Akun level Siswa memakai NIS sebagai username (tidak ada kolom tambahan di tabel users).

CREATE TABLE IF NOT EXISTS siswa (
  nis   INT(10) NOT NULL,
  nama  VARCHAR(100) NOT NULL,
  kelas VARCHAR(10) NOT NULL,
  PRIMARY KEY (nis)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id_user  INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama     VARCHAR(100) NOT NULL,
  level    ENUM('Admin','Guru','Siswa') NOT NULL,
  PRIMARY KEY (id_user),
  UNIQUE KEY uk_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS perangkat (
  id_perangkat   INT(11) NOT NULL AUTO_INCREMENT,
  nama_perangkat VARCHAR(100) NOT NULL,
  jenis          VARCHAR(50) NOT NULL,
  keterangan     TEXT,
  PRIMARY KEY (id_perangkat)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS checklist (
  id_checklist INT(11) NOT NULL AUTO_INCREMENT,
  nis          INT(10) NOT NULL,
  tanggal      DATE NOT NULL,
  jam          TIME NOT NULL,
  PRIMARY KEY (id_checklist),
  KEY idx_nis (nis),
  CONSTRAINT fk_checklist_siswa FOREIGN KEY (nis) REFERENCES siswa (nis)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detail_checklist (
  id_detail    INT(11) NOT NULL AUTO_INCREMENT,
  id_checklist INT(11) NOT NULL,
  id_perangkat INT(11) NOT NULL,
  tahapan      ENUM('SEBELUM','SELAMA','SESUDAH','5R') NOT NULL,
  kondisi      VARCHAR(100) NOT NULL,
  status       ENUM('Ya','Tidak') NOT NULL,
  keterangan   TEXT,
  PRIMARY KEY (id_detail),
  KEY idx_checklist (id_checklist),
  KEY idx_perangkat (id_perangkat),
  CONSTRAINT fk_detail_checklist FOREIGN KEY (id_checklist) REFERENCES checklist (id_checklist)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_perangkat FOREIGN KEY (id_perangkat) REFERENCES perangkat (id_perangkat)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Data contoh
INSERT INTO siswa (nis, nama, kelas) VALUES
(12345, 'Siswa Contoh',    'XI RPL2'),
(12346, 'Budi Santoso',    'XI RPL2'),
(12347, 'Sari Wulandari',  'XI RPL1'),
(12348, 'Andi Pratama',    'XI TKJ1');

-- Akun: admin/admin123 dan guru/guru123.
-- Akun siswa dibuat otomatis saat siswa pertama kali login (username = NIS, password awal = NIS).
INSERT INTO users (username, password, nama, level) VALUES
('admin', '$2y$10$MQ/ngSo2GzmYuUK/4/irGOzoPR5oTE4uHi7txJaJ5qzIVer5.cZ5e', 'Administrator', 'Admin'),
('guru',  '$2y$10$Vu1epxK4MKvkqW5n1ckN/.3bxwbOQ5cZN4VDKdeY7emLz55fyz3BK', 'Guru',          'Guru');

-- Perangkat per unit: Lab Komputer 1 (20 komputer, 4 lampu, 1 proyektor)
INSERT INTO perangkat (nama_perangkat, jenis, keterangan) VALUES
('Lab 1 - Komputer 01', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 02', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 03', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 04', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 05', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 06', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 07', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 08', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 09', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 10', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 11', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 12', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 13', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 14', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 15', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 16', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 17', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 18', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 19', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Komputer 20', 'Komputer', 'Lab Komputer 1'),
('Lab 1 - Lampu 1', 'Lampu', 'Lab Komputer 1'),
('Lab 1 - Lampu 2', 'Lampu', 'Lab Komputer 1'),
('Lab 1 - Lampu 3', 'Lampu', 'Lab Komputer 1'),
('Lab 1 - Lampu 4', 'Lampu', 'Lab Komputer 1'),
('Lab 1 - Proyektor', 'Proyektor', 'Lab Komputer 1');
