<?php

namespace App\Listeners;

use App\Events\PaymentApproved;
use App\Events\PaymentReceived;
use App\Events\PaymentRejected;
use App\Models\TelegramAdminChat;
use App\Jobs\Telegram\SendTelegramMessageJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTelegramPaymentNotification implements ShouldQueue
{
    public function handlePaymentReceived(PaymentReceived $event): void
    {
        $admins = TelegramAdminChat::active()->where('notify_payments', true)->get();

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    💳 <b>PAYMENT RECEIVED</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👤 {$event->order->user->name}\n"
            . "💰 \${$event->order->total}\n"
            . "💳 {$event->order->payment_method}\n"
            . "📦 Order #{$event->order->id}\n";

        $buttons = [
            [
                ['text' => '✅ Approve', 'callback_data' => "admin_approve_payment_{$event->order->id}"],
                ['text' => '❌ Reject', 'callback_data' => "admin_reject_payment_{$event->order->id}"],
            ],
        ];

        foreach ($admins as $admin) {
            SendTelegramMessageJob::dispatch($admin->chat_id, $text, $buttons);
        }

        if ($admins->isEmpty() && config('telegram.admin_chat_id')) {
            SendTelegramMessageJob::dispatch(config('telegram.admin_chat_id'), $text, $buttons);
        }
    }

    public function handlePaymentApproved(PaymentApproved $event): void
    {
        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ✅ <b>PAYMENT APPROVED</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "Order #{$event->order->id}\n"
            . "Amount: \${$event->order->total}\n\n"
            . "Customer has been notified.";

        $admins = TelegramAdminChat::active()->where('notify_payments', true)->get();
        foreach ($admins as $admin) {
            SendTelegramMessageJob::dispatch($admin->chat_id, $text);
        }
    }

    public function handlePaymentRejected(PaymentRejected $event): void
    {
        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ❌ <b>PAYMENT REJECTED</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "Order #{$event->order->id}\n"
            . "Amount: \${$event->order->total}\n\n"
            . "Customer has been notified.";

        $admins = TelegramAdminChat::active()->where('notify_payments', true)->get();
        foreach ($admins as $admin) {
            SendTelegramMessageJob::dispatch($admin->chat_id, $text);
        }
    }
}
