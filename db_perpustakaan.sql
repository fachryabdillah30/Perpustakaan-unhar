-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 06:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `penulis` varchar(150) NOT NULL,
  `penerbit` varchar(150) NOT NULL,
  `tahun_terbit` year(4) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `isbn` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `kategori_id`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `stok`, `isbn`, `foto`, `deskripsi`) VALUES
(4, 3, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', 7, '978-000-111-004-4', 'buku_6a3a778393f6f.jpg', 'Kisah perjuangan sepuluh anak di Belitung yang tetap semangat menuntut ilmu meskipun memiliki keterbatasan ekonomi dan fasilitas pendidikan.'),
(7, 7, 'Filosodi Teras', 'Henry Manampiring', 'Kompas', '2018', 4, '978-6-02412-518-9', 'buku_6a36e11f2186d.jpg', NULL),
(8, 3, 'Hujan', 'Tere Liye', 'Tere Liye', '2017', 6, '978-623-99878-7-9', 'buku_6a36e24b5fc5c.jpg', NULL),
(9, 7, 'Emotional Intelligence', 'Daniel Goleman', 'Bantam Books', '1995', 3, '978-0553383713', 'buku_6a36e31520366.jpg', NULL),
(10, 7, 'Atomic Habits', 'James', 'Penguin Books', '2001', 1, '9786020633176', 'buku_6a37a1764e8aa.jpg', 'Menjadi 1% lebih baik setiap hari akan melipatgandakan hasil Anda hingga hampir 37 kali lipat dalam kurun waktu satu tahun. Kebiasaan adalah bunga majemuk dari perbaikan diri.'),
(11, 3, 'Gadis Kretek', 'Ratih Kumala', 'Gramedia Pustaka Utama', '2012', 2, '9789792281415', 'buku_6a3a73d4ebadc.jpg', 'Kisah pencarian Soeraja, seorang pemilik pabrik kretek yang sedang sekarat, terhadap cinta lamanya bernama Jeng Yah. Pencarian ini mengungkap sejarah industri kretek dan rahasia keluarga di Jawa.'),
(12, 3, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', '1980', 5, '9789799731234', 'buku_6a3a785377d91.jpg', 'Menceritakan kehidupan Minke, seorang pemuda pribumi yang hidup pada masa kolonial Belanda dan menghadapi ketidakadilan sosial.'),
(13, 3, 'Dilan: Dia Adalah Dilanku Tahun 1990', 'Pidi Baiq', 'Pastel Books', '2014', 3, '9786027870413', 'buku_6a3a79c77dca1.jpg', 'Kisah cinta remaja antara Milea dan Dilan dengan latar Bandung tahun 1990.'),
(14, 3, 'Bumi', 'Tere Liye', 'Gramedia Pustaka Utama', '2014', 3, '979-3062-79-7', 'buku_6a3a7c6e21e0a.jpg', 'Raib menemukan kekuatan unik dan masuk ke dunia paralel penuh misteri.'),
(15, 21, 'Pendidikan Islam Di Pulau Lombok', 'Saepudin Mashuri', 'Litnus', '2021', 3, '978-623-7125-07-5', 'buku_6a3a80544ae5e.jpg', 'Mengkaji perkembangan pendidikan Islam di Pulau Lombok.'),
(16, 21, 'Epinegram 60', 'Joko Pinurbo', 'Gramedia Pustaka Utama', '2022', 4, '978-602-06-6210-7', 'buku_6a3a80d4181f5.jpg', 'Kumpulan puisi yang menggambarkan kehidupan sehari-hari dengan gaya khas Joko Pinurbo.'),
(17, 16, 'Gundala vs Sancaka', 'Bumilangit Entertainment', 'M&C Gramedia', '2026', 3, '978-623-03-1730-9', 'buku_6a3a820272831.jpg', 'Komik superhero Indonesia yang mengisahkan petualangan Gundala.'),
(18, 7, 'The Art Of Loving', 'Erich Fromm', 'Gramedia Pustaka Utama', '2020', 12, '9786020304083', 'buku_6a3a83300a06c.jpg', 'Membahas cinta sebagai keterampilan yang perlu dipelajari dan dikembangkan.'),
(19, 16, 'Habibie & Ainun', 'B.J. Habibie', 'THC Mandiri', '2010', 4, '9789791255134', 'buku_6a3a8476a87ac.jpg', 'Kisah perjalanan hidup dan cinta B.J. Habibie bersama Ainun.'),
(20, 21, 'Madilog', 'Tan Malaka', 'Narasi', '2014', 4, '9789791684149', 'buku_6a3a88b96b708.jpg', 'Pemikiran Tan Malaka mengenai materialisme, dialektika, dan logika.'),
(21, 3, 'Sherlock Holmes: A Study in Scarlet', 'Arthur Conan Doyle', 'Ward Lock & Co.', '0000', 3, '9780007420227', 'buku_6a3a8ada0d28b.jpg', 'Awal kisah detektif Sherlock Holmes memecahkan kasus pembunuhan misterius.'),
(22, 3, 'The Alchemist', 'Paulo Coelho', 'HarperCollins', '1988', 1, '9780061122415', 'buku_6a3a903b730f1.jpg', 'Perjalanan Santiago mencari harta karun sekaligus menemukan makna kehidupan.'),
(23, 13, 'Rich Dad Poor Dad', 'Robert Kiyosaki', 'Plata Publishing', '1997', 2, '9781612680194', 'buku_6a3a90d558140.jpg', 'Membahas pola pikir keuangan dan perbedaan cara pandang terhadap uang.'),
(24, 16, 'Sapiens', 'Yuval Noah Harari', 'Harper', '2011', 2, '9780062316097', 'buku_6a3a912708eba.jpg', 'Membahas sejarah perkembangan manusia dari masa awal hingga modern.'),
(25, 3, 'Laut Bercerita', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', '2017', 3, '9786024246948', 'buku_6a3a91c0e5756.jpg', 'Kisah aktivis mahasiswa pada masa reformasi yang menghadapi kehilangan dan perjuangan.'),
(26, 3, 'Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia Pustaka Utama', '2002', 1, '9786024246948', 'buku_6a3a92f7f104f.jpg', 'Kisah keluarga dan kehidupan Dewi Ayu dengan latar sejarah Indonesia yang penuh konflik.'),
(27, 3, 'The Maze Runner', 'James Dashner', 'Delacorte Press', '2009', 3, '9780385737944', 'buku_6a3a93ace615c.jpg', 'Thomas terbangun tanpa ingatan di sebuah tempat misterius bersama kelompok anak lain dan harus mencari jalan keluar.'),
(28, 16, 'Kambing Jantan', 'Raditya Dika', 'GagasMedia', '2005', 3, '9789793600406', 'buku_6a3a94ca50930.jpg', 'Cerita pengalaman seorang mahasiswa dengan berbagai kejadian lucu dan kehidupan sehari-hari.'),
(29, 3, 'Negeri di Ujung Tanduk', 'Tere Liye', 'Gramedia', '2013', 2, '9789792295412', 'buku_6a3a96f999dd1.jpg', 'Thomas kembali menghadapi masalah besar yang berkaitan dengan politik, kekuasaan, dan kebenaran.'),
(30, 7, 'The Power of Habit', 'Charles Duhigg', 'Random House', '2012', 4, '9780812981605', 'buku_6a3a978eb111f.jpg', 'Menjelaskan bagaimana kebiasaan terbentuk dan bagaimana manusia dapat memahami pola perilakunya.'),
(31, 7, 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus and Giroux', '2011', 1, '9780374533557', 'buku_6a3a97fe4d2b8.jpg', 'Membahas cara manusia berpikir, mengambil keputusan, dan berbagai kesalahan dalam penilaian.'),
(32, 8, 'The Da Vinci Code', 'Dan Brown', 'Doubleday', '2003', 3, '9780385504201', 'buku_6a3a9a0011a69.jpg', 'Robert Langdon memecahkan misteri simbol kuno dan rahasia besar yang tersembunyi dalam sejarah.'),
(33, 8, 'Angels & Demons', 'Dan Brown', 'Pocket Books', '2000', 3, '9780671027360', 'buku_6a3a9a7e27cc5.jpg', 'Seorang ahli simbol menghadapi ancaman besar yang berkaitan dengan organisasi rahasia dan ilmu pengetahuan.'),
(34, 12, 'The Kite Runner', 'Khaled Hosseini', 'Riverhead Books', '2003', 1, '9781594631931', 'buku_6a3a9b0120afb.jpg', 'Kisah persahabatan, penyesalan, dan penebusan seorang anak laki-laki di Afghanistan.'),
(35, 24, 'A Man Called Ove', 'Fredrik Backman', 'Atria Books', '2012', 3, '9781476738024', 'buku_6a3a9b9a79f28.jpg', 'Cerita seorang pria tua yang hidupnya berubah setelah bertemu dengan tetangga baru yang penuh warna.');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
(16, 'Anak-Anak'),
(18, 'Arsitektur & Desain'),
(11, 'Biografi'),
(13, 'Bisnis & Keuangan'),
(24, 'Drama'),
(20, 'Ekonomi'),
(9, 'Fiksi Ilmiah'),
(15, 'Fiksi Remaja'),
(8, 'Fiksi Sejearah'),
(12, 'Filsafat Populer'),
(10, 'horor'),
(2, 'Jaringan Komputer'),
(17, 'Kesehatan'),
(22, 'Komik'),
(6, 'Matematika'),
(3, 'Novel'),
(1, 'Pemrograman'),
(21, 'Pendidikan'),
(23, 'Politik & Hukum'),
(19, 'Psikologi'),
(4, 'Sains'),
(5, 'Sejarah'),
(7, 'Self Improvement'),
(14, 'Sosiologi/Antropologi');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `batas_kembali` date NOT NULL,
  `durasi_hari` int(11) NOT NULL DEFAULT 7,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan') NOT NULL DEFAULT 'dipinjam',
  `denda` int(11) NOT NULL DEFAULT 0,
  `denda_per_hari` int(11) NOT NULL DEFAULT 1000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `user_id`, `buku_id`, `tanggal_pinjam`, `batas_kembali`, `durasi_hari`, `tanggal_kembali`, `status`, `denda`, `denda_per_hari`) VALUES
(5, 11, 7, '2026-06-21', '2026-06-28', 7, NULL, 'dipinjam', 0, 1000),
(6, 12, 10, '2026-06-22', '2026-07-02', 10, NULL, 'dipinjam', 0, 1000);

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_peminjaman`
--

CREATE TABLE `pengajuan_peminjaman` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  `alasan_tolak` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`id`, `user_id`, `buku_id`, `catatan`, `status`, `alasan_tolak`, `created_at`) VALUES
(3, 11, 7, '', 'disetujui', NULL, '2026-06-20 19:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `pesan_kontak`
--

CREATE TABLE `pesan_kontak` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subjek` varchar(200) DEFAULT NULL,
  `pesan` text NOT NULL,
  `status` enum('belum_dibaca','sudah_dibaca','dibalas') DEFAULT 'belum_dibaca',
  `balasan` text DEFAULT NULL,
  `sudah_dibaca_mahasiswa` tinyint(1) DEFAULT 0,
  `waktu_balas` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan_kontak`
--

INSERT INTO `pesan_kontak` (`id`, `user_id`, `nama`, `email`, `subjek`, `pesan`, `status`, `balasan`, `sudah_dibaca_mahasiswa`, `waktu_balas`, `created_at`) VALUES
(1, 11, 'Budi Santoso', 'budi@mahasiswa.com', 'Saran & Masukan', 'harus lebih banyak buku lagi', 'sudah_dibaca', NULL, 1, NULL, '2026-06-21 17:51:14'),
(2, 11, 'Budi Santoso', 'budi@mahasiswa.com', 'Usulan Buku', 'harus banyak buku tentang komputer', 'dibalas', 'oke', 0, '2026-06-21 17:54:27', '2026-06-21 17:53:13'),
(3, NULL, 'asdasda', 'yogihendra700@gmail.com', 'Pertanyaan Peminjaman Buku', 'asdasdasdasd', 'sudah_dibaca', NULL, 0, NULL, '2026-06-21 18:21:59'),
(4, 13, 'yogik maniez', 'yogihendra700@gmail.com', 'Pertanyaan Peminjaman', 'acc buku ku bang', 'dibalas', 'gamau wlee', 1, '2026-06-22 15:24:26', '2026-06-22 15:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `role` enum('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `nim`, `foto`, `role`, `created_at`) VALUES
(10, 'Paceniboi', 'admin@perpus.com', '$2y$10$TfqRgXpX.I3u6prEbvb0GO0yqBcW1Jr0Bgeq2vtP169KAUZKwLZO.', NULL, 'admin_6a3667aa3b979.jpg', 'admin', '2026-06-19 12:27:40'),
(11, 'Budi Santoso', 'budi@mahasiswa.com', '$2y$10$ynW2ynOW5IShjiqRIAp9EuMm7bqrvRGLe9n8GWHLfrWEj6i2.xJyO', '2021001', 'mhs_6a364b6e01f5b.png', 'mahasiswa', '2026-06-19 12:27:59'),
(12, 'yoga andre', 'andre@gmail.com', '$2y$10$d3hN8aLRi7ZNCVnTirY57.E1s5aEWfevaP/3xxW0V4jlrVi94dmHi', '1231231232', 'mhs_6a37b1b35f24c.webp', 'mahasiswa', '2026-06-21 09:40:35'),
(13, 'yogik maniez', 'yogihendra700@gmail.com', '$2y$10$1dvIlcLGhMmwbdYtESzC9u8MCYidDL4dohg7xlJS/f3DOeI/KBBCC', '2323700066', 'mhs_6a399f44dfc12.png', 'mahasiswa', '2026-06-22 15:21:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_buku_kategori` (`kategori_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pinjam_buku` (`buku_id`),
  ADD KEY `fk_pinjam_user` (`user_id`);

--
-- Indexes for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengajuan_buku` (`buku_id`),
  ADD KEY `fk_pengajuan_user` (`user_id`);

--
-- Indexes for table `pesan_kontak`
--
ALTER TABLE `pesan_kontak`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pesan_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pesan_kontak`
--
ALTER TABLE `pesan_kontak`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `fk_buku_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_pinjam_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pinjam_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD CONSTRAINT `fk_pengajuan_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengajuan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pesan_kontak`
--
ALTER TABLE `pesan_kontak`
  ADD CONSTRAINT `fk_pesan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
