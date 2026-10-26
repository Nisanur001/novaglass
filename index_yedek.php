<?php
require_once 'config.php';
require_once 'whatsapp.php';

// Oturum ve kullanıcı bilgileri
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? ($_SESSION['username'] ?? '') : '';
$userRole = 'user';

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $dbRole = $stmt->fetchColumn();

    if ($dbRole !== false) {
        $userRole = $dbRole;
        $_SESSION['role'] = $dbRole;
    }
}

$message = '';

// Yeni kod ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_snippet']) && $isLoggedIn) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Genel');
    $code_content = trim($_POST['code_content'] ?? '');

    if ($title !== '' && $code_content !== '') {
        $stmt = $pdo->prepare(
            "INSERT INTO snippets (user_id, title, category, code_content, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$_SESSION['user_id'], $title, $category, $code_content]);

        $adminPhone = "5551234567";
        $msg = "NovaGlass - Yeni Kod Paylaşımı!\n\nEkleyen: {$username}\nBaşlık: {$title}\nKategori: {$category}";

        if (function_exists('sendWhatsAppMessage')) {
            try {
                sendWhatsAppMessage($adminPhone, $msg);
            } catch (Throwable $e) {}
        }

        header('Location: index.php?added=1#code-pool');
        exit;
    } else {
        $message = 'Lütfen başlık ve kod alanlarını doldurun.';
    }
}

if (isset($_GET['added'])) {
    $message = 'Kod parçacığı başarıyla eklendi!';
}

// Arama ve kategori filtresi
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

$query = "SELECT snippets.*, users.username FROM snippets LEFT JOIN users ON snippets.user_id = users.id WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (snippets.title LIKE ? OR snippets.code_content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category_filter !== '') {
    $query .= " AND snippets.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY snippets.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$snippets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = ['HTML', 'CSS', 'JavaScript', 'Java', 'C#', 'Python', 'SQL', 'Genel'];
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaGlass | Kod Havuzu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            color: #43334f;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(ellipse at 15% 8%, rgba(245,208,254,.72), transparent 36%),
                radial-gradient(ellipse at 85% 25%, rgba(221,214,254,.8), transparent 34%),
                linear-gradient(145deg, #fff8fd 0%, #f7f1ff 48%, #fff7fb 100%);
            min-height: 100vh;
        }
        .brand-text {
            background: linear-gradient(100deg, #9b5de5, #e879b9 55%, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .glass-card {
            background: rgba(255,255,255,.78);
            border: 1px solid rgba(196,164,226,.48);
            box-shadow: 0 18px 55px rgba(122,82,151,.10);
            backdrop-filter: blur(18px);
        }
        .pool-card {
            background: linear-gradient(135deg, rgba(255,255,255,.92), rgba(250,232,255,.88));
            border: 1px solid rgba(192,132,252,.45);
            box-shadow: 0 20px 60px rgba(145,91,178,.16);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .pool-card:hover, .pool-card:focus-visible {
            transform: translateY(-5px);
            box-shadow: 0 26px 70px rgba(145,91,178,.24);
            outline: none;
        }
        .code-window { background: #241b30; color: #e9d5ff; }
        .code-window code { white-space: pre; }
        .soft-button {
            background: linear-gradient(110deg, #a855f7, #ec4899);
            color: white;
            transition: filter .2s ease, transform .2s ease;
        }
        .soft-button:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .category-pill { transition: background .2s ease, color .2s ease; }
        .category-pill.active { background: #9333ea; color: white; }
        .delete-btn {
            background: #fff0f5;
            color: #d65b83;
            border: 1px solid #f5c2d7;
            border-radius: 10px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .delete-btn:hover {
            background: #f8d7e5;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(214, 91, 131, 0.15);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<header class="w-full px-4 sm:px-8 py-4 flex items-center justify-between">
    <a href="index.php" class="text-xl sm:text-2xl font-black tracking-tight brand-text">✦ NovaGlass</a>
    <div class="flex items-center gap-2">
        <?php if ($isLoggedIn): ?>
            <span class="hidden sm:inline text-sm text-purple-700">
                Merhaba, <strong><?= htmlspecialchars($username) ?></strong>
            </span>
            <?php if ($userRole === 'admin'): ?>
                <a href="admin_users.php" class="rounded-full border border-purple-200 bg-white/70 px-3 py-2 text-xs font-semibold text-purple-700">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="rounded-full bg-white/80 border border-pink-200 px-3 py-2 text-xs font-semibold text-pink-700">Çıkış</a>
        <?php else: ?>
            <a href="login.php" class="rounded-full bg-white/80 border border-purple-200 px-3.5 py-2 text-sm font-semibold text-purple-700">Giriş Yap</a>
            <a href="register.php" class="soft-button rounded-full px-3.5 py-2 text-sm font-semibold">Kayıt Ol</a>
        <?php endif; ?>
    </div>
</header>

<main class="flex-1">
    <section class="min-h-[65vh] sm:min-h-[72vh] flex items-center justify-center px-4 py-16">
        <div class="text-center">
            <p class="mb-3 text-xs font-bold uppercase tracking-[.3em] text-pink-500">Kodlarını keşfet</p>
            <h1 class="text-5xl sm:text-7xl md:text-8xl font-black tracking-tight brand-text">NovaGlass</h1>
            <p class="mt-5 text-purple-700/80">Kodlarını keşfet, fikirlerini geliştir. ✨</p>
        </div>
    </section>

    <section class="px-4 pb-20 flex justify-center">
        <button type="button" id="pool-card" aria-controls="code-pool" aria-expanded="<?= (!empty($search) || !empty($category_filter)) ? 'true' : 'false' ?>" class="pool-card w-full max-w-2xl rounded-[2rem] p-7 sm:p-10 text-left cursor-pointer">
            <div class="flex items-center gap-5 sm:gap-7">
                <div class="flex h-16 w-16 sm:h-20 sm:w-20 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-purple-200 to-pink-200 text-4xl shadow-inner">🐱</div>
                <div class="min-w-0 flex-1">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[.2em] text-purple-500">NovaGlass</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-purple-950">Kod Havuzu</h2>
                    <p class="mt-2 text-sm sm:text-base text-purple-700/80">Hazır kodları keşfet, incele ve tek tıkla kopyala.</p>
                </div>
                <span id="pool-arrow" class="text-3xl text-purple-500" aria-hidden="true">↓</span>
            </div>
        </button>
    </section>

    <section id="code-pool" class="px-4 pb-16 <?= (!empty($search) || !empty($category_filter) || isset($_GET['added'])) ? '' : 'hidden' ?>">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.2em] text-pink-500">Keşfet ve öğren</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-purple-950">Hazır Kodlar</h2>
                    <p class="mt-2 text-sm text-purple-700/80"><?= count($snippets) ?> kod listeleniyor.</p>
                </div>
                <a href="index.php#code-pool" class="self-start rounded-full border border-purple-200 bg-white/70 px-4 py-2 text-sm font-semibold text-purple-700">Filtreleri temizle</a>
            </div>

            <div class="glass-card mb-8 rounded-3xl p-4 sm:p-5">
                <form action="index.php#code-pool" method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_190px_auto]">
                    <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Kod başlığı veya içerik ara..." class="min-w-0 rounded-2xl border border-purple-200 bg-white/80 px-4 py-3 text-sm outline-none focus:border-purple-400">
                    <select name="category" class="rounded-2xl border border-purple-200 bg-white/80 px-4 py-3 text-sm outline-none">
                        <option value="">Tüm kategoriler</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="soft-button rounded-2xl px-6 py-3 text-sm font-bold">Ara</button>
                </form>
            </div>

            <?php if (empty($snippets)): ?>
                <div class="glass-card rounded-3xl p-10 text-center text-purple-700">Bu aramada henüz kod bulunamadı. 🌷</div>
            <?php else: ?>
                <?php foreach ($snippets as $snippet): ?>
                    <article class="glass-card mb-6 rounded-3xl p-5 sm:p-7">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-purple-950"><?= htmlspecialchars($snippet['title']) ?></h3>
                                <span class="mt-2 inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700"><?= htmlspecialchars($snippet['category']) ?></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="copy-code rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-bold text-purple-700">Kodu Kopyala</button>
                            </div>
                        </div>

                        <pre class="code-window overflow-x-auto rounded-2xl p-4 sm:p-5 text-xs sm:text-sm leading-relaxed"><code><?= htmlspecialchars($snippet['code_content']) ?></code></pre>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-purple-100 pt-3 text-xs text-purple-600">
                            <span>Ekleyen: <strong><?= htmlspecialchars($snippet['username'] ?? 'Sistem') ?></strong></span>
                            <div class="flex items-center gap-3">
                                <span><?= htmlspecialchars($snippet['created_at'] ?? '') ?></span>
                                <?php if (isset($_SESSION['user_id']) && $userRole === 'admin'): ?>
                                    <form action="delete.php" method="POST" onsubmit="return confirm('Bu kodu silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="id" value="<?= (int)$snippet['id'] ?>">
                                        <button type="submit" class="delete-btn">🗑️ Sil</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
const poolCard = document.getElementById('pool-card');
const codePool = document.getElementById('code-pool');
const poolArrow = document.getElementById('pool-arrow');

poolCard.addEventListener('click', () => {
    const willOpen = codePool.classList.contains('hidden');
    codePool.classList.toggle('hidden', !willOpen);
    poolCard.setAttribute('aria-expanded', String(willOpen));
    poolArrow.textContent = willOpen ? '↑' : '↓';
    if (willOpen) {
        setTimeout(() => { codePool.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 30);
    }
});

document.querySelectorAll('.copy-code').forEach(button => {
    button.addEventListener('click', async () => {
        const code = button.closest('article').querySelector('pre code').innerText;
        await navigator.clipboard.writeText(code);
        button.textContent = 'Kopyalandı ✓';
        setTimeout(() => { button.textContent = 'Kodu Kopyala'; }, 1800);
    });
});
</script>
</body>
</html>