<?php

namespace App\Support;

/**
 * Detects phone numbers, emails and social handles inside message bodies and
 * masks them (blueprint §10). Users routinely try to bypass the privacy system
 * this way, so the enforcement happens server-side before the message persists.
 */
class ContactInfoDetector
{
    private const MASK = '•••';

    /**
     * @return array{body:string, masked:bool}
     */
    public static function mask(string $body): array
    {
        $original = $body;

        $patterns = [
            // Emails
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            // Phone numbers: 7+ digits, optionally spaced/dashed, with optional +.
            '/(?:\+?\d[\d\s\-]{6,}\d)/',
            // Social handles like @username
            '/(?<!\w)@[A-Z0-9_]{3,}/i',
            // URLs
            '/\b(?:https?:\/\/|www\.)\S+/i',
        ];

        foreach ($patterns as $pattern) {
            $body = preg_replace($pattern, self::MASK, $body) ?? $body;
        }

        return [
            'body' => $body,
            'masked' => $body !== $original,
        ];
    }
}
