<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleService;

class GoogleSocialiteController extends Controller
{
    public function __construct(
        protected GoogleService $googleService
    ) {}

    public function redirect()
    {
        return $this->googleService->redirect();
    }

    public function callback()
    {
        $result = $this->googleService->handleCallback();

        if (!$result['success']) {
            return redirect(config('app.frontend_url') . '/login?error=' . urlencode($result['message']));
        }

        return redirect(config('app.frontend_url') . '/?auth_token=' . $result['token']);
    }
}
