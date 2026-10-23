<?php

namespace Tests\Unit;

use App\Support\Embeds;
use App\Support\Phone;
use App\Support\SafeUrl;
use Tests\TestCase;

class SafeUrlTest extends TestCase
{
    public function test_accepts_http_and_https_and_adds_scheme(): void
    {
        $this->assertSame('https://example.com/path?a=1', SafeUrl::normalize('https://example.com/path?a=1'));
        $this->assertSame('http://example.com', SafeUrl::normalize('http://example.com'));
        $this->assertSame('https://example.com/page', SafeUrl::normalize('example.com/page'));
        $this->assertSame('https://example.com/X', SafeUrl::normalize('https://EXAMPLE.com/X'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dangerousUrls')]
    public function test_rejects_dangerous_urls(string $url): void
    {
        $this->assertNull(SafeUrl::normalize($url));
    }

    public static function dangerousUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript mixed case' => ['JaVaScRiPt:alert(document.cookie)'],
            'javascript with whitespace' => [" javascript\t:alert(1)"],
            'data uri' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'vbscript' => ['vbscript:msgbox(1)'],
            'file' => ['file:///etc/passwd'],
            'protocol relative' => ['//evil.example/x'],
            'credentials' => ['https://user:pass@example.com'],
            'newline injection' => ["https://example.com/\nSet-Cookie:x"],
            'no host' => ['https://'],
            'invalid host' => ['https://exa mple.com'],
            'no tld' => ['https://localhost'],
            'ftp' => ['ftp://example.com/file'],
        ];
    }

    public function test_youtube_ids_are_extracted_only_from_youtube_hosts(): void
    {
        $this->assertSame('dQw4w9WgXcQ', Embeds::youtubeId('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', Embeds::youtubeId('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('dQw4w9WgXcQ', Embeds::youtubeId('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
        $this->assertNull(Embeds::youtubeId('https://evil.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Embeds::youtubeId('https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Embeds::youtubeId('https://www.youtube.com/watch?v="><script>'));
    }

    public function test_music_embeds_are_rebuilt_on_allow_listed_hosts(): void
    {
        $spotify = Embeds::music('https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC');
        $this->assertSame('https://open.spotify.com/embed/track/4uLU6hMCjMI75M1A2tKUQC', $spotify['embed']);
        $this->assertNull(Embeds::music('https://open.spotify.com.evil.com/track/4uLU6hMCjMI75M1A2tKUQC'));
        $this->assertNull(Embeds::music('https://example.com/song.mp3'));

        $soundcloud = Embeds::music('https://soundcloud.com/artist/track-name');
        $this->assertStringStartsWith('https://w.soundcloud.com/player/?url=', $soundcloud['embed']);
    }

    public function test_phone_normalisation(): void
    {
        $this->assertSame('+905551234567', Phone::normalize('0555 123 45 67'));
        $this->assertSame('+905551234567', Phone::normalize('+90 (555) 123-45-67'));
        $this->assertSame('+14155552671', Phone::normalize('+1 415 555 2671'));
        $this->assertNull(Phone::normalize('call me'));
        $this->assertNull(Phone::normalize('123'));
        $this->assertNull(Phone::normalize('+90 555<script>'));
    }

    public function test_slug_pattern(): void
    {
        $pattern = config('biofy.slug.pattern');

        foreach (['mito', 'mi-to', 'm_1to', 'abc123'] as $ok) {
            $this->assertMatchesRegularExpression($pattern, $ok);
        }

        foreach (['ab', 'Mito', '-mito', 'mito-', 'mi--to', 'mi.to', 'mi to', 'çağrı', str_repeat('a', 31), '../etc'] as $bad) {
            $this->assertDoesNotMatchRegularExpression($pattern, $bad);
        }
    }
}
