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
    protected function getCode(string $secret, int $timestamp): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $time, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $truncated = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string)$truncated, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode Base32 string
     */
    protected function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }

        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) {
                return '';
            }

            for ($j = 0; $j < 8; $j++) {
                if (isset($secret[$i + $j])) {
                    $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
                }
            }

            $eightBits = str_split($x, 8);

            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }
}
