-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 07 Nov 2025 pada 05.18
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fab_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `assurance_tickets`
--

CREATE TABLE `assurance_tickets` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `service_affected` varchar(100) NOT NULL,
  `issue_description` text NOT NULL,
  `reported_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `status` enum('open','in_progress','resolved') DEFAULT 'open',
  `sla_deadline` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `assurance_tickets`
--

INSERT INTO `assurance_tickets` (`id`, `ticket_id`, `customer_name`, `service_affected`, `issue_description`, `reported_at`, `resolved_at`, `status`, `sla_deadline`) VALUES
(1, 'TKT-20251104-001', 'PT. Telkom Indonesia', 'VSAT Bandung', 'Down total', '2025-11-04 15:19:45', '2025-11-05 11:51:07', 'resolved', '2025-11-05 09:19:45'),
(2, 'TKT-20251104-002', 'Kominfo', 'VSAT Jakarta', 'koneksi lemah', '2025-11-04 16:10:11', '2025-11-05 11:51:01', 'resolved', '2025-11-05 10:10:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `billing_invoices`
--

CREATE TABLE `billing_invoices` (
  `id` int(11) NOT NULL,
  `invoice_id` varchar(50) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` datetime DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `billing_invoices`
--

INSERT INTO `billing_invoices` (`id`, `invoice_id`, `order_id`, `customer_name`, `amount`, `due_date`, `status`, `created_at`, `paid_at`) VALUES
(1, 'INV-690AD7C6', 'ORD-002', 'PT. Telkom Indonesia', 0.00, '2025-11-19', 'paid', '2025-11-05 11:51:18', '2025-11-05 11:51:53'),
(2, 'INV-690AD7C7', 'ORD-1001', 'VSAT Bandung', 0.00, '2025-11-19', 'paid', '2025-11-05 11:51:19', '2025-11-05 11:51:54'),
(3, 'INV-690AD7C8', 'ORD-003', 'Kementerian Kominfo', 0.00, '2025-11-19', 'paid', '2025-11-05 11:51:20', '2025-11-05 11:51:55'),
(4, 'INV-690C09A7', 'ORD-001', 'PT. Pasifik Satelit Nusantara', 15000000.00, '2025-11-20', 'unpaid', '2025-11-06 09:36:23', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `fulfillment_orders`
--

CREATE TABLE `fulfillment_orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `revenue` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fulfillment_orders`
--

INSERT INTO `fulfillment_orders` (`id`, `order_id`, `customer_name`, `service_type`, `revenue`, `status`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'ORD-001', 'PT. Pasifik Satelit Nusantara', 'Internet Merah Putih', 15000000.00, 'completed', '2025-11-04 14:39:54', '2025-11-04 15:43:33', NULL),
(2, 'ORD-002', 'PT. Telkom Indonesia', 'Internet Merah Putih', 0.00, 'completed', '2025-11-04 14:42:44', '2025-11-05 11:51:18', NULL),
(3, 'ORD-1001', 'VSAT Bandung', 'Internet Merah Putih', 0.00, 'completed', '2025-11-04 15:08:42', '2025-11-05 11:51:19', NULL),
(4, 'ORD-003', 'Kementerian Kominfo', 'Backbone Link', 0.00, 'completed', '2025-11-04 15:43:33', '2025-11-05 11:51:20', NULL),
(11, 'ORD-1002', 'PT. Amas Iscindo', 'User terminal', 30000000.00, 'pending', '2025-11-05 16:46:11', '2025-11-05 16:46:11', NULL),
(12, 'ORD-1003', 'PT. Geo Tekno Globalindo', 'Router remote', 25000000.00, 'pending', '2025-11-06 09:55:03', '2025-11-06 09:55:03', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('admin','operator','viewer') NOT NULL DEFAULT 'viewer',
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `role`, `password`) VALUES
(1, 'admin', 'admin', '$2y$10$QJe9Dq8x.KJUKVRNrL31i.3VGaUNn7Ye/nPlfiZCyxC17Et1Z1tda'),
(12, 'operator', 'operator', '$2y$10$QJe9Dq8x.KJUKVRNrL31i.3VGaUNn7Ye/nPlfiZCyxC17Et1Z1tda'),
(13, 'viewer', 'viewer', '$2y$10$QJe9Dq8x.KJUKVRNrL31i.3VGaUNn7Ye/nPlfiZCyxC17Et1Z1tda');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `assurance_tickets`
--
ALTER TABLE `assurance_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_id` (`ticket_id`);

--
-- Indeks untuk tabel `billing_invoices`
--
ALTER TABLE `billing_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_id` (`invoice_id`);

--
-- Indeks untuk tabel `fulfillment_orders`
--
ALTER TABLE `fulfillment_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `assurance_tickets`
--
ALTER TABLE `assurance_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `billing_invoices`
--
ALTER TABLE `billing_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `fulfillment_orders`
--
ALTER TABLE `fulfillment_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
