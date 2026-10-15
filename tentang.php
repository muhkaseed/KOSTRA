<?php
require_once __DIR__ . '/config/koneksi.php';
$pageTitle='Tentang KOSTRA';
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="page-main"><div class="container about-page">
  <section class="about-hero"><span class="eyebrow">TENTANG KAMI</span><h1>Kenalan dengan <span>KOSTRA.</span></h1><p>KOSTRA (Kos ITS Raya) adalah website pencarian informasi kos yang berfokus pada beberapa area di sekitar ITS Raya.</p></section>
  <section class="about-feature-grid"><article class="feature-card"><span class="feature-icon">⌖</span><h2>Fokus lokasi</h2><p>Menjelajahi kos di Keputih, Gebang, Tegal Mulyorejo Baru, dan Mulyosari.</p></article><article class="feature-card"><span class="feature-icon">⌕</span><h2>Pencarian sederhana</h2><p>Gunakan pencarian, filter harga, lokasi, dan fasilitas untuk menyaring pilihan.</p></article><article class="feature-card"><span class="feature-icon">♡</span><h2>Informasi terpusat</h2><p>Melihat informasi kos dan menghubungi pemilik melalui WhatsApp setelah login.</p></article></section>
  <section class="about-note"><h2>Cara menggunakan KOSTRA</h2><ol><li>Pilih area atau buka halaman Cari Kos.</li><li>Gunakan filter sesuai kebutuhanmu.</li><li>Login untuk melihat informasi lengkap kos.</li><li>Hubungi pemilik melalui WhatsApp untuk bertanya lebih lanjut.</li></ol><p class="muted">KOSTRA merupakan sarana informasi, bukan layanan pemesanan atau pembayaran kos.</p></section>
</div></main>
<?php require __DIR__.'/includes/footer.php'; ?>