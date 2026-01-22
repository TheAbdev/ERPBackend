<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notifications = \App\Core\Models\Notification::with('notifiable')->get();

echo "=== ملخص الإشعارات ===\n\n";

foreach ($notifications as $n) {
    echo "ID: {$n->id}\n";
    echo "النوع: {$n->type}\n";
    echo "المستخدم المستلم: {$n->notifiable_id} ({$n->notifiable->name} - {$n->notifiable->email})\n";
    echo "البيانات: " . json_encode($n->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "الحالة: " . ($n->read_at ? 'مقروء' : 'غير مقروء') . "\n";
    echo "---\n\n";
}

echo "\n=== ملخص حسب المستخدم ===\n\n";

$byUser = $notifications->groupBy('notifiable_id');
foreach ($byUser as $userId => $userNotifications) {
    $user = $userNotifications->first()->notifiable;
    echo "المستخدم: {$user->name} (ID: {$userId})\n";
    echo "عدد الإشعارات: {$userNotifications->count()}\n";
    echo "غير مقروء: " . $userNotifications->whereNull('read_at')->count() . "\n";
    echo "---\n\n";
}







