<?php

namespace App\Services;

use App\Models\Store;

class LanNetworkService
{
    /**
     * Get Server's local LAN IP address (e.g. 192.168.1.100).
     */
    public function getServerLanIp(): string
    {
        $ip = gethostbyname(gethostname());

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && $ip !== '127.0.0.1') {
            return $ip;
        }

        // Fallback check on Windows / Linux environment variables
        $serverAddr = request()->server('SERVER_ADDR');
        if ($serverAddr && filter_var($serverAddr, FILTER_VALIDATE_IP) && $serverAddr !== '127.0.0.1') {
            return $serverAddr;
        }

        return $ip ?: '127.0.0.1';
    }

    /**
     * Get the full local LAN URL for POS counter access on tablets / secondary terminals.
     */
    public function getLanPosUrl(Store $store, int $port = 8501): string
    {
        $ip = $this->getServerLanIp();
        $scheme = request()->secure() ? 'https' : 'http';

        return "{$scheme}://{$ip}:{$port}/store/{$store->slug}/pos";
    }

    /**
     * Get LAN connection info bundle for store settings UI.
     */
    public function getLanConnectionInfo(Store $store, int $port = 8501): array
    {
        $ip = $this->getServerLanIp();
        $posUrl = $this->getLanPosUrl($store, $port);

        return [
            'lan_ip'         => $ip,
            'port'           => $port,
            'pos_access_url' => $posUrl,
            'instructions'   => "ကောင်တာ Tablet သို့မဟုတ် အခြား ကွန်ပျူတာများမှ အထက်ပါ LAN Link သို့မဟုတ် QR Code ဖြင့် ချိတ်ဆက်၍ POS စနစ်ကို အသုံးပြုနိုင်ပါသည်။",
        ];
    }
}
