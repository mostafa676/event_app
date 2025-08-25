<?php

namespace App\Helpers;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NotificationHelper
{
    /**
     * إرسال إشعار (يحفظ في DB ثم يرسل FCM إن وُجد token)
     *
     * @param \App\Models\User $user
     * @param string $type
     * @param string $title
     * @param string $body
     * @param array $data
     * @return \App\Models\Notification
     */
    public static function sendFCM($user, $type, $title, $body, array $data = [])
    {
        // إعداد البيانات للحفظ في DB
        $dbData = array_merge([
            'title' => $title,
            'body' => $body,
        ], $data);

        // إنشاء الإشعار
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'notifiable_id' => $data['notifiable_id'] ?? null,
            'notifiable_type' => $data['notifiable_type'] ?? null, // مثال: App\Models\Reservation
            'title' => $title,
            'data' => $dbData, // ⚠️ لا تستخدم json_encode – $casts يفعل ذلك تلقائيًا
            'is_sent_to_firebase' => false,
        ]);

        // إذا لم يكن هناك FCM token، لا نرسل
        if (empty($user->fcm_token)) {
            return $notification;
        }

        $serverKey = config('services.fcm.server_key');
        if (!$serverKey) {
            \Log::warning('FCM server key not set in config.');
            return $notification;
        }

        // حمولة FCM
        $payload = [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => array_merge($data, [
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'notification_id' => $notification->id,
            ]),
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload); // ⚠️ إزالة المسافة

            if ($response->successful()) {
                $notification->update(['is_sent_to_firebase' => true]);
            } else {
                \Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('FCM send error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);
        }

        return $notification->fresh(); // إرجاع أحدث حالة (بما في ذلك is_sent_to_firebase)
    }
}