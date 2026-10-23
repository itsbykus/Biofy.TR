<?php

namespace App\Services\Domains;

interface DnsResolver
{
    /** @return list<string> TXT record values */
    public function txt(string $name): array;

    /** @return list<string> CNAME targets (lower-case, no trailing dot) */
    public function cname(string $name): array;

    /** @return list<string> IPv4 addresses */
    public function a(string $name): array;
}
