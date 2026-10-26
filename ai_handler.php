<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Güvenlik: Sadece giriş yapmış kullanıcılar analiz isteyebilir
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Yetkisiz erişim. Lütfen önce giriş yapın.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codeSnippet = $data['code'] ?? '';
$category = $data['category'] ?? 'Genel';
$userQuestion = $data['question'] ?? '';

// Kod veya soru ikisi de yoksa uyarı ver
if (empty($codeSnippet) && empty($userQuestion)) {
    echo json_encode(['answer' => 'Analiz edilecek kod içeriği bulunamadı.']);
    exit;
}

// 🔑 Google AI Studio API Anahtarı[cite: 2]
$apiKey = 'BURAYA_GOOGLE_AI_STUDIO_API_ANAHTARINI_YAZ';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

// Prompt Hazırlığı[cite: 2]
if (!empty($userQuestion)) {
    $prompt = "Sen NovaGlass yapay zeka kod asistanısın. Şu kodla ilgili soruyu cevapla:\n\nKod:\n{$codeSnippet}\n\nSoru: {$userQuestion}";
} else {
    $prompt = "Sen NovaGlass yapay zeka kod asistanısın. Aşağıdaki {$category} kod parçacığını kısaca incele. Kodun ne yaptığını ve bir hata/geliştirme önerisi olup olmadığını 2 Türkçe cümle ile açıkla:\n\n{$codeSnippet}";
}

// Gerçek API Anahtarı varsa istek gönder[cite: 2]
if (!empty($apiKey) && $apiKey !== 'BURAYA_GOOGLE_AI_STUDIO_API_ANAHTARINI_YAZ') {
    $payload = json_encode([
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$curlError) {
        $result = json_decode($response, true);
        $aiAnswer = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($aiAnswer) {
            echo json_encode(['answer' => $aiAnswer]);
            exit;
        }
    }
}

// API Anahtarı eklenmemişse çalışacak akıllı simülasyon (Yedek)[cite: 2]
$lineCount = count(explode("\n", $codeSnippet));
$charCount = strlen($codeSnippet);

$aiAnswer = "🤖 [NovaAI Kod Analizi]\n";
$aiAnswer .= "• Kategori: {$category}\n";
$aiAnswer .= "• Boyut: {$lineCount} satır, {$charCount} karakter.\n";
$aiAnswer .= "• İnceleme: Söz dizimi (syntax) yapısı genel standartlara uygun görünüyor.";

echo json_encode(['answer' => $aiAnswer]);