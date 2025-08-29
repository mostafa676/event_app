<?php

// namespace App\Helpers;

// use App\Models\Notification;
// use Kreait\Firebase\Factory;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
// use Illuminate\Support\Facades\Log;

// class NotificationHelper
// {
//     /**
//      * إرسال إشعار عبر Firebase (مع حفظه في DB).
//      *
//      * @param \App\Models\User $user
//      * @param string $type
//      * @param string $title
//      * @param string $body
//      * @param array $data
//      * @return \App\Models\Notification
//      */
//     public static function sendFCM($user, string $type, string $title, string $body, array $data = [])
//     {
//         // البيانات المحفوظة في DB
//         $dbData = array_merge([
//             'title' => $title,
//             'body'  => $body,
//         ], $data);

//         $notificationData = [
//             'user_id' => $user->id,
//             'type' => $type,
//             'title' => $title,
//             'data' => $dbData,
//             'is_sent_to_firebase' => false,
//             'notifiable_id' => $data['notifiable_id'] ?? null,
//             'notifiable_type' => $data['notifiable_type'] ?? null,
//         ];

//         $notification = Notification::create($notificationData);

//         // إذا المستخدم ما عنده fcm_token => بس خزّن الإشعار
//         if (empty($user->fcm_token)) {
//             return $notification->fresh();
//         }

//         try {
//             self::sendFCMMessage($user->fcm_token, $title, $body, $type, $data, $notification->id);
//             $notification->update(['is_sent_to_firebase' => true]);
//         } catch (\Throwable $e) {
//             Log::error('FCM send error', [
//                 'user_id' => $user->id,
//                 'message' => $e->getMessage(),
//             ]);
//         }

//         return $notification->fresh();
//     }

//     /**
//      * إرسال رسالة فعلية عبر Firebase.
//      */
//     private static function sendFCMMessage(string $token, string $title, string $body, string $type, array $data, int $notificationId): void
//     {
//         $serviceAccount = storage_path('app/firebase-credentials.json');

//         $factory = (new Factory)->withServiceAccount($serviceAccount);
//         $messaging = $factory->createMessaging();

//         $message = CloudMessage::withTarget('token', $token)
//             ->withNotification(FirebaseNotification::create($title, $body))
//             ->withData(array_merge($data, [
//                 'type' => $type,
//                 'title' => $title,
//                 'body' => $body,
//                 'notification_id' => (string)$notificationId,
//             ]));

//         $messaging->send($message);
//     }
// }

namespace App\Helpers;

use App\Models\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * إرسال إشعار عبر Firebase (مع حفظه في DB).
     *
     * @param \App\Models\User $user
     * @param string $type
     * @param string $title
     * @param string $body
     * @param array $data
     * @return \App\Models\Notification
     */
    public static function sendFCM($user, string $type, string $title, string $body, array $data = [])
    {
        // تجهيز بيانات التخزين
        $dbData = array_merge([
            'title' => $title,
            'body'  => $body,
        ], $data);

        $notificationData = [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'data' => $dbData,
            'is_sent_to_firebase' => false,
            'notifiable_id' => $data['notifiable_id'] ?? null,
            'notifiable_type' => $data['notifiable_type'] ?? null,
        ];

        $notification = Notification::create($notificationData);

        // إذا المستخدم ما عنده fcm_token => فقط خزّن الإشعار
        if (empty($user->fcm_token)) {
            return $notification->fresh();
        }

        try {
            self::sendFCMMessage(
                $user->fcm_token,
                $title,
                $body,
                $type,
                $data,
                $notification->id // UUID (string)
            );

            $notification->update(['is_sent_to_firebase' => true]);
        } catch (\Throwable $e) {
            Log::error('FCM send error', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $notification->fresh();
    }

    /**
     * إرسال رسالة فعلية عبر Firebase.
     */
    private static function sendFCMMessage(
        string $token,
        string $title,
        string $body,
        string $type,
        array $data,
        string $notificationId // ✅ صار string بدل int
    ): void {
        $serviceAccount = storage_path('app/firebase-credentials.json');

        $factory = (new Factory)->withServiceAccount($serviceAccount);
        $messaging = $factory->createMessaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData(array_merge($data, [
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'notification_id' => $notificationId, // UUID كـ string
            ]));

        $messaging->send($message);
    }
}
