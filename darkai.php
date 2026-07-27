<?php
// ------------------- কনফিগারেশন -------------------
$botToken = "YOUR_BOT_TOKEN";
$botUsername = "YourBotUsername";   // ★ আপনার বটের ইউজারনেম দিন (যেমন: DarkAIBot)
$apiURL = "https://darktoolshub.site/darkai/Api.php";
$channelLink = "https://t.me/xboomva";
$supportLink = "https://t.me/xboomva";
// --------------------------------------------------

// টেলিগ্রাম থেকে ইনকামিং ডাটা পড়ি
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$message = isset($update['message']) ? $update['message'] : null;
$callbackQuery = isset($update['callback_query']) ? $update['callback_query'] : null;

// ------------------- মেসেজ হ্যান্ডেল -------------------
if ($message) {
    $chatId = $message['chat']['id'];
    $text = isset($message['text']) ? $message['text'] : '';

    if ($text === "/start") {
        sendStartMessage($chatId);
    } else {
        $reply = getAIResponse($text);
        sendMessage($chatId, $reply);
    }
}

// ------------------- Callback Query হ্যান্ডেল -------------------
if ($callbackQuery) {
    $callbackId = $callbackQuery['id'];
    $chatId = $callbackQuery['message']['chat']['id'];
    $data = $callbackQuery['data'];

    if ($data === "add_group") {
        answerCallbackQuery($callbackId, "⏳ গ্রুপ সিলেক্ট করুন...");

        $addGroupLink = "https://t.me/{$botUsername}?startgroup=addbot";

        $text = "👇 নিচের বাটনে ক্লিক করুন – তারপর আপনার যেকোনো গ্রুপ সিলেক্ট করে বটকে অ্যাড করুন।\n\n";
        $text .= "⚠️ *বটকে গ্রুপের এডমিন দিতে ভুলবেন না!*";

        // ★ এখানেও রঙ যোগ করা হলো (সবুজ)
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '➕ গ্রুপে বট যোগ করুন', 'url' => $addGroupLink, 'style' => 'success']
                ]
            ]
        ];
        $keyboardJson = json_encode($keyboard);
        sendMessage($chatId, $text, $keyboardJson);
    }

    answerCallbackQuery($callbackId);
}

// ------------------- ফাংশনসমূহ -------------------

// স্টার্ট মেসেজ + ৩টি রঙিন বাটন
function sendStartMessage($chatId) {
    global $channelLink, $supportLink;

    $text = "👋 স্বাগতম! আমি DARK AI বট।\n\n";
    $text .= "নিচের বাটনগুলোর মাধ্যমে আমাদের সাথে সংযুক্ত থাকুন:\n";
    $text .= "✅ চ্যানেল জয়েন করুন\n";
    $text .= "🆘 সাপোর্ট গ্রুপে যোগ দিন\n";
    $text .= "➕ আপনার গ্রুপে বটকে এড করুন";

    // ★ ৩টি বাটনে ৩টি ভিন্ন রঙ (style) দেওয়া হলো
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '➕ ADD YOUR GROUP', 'callback_data' => 'add_group', 'style' => 'primary']  // নীল
            ],
            [
                ['text' => '📢 JOIN CHANNEL', 'url' => $channelLink, 'style' => 'success'],          // সবুজ
                ['text' => '🆘 SUPPORT', 'url' => $supportLink, 'style' => 'danger']                 // লাল
            ]
        ]
    ];

    sendMessage($chatId, $text, json_encode($keyboard));
}

// মেসেজ পাঠানোর ফাংশন
function sendMessage($chatId, $text, $keyboard = null) {
    global $botToken;

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $postData = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    if ($keyboard) {
        $postData['reply_markup'] = $keyboard;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// Callback উত্তর
function answerCallbackQuery($callbackId, $text = null) {
    global $botToken;

    $url = "https://api.telegram.org/bot{$botToken}/answerCallbackQuery";

    $postData = ['callback_query_id' => $callbackId];
    if ($text) {
        $postData['text'] = $text;
        $postData['show_alert'] = false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// AI API কল
function getAIResponse($userMessage) {
    global $apiURL;

    $url = $apiURL . "?ask=" . urlencode($userMessage);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return "❌ দুঃখিত, API-তে সংযোগ করতে সমস্যা হচ্ছে।";
    }

    $data = json_decode($response, true);

    if (isset($data['status']) && $data['status'] === true && isset($data['reply'])) {
        return $data['reply'];
    } else {
        return "❌ দুঃখিত, কোনো রিপ্লাই পাইনি। আবার চেষ্টা করুন।";
    }
}
?>
