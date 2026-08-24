<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class WebhookVerifier {
    private const MAX_CLOCK_SKEW = 600;

    public static function verify(string $provider, \WP_REST_Request $request, string $raw, array $body): array {
        if ($provider === 'resend') { return self::verifyResend($request, $raw); }
        if ($provider === 'sendgrid') { return self::verifySendGrid($request, $raw); }
        if ($provider === 'mailgun') { return self::verifyMailgun($body); }
        return ['ok' => false, 'message' => 'Unsupported provider.'];
    }

    private static function verifyResend(\WP_REST_Request $request, string $raw): array {
        $secret = (string) Settings::get('mail_webhook_resend_secret', '');
        $id = trim((string) $request->get_header('svix-id'));
        $timestamp = trim((string) $request->get_header('svix-timestamp'));
        $signatureHeader = trim((string) $request->get_header('svix-signature'));
        if ($secret === '' || $id === '' || $timestamp === '' || $signatureHeader === '') {
            return ['ok' => false, 'message' => 'Resend signature configuration or headers missing.'];
        }
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW) {
            return ['ok' => false, 'message' => 'Resend webhook timestamp outside the allowed window.'];
        }
        $encodedSecret = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($encodedSecret, true);
        if ($key === false) { return ['ok' => false, 'message' => 'Invalid Resend signing secret.']; }
        $expected = base64_encode(hash_hmac('sha256', $id.'.'.$timestamp.'.'.$raw, $key, true));
        $valid = false;
        foreach (preg_split('/\s+/', $signatureHeader) ?: [] as $part) {
            $bits = explode(',', trim($part), 2);
            if (count($bits) === 2 && $bits[0] === 'v1' && hash_equals($expected, $bits[1])) { $valid = true; break; }
        }
        if (!$valid) { return ['ok' => false, 'message' => 'Invalid Resend webhook signature.']; }
        $replayKey = 'lacvo_wtp_hook_resend_'.hash('sha256', $id);
        if (get_transient($replayKey)) { return ['ok' => false, 'message' => 'Duplicate Resend webhook.']; }
        set_transient($replayKey, 1, self::MAX_CLOCK_SKEW);
        return ['ok' => true, 'message' => 'Verified with Resend native signature.'];
    }

    private static function verifySendGrid(\WP_REST_Request $request, string $raw): array {
        $publicKey = trim((string) Settings::get('mail_webhook_sendgrid_public_key', ''));
        $timestamp = trim((string) $request->get_header('x-twilio-email-event-webhook-timestamp'));
        $signature = trim((string) $request->get_header('x-twilio-email-event-webhook-signature'));
        if ($publicKey === '' || $timestamp === '' || $signature === '') {
            return ['ok' => false, 'message' => 'SendGrid verification key or signature headers missing.'];
        }
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW) {
            return ['ok' => false, 'message' => 'SendGrid webhook timestamp outside the allowed window.'];
        }
        if (!extension_loaded('openssl')) { return ['ok' => false, 'message' => 'OpenSSL is required for SendGrid signature verification.']; }
        $pem = self::normalizePublicKey($publicKey);
        $sigBytes = base64_decode($signature, true);
        if ($sigBytes === false) { return ['ok' => false, 'message' => 'Invalid SendGrid signature encoding.']; }
        $result = openssl_verify($timestamp.$raw, $sigBytes, $pem, OPENSSL_ALGO_SHA256);
        if ($result !== 1) { return ['ok' => false, 'message' => 'Invalid SendGrid webhook signature.']; }
        $replayKey = 'lacvo_wtp_hook_sg_'.hash('sha256', $timestamp.$signature);
        if (get_transient($replayKey)) { return ['ok' => false, 'message' => 'Duplicate SendGrid webhook.']; }
        set_transient($replayKey, 1, self::MAX_CLOCK_SKEW);
        return ['ok' => true, 'message' => 'Verified with SendGrid signed Event Webhook.'];
    }

    private static function verifyMailgun(array $body): array {
        $key = (string) Settings::get('mail_webhook_mailgun_signing_key', '');
        $signature = (array) ($body['signature'] ?? []);
        $timestamp = trim((string) ($signature['timestamp'] ?? ''));
        $token = trim((string) ($signature['token'] ?? ''));
        $provided = strtolower(trim((string) ($signature['signature'] ?? '')));
        if ($key === '' || $timestamp === '' || $token === '' || $provided === '') {
            return ['ok' => false, 'message' => 'Mailgun signing key or signature payload missing.'];
        }
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW) {
            return ['ok' => false, 'message' => 'Mailgun webhook timestamp outside the allowed window.'];
        }
        $expected = hash_hmac('sha256', $timestamp.$token, $key);
        if (!hash_equals($expected, $provided)) { return ['ok' => false, 'message' => 'Invalid Mailgun webhook signature.']; }
        $replayKey = 'lacvo_wtp_hook_mg_'.hash('sha256', $token);
        if (get_transient($replayKey)) { return ['ok' => false, 'message' => 'Duplicate Mailgun webhook.']; }
        set_transient($replayKey, 1, self::MAX_CLOCK_SKEW);
        return ['ok' => true, 'message' => 'Verified with Mailgun native signature.'];
    }

    private static function normalizePublicKey(string $key): string {
        if (str_contains($key, 'BEGIN PUBLIC KEY')) { return $key; }
        $decoded = base64_decode(preg_replace('/\s+/', '', $key) ?? '', true);
        if ($decoded !== false) {
            return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($decoded), 64, "\n")."-----END PUBLIC KEY-----\n";
        }
        return $key;
    }
}
