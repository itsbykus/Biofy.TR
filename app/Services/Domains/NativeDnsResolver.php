<?php

namespace App\Services\Domains;

/** Uses PHP's resolver (works on shared hosting; no shell access needed). */
class NativeDnsResolver implements DnsResolver
{
    public function txt(string $name): array
    {
        return array_values(array_filter(array_map(
            fn ($r) => isset($r['entries']) ? implode('', $r['entries']) : ($r['txt'] ?? null),
            $this->query($name, DNS_TXT),
        )));
    }

    public function cname(string $name): array
    {
        return array_values(array_filter(array_map(
            fn ($r) => isset($r['target']) ? rtrim(strtolower($r['target']), '.') : null,
            $this->query($name, DNS_CNAME),
        )));
    }

    public function a(string $name): array
    {
        return array_values(array_filter(array_map(fn ($r) => $r['ip'] ?? null, $this->query($name, DNS_A))));
    }

    private function query(string $name, int $type): array
    {
        if (! function_exists('dns_get_record')) {
            return [];
        }

        $records = @dns_get_record($name, $type);

        return is_array($records) ? $records : [];
    }
}
