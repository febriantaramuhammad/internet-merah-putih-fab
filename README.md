# 🛰️ FAB Dashboard – Sistem Enterprise

Sistem terpadu untuk manajemen **Fulfillment, Assurance, dan Billing** layanan satelit.

## 🔧 Instalasi

1. Salin folder `fab-dashboard` ke `htdocs/` (XAMPP) atau direktori web server Anda.
2. Jalankan **Apache** dan **MySQL**.
3. Buka browser:
→ Akan otomatis:
- Buat database `fab_db`
- Buat tabel & data demo
- Buat user: `admin` / password: `admin123`
4. **Hapus file `setup.php`** setelah instalasi selesai (untuk keamanan).
5. Akses sistem:

## 👤 Kredensial Default
- **Username**: `admin`  
- **Password**: `admin123`  

> 💡 Untuk produksi, ubah password di database.

## 📦 Fitur Utama
- **Fulfillment**: Order → Invoice otomatis saat selesai
- **Assurance**: Pelacakan tiket + deteksi SLA breach (24 jam)
- **Billing**: Tagihan + pelacakan pembayaran
- **Dashboard**: KPI + grafik ringkas
- **Ekspor**: Data ke CSV (opsional)

## 🔒 Keamanan
- Password di-hash
- Semua query pakai PDO + prepared statement
- Proteksi halaman via sesi

## 🎨 Desain
- Warna: Merah Telkomsat (`#d32f2f`) + biru gelap
- Layout: Sidebar navigasi + konten utama
- Responsif: Dukungan tablet & desktop

© 2025 Telkomsat — Mendukung Kedaulatan Digital Indonesia    