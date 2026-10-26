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

// Yeni kod ekleme işlemi
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

        header('Location: index.php?added=1#code-pool-section');
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

$query = "SELECT snippets.*, users.username 
          FROM snippets 
          LEFT JOIN users ON snippets.user_id = users.id 
          WHERE 1=1";
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

// Yazılım Dilleri Listesi ve İkonları
$languageButtons = [
    'HTML'       => ['icon' => '🌐', 'color' => 'from-orange-500 to-amber-500'],
    'CSS'        => ['icon' => '🎨', 'color' => 'from-blue-500 to-cyan-500'],
    'JavaScript' => ['icon' => '⚡', 'color' => 'from-yellow-400 to-amber-500'],
    'Python'     => ['icon' => '🐍', 'color' => 'from-emerald-500 to-teal-600'],
    'C#'         => ['icon' => '🔷', 'color' => 'from-purple-600 to-indigo-600'],
    'Java'       => ['icon' => '☕', 'color' => 'from-red-500 to-rose-600'],
    'PHP'        => ['icon' => '🐘', 'color' => 'from-indigo-500 to-purple-500'],
    'SQL'        => ['icon' => '🗄️', 'color' => 'from-sky-500 to-blue-600'],
    'Genel'      => ['icon' => '📦', 'color' => 'from-pink-500 to-rose-500']
];
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
            background: rgba(255,255,255,.82);
            border: 1px solid rgba(196,164,226,.48);
            box-shadow: 0 18px 55px rgba(122,82,151,.10);
            backdrop-filter: blur(18px);
        }
        .hero-btn {
            background: linear-gradient(135deg, rgba(255,255,255,.95), rgba(250,232,255,.90));
            border: 2px solid rgba(192,132,252,.5);
            box-shadow: 0 20px 50px rgba(145,91,178,.15);
            transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hero-btn:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 30px 60px rgba(145,91,178,.28);
            border-color: rgba(168,85,247,.8);
        }
        .lang-btn {
            transition: all .25s ease;
        }
        .lang-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(147,51,234,.2);
        }
        .code-window { background: #1e1726; color: #e9d5ff; }
        .code-window code { white-space: pre; }
        .soft-button {
            background: linear-gradient(110deg, #a855f7, #ec4899);
            color: white;
            transition: filter .2s ease, transform .2s ease;
        }
        .soft-button:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .delete-btn {
            background: #fff0f5;
            color: #d65b83;
            border: 1px solid #f5c2d7;
            border-radius: 10px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }
        .delete-btn:hover { background: #f8d7e5; transform: translateY(-2px); }
        .snippet-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        @media (max-width: 768px) {
            .snippet-grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full px-6 sm:px-10 py-5 flex items-center justify-between">
    <a href="index.php" class="text-2xl sm:text-3xl font-black tracking-tight brand-text">
        ✦ NovaGlass
    </a>
    <div class="flex items-center gap-3">
        <?php if ($isLoggedIn): ?>
            <span class="hidden sm:inline text-sm font-medium text-purple-800">
                Merhaba, <strong><?= htmlspecialchars($username) ?></strong>
            </span>
            <?php if ($userRole === 'admin'): ?>
                <a href="admin_users.php" class="rounded-full border border-purple-200 bg-white/80 px-4 py-2 text-xs font-bold text-purple-700 shadow-sm">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="rounded-full bg-white/80 border border-pink-200 px-4 py-2 text-xs font-bold text-pink-700 shadow-sm">Çıkış</a>
        <?php else: ?>
            <a href="login.php" class="rounded-full bg-white/90 border border-purple-200 px-5 py-2.5 text-sm font-bold text-purple-700 shadow-sm">Giriş Yap</a>
            <a href="register.php" class="soft-button rounded-full px-5 py-2.5 text-sm font-bold shadow-md">Kayıt Ol</a>
        <?php endif; ?>
    </div>
</header>

<main class="flex-1">

    <!-- Hero Başlık -->
    <section class="min-h-[25vh] flex items-center justify-center px-4 py-6">
        <div class="text-center">
            <p class="mb-2 text-xs font-extrabold uppercase tracking-[.35em] text-pink-500">Kod dünyanızı keşfedin</p>
            <h1 class="text-6xl sm:text-7xl font-black tracking-tight brand-text">NovaGlass</h1>
            <p class="mt-3 text-base text-purple-800/80 font-medium">Kodlarınızı keşfedin, diller arasında gezin ve kendi kodlarınızı paylaşın. ✨</p>
        </div>
    </section>

    <!-- BÜYÜTÜLMÜŞ İKİ ANA BUTON -->
    <section class="px-6 pb-12 flex justify-center">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-5xl">
            
            <!-- 1. BÜYÜK BUTON: KOD HAVUZU -->
            <button type="button" onclick="toggleSection('code-pool-section')" class="hero-btn rounded-[2.5rem] p-8 sm:p-10 text-left cursor-pointer group">
                <div class="flex items-center gap-6 sm:gap-8">
                    <div class="flex h-20 w-20 sm:h-24 sm:w-24 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-purple-500 to-indigo-600 text-4xl sm:text-5xl text-white shadow-lg group-hover:scale-110 transition-transform">
                        📦
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-extrabold tracking-wider uppercase mb-2">Tüm Kodlar</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-purple-950">Kod Havuzu</h2>
                        <p class="mt-2 text-sm sm:text-base text-purple-700/80 font-medium">Paylaşılan hazır kodları dillerine göre listeleyin ve inceleyin.</p>
                    </div>
                </div>
            </button>

            <!-- 2. BÜYÜK BUTON: KOD PAYLAŞ -->
            <button type="button" onclick="toggleSection('add-code-section')" class="hero-btn rounded-[2.5rem] p-8 sm:p-10 text-left cursor-pointer group">
                <div class="flex items-center gap-6 sm:gap-8">
                    <div class="flex h-20 w-20 sm:h-24 sm:w-24 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-pink-500 to-rose-500 text-4xl sm:text-5xl text-white shadow-lg group-hover:scale-110 transition-transform">
                        🐱
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 bg-pink-100 text-pink-700 rounded-full text-xs font-extrabold tracking-wider uppercase mb-2">Yeni Paylaşım</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-purple-950">Kod Paylaş / Yaz</h2>
                        <p class="mt-2 text-sm sm:text-base text-purple-700/80 font-medium">Kendi kod parçacığınızı toplulukla buluşturmak için ekleyin.</p>
                    </div>
                </div>
            </button>

        </div>
    </section>

    <!-- 1. BÖLÜM: KOD HAVUZU & YAZILIM DİLİ BUTONLARI -->
    <section id="code-pool-section" class="px-6 pb-20 <?= (!empty($search) || !empty($category_filter)) ? '' : 'hidden' ?>">
        <div class="mx-auto max-w-6xl">
            
            <div class="mb-8 border-b border-purple-200/60 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-extrabold text-purple-950">Hazır Kod Havuzu</h2>
                        <p class="mt-1 text-sm text-purple-700/80 font-medium"><?= count($snippets) ?> adet kod parçacığı bulunuyor.</p>
                    </div>
                    <?php if (!empty($category_filter) || !empty($search)): ?>
                        <a href="index.php?show_pool=1#code-pool-section" class="self-start rounded-full border border-purple-300 bg-white/80 px-5 py-2 text-xs font-bold text-purple-800 shadow-sm hover:bg-purple-50">
                            Filtreleri Temizle ✕
                        </a>
                    <?php endif; ?>
                </div>

                <!-- YAZILIM DİLLERİ ÖZEL BUTONLARI -->
                <div class="mt-6">
                    <p class="text-xs font-extrabold text-purple-900 tracking-wider uppercase mb-3">Yazılım Diline Göre Filtrele:</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="index.php?show_pool=1#code-pool-section" class="lang-btn flex items-center gap-2 rounded-2xl bg-white border border-purple-200 px-4 py-2.5 text-xs font-bold text-purple-800 shadow-sm <?= empty($category_filter) ? 'ring-2 ring-purple-600 bg-purple-50' : '' ?>">
                            <span>🌈</span> Hepsi
                        </a>
                        <?php foreach ($languageButtons as $lang => $info): ?>
                            <a href="index.php?category=<?= urlencode($lang) ?>&show_pool=1#code-pool-section" 
                               class="lang-btn flex items-center gap-2 rounded-2xl bg-white border border-purple-200 px-4 py-2.5 text-xs font-bold text-purple-800 shadow-sm <?= $category_filter === $lang ? 'ring-2 ring-purple-600 bg-purple-100/70' : '' ?>">
                                <span class="text-sm"><?= $info['icon'] ?></span> <?= $lang ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Arama Çubuğu -->
            <div class="glass-card mb-8 rounded-3xl p-4">
                <form action="index.php" method="GET" class="flex gap-3">
                    <input type="hidden" name="show_pool" value="1">
                    <?php if (!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                    <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Kod başlığı veya içeriğinde ara..." class="w-full rounded-2xl border border-purple-200 bg-white/90 px-5 py-3.5 text-sm outline-none focus:border-purple-500 font-medium">
                    <button type="submit" class="soft-button rounded-2xl px-8 py-3.5 text-sm font-bold shrink-0">Ara</button>
                </form>
            </div>

            <!-- Kod Kartları Grid -->
            <?php if (empty($snippets)): ?>
                <div class="glass-card rounded-3xl p-12 text-center text-purple-800 font-medium">Bu kategoride veya aramada henüz kod bulunamadı. 🌷</div>
            <?php else: ?>
                <div class="snippet-grid">
                    <?php foreach ($snippets as $snippet): ?>
                        <article class="glass-card rounded-3xl p-6 flex flex-col justify-between">
                            <div>
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <span class="rounded-full bg-purple-100 border border-purple-200 px-3 py-1 text-xs font-extrabold text-purple-700">
                                        <?= $languageButtons[$snippet['category']]['icon'] ?? '💻' ?> <?= htmlspecialchars($snippet['category']) ?>
                                    </span>
                                    
                                    <!-- BUTONLAR: YAPAY ZEKA VE KOPYALA -->
                                    <div class="flex gap-2">
                                        <button type="button" 
                                                onclick="analyzeWithAI(this)" 
                                                class="rounded-full border border-purple-300 bg-purple-600 text-white px-3 py-1.5 text-xs font-bold hover:bg-purple-700 transition shadow-sm cursor-pointer">
                                            🤖 AI İncele
                                        </button>

                                        <button type="button" class="copy-code rounded-full border border-purple-200 bg-white px-3.5 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50 transition">
                                            Kodu Kopyala
                                        </button>
                                    </div>
                                </div>

                                <h3 class="break-words text-lg font-bold text-purple-950 mb-3"><?= htmlspecialchars($snippet['title']) ?></h3>
                                <pre class="code-window rounded-2xl p-4 text-xs leading-relaxed overflow-auto max-h-64"><code><?= htmlspecialchars($snippet['code_content']) ?></code></pre>

                                <!-- YAPAY ZEKA YANIT KUTUSU -->
                                <div class="ai-result-box hidden mt-3 p-4 rounded-2xl bg-purple-950 text-purple-100 text-xs leading-relaxed border border-purple-400/30 font-sans shadow-lg">
                                    <div class="font-bold text-pink-300 mb-1 flex items-center gap-1.5">
                                        <span>✨ NovaAI Analiz Sonucu:</span>
                                    </div>
                                    <div class="ai-response-text whitespace-pre-line text-purple-200">Analiz ediliyor...</div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-purple-100 pt-3 text-xs text-purple-600 font-medium">
                                <span>Ekleyen: <strong><?= htmlspecialchars($snippet['username'] ?? 'Sistem') ?></strong></span>
                                <div class="flex items-center gap-3">
                                    <span><?= htmlspecialchars($snippet['created_at'] ?? '') ?></span>
                                    <?php if ($isLoggedIn && ($userRole === 'admin' || $_SESSION['user_id'] == $snippet['user_id'])): ?>
                                        <form action="delete.php" method="POST" onsubmit="return confirm('Bu kodu silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="id" value="<?= (int)$snippet['id'] ?>">
                                            <button type="submit" class="delete-btn">🗑️ Sil</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- 2. BÖLÜM: KOD EKLEME / YAZMA FORMU -->
    <section id="add-code-section" class="px-6 pb-20 <?= isset($_GET['added']) ? '' : 'hidden' ?>">
        <div class="mx-auto max-w-3xl">
            <div class="glass-card rounded-[2.5rem] p-8 sm:p-10">
                <div class="flex items-center gap-4 mb-4">
                    <span class="text-4xl">🐱</span>
                    <div>
                        <h2 class="text-3xl font-extrabold text-purple-950">Yeni Kod Paylaş</h2>
                        <p class="text-xs text-purple-700/80 font-medium">Yazdığınız harika kod parçacıklarını toplulukla paylaşın.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="mb-6 rounded-2xl border border-purple-200 bg-purple-50 p-4 text-sm font-bold text-purple-800">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <form action="index.php" method="POST" class="space-y-5">
                        <input type="hidden" name="add_snippet" value="1">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-purple-900">Kod Başlığı</label>
                                <input type="text" name="title" required placeholder="Örn: Python Asal Sayı Bulucu" class="w-full rounded-2xl border border-purple-200 bg-white px-4 py-3.5 text-sm outline-none focus:border-purple-500 font-medium">
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-purple-900">Yazılım Dili / Kategori</label>
                                <select name="category" class="w-full rounded-2xl border border-purple-200 bg-white px-4 py-3.5 text-sm outline-none focus:border-purple-500 font-medium">
                                    <?php foreach ($languageButtons as $lang => $info): ?>
                                        <option value="<?= htmlspecialchars($lang) ?>"><?= $info['icon'] ?> <?= htmlspecialchars($lang) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-purple-900">Kod İçeriği</label>
                            <textarea name="code_content" rows="9" required placeholder="Kodlarınızı buraya yapıştırın..." class="w-full rounded-2xl border border-purple-200 bg-white p-4 font-mono text-sm text-purple-950 outline-none focus:border-purple-500"></textarea>
                        </div>

                        <button type="submit" class="soft-button w-full rounded-2xl py-4 text-base font-extrabold shadow-lg">
                            Havuza Kaydet ✨
                        </button>
                    </form>
                <?php else: ?>
                    <div class="rounded-2xl border border-purple-200 bg-white/80 p-8 text-center text-sm font-medium text-purple-900">
                        Kod paylaşabilmek için lütfen 
                        <a href="login.php" class="font-extrabold underline text-purple-700">Giriş Yapın</a> veya 
                        <a href="register.php" class="font-extrabold underline text-purple-700">Kayıt Olun</a>.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

</main>

<script>
// Section Açma / Kapama
function toggleSection(sectionId) {
    const target = document.getElementById(sectionId);
    const poolSec = document.getElementById('code-pool-section');
    const addSec = document.getElementById('add-code-section');

    if (!target.classList.contains('hidden')) {
        target.classList.add('hidden');
        return;
    }

    poolSec.classList.add('hidden');
    addSec.classList.add('hidden');

    target.classList.remove('hidden');
    target.scrollIntoView({ behavior: 'smooth' });
}

// Kopyalama İşlemi
document.querySelectorAll('.copy-code').forEach(button => {
    button.addEventListener('click', async () => {
        const article = button.closest('article');
        const codeElement = article?.querySelector('pre code');
        if (!codeElement) return;

        try {
            await navigator.clipboard.writeText(codeElement.innerText);
            button.textContent = 'Kopyalandı ✓';
        } catch (error) {
            button.textContent = 'Kopyalanamadı';
        }
        setTimeout(() => { button.textContent = 'Kodu Kopyala'; }, 1800);
    });
});

// Yapay Zeka (AI) İnceleme İsteği
async function analyzeWithAI(button) {
    const article = button.closest('article');
    const code = article.querySelector('pre code').innerText;
    
    // Kategori ismini temiz biçimde al
    const categorySpan = article.querySelector('.rounded-full');
    const category = categorySpan ? categorySpan.innerText.trim() : 'Genel';
    
    const resultBox = article.querySelector('.ai-result-box');
    const responseText = article.querySelector('.ai-response-text');

    resultBox.classList.remove('hidden');
    responseText.innerHTML = '⏳ <i>Yapay zeka kodu inceliyor, lütfen bekleyin...</i>';
    button.disabled = true;

    try {
        const response = await fetch('ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code, category: category })
        });

        const data = await response.json();

        if (data.answer) {
            responseText.innerText = data.answer;
        } else if (data.error) {
            responseText.innerText = '⚠️ ' + data.error;
        } else {
            responseText.innerText = 'Yanıt alınamadı.';
        }
    } catch (err) {
        responseText.innerText = 'İstek gönderilirken bir hata oluştu.';
    } finally {
        button.disabled = false;
    }
}

// Otomatik Havuz Açma
<?php if (isset($_GET['show_pool']) || !empty($category_filter) || !empty($search)): ?>
    document.getElementById('code-pool-section').classList.remove('hidden');
<?php endif; ?>
// Ekrana tıklandığında rastgele belirecek kısa kodlar
const floatingCodes = [
    'console.log("Hello Nova!");',
    '<?php echo "NovaGlass"; ?>',
    'SELECT * FROM magic;',
    'import { Magic } from "nova";',
    'p { color: #9b5de5; }',
    'const ai = new Gemini();',
    'git commit -m "Awesome UI"',
    'System.out.println("✦");',
    'return true;'
];

document.addEventListener('click', (e) => {
    // Eğer tıklanan yer bir buton, girdi alanı veya link ise kod patlaması oluşmasın
    if (e.target.closest('button, a, input, textarea, select, option, form, article')) {
        return;
    }

    // Tıklanan konuma özel element oluştur
    const el = document.createElement('div');
    const randomCode = floatingCodes[Math.floor(Math.random() * floatingCodes.length)];
    
    el.innerText = randomCode;
    el.className = 'fixed pointer-events-none text-xs font-mono font-bold px-3 py-1.5 rounded-xl border z-50 transition-all duration-1000 ease-out';
    
    // Glassmorphism ve Neon Mor/Pembe Stil
    el.style.left = `${e.clientX}px`;
    el.style.top = `${e.clientY}px`;
    el.style.background = 'rgba(255, 255, 255, 0.9)';
    el.style.color = '#9b5de5';
    el.style.borderColor = 'rgba(196, 164, 226, 0.6)';
    el.style.boxShadow = '0 10px 25px rgba(155, 93, 229, 0.2)';
    el.style.transform = 'translate(-50%, -50%) scale(0.6)';
    el.style.opacity = '0';

    document.body.appendChild(el);

    // Animasyon başlat (Büyü, yukarı çık ve kaybol)
    requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translate(-50%, -80px) scale(1)';
    });

    // 1 saniye sonra tamamen yok et
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translate(-50%, -120px) scale(0.8)';
        setTimeout(() => el.remove(), 500);
    }, 800);
});
</script>

</body>
</html>