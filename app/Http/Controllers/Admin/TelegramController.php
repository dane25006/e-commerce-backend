<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramAdminChat;
use App\Models\TelegramFailedMessage;
use App\Models\TelegramUpdateLog;
use App\Models\TelegramMessageLog;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TelegramController extends Controller
{
    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramLogService $logger
    ) {}

    public function index()
    {
        $stats = $this->logger->getStats();
        $webhookInfo = $this->bot->getWebhookInfo();

        return view('admin.telegram.index', array_merge($stats, [
            'status'          => $webhookInfo ? 'running' : 'error',
            'lastChecked'     => now()->diffForHumans(),
            'webhookActive'   => ! empty($webhookInfo['result']['url'] ?? ''),
            'pendingUpdates'  => $webhookInfo['result']['pending_update_count'] ?? 0,
            'queueSize'       => \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'telegram')->count(),
            'failedCount'     => TelegramFailedMessage::where('is_resolved', false)->count(),
            'recentLogs'      => TelegramUpdateLog::latest()->take(10)->get(),
        ]));
    }

    public function logs(Request $request)
    {
        $query = TelegramUpdateLog::query();

        if ($request->chat_id) {
            $query->where('chat_id', $request->chat_id);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->status === 'processed') {
            $query->where('is_processed', true);
        } elseif ($request->status === 'failed') {
            $query->whereNotNull('error_message');
        } elseif ($request->status === 'pending') {
            $query->where('is_processed', false)->whereNull('error_message');
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(20);

        return view('admin.telegram.logs', compact('logs'));
    }

    public function logDetail(int $id)
    {
        $log = TelegramUpdateLog::findOrFail($id);
        return view('admin.telegram.log-detail', compact('log'));
    }

    public function failed()
    {
        $failed = TelegramFailedMessage::where('is_resolved', false)
            ->latest()
            ->paginate(20);

        return view('admin.telegram.failed', compact('failed'));
    }

    public function retryFailed(int $id)
    {
        $message = TelegramFailedMessage::findOrFail($id);
        $message->retry();

        return back()->with('success', 'Message queued for retry.');
    }

    public function retryAllFailed()
    {
        $messages = TelegramFailedMessage::where('is_resolved', false)->get();

        $count = 0;
        foreach ($messages as $message) {
            try {
                $message->retry();
                $count++;
            } catch (\Exception $e) {
                // Skip
            }
        }

        return back()->with('success', "{$count} messages queued for retry.");
    }

    public function settings()
    {
        $adminChats = TelegramAdminChat::all();
        $webhookInfo = $this->bot->getWebhookInfo();
        $failedCount = TelegramFailedMessage::where('is_resolved', false)->count();

        return view('admin.telegram.settings', compact('adminChats', 'webhookInfo', 'failedCount'));
    }

    public function updateSettings(Request $request)
    {
        if ($request->action === 'check_webhook') {
            $info = $this->bot->getWebhookInfo();
            if ($info && ($info['ok'] ?? false)) {
                return response()->json([
                    'ok' => true,
                    'message' => "Webhook: {$info['result']['url']}<br>Pending: {$info['result']['pending_update_count']}",
                ]);
            }
            return response()->json(['ok' => false, 'message' => 'Failed to get webhook info']);
        }

        if ($request->action === 'set_webhook') {
            $url = config('telegram.webhook_url');
            if ($this->bot->setWebhook($url)) {
                return response()->json(['ok' => true, 'message' => "Webhook set to: {$url}"]);
            }
            return response()->json(['ok' => false, 'message' => 'Failed to set webhook']);
        }

        return back()->with('success', 'Settings saved.');
    }

    public function sendTest(Request $request)
    {
        $chatId = $request->chat_id ?: config('telegram.admin_chat_id');

        if (! $chatId) {
            return response()->json(['ok' => false, 'message' => 'No chat ID configured']);
        }

        $result = $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    🧪 <b>TEST MESSAGE</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "If you can see this, the bot is working correctly! ✅\n\n"
            . "Server time: " . now()->format('M d, Y H:i:s')
        );

        if ($result) {
            return response()->json(['ok' => true, 'message' => 'Test message sent! ✅']);
        }

        return response()->json(['ok' => false, 'message' => 'Failed to send message. Check bot token.']);
    }

    public function chats()
    {
        $chats = TelegramAdminChat::all();
        return view('admin.telegram.chats', compact('chats'));
    }

    public function storeChat(Request $request)
    {
        $data = $request->validate([
            'chat_id' => 'required|string|unique:telegram_admin_chats,chat_id',
            'name'    => 'nullable|string|max:255',
            'role'    => 'required|in:super_admin,admin,moderator',
        ]);

        TelegramAdminChat::create($data);

        return back()->with('success', 'Admin chat added.');
    }

    public function destroyChat(int $id)
    {
        TelegramAdminChat::findOrFail($id)->delete();
        return back()->with('success', 'Admin chat removed.');
    }

    public function toggleChat(int $id)
    {
        $chat = TelegramAdminChat::findOrFail($id);
        $chat->update(['is_active' => ! $chat->is_active]);

        return back()->with('success', 'Admin chat ' . ($chat->is_active ? 'enabled' : 'disabled') . '.');
    }
}
