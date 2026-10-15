<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT k.*, l.nama_lokasi FROM kos k JOIN lokasi l ON l.id=k.lokasi_id WHERE k.id=? AND k.status_aktif=1");
$stmt->execute([$id ?: 0]);
$kos = $stmt->fetch();

if (!$kos) {
    http_response_code(404);
    $pageTitle = 'Kos tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/navbar.php';
    echo '<main class="container page-main"><div class="empty-state"><h1>Kos tidak ditemukan</h1><p>Kos mungkin sudah tidak aktif atau tautannya tidak valid.</p><a class="btn btn-primary" href="kos.php">Kembali ke daftar kos</a></div></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $kos['nama_kos'];

$photoStmt = $pdo->prepare("SELECT path_file FROM foto_kos WHERE kos_id=? ORDER BY is_utama DESC,id ASC");
$photoStmt->execute([$kos['id']]);
$photos = $photoStmt->fetchAll();

$facStmt = $pdo->prepare("SELECT f.nama_fasilitas FROM fasilitas f JOIN kos_fasilitas kf ON kf.fasilitas_id=f.id WHERE kf.kos_id=? ORDER BY f.nama_fasilitas");
$facStmt->execute([$kos['id']]);
$facilities = $facStmt->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main class="page-main">
    <div class="container">
        <a class="back-link" href="kos.php">← Kembali ke daftar kos</a>

        <div class="detail-heading">
            <div>
                <span class="location-label"><?= e($kos['nama_lokasi']) ?></span>
                <h1><?= e($kos['nama_kos']) ?></h1>
                <p>Informasi kos di area <?= e($kos['nama_lokasi']) ?>.</p>
            </div>
            <div class="detail-price">
                Rp<?= number_format((float)$kos['harga'], 0, ',', '.') ?><small>/ bulan</small>
            </div>
        </div>

        <div class="detail-gallery">
            <?php if ($photos): ?>
                <?php foreach (array_slice($photos, 0, 4) as $p): ?>
                    <div class="gallery-item">
                        <img src="<?= e($p['path_file']) ?>" alt="Foto <?= e($kos['nama_kos']) ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="photo-placeholder gallery-empty">
                    <span>⌂</span>
                    <small>Foto kos belum tersedia</small>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!is_logged_in()): ?>
            <section class="login-notice">
                <div>
                    <h2>Masuk untuk melihat detail lengkap</h2>
                    <p>Login diperlukan untuk melihat informasi lengkap dan menghubungi pemilik kos melalui WhatsApp.</p>
                </div>
                <div class="button-row">
                    <a class="btn btn-primary" href="login.php">Login</a>
                    <a class="btn btn-outline" href="register.php">Daftar akun</a>
                </div>
            </section>
        <?php else: ?>
            <div class="detail-columns">
                <div class="detail-content card-panel">
                    <h2>Deskripsi kos</h2>
                    <p class="preserve-lines"><?= e($kos['deskripsi']) ?></p>

                    <h2>Fasilitas</h2>
                    <?php if ($facilities): ?>
                        <div class="facility-tags">
                            <?php foreach ($facilities as $f): ?>
                                <span><?= e($f['nama_fasilitas']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Informasi fasilitas belum tersedia.</p>
                    <?php endif; ?>
                </div>

                <aside class="detail-sidebar card-panel">
                    <h2>Ketersediaan kamar</h2>
                    <div class="room-number">
                        <?= (int)$kos['jumlah_kamar_tersedia'] ?><small> dari <?= (int)$kos['jumlah_kamar_total'] ?> kamar tersedia</small>
                    </div>
                    <span class="status-badge <?= (int)$kos['jumlah_kamar_tersedia'] > 0 ? 'available' : 'full' ?>">
                        <?= (int)$kos['jumlah_kamar_tersedia'] > 0 ? 'Masih tersedia' : 'Kamar penuh' ?>
                    </span>
                    <p>Hubungi pemilik untuk bertanya lebih lanjut mengenai kos ini.</p>

                    <?php if ($kos['kontak']): ?>
                        <?php
                        $wa = preg_replace('/\D/', '', $kos['kontak']);
                        $msg = rawurlencode('Halo, saya ingin bertanya tentang ' . $kos['nama_kos'] . '.');
                        ?>
                        <a class="btn btn-primary btn-block" target="_blank" rel="noopener noreferrer" href="https://wa.me/<?= e($wa) ?>?text=<?= $msg ?>">Hubungi via WhatsApp ↗</a>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
