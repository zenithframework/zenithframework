<?php

declare(strict_types=1);

namespace Zenith\Security\Firewall;

use Zenith\Database\QueryBuilder;

class IPBlocker
{
    protected array $blockedIPs = [];
    protected array $whitelist = [];
    protected string $storagePath;

    public function __construct()
    {
        $this->storagePath = dirname(__DIR__, 2) . '/storage/security';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function block(string $ip, int $duration = 3600, ?string $reason = null): void
    {
        $this->blockedIPs[$ip] = [
            'blocked_at' => time(),
            'expires_at' => time() + $duration,
            'reason' => $reason ?? 'Manual block',
        ];

        $this->persist();
    }

    public function unblock(string $ip): void
    {
        unset($this->blockedIPs[$ip]);
        $this->persist();
    }

    public function isBlocked(string $ip): bool
    {
        if (in_array($ip, $this->whitelist)) {
            return false;
        }

        if (!isset($this->blockedIPs[$ip])) {
            return false;
        }

        $block = $this->blockedIPs[$ip];

        if ($block['expires_at'] < time()) {
            $this->unblock($ip);
            return false;
        }

        return true;
    }

    public function blockCIDR(string $cidr, int $duration = 3600): void
    {
        $ip = $this->cidrToRange($cidr);
        $this->block($ip['start'], $duration, "CIDR: {$cidr}");
    }

    public function addToWhitelist(array $ips): void
    {
        $this->whitelist = array_merge($this->whitelist, $ips);
    }

    public function getBlockInfo(string $ip): ?array
    {
        return $this->blockedIPs[$ip] ?? null;
    }

    public function getAllBlocked(): array
    {
        $this->cleanupExpired();
        return $this->blockedIPs;
    }

    public function blockCountry(string $countryCode): void
    {
        $geoConfig = config('security.geo_blocking') ?? [];
        $geoConfig['blocked_countries'][] = $countryCode;
        
        $this->saveConfig($geoConfig);
    }

    public function blockISP(string $asn): void
    {
        $asnConfig = config('security.asn_blocking') ?? [];
        $asnConfig['blocked_asn'][] = $asn;
        
        $this->saveConfig($asnConfig);
    }

    public function recordViolation(string $ip, string $type): void
    {
        $file = $this->storagePath . '/violations.json';
        $violations = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        
        if (!isset($violations[$ip])) {
            $violations[$ip] = [];
        }
        
        if (!isset($violations[$ip][$type])) {
            $violations[$ip][$type] = 0;
        }
        
        $violations[$ip][$type]++;
        $violations[$ip]['last_violation'] = time();
        $violations[$ip]['total'] = ($violations[$ip]['total'] ?? 0) + 1;
        
        file_put_contents($file, json_encode($violations));
        
        $threshold = config('security.block_threshold') ?? 10;
        if (($violations[$ip]['total'] ?? 0) >= $threshold) {
            $this->block($ip, 3600, "Automated block: {$threshold} violations");
        }
    }

    public function getViolations(string $ip): array
    {
        $file = $this->storagePath . '/violations.json';
        $violations = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        return $violations[$ip] ?? [];
    }

    public function autoBlock(int $threshold = 10, int $duration = 3600): void
    {
        $file = $this->storagePath . '/violations.json';
        if (!file_exists($file)) {
            return;
        }
        
        $violations = json_decode(file_get_contents($file), true);
        $now = time();
        
        foreach ($violations as $ip => $data) {
            if (($data['total'] ?? 0) >= $threshold) {
                if (!isset($this->blockedIPs[$ip])) {
                    $this->block($ip, $duration, "Auto-block: {$data['total']} violations");
                }
            }
            
            if (isset($data['last_violation']) && ($now - $data['last_violation']) > 86400) {
                unset($violations[$ip]);
            }
        }
        
        file_put_contents($file, json_encode($violations));
    }

    protected function cidrToRange(string $cidr): array
    {
        $parts = explode('/', $cidr);
        $ip = $parts[0];
        $bits = $parts[1] ?? 32;
        
        $ip = ip2long($ip);
        $mask = ~((1 << (32 - $bits)) - 1);
        
        return [
            'start' => long2ip($ip & $mask),
            'end' => long2ip($ip | ~$mask),
        ];
    }

    protected function persist(): void
    {
        $file = $this->storagePath . '/blocked_ips.json';
        file_put_contents($file, json_encode($this->blockedIPs));
    }

    protected function load(): void
    {
        $file = $this->storagePath . '/blocked_ips.json';
        if (file_exists($file)) {
            $this->blockedIPs = json_decode(file_get_contents($file), true) ?? [];
        }
    }

    protected function cleanupExpired(): void
    {
        $now = time();
        foreach ($this->blockedIPs as $ip => $block) {
            if ($block['expires_at'] < $now) {
                unset($this->blockedIPs[$ip]);
            }
        }
        $this->persist();
    }

    protected function saveConfig(array $config): void
    {
        $file = $this->storagePath . '/geo_config.json';
        file_put_contents($file, json_encode($config));
    }

    public static function check(): bool
    {
        $blocker = new self();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return $blocker->isBlocked($ip);
    }
}