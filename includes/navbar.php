<header class="site-header"><div class="container nav-container">
  <a class="brand" href="index.php" aria-label="KOSTRA beranda"><img src="assets/images/logo.png" alt="" onerror="this.style.display='none'"><span class="brand-mark">K.</span><span class="brand-text">KOSTRA<small>Kos ITS Raya</small></span></a>
  <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
  <nav class="main-nav" id="main-nav"><a href="index.php">Beranda</a><a href="kos.php">Cari Kos</a><div class="nav-dropdown"><button type="button" class="nav-dropdown-toggle">Lokasi <span>⌄</span></button><div class="nav-dropdown-menu"><?php
  try { $navLocations=$pdo->query("SELECT id,nama_lokasi FROM lokasi WHERE status_aktif=1 ORDER BY nama_lokasi")->fetchAll(); foreach($navLocations as $nl): ?><a href="kos.php?lokasi=<?= (int)$nl['id'] ?>"><?= e($nl['nama_lokasi']) ?></a><?php endforeach; } catch(Throwable $ex) { }
  ?></div></div><a href="tentang.php">Tentang</a>
  <?php if (function_exists('is_logged_in') && is_logged_in()): ?><a href="profil.php">Profil</a><?php if (is_admin()): ?><a href="admin.php">Admin</a><?php endif; ?><a class="nav-cta" href="logout.php">Keluar</a><?php else: ?><a href="login.php">Login</a><a class="nav-cta" href="register.php">Daftar</a><?php endif; ?>
  </nav>
</div></header>
<main id="main-content" class="main-content-start">