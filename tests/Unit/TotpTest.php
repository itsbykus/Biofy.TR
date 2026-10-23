<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /** RFC 6238 appendix B test vectors (SHA-1, secret "12345678901234567890"), last 6 digits. */
    public function test_matches_rfc_6238_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');

        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertSame('081804', Totp::code($secret, 1111111109));
        $this->assertSame('050471', Totp::code($secret, 1111111111));
        $this->assertSame('005924', Totp::code($secret, 1234567890));
        $this->assertSame('279037', Totp::code($secret, 2000000000));
    }

    public function test_base32_round_trip(): void
    {
        $bytes = random_bytes(20);
        $this->assertSame($bytes, Totp::base32Decode(Totp::base32Encode($bytes)));
    }

    public function test_verify_accepts_adjacent_window_and_rejects_others(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_700_000_000;

        $this->assertNotNull(Totp::verify($secret, Totp::code($secret, $now), $now));
        $this->assertNotNull(Totp::verify($secret, Totp::code($secret, $now - 30), $now));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $now - 120), $now));
        $this->assertNull(Totp::verify($secret, 'abcdef', $now));
        $this->assertNull(Totp::verify($secret, '12345', $now));
    }
}
