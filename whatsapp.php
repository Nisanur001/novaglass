<?php
/**
 * NovaGlass WhatsApp Bildirim Modülü
 */

function sendWhatsAppMessage($phone, $message) {
    // UltraMsg veya benzeri bir servisten alacağın bilgiler:
    $instanceId = 'instance_id_buraya'; // Kendi Instance ID'ni buraya yazacaksın
    $token = 'token_buraya';             // Kendi API Token'ını buraya yazacaksın
    
    // Telefon numarasını uluslararası formata getir (+90...)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $phone = '90' . $phone; 
    }

    $url = "https://api.ultramsg.com/{$instanceId}/messages/chat";

    $data = array(
        'token' => $token,
        'to' => '+' . $phone,
        'body' => $message
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    return $result !== FALSE;
}
?>