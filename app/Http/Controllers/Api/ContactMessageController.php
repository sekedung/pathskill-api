<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * POST /api/contact
     * Endpoint publik (tanpa auth) untuk form kontak di landing page.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::create($validated);

        // catatan: kalau nanti mau ada notifikasi email otomatis ke admin,
        // tambahkan Mail::to(...)->send(new NewContactMessage($validated)) di sini
        // (butuh setup SMTP di .env dulu, mis. Mailtrap untuk testing).

        return response()->json([
            'message' => 'Pesan berhasil dikirim. Kami akan segera menghubungi Anda.',
        ], 201);
    }
}
