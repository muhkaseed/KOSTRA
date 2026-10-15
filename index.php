<?php
require_once __DIR__ . '/config/koneksi.php';

$pageTitle = 'Beranda';

$lokasi = $pdo->query("SELECT l.id, l.nama_lokasi, l.deskripsi, COUNT(k.id) AS jumlah_kos FROM lokasi l LEFT JOIN kos k ON k.lokasi_id=l.id AND k.status_aktif=1 GROUP BY l.id ORDER BY l.id")->fetchAll();

$stmt = $pdo->query("SELECT k.*, l.nama_lokasi, (SELECT f.path_file FROM foto_kos f WHERE f.kos_id=k.id ORDER BY f.is_utama DESC, f.id ASC LIMIT 1) AS foto FROM kos k JOIN lokasi l ON l.id=k.lokasi_id WHERE k.status_aktif=1 AND k.is_rekomendasi=1 ORDER BY k.updated_at DESC LIMIT 4");
$rekomendasi = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">TEMUKAN TEMPAT TINGGALMU</span>
                <h1>Temukan kos nyaman di sekitar <span>ITS Raya.</span></h1>
                <p>KOSTRA membantu kamu menjelajahi pilihan kos di sekitar kampus dengan cara yang lebih mudah.</p>

                <form class="hero-search" action="kos.php" method="get">
                    <label class="sr-only" for="q">Cari nama kos</label>
                    <input id="q" type="search" name="q" placeholder="Cari nama kos..." />
                    <button class="btn btn-primary" type="submit">Cari Kos <span aria-hidden="true">→</span></button>
                </form>

                <div class="hero-note">
                    <span class="status-dot"></span> Fokus di empat area sekitar ITS Raya
                </div>
            </div>

            <div class="hero-art" aria-label="Ilustrasi tempat tinggal">
                <div class="art-orbit orbit-one"></div>
                <div class="art-orbit orbit-two"></div>
                <div class="art-building">
                    <div class="building-roof"></div>
                    <div class="building-body">
                        <div class="window-grid">
                            <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                        </div>
                        <div class="door"></div>
                    </div>
                </div>
                <div class="floating-card floating-top">
                    <span class="mini-icon">⌂</span>
                    <div>
                        <b>Area pilihan</b>
                        <small>Di sekitar ITS Raya</small>
                    </div>
                </div>
                <div class="floating-card floating-bottom">
                    <span class="check-icon">✓</span>
                    <div>
                        <b>Lebih mudah dicari</b>
                        <small>Lokasi dalam satu tempat</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">JELAJAHI SEKITAR KAMPUS</span>
                    <h2>Pilih area favoritmu</h2>
                    <p>Mulai pencarian dari area yang paling sesuai dengan kebutuhanmu.</p>
                </div>
                <a class="text-link" href="kos.php">Lihat semua kos <span>→</span></a>
            </div>

            <div class="location-grid">
                <?php foreach ($lokasi as $item): ?>
                    <a class="location-card" href="kos.php?lokasi=<?= (int)$item['id'] ?>">
                        <span class="location-symbol">⌖</span>
                        <div>
                            <h3><?= e($item['nama_lokasi']) ?></h3>
                            <p><?= e($item['deskripsi'] ?: 'Jelajahi pilihan kos di area ini.') ?></p>
                            <small><?= (int)$item['jumlah_kos'] ?> kos aktif</small>
                        </div>
                        <span class="card-arrow">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">PILIHAN KOSTRA</span>
                    <h2>Rekomendasi kos</h2>
                    <p>Pilihan yang ditandai langsung oleh admin KOSTRA.</p>
                </div>
                <a class="text-link" href="kos.php">Jelajahi kos <span>→</span></a>
            </div>

            <?php if ($rekomendasi): ?>
                <div class="kos-grid">
                    <?php foreach ($rekomendasi as $item): ?>
                        <article class="kos-card">
                            <a class="kos-photo" href="detail-kos.php?id=<?= (int)$item['id'] ?>">
                                <?php if (!empty($item['foto'])): ?>
                                    <img src="<?= e($item['foto']) ?>" alt="<?= e($item['nama_kos']) ?>">
                                <?php else: ?>
                                    <div class="photo-placeholder">
                                        <span>⌂</span>
                                        <small>Foto kos belum tersedia</small>
                                    </div>
                                <?php endif; ?>
                                <span class="recommend-badge">Pilihan KOSTRA</span>
                            </a>
                            <div class="kos-card-body">
                                <span class="location-label"><?= e($item['nama_lokasi']) ?></span>
                                <h3>
                                    <a href="detail-kos.php?id=<?= (int)$item['id'] ?>"><?= e($item['nama_kos']) ?></a>
                                </h3>
                                <p class="price">
                                    Rp<?= number_format((float)$item['harga'], 0, ',', '.') ?><small> / bulan</small>
                                </p>
                                <div class="card-meta">
                                    <span><?= (int)$item['jumlah_kamar_tersedia'] > 0 ? 'Tersedia' : 'Penuh' ?></span>
                                    <span><?= (int)$item['jumlah_kamar_tersedia'] ?> kamar tersedia</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">⌂</div>
                    <h3>Rekomendasi belum tersedia</h3>
                    <p>Admin akan menambahkan pilihan kos di bagian ini. Kamu tetap bisa menjelajahi daftar kos.</p>
                    <a class="btn btn-outline" href="kos.php">Lihat daftar kos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container about-strip">
            <div class="about-mark">K.</div>
            <div>
                <span class="eyebrow">KENALAN DENGAN KOSTRA</span>
                <h2>Lebih mudah menemukan kos di sekitar ITS Raya.</h2>
                <p>KOSTRA mengumpulkan informasi kos di beberapa area sekitar kampus agar pencarian tempat tinggal terasa lebih sederhana.</p>
            </div>
            <a class="btn btn-primary" href="tentang.php">Tentang KOSTRA</a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
