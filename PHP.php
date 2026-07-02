<?php

// Versione aggiornata funzionante per 02/07/2026, ritorna url con tag, gli url sono sotto redirect sullap pagina con forziere

class BidooChestFinder {
    private $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36";
    private $dessCookie = "tuo_biscotto_qui=)";
    private $domain = "https://it.bidoo.com";
    
    private function httpRequest($url, $headers = [], $timeout = 5) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $headerStrings = [];
        foreach ($headers as $key => $value) {
            $headerStrings[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerStrings);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'body' => $response
        ];
    }
    
    public function findRefererUrls() {
        $refererUrls = [];
        
        $headers = [
            "Cookie" => "dess={$this->dessCookie};",
            "User-Agent" => $this->userAgent,
            "Accept" => "application/json, text/javascript, */*; q=0.01",
            "X-Requested-With" => "XMLHttpRequest",
            "Referer" => $this->domain . "/"
        ];
        
        for ($tag = 0; $tag <= 50; $tag++) {
            try {
                $apiUrl = $this->domain . "/ajax/chest/get_chest_tag_url.php?tag=" . $tag;
                $response = $this->httpRequest($apiUrl, $headers, 5);
                
                if ($response['status'] == 200 && !empty($response['body'])) {
                    $data = json_decode($response['body'], true);
                    
                    if ($data && is_array($data)) {
                        $chestUrl = null;
                        $keys = ['url', 'link', 'href', 'chest_url', 'redirect'];
                        
                        foreach ($keys as $key) {
                            if (isset($data[$key]) && !empty($data[$key])) {
                                $chestUrl = $data[$key];
                                break;
                            }
                        }
                        
                        if ($chestUrl && strpos($chestUrl, 'c=') !== false && strpos($chestUrl, 'sign=') !== false) {
                            $refererUrls[] = $this->domain . "/?tag=" . $tag;
                        }
                    }
                }
            } catch (Exception $e) {
            }
        }
        
        return $refererUrls;
    }
}

$finder = new BidooChestFinder();
$refererUrls = $finder->findRefererUrls();

foreach ($refererUrls as $url) {
    echo $url . "\n";
}

?>
