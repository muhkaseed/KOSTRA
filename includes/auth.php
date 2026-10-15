<?php
require_once __DIR__ . '/../config/koneksi.php';
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function is_admin(): bool { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function require_login(): void { if (!is_logged_in()) redirect('login.php'); }
function require_admin(): void { if (!is_logged_in()) redirect('login.php'); if (!is_admin()) { http_response_code(403); exit('Akses ditolak. Halaman ini hanya untuk admin.'); } }
function logout_user(): void { $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { $sent=$_POST['csrf_token']??''; if (!is_string($sent)||!hash_equals(csrf_token(),$sent)) { http_response_code(419); exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'); } }
?>