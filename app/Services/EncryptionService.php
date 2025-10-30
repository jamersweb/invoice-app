<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class EncryptionService
{
    /**
     * Encrypt sensitive data for storage (PDPL compliance)
     */
    public static function encrypt($value): string
    {
        if (empty($value)) {
            return $value;
        }
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt sensitive data
     */
    public static function decrypt($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            \Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Encrypt JSON data
     */
    public static function encryptJson(array $data): string
    {
        return self::encrypt(json_encode($data));
    }

    /**
     * Decrypt JSON data
     */
    public static function decryptJson(string $encrypted): ?array
    {
        $decrypted = self::decrypt($encrypted);
        if (!$decrypted) {
            return null;
        }
        return json_decode($decrypted, true);
    }

    /**
     * Check if encryption is enabled
     */
    public static function isEncryptionEnabled(): bool
    {
        return Config::get('app.encrypt_sensitive_data', false);
    }
}

