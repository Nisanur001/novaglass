<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyalarını yükleyelim (Composer veya manuel klasör yapısına uygun)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

/**
 * Kullanıcıya E-Posta Gönderen Fonksiyon
 */
function sendWelcomeEmail($toEmail, $username) {
    $mail = new PHPMailer(true);

    try {
        // --- SMTP AYARLARI ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             // SMTP sunucusu (Gmail, Yandex vs.)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ORNEK_MAIL@gmail.com';       // Gönderen e-posta adresiniz
        $mail->Password   = 'BURAYA_UYGULAMA_SIFRESI';    // Gmail Uygulama Şifresi (16 karakterli)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- ALICI VE GÖNDEREN ---
        $mail->setFrom('ORNEK_MAIL@gmail.com', 'NovaGlass Platform');
        $mail->addAddress($toEmail, $username);

        // --- İÇERİK ---
        $mail->isHTML(true);
        $mail->Subject = '✦ NovaGlass Ailesine Hoş Geldiniz!';
        
        // Şık HTML E-posta Şablonu
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; background-color: #f7f1ff; padding: 30px; color: #43334f;'>
                <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 20px; border: 1px solid #e9d5ff; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                    <h2 style='color: #9b5de5; text-align: center; margin-bottom: 10px;'>✦ NovaGlass'a Hoş Geldin!</h2>
                    <p style='font-size: 14px; line-height: 1.6; color: #555;'>Merhaba <strong>{$username}</strong>,</p>
                    <p style='font-size: 14px; line-height: 1.6; color: #555;'>NovaGlass kod paylaşım topluluğuna başarıyla katıldın. Artık kendi kod parçacıklarını paylaşabilir ve yapay zeka (NovaAI) ile kodlarını inceleyebilirsin!</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='http://localhost/index.php' style='background: linear-gradient(110deg, #a855f7, #ec4899); color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 13px;'>Hemen Kod Paylaş ✨</a>
                    </div>
                    <hr style='border: none; border-top: 1px solid #f0e6ff; margin: 20px 0;'>
                    <p style='font-size: 11px; text-align: center; color: #aaa;'>Bu e-posta NovaGlass otomatik bildirim sistemi tarafından gönderilmiştir.</p>
                </div>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Sunum anında SMTP hatası olsa bile projenin akışını bozmamak için hata kaydı tutulur
        error_log("Mail Gönderim Hatası: {$mail->ErrorInfo}");
        return false;
    }
}