<?php
// Oturumu başlat ve sonlandır
require_once 'config.php';

// Tüm oturum değişkenlerini sıfırla
$_SESSION = [];

// Oturum çerezini sil (varsa)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen yok et
session_destroy();

// Ana sayfaya yönlendir
header("Location: index.php");
exit;