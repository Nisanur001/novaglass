<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Geçersiz kullanıcı adı veya şifre!";
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - NovaGlass</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        .dark body { background-color: #0f0c15; color: #f3e8ff; }
        .dark .glass-box { background-color: rgba(24, 18, 35, 0.75) !important; border-color: rgba(107, 70, 193, 0.3) !important; }
        .dark input { background-color: rgba(30, 22, 45, 0.8) !important; border-color: rgba(107, 70, 193, 0.4) !important; color: #ffffff !important; }
        .dark input::placeholder { color: #a78bfa !important; }
    </style>
</head>
<body class="bg-[#f7f2fb] text-slate-700 min-h-screen flex items-center justify-center font-sans transition-colors duration-300">

    <div class="glass-box bg-white/80 backdrop-blur-md border border-purple-100 p-8 rounded-3xl shadow-xl w-full max-w-md space-y-6">
        <div class="text-center space-y-2">
            <span class="text-3xl">✨</span>
            <h1 class="text-2xl font-extrabold text-purple-950 dark:text-white">Tekrar Hoş Geldin</h1>
            <p class="text-xs text-purple-900/70 dark:text-purple-300">NovaGlass hesabına giriş yap ve kodlarını keşfet.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-3 bg-red-100 dark:bg-red-900/50 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-200 rounded-xl text-center text-xs font-medium">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-purple-300 mb-1">Kullanıcı Adı</label>
                <input type="text" name="username" class="w-full bg-purple-50/30 border border-purple-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-purple-300 mb-1">Şifre</label>
                <input type="password" name="password" class="w-full bg-purple-50/30 border border-purple-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-purple-400 outline-none" required>
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white text-sm py-3 rounded-xl hover:bg-purple-700 transition font-medium shadow-sm">Giriş Yap 💜</button>
        </form>

        <div class="text-center text-xs text-purple-900/70 dark:text-purple-300 space-y-2">
            <p>Hesabın yok mu? <a href="register.php" class="text-purple-700 dark:text-purple-400 font-bold underline">Kayıt Ol</a></p>
            <p><a href="index.php" class="text-xs text-purple-600 hover:underline block text-center mt-2">← Ana Sayfaya Dön</a></p>
        </div>
    </div>

</body>
</html>