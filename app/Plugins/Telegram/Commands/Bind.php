<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class Bind extends Telegram {
    public $command = '/bind';
    public $description = 'Bind Telegram account to website';

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        if (!isset($message->args[0])) {
            abort(500, 'Invalid parameter, please send with subscribe URL');
        }
        $subscribeUrl = $message->args[0];
        $subscribeUrl = parse_url($subscribeUrl);
        parse_str($subscribeUrl['query'], $query);
        $token = $query['token'];
        if (!$token) {
            abort(500, 'Subscribe URL is invalid');
        }
        $user = User::where('token', $token)->first();
        if (!$user) {
            abort(500, 'User does not exist');
        }
        if ($user->telegram_id) {
            abort(500, 'This account is already bound to a Telegram account');
        }
        $user->telegram_id = $message->chat_id;
        if (!$user->save()) {
            abort(500, 'Setting failed');
        }
        $telegramService = $this->telegramService;
        $telegramService->sendMessage($message->chat_id, 'Bind successful');
    }
}
