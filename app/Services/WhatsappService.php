<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public static function send($targetPhone, $messageContent)
    {
        // Pastikan IP dan Port API Node.js Anda sudah benar
        $url = 'http://192.168.10.40:3000/send/message';
        $phone = self::formatPhone($targetPhone);

        try {
            // Tambahkan timeout 3 detik sebagai jaring pengaman
            $response = Http::timeout(3)->post($url, [
                'phone' => $phone,
                'message' => $messageContent
            ]);

            if ($response->successful()) {
                return true;
            } else {
                Log::error("WA gagal terkirim. Status: " . $response->status() . " Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Koneksi WA error: " . $e->getMessage());
            return false;
        }
    }

    private static function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 2) === '08') {
            return ('62' . substr($phone, 1));
        }

        return $phone;
    }
}
