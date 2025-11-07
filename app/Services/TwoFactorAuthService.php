<?php

namespace App\Services;

class TwoFactorAuthService
{
    /**
     * Generate a random secret key
     */
    public function generateSecretKey(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
        $secret = '';

        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * Generate QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(string $company, string $email, string $secret): string
    {
        $urlEncoded = urlencode("otpauth://totp/{$company}:{$email}?secret={$secret}&issuer={$company}");
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$urlEncoded}";
    }

    /**
     * Verify TOTP code
     */
    public function verifyKey(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = floor(time() / 30);

        // Check current timestamp and a window before/after
        for ($i = -$window; $i <= $window; $i++) {
            if ($this->getCode($secret, $timestamp + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current TOTP code
     */
    public function getCode(string $secret, int $timestamp): string
    {
        $key = $this->base32Decode($secret);

        // Pack timestamp as 64-bit big-endian integer
        $time = pack('J', $timestamp);

        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $time, $key, true);

        // Dynamic truncation as per RFC 4226
        $offset = ord($hash[19]) & 0xf;

        $truncated = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string)$truncated, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode Base32 string (RFC 4648)
     */
    protected function base32Decode(string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $secret = strtoupper($secret);
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }

        // Remove padding
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        // Process in chunks of 8 characters
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';

            // Convert 8 base32 chars to 40 bits
            for ($j = 0; $j < 8; $j++) {
                if (!isset($secret[$i + $j])) {
                    continue;
                }

                $char = $secret[$i + $j];
                if (!isset($base32charsFlipped[$char])) {
                    return '';
                }

                $x .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
            }

            // Convert 40 bits to 5 bytes
            $eightBits = str_split($x, 8);

            foreach ($eightBits as $bits) {
                if (strlen($bits) === 8) {
                    $binaryString .= chr(bindec($bits));
                }
            }
        }

        return $binaryString;
    }
}
