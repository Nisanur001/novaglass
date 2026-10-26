<?php
require_once 'config.php';
require_once 'whatsapp.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$message = '';

$categories = [
    'HTML',
    'CSS',
    'JavaScript',
    'Java',
    'C#',
    'Python',
    'SQL',
    'Genel'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isLoggedIn) {
        header('Location: login.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Genel');
    $code_content = trim($_POST['code_content'] ?? '');

    if ($title === '' || $code_content === '') {
        $message = 'Lütfen başlık ve kod alanlarını doldur.';
    } elseif (!in_array($category, $categories, true)) {
        $message = 'Lütfen geçerli bir kategori seç.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO snippets
            (user_id, title, category, code_content, created_at)
            VALUES (?, ?, ?, ?, NOW())"
        );

        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $category,
            $code_content
        ]);

        header('Location: index.php?added=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NovaGlass | Kod Paylaş</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            color: #43334f;
            font-family: Inter, system-ui, sans-serif;
            background:
                radial-gradient(
                    ellipse at 15% 8%,
                    rgba(245,208,254,.72),
                    transparent 36%
                ),
                radial-gradient(
                    ellipse at 85% 25%,
                    rgba(221,214,254,.8),
                    transparent 34%
                ),
                linear-gradient(
                    145deg,
                    #fff8fd 0%,
                    #f7f1ff 48%,
                    #fff7fb 100%
                );
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

        .glass-card {
            background: rgba(255,255,255,.82);
            border: 1px solid rgba(196,164,226,.48);
            box-shadow: 0 18px 55px rgba(122,82,151,.10);
            backdrop-filter: blur(18px);
        }

        .soft-button {
            background: linear-gradient(110deg, #a855f7, #ec4899);
            color: white;
            transition: .2s ease;
        }

        .soft-button:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #e4d4f4;
            background: rgba(255,255,255,.9);
            border-radius: 14px;
            padding: 13px 15px;
            outline: none;
            color: #43334f;
        }

        .form-control:focus {
            border-color: #b77ae8;
            box-shadow: 0 0 0 3px rgba(168,85,247,.10);
        }

        textarea.form-control {
            min-height: 250px;
            resize: vertical;
            font-family: Consolas, monospace;
            line-height: 1.6;
        }
    </style>
</head>

<body>

<header class="flex items-center justify-between px-4 py-5 sm:px-8">
    <a href="index.php"
       class="brand-text text-2xl font-black">
        ✦ NovaGlass
    </a>

    <a href="index.php"
       class="rounded-full border border-purple-200 bg-white/80 px-4 py-2 text-sm font-semibold text-purple-700">
        ← Ana Sayfa
    </a>
</header>

<main class="px-4 py-8 sm:py-12">

    <div class="mx-auto max-w-3xl">

        <div class="mb-8 text-center">
            <p class="mb-3 text-xs font-bold uppercase tracking-[.25em] text-pink-500">
                Fikrini kodla
            </p>

            <h1 class="brand-text text-4xl font-black sm:text-5xl">
                Kod Havuzu 💻
            </h1>

            <p class="mt-4 text-purple-700/80">
                Kodunu paylaş, başkalarının öğrenmesine yardımcı ol. ✨
            </p>
        </div>

        <?php if (!$isLoggedIn): ?>

            <div class="glass-card rounded-3xl p-8 text-center">
                <p class="mb-5 text-purple-800">
                    Kod paylaşmak için önce giriş yapmalısın. 🌸
                </p>

                <a href="login.php"
                   class="soft-button inline-block rounded-xl px-6 py-3 font-bold">
                    Giriş Yap
                </a>
            </div>

        <?php else: ?>

            <form method="POST"
                  action="code_pool.php"
                  class="glass-card space-y-6 rounded-3xl p-5 sm:p-8">

                <div>
                    <label for="title"
                           class="mb-2 block font-bold text-purple-950">
                        Kodun başlığı
                    </label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        maxlength="150"
                        required
                        placeholder="Örn. Basit bir HTML kart tasarımı"
                        class="form-control"
                    >
                </div>

                <div>
                    <label for="category"
                           class="mb-2 block font-bold text-purple-950">
                        Kategori
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                        class="form-control"
                    >
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="code_content"
                           class="mb-2 block font-bold text-purple-950">
                        Kodun
                    </label>

                    <textarea
                        id="code_content"
                        name="code_content"
                        required
                        placeholder="Kodunu buraya yaz veya yapıştır..."
                        class="form-control"
                    ></textarea>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="rounded-xl border border-pink-200 bg-pink-50 p-4 text-sm text-pink-700">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <button
                    type="submit"
                    class="soft-button w-full rounded-2xl px-6 py-4 font-bold"
                >
                    ✨ Kodu Paylaş
                </button>

            </form>

        <?php endif; ?>

        <p class="mt-6 text-center text-sm text-purple-600/80">
            Paylaştığın kod, ana sayfadaki kod listesinde görüntülenir.
        </p>

    </div>

</main>

</body>
</html>