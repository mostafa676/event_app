<?php

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)->withServiceAccount(__DIR__.'/storage/app/firebase-credentials.json');

$messaging = $factory->createMessaging();

// ضع هنا توكن جهاز موبايل حقيقي (FCM registration token)
$deviceToken = 'dE4UD-XuRdKNB8HG0Gi9Cu:APA91bFlh-amyueliaFF-qF-1fObAtD_V84WltVL_X_-ysRhsizMpyhnGG7PCWV4HtYIXlbLsl2HTTVJGR-lbXL9jD5nT76OY2hVaEZW_US38Ue17Xl189Q';

$message = [
    'token' => $deviceToken,
    'notification' => [
        'title' => '🚀 اختبار Firebase',
        'body' => 'هاي أول رسالة تجريبية من PHP مباشرة'
    ],
    'data' => [
        'customKey' => 'customValue'
    ],
];

try {
    $messaging->send($message);
    echo "✅ تم إرسال الإشعار بنجاح\n";
} catch (\Throwable $e) {
    echo "❌ خطأ: ".$e->getMessage()."\n";
}
