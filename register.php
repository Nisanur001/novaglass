```php
<?php
require_once 'config.php';
require_once 'mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $email !== '' && $password !== '') {

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Bu kullanıcı adı veya e-posta zaten kayıtlı.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (username, email, password, created_at)
                VALUES (?, ?, ?, NOW())"
            );

            $executed = $stmt->execute([
                $username,
                $email,
                $hashedPassword
            ]);

            if ($executed) {
                $subject = "NovaGlass'a Hoş Geldin!";

                $safeUsername = htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $body = "
                    <h2>Merhaba {$safeUsername}!</h2>
                    <p>NovaGlass ailesine katıldığın için teşekkürler.</p>
                    <p>Kodlarını keşfetmeye ve paylaşmaya başlayabilirsin.</p>
                    <p>İyi kodlamalar! 💜</p>
                ";

                try {
                    sendEmail(
                        $email,
                        $username,
                        $subject,
                        $body
                    );
                } catch (Throwable $e) {
                    // E-posta gönderilemese bile kayıt korunur.
                }

                $success = "Kayıt başarılı! Artık giriş yapabilirsin.";
            } else {
                $error = "Kayıt sırasında bir hata oluştu.";
            }
        }
    } else {
        $error = "Lütfen tüm alanları eksiksiz doldur.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kayıt Ol | NovaGlass</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #493653;
            background:
                radial-gradient(
                    ellipse at 10% 10%,
                    rgba(245, 208, 254, 0.85),
                    transparent 40%
                ),
                radial-gradient(
                    ellipse at 90% 85%,
                    rgba(221, 214, 254, 0.85),
                    transparent 40%
                ),
                linear-gradient(
                    145deg,
                    #fff8fd 0%,
                    #f7f1ff 50%,
                    #fff7fb 100%
                );
        }

        .register-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(196, 164, 226, 0.55);
            box-shadow: 0 24px 70px rgba(122, 82, 151, 0.13);
            backdrop-filter: blur(20px);
        }

        .brand-text {
            background: linear-gradient(
                100deg,
                #9b5de5,
                #e879b9 55%,
                #8b5cf6
            );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .form-input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e5d4f5;
            border-radius: 14px;
            background: rgba(250, 245, 255, 0.75);
            color: #493653;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: #b47be8;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.12);
        }

        .primary-button {
            background: linear-gradient(110deg, #a855f7, #ec4899);
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .primary-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(168, 85, 247, 0.2);
        }

        .home-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            border: 1px solid #e5d4f5;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            color: #7e22ce;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }

        .home-button:hover {
            background: #f3e8ff;
            transform: translateY(-1px);
        }

        .message-box {
            padding: 13px;
            border-radius: 14px;
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
        }

        .error-message {
            color: #be3455;
            background: #fff0f3;
            border: 1px solid #fecdd8;
        }

        .success-message {
            color: #18794e;
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Üst logo -->
    <header class="w-full px-5 sm:px-8 py-5">
        <a
            href="index.php"
            class="brand-text text-2xl font-black tracking-tight"
        >
            ✦ NovaGlass
        </a>
    </header>

    <!-- Kayıt alanı -->
    <main class="flex-1 flex items-center justify-center px-4 py-8">

        <section class="register-card w-full max-w-md rounded-[2rem] p-6 sm:p-9">

            <div class="text-center mb-7">

                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-purple-200 to-pink-200 text-3xl shadow-inner">
                    🐱
                </div>

                <p class="text-xs font-bold uppercase tracking-[.25em] text-pink-500 mb-2">
                    NovaGlass ailesi
                </p>

                <h1 class="text-3xl font-black text-purple-950">
                    Aramıza Katıl
                </h1>

                <p class="mt-3 text-sm leading-6 text-purple-700/80">
                    Yeni hesabını oluştur, kodlarını keşfet
                    ve fikirlerini geliştir. ✨
                </p>

            </div>

            <?php if ($error !== ''): ?>
                <div class="message-box error-message mb-5" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="message-box success-message mb-5" role="status">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    <br>
                    <a
                        href="login.php"
                        class="mt-2 inline-block font-bold underline"
                    >
                        Giriş Yap →
                    </a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-5">

                <div>
                    <label
                        for="username"
                        class="mb-2 block text-sm font-semibold text-purple-900"
                    >
                        Kullanıcı Adı
                    </label>

                    <input
                        id="username"
                        type="text"
                        name="username"
                        placeholder="Kullanıcı adını yaz"
                        autocomplete="username"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="form-input"
                        required
                    >
                </div>

                <div>
                    <label
                        for="email"
                        class="mb-2 block text-sm font-semibold text-purple-900"
                    >
                        E-posta Adresi
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="ornek@mail.com"
                        autocomplete="email"
                        maxlength="254"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        class="form-input"
                        required
                    >
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-2 block text-sm font-semibold text-purple-900"
                    >
                        Şifre
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Şifreni oluştur"
                        autocomplete="new-password"
                        class="form-input"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="primary-button w-full rounded-2xl py-3.5 text-sm font-bold shadow-md"
                >
                    Hesabımı Oluştur ✨
                </button>

            </form>

            <div class="mt-6 border-t border-purple-100 pt-5 text-center">

                <p class="mb-5 text-sm text-purple-700/80">
                    Zaten hesabın var mı?
                    <a
                        href="login.php"
                        class="font-bold text-purple-700 hover:text-pink-600 hover:underline"
                    >
                        Giriş Yap
                    </a>
                </p>

                <!-- Ana sayfa bağlantısı: Kod işaretleri olmadan -->
                <a href="/snippet-kutuphanesi/index.php" class="home-button">
    ← Ana Sayfaya Dön
</a>

            </div>

        </section>

    </main>

    <footer class="px-4 py-5 text-center text-xs text-purple-500/80">
        NovaGlass © 2026 · Kodlarını keşfet, hayallerini geliştir. 💜
    </footer>

</body>
</html>
```
