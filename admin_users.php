<?php
require_once 'config.php';

// Güvenlik ve Yetki Kontrolü: Sadece admin giriş yaptıysa erişebilir
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit('Bu sayfaya erişim yetkiniz yok.');
}

$message = '';
$error = '';

// KULLANICI SİLME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    // Kendisini silmesini engelle
    if ($userId === (int)$_SESSION['user_id']) {
        $error = "Kendi admin hesabınızı silemezsiniz!";
    } elseif ($userId) {
        $stmt = $pdo->prepare("DELETE FROM snippets WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $message = "Kullanıcı ve tüm paylaşımları başarıyla silindi.";
    }
}

// ROL DEĞİŞTİRME İŞLEMİ (User <-> Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $newRole = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';

    if ($userId === (int)$_SESSION['user_id']) {
        $error = "Kendi rolünüzü değiştiremezsiniz!";
    } elseif ($userId) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $message = "Kullanıcı rolü başarıyla güncellendi.";
    }
}

// İSTATİSTİKLERİ ÇEK
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSnippets = (int)$pdo->query("SELECT COUNT(*) FROM snippets")->fetchColumn();

// KULLANICILARI VE KOD BİLGİLERİNİ ÇEK
$usersStmt = $pdo->query("
    SELECT users.*, COUNT(snippets.id) AS snippet_count 
    FROM users 
    LEFT JOIN snippets ON users.id = snippets.user_id 
    GROUP BY users.id 
    ORDER BY users.id DESC
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// KULLANICILARIN PAYLAŞTIĞI KODLARI DETAYLI GÖZLEMLEMEK İÇİN VERİ HAZIRLIĞI
$userSnippetsMap = [];
$allSnippetsStmt = $pdo->query("SELECT id, user_id, title, category, created_at FROM snippets ORDER BY id DESC");
while ($row = $allSnippetsStmt->fetch(PDO::FETCH_ASSOC)) {
    $userSnippetsMap[$row['user_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli | NovaGlass</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(145deg, #fff8fd 0%, #f7f1ff 48%, #fff7fb 100%);
            color: #43334f;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(196, 164, 226, 0.48);
            box-shadow: 0 18px 55px rgba(122, 82, 151, 0.10);
            backdrop-filter: blur(18px);
        }
        .brand-text {
            background: linear-gradient(100deg, #9b5de5, #e879b9 55%, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body class="p-4 sm:p-8">

    <div class="mx-auto max-w-6xl space-y-8">
        
        <!-- Üst Bar -->
        <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 glass-card p-6 rounded-3xl">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black brand-text">👑 Admin Yönetim & Gözlem Paneli</h1>
                <p class="text-xs text-purple-700/80 font-medium mt-1">Kullanıcı hareketlerini, paylaşılan kodları ve yetkileri buradan izleyebilirsiniz.</p>
            </div>
            <a href="index.php" class="rounded-full bg-purple-600 text-white px-5 py-2.5 text-xs font-bold hover:bg-purple-700 transition shadow-md shrink-0">
                ← Ana Sayfaya Dön
            </a>
        </header>

        <!-- Bildirim Mesajları -->
        <?php if (!empty($message)): ?>
            <div class="p-4 bg-green-100/90 border border-green-300 text-green-800 rounded-2xl text-sm font-bold shadow-sm">
                ✨ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-red-100/90 border border-red-300 text-red-800 rounded-2xl text-sm font-bold shadow-sm">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="glass-card p-6 rounded-3xl flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-3xl font-black shadow-inner">
                    👥
                </div>
                <div>
                    <p class="text-xs font-extrabold text-purple-500 uppercase tracking-wider">Toplam Kayıtlı Kullanıcı</p>
                    <h3 class="text-3xl font-black text-purple-950 mt-0.5"><?php echo $totalUsers; ?></h3>
                </div>
            </div>

            <div class="glass-card p-6 rounded-3xl flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-pink-100 text-pink-600 flex items-center justify-center text-3xl font-black shadow-inner">
                    💻
                </div>
                <div>
                    <p class="text-xs font-extrabold text-pink-500 uppercase tracking-wider">Toplam Paylaşılan Kod</p>
                    <h3 class="text-3xl font-black text-purple-950 mt-0.5"><?php echo $totalSnippets; ?></h3>
                </div>
            </div>
        </div>

        <!-- Kullanıcı Listesi ve Gözlem Tablosu -->
        <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
            <div class="flex items-center justify-between border-b border-purple-100 pb-4">
                <h2 class="text-xl font-extrabold text-purple-950">Kullanıcı Aktiviteleri & Yönetim</h2>
                <span class="text-xs font-bold text-purple-600 bg-purple-100 px-3 py-1 rounded-full"><?php echo count($users); ?> Üye Mevcut</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-purple-100 text-xs font-extrabold uppercase text-purple-600 tracking-wider">
                            <th class="py-3.5 px-4">ID</th>
                            <th class="py-3.5 px-4">Kullanıcı</th>
                            <th class="py-3.5 px-4">E-posta</th>
                            <th class="py-3.5 px-4">Paylaştığı Kod</th>
                            <th class="py-3.5 px-4">Rolü</th>
                            <th class="py-3.5 px-4 text-right">İşlemler / Gözlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-purple-50 text-sm font-medium">
                        <?php foreach ($users as $u): ?>
                            <?php 
                                $uSnippets = $userSnippetsMap[$u['id']] ?? []; 
                                $snippetJson = htmlspecialchars(json_encode($uSnippets), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-purple-50/60 transition">
                                <td class="py-4 px-4 font-bold text-purple-900">#<?php echo $u['id']; ?></td>
                                <td class="py-4 px-4 font-extrabold text-purple-950"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="py-4 px-4 text-purple-700"><?php echo htmlspecialchars($u['email'] ?? 'Belirtilmedi'); ?></td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block bg-purple-100/80 text-purple-800 px-3 py-1 rounded-full text-xs font-extrabold border border-purple-200">
                                            <?php echo $u['snippet_count']; ?> Kod
                                        </span>
                                        <?php if (!empty($uSnippets)): ?>
                                            <button type="button" 
                                                    onclick="openSnippetsModal('<?php echo htmlspecialchars($u['username']); ?>', <?php echo $snippetJson; ?>)"
                                                    class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-2.5 py-1 rounded-lg border border-indigo-200 transition">
                                                👁️ Kodları İncele
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="change_role" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="role" onchange="this.form.submit()" class="bg-white border border-purple-200 rounded-xl px-3 py-1.5 text-xs font-bold text-purple-800 outline-none focus:border-purple-500 cursor-pointer shadow-sm">
                                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Kullanıcı (User)</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Yönetici (Admin)</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <form action="" method="POST" onsubmit="return confirm('Bu kullanıcıyı ve tüm kodlarını silmek istediğinize emin misiniz?');" class="inline-block">
                                            <input type="hidden" name="delete_user" value="1">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold px-3.5 py-1.5 rounded-xl text-xs transition shadow-sm cursor-pointer">
                                                🗑️ Sil
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs font-bold text-purple-400 italic bg-purple-50 px-3 py-1.5 rounded-xl border border-purple-100">(Siz)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- KULLANICI KODLARI GÖZLEM MODAL'I (POP-UP) -->
    <div id="snippets-modal" class="fixed inset-0 bg-purple-950/40 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="glass-card w-full max-w-2xl rounded-3xl p-6 sm:p-8 space-y-5 relative max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-purple-100 pb-3">
                <h3 class="text-lg font-black text-purple-950 flex items-center gap-2">
                    <span>🔍</span> <span id="modal-username">...</span> İsimli Kullanıcının Kodları
                </h3>
                <button type="button" onclick="closeSnippetsModal()" class="w-8 h-8 rounded-full bg-purple-100 text-purple-800 font-bold text-sm hover:bg-purple-200 transition">✕</button>
            </div>

            <div id="modal-content" class="space-y-3">
                <!-- Dinamik kod listesi buraya yüklenecek -->
            </div>

            <div class="pt-2 text-right">
                <button type="button" onclick="closeSnippetsModal()" class="bg-purple-600 text-white font-bold text-xs px-5 py-2.5 rounded-full hover:bg-purple-700 transition">Kapat</button>
            </div>
        </div>
    </div>

<script>
function openSnippetsModal(username, snippets) {
    document.getElementById('modal-username').innerText = username;
    const container = document.getElementById('modal-content');
    container.innerHTML = '';

    if (!snippets || snippets.length === 0) {
        container.innerHTML = '<p class="text-sm text-purple-600 italic">Bu kullanıcının henüz paylaştığı kod bulunmuyor.</p>';
    } else {
        snippets.forEach(item => {
            const card = document.createElement('div');
            card.className = 'p-4 rounded-2xl bg-white/90 border border-purple-200/80 shadow-sm flex items-center justify-between';
            card.innerHTML = `
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700 font-extrabold text-[10px] mb-1">
                        ${item.category}
                    </span>
                    <h4 class="font-bold text-purple-950 text-sm">${item.title}</h4>
                    <p class="text-[11px] text-purple-500 font-medium mt-0.5">Tarih: ${item.created_at}</p>
                </div>
                <a href="index.php?search=${encodeURIComponent(item.title)}&show_pool=1#code-pool-section" 
                   target="_blank" 
                   class="text-xs bg-purple-600 text-white font-bold px-3 py-1.5 rounded-xl hover:bg-purple-700 transition shadow-sm">
                    Görüntüle ↗
                </a>
            `;
            container.appendChild(card);
        });
    }

    document.getElementById('snippets-modal').classList.remove('hidden');
}

function closeSnippetsModal() {
    document.getElementById('snippets-modal').classList.add('hidden');
}
</script>

</body>
</html>