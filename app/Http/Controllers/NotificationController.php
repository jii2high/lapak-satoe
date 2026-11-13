<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi (`id`) atau semua notifikasi jadi sudah dibaca.
     */
    public function read(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['nullable', 'uuid']]);

        $query = $request->user()->unreadNotifications();

        if (isset($data['id'])) {
            $query->whereKey($data['id']);
        }

        $query->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
