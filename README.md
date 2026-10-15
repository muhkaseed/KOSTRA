# KOSTRA — Kos ITS Raya

Proyek pembelajaran berbasis PHP Native, MySQL, HTML, CSS, dan JavaScript. Dijalankan secara lokal menggunakan Laragon.

## 1. Persiapan

1. Letakkan folder proyek di `C:\laragon\www\kostra`.
2. Pastikan PHP dan MySQL aktif melalui Laragon.
3. Pastikan ekstensi PHP `pdo_mysql`, `fileinfo`, dan `gd` aktif.
4. Salin logo KOSTRA ke `assets/images/logo.png`. Jika belum ada, website tetap berjalan tetapi logo khusus tidak tampil.

## 2. Membuat database melalui terminal

Jalankan MySQL dari Laragon. Buka terminal VS Code pada folder `C:\laragon\www\kostra`.

Periksa apakah perintah `mysql` tersedia:

```bash
mysql --version
```

Jika perintah tidak dikenali, gunakan path lengkap `mysql.exe` dari folder MySQL Laragon. Lokasinya bisa berbeda tergantung versi, misalnya di bawah `C:\laragon\bin\mysql\`.

Buat database:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS kostra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Jika root tidak memakai password di instalasi lokal, hapus opsi `-p`.

Impor tabel dan data awal dari folder proyek:

```bash
mysql -u root -p kostra < database/kostra.sql
```

File SQL menggunakan `CREATE DATABASE IF NOT EXISTS` dan `USE kostra`, lalu membuat ulang tabel. **Perhatian:** mengimpor ulang file ini akan menghapus tabel dan data KOSTRA sebelumnya. Jangan impor ulang jika sudah memasukkan data yang ingin dipertahankan, kecuali sudah membuat cadangan.

Verifikasi tabel:

```bash
mysql -u root -p -D kostra -e "SHOW TABLES; SELECT * FROM lokasi; SELECT * FROM fasilitas;"
```

Alternatif, phpMyAdmin dapat digunakan untuk memeriksa data; tidak wajib untuk membuat tabel.

## 3. Konfigurasi koneksi PHP

Buka `config/koneksi.php` dan sesuaikan `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS` dengan MySQL Laragon. Nilai awal yang umum pada Laragon adalah host `127.0.0.1`, database `kostra`, user `root`, dan password kosong, tetapi periksa konfigurasi lokalmu.

## 4. Membuat akun admin dengan aman

Jangan mengubah role pengguna melalui form registrasi publik. Setelah database berhasil dibuat, buat hash password melalui PHP CLI:

```bash
php -r "echo password_hash('GANTI_DENGAN_PASSWORD_KUAT', PASSWORD_DEFAULT), PHP_EOL;"
```

Salin hash yang dihasilkan, lalu jalankan perintah berikut di MySQL. Ganti email, nama, dan hash dengan milikmu:

```sql
INSERT INTO users (nama, email, password_hash, role)
VALUES ('Admin KOSTRA', 'admin@example.com', 'TEMPEL_HASH_PASSWORD_DI_SINI', 'admin');
```

Hapus teks placeholder hash dan ganti dengan hash lengkap yang dihasilkan PHP. Jangan gunakan password contoh untuk akun nyata. Jangan commit kredensial ke GitHub.

## 5. Menjalankan website

1. Pastikan Apache dan MySQL aktif.
2. Buka `http://localhost/kostra/`.
3. Daftar akun pengguna biasa melalui halaman Daftar.
4. Login admin dengan akun yang dibuat secara manual.
5. Tambahkan kos melalui Admin → Kelola Kos. Setiap kos harus memiliki setidaknya satu foto valid.

## 6. Pengujian dasar

- Registrasi dengan email baru dan email yang sudah terdaftar.
- Login sebagai pengguna dan admin.
- Filter kos berdasarkan lokasi, harga, fasilitas, dan urutan.
- Detail kos sebelum dan sesudah login.
- Tambah, edit, nonaktifkan, dan hapus kos.
- Unggah beberapa foto dan pilih foto utama.
- Kelola lokasi dan fasilitas.
- Coba akses `admin.php` menggunakan akun pengguna biasa.
- Periksa tampilan desktop dan mobile.

## 7. Catatan reset password

Form lupa password saat ini menyimpan token reset dalam database, tetapi pengiriman email belum terintegrasi dengan SMTP. Karena itu, pesan pada halaman tidak berarti email sudah benar-benar dikirim. Untuk penggunaan penuh, integrasikan layanan email/SMTP dan kirim tautan yang memuat token mentah hanya ke alamat email pengguna. Jangan menampilkan token kepada publik atau mengatur ulang password hanya berdasarkan email.

## 8. Catatan keamanan dan batasan

- Proyek ini merupakan fondasi pembelajaran; uji sebelum digunakan untuk data nyata.
- Jangan commit foto pengguna, rahasia, password, atau konfigurasi lokal.
- Periksa error log PHP jika halaman gagal dibuka.
- Pesan koneksi database sengaja dibuat umum agar tidak membocorkan kredensial.
- Jika unggah foto gagal, periksa ekstensi PHP, ukuran upload pada `php.ini`, dan izin folder `uploads/kos`.
