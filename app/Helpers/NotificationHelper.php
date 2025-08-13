<?php
namespace App\Helpers;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NotificationHelper
{
    /**
     * إرسال إشعار (يحفظ بـ DB ثم يرسل FCM إن وُجد token)
     *
     * $user : نموذج User
     * $type : string (مثال 'task_assigned')
     * $title, $body : نص الإشعار
     * $data : array إضافي (مثل reservation_id, assignment_id, ...)
     */
    public static function sendFCM($user, $type, $title, $body, array $data = [])
    {
        // حفظ في الـ DB
        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'notifiable_id' => $data['notifiable_id'] ?? null,
            'notifiable_type' => $data['notifiable_type'] ?? null,
            'data' => json_encode(array_merge(['title'=>$title,'body'=>$body], $data)),
        ]);

        // إذا ما عنده توكن لا نرسل FCM (بس سجلناه)
        if (empty($user->fcm_token)) {
            return $notification;
        }

        $serverKey = config('services.fcm.server_key');

        $payload = [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            // إذا احتجت تحتفظ بالـ response لغرض debugging
            // \Log::info('FCM response: '.$response->body());

        } catch (\Exception $e) {
            \Log::error('FCM send error: '.$e->getMessage());
        }

        return $notification;
    }
}
