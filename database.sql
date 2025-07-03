CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `praktikum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_praktikum` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT,
  `kode_praktikum` VARCHAR(50) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `modul` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `praktikum_id` INT(11) NOT NULL,
  `nama_modul` VARCHAR(255) NOT NULL,
  `deskripsi_modul` TEXT,
  `file_materi` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikum`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `laporan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `modul_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `file_laporan` VARCHAR(255) NOT NULL,
  `nilai` INT(11) NULL,
  `feedback` TEXT NULL,
  `tgl_pengumpulan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `tgl_dinilai` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`modul_id`) REFERENCES `modul`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `enrollments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `praktikum_id` INT(11) NOT NULL,
  `tgl_enrollment` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`user_id`, `praktikum_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikum`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;