<?php
// RFC 6238 TOTP 구현 (외부 라이브러리 없이 순수 PHP)
// 이 랩은 인터넷 의존성을 줄이기 위해 composer 없이 직접 구현한다.

function b32_decode(string $b32): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(str_replace('=', '', $b32));
    $bits = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $v = strpos($map, $b32[$i]);
        if ($v === false) continue;
        $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $out .= chr(bindec(substr($bits, $i, 8)));
    }
    return $out;
}

function b32_encode(string $bin): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    for ($i = 0; $i < strlen($bin); $i++) {
        $bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i < strlen($bits); $i += 5) {
        $out .= $map[bindec(str_pad(substr($bits, $i, 5), 5, '0', STR_PAD_RIGHT))];
    }
    return $out;
}

function totp_secret_new(int $bytes = 20): string {
    return b32_encode(random_bytes($bytes));
}

function totp_at(string $secret, ?int $t = null, int $step = 30, int $digits = 6): string {
    if ($t === null) $t = time();
    $counter = intdiv($t, $step);
    $bin     = pack('N*', 0, $counter);              // 8바이트 big-endian 카운터
    $hash    = hash_hmac('sha1', $bin, b32_decode($secret), true);
    $offset  = ord($hash[19]) & 0xf;                 // dynamic truncation
    $code    = ((ord($hash[$offset])     & 0x7f) << 24)
             | ((ord($hash[$offset + 1]) & 0xff) << 16)
             | ((ord($hash[$offset + 2]) & 0xff) << 8)
             |  (ord($hash[$offset + 3]) & 0xff);
    return str_pad((string)($code % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}

// 시계 오차 허용(window=1 → 앞뒤 30초)
function totp_verify(string $secret, string $code, int $window = 1): bool {
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return false;
    $t = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_at($secret, $t + $i * 30), $code)) return true;
    }
    return false;
}

function totp_uri(string $user, string $secret, string $issuer = 'sunrinNS'): string {
    return sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30',
        rawurlencode($issuer), rawurlencode($user), $secret, rawurlencode($issuer));
}
