<?php
// trova_chest.php

define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
define('DESS_COOKIE', 'incola qui il tuo dess :)');

$PAGES = [
    ["url" => "https://it.bidoo.com/?tab=BIDS", "name" => "BIDS"],
    ["url" => "https://it.bidoo.com/?tab=MANUAL", "name" => "MANUAL"],
    ["url" => "https://it.bidoo.com/?tag=buoni", "name" => "buoni"],
    ["url" => "https://it.bidoo.com/?tag=smartphone", "name" => "smartphone"],
    ["url" => "https://it.bidoo.com/?tag=apple", "name" => "apple"],
    ["url" => "https://it.bidoo.com/?tag=bellezza", "name" => "bellezza"],
    ["url" => "https://it.bidoo.com/?tag=cucina", "name" => "cucina"],
    ["url" => "https://it.bidoo.com/?tab=casa_e_giardino", "name" => "casa_e_giardino"],
    ["url" => "https://it.bidoo.com/?tag=elettrodomestici", "name" => "elettrodomestici"],
    ["url" => "https://it.bidoo.com/?tag=videogame", "name" => "videogame"],
    ["url" => "https://it.bidoo.com/?tag=giocattoli", "name" => "giocattoli"],
    ["url" => "https://it.bidoo.com/?tag=tablet-e-pc", "name" => "tablet-e-pc"],
    ["url" => "https://it.bidoo.com/?tag=hobby", "name" => "hobby"],
    ["url" => "https://it.bidoo.com/?tag=smartwatch", "name" => "smartwatch"],
    ["url" => "https://it.bidoo.com/?tag=animali_domestici", "name" => "animali_domestici"],
    ["url" => "https://it.bidoo.com/?tag=moda", "name" => "moda"],
    ["url" => "https://it.bidoo.com/?tag=smart-tv", "name" => "smart-tv"],
    ["url" => "https://it.bidoo.com/?tag=fai_da_te", "name" => "fai_da_te"],
    ["url" => "https://it.bidoo.com/?tag=luxury", "name" => "luxury"],
    ["url" => "https://it.bidoo.com/?tag=cuffie-e-audio", "name" => "cuffie-e-audio"],
    ["url" => "https://it.bidoo.com/?tag=back-to-school", "name" => "back-to-school"],
    ["url" => "https://it.bidoo.com/?tag=prima-infanzia", "name" => "prima-infanzia"],
    ["url" => "https://it.bidoo.com/", "name" => "homepage"]
];

/**
 * Recupera il contenuto HTML di una URL
 */
function getHtml($url) {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: " . USER_AGENT . "\r\n" .
                       "Cookie: dess=" . DESS_COOKIE . "\r\n",
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    
    try {
        $content = @file_get_contents($url, false, $context);
        return $content !== false ? $content : null;
    } catch (Exception $e) {
        error_log("Errore nel caricare $url: " . $e->getMessage());
        return null;
    }
}

/**
 * Converte un URL relativo in assoluto
 */
function makeAbsoluteUrl($relativeUrl, $baseUrl) {
    if (strpos($relativeUrl, 'http://') === 0 || strpos($relativeUrl, 'https://') === 0) {
        return $relativeUrl;
    }
    
    $parsedBase = parse_url($baseUrl);
    $baseScheme = isset($parsedBase['scheme']) ? $parsedBase['scheme'] : 'https';
    $baseHost = isset($parsedBase['host']) ? $parsedBase['host'] : '';
    
    if (strpos($relativeUrl, '/') === 0) {
        return $baseScheme . '://' . $baseHost . $relativeUrl;
    } else {
        $basePath = isset($parsedBase['path']) ? $parsedBase['path'] : '/';
        $basePath = dirname($basePath);
        if ($basePath === '.') $basePath = '';
        return $baseScheme . '://' . $baseHost . $basePath . '/' . $relativeUrl;
    }
}

/**
 * Cerca chest visibili seguendo la logica: .huntChest.huntChestTag a.visible
 */
function findVisibleChestLinks($html, $baseUrl) {
    if (!$html) {
        return [];
    }
    
    $chestLinks = [];
    
    // Carica l'HTML in DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    
    // Metodo 1: Cerca con XPath per .huntChest.huntChestTag a.visible
    $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' huntChest ') " .
             "and contains(concat(' ', normalize-space(@class), ' '), ' huntChestTag ')]" .
             "//a[contains(concat(' ', normalize-space(@class), ' '), ' visible ')]";
    
    $visibleLinks = $xpath->query($query);
    
    foreach ($visibleLinks as $link) {
        $href = $link->getAttribute('href');
        
        if ($href && strpos($href, 'chest.php') !== false && 
            strpos($href, 'c=chest_hunt_tag') !== false) {
            
            // Ottieni il tag dal chest
            $tag = '?';
            $classAttr = $link->getAttribute('class');
            $classes = explode(' ', $classAttr);
            
            foreach ($classes as $class) {
                if (strpos($class, 'chest-tag') === 0) {
                    $tag = str_replace('chest-tag', '', $class);
                    break;
                }
            }
            
            $fullUrl = makeAbsoluteUrl($href, $baseUrl);
            
            $chestLinks[] = [
                'url' => $fullUrl,
                'tag' => $tag,
                'href' => $href
            ];
        }
    }
    
    // Metodo 2: Se non trovati con XPath preciso, cerca in modo più generico
    if (empty($chestLinks)) {
        // Cerca tutti i link a.visible
        $allLinks = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' visible ')]");
        
        foreach ($allLinks as $link) {
            $href = $link->getAttribute('href');
            
            if ($href && strpos($href, 'chest.php') !== false && 
                strpos($href, 'c=chest_hunt_tag') !== false) {
                
                // Verifica se il link è dentro un elemento con le classi corrette
                $parent = $link;
                $hasValidParent = false;
                
                while ($parent = $parent->parentNode) {
                    if ($parent->nodeType === XML_ELEMENT_NODE) {
                        $parentClass = $parent->getAttribute('class');
                        if (strpos($parentClass, 'huntChest') !== false && 
                            strpos($parentClass, 'huntChestTag') !== false) {
                            $hasValidParent = true;
                            break;
                        }
                    }
                }
                
                if ($hasValidParent) {
                    // Ottieni il tag dal chest
                    $tag = '?';
                    $classAttr = $link->getAttribute('class');
                    $classes = explode(' ', $classAttr);
                    
                    foreach ($classes as $class) {
                        if (strpos($class, 'chest-tag') === 0) {
                            $tag = str_replace('chest-tag', '', $class);
                            break;
                        }
                    }
                    
                    $fullUrl = makeAbsoluteUrl($href, $baseUrl);
                    
                    $chestLinks[] = [
                        'url' => $fullUrl,
                        'tag' => $tag,
                        'href' => $href
                    ];
                }
            }
        }
    }
    
    // Rimuovi duplicati
    $uniqueLinks = [];
    $seenUrls = [];
    
    foreach ($chestLinks as $chest) {
        if (!in_array($chest['url'], $seenUrls)) {
            $uniqueLinks[] = $chest;
            $seenUrls[] = $chest['url'];
        }
    }
    
    return $uniqueLinks;
}

/**
 * Controlla una singola pagina
 */
function checkPage($pageInfo) {
    $url = $pageInfo['url'];
    $name = $pageInfo['name'];
    
    $html = getHtml($url);
    
    if (!$html) {
        return [
            'page' => $name,
            'url' => $url,
            'chests' => [],
            'status' => 'error',
            'message' => 'Impossibile caricare la pagina'
        ];
    }
    
    $chests = findVisibleChestLinks($html, $url);
    
    if (!empty($chests)) {
        return [
            'page' => $name,
            'url' => $url,
            'chests' => $chests,
            'status' => 'success',
            'message' => 'Trovati ' . count($chests) . ' chest'
        ];
    } else {
        return [
            'page' => $name,
            'url' => $url,
            'chests' => [],
            'status' => 'no_chest',
            'message' => 'Nessun chest visibile trovato'
        ];
    }
}

/**
 * Funzione principale che controlla tutte le pagine
 */
function trovaChestBidoo($pagineDaControllare = null) {
    global $PAGES;
    
    $pagesToCheck = $pagineDaControllare ?: $PAGES;
    $results = [];
    $totalChests = 0;
    $pagesWithChests = 0;
    
    echo str_repeat("=", 80) . "\n";
    echo "RICERCA CHEST VISIBILI SU BIDOO (PHP)\n";
    echo "Ricerca: .huntChest.huntChestTag a.visible\n";
    echo str_repeat("=", 80) . "\n\n";
    
    foreach ($pagesToCheck as $index => $pageInfo) {
        echo "Controllo pagina " . ($index + 1) . "/" . count($pagesToCheck) . ": {$pageInfo['name']}... ";
        
        $result = checkPage($pageInfo);
        $results[] = $result;
        
        if ($result['status'] === 'success') {
            echo "✓ Trovati " . count($result['chests']) . " chest\n";
            $pagesWithChests++;
            $totalChests += count($result['chests']);
        } elseif ($result['status'] === 'no_chest') {
            echo "✗ Nessun chest\n";
        } else {
            echo "✗ Errore\n";
        }
    }
    
    // Output dettagliato
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "RISULTATI DETTAGLIATI\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($results as $result) {
        $pageName = $result['page'];
        $pageUrl = $result['url'];
        $chests = $result['chests'];
        $status = $result['status'];
        $message = $result['message'];
        
        if ($status === 'error') {
            echo "\n❌ [ERRORE] {$pageName}\n";
            echo "   URL: {$pageUrl}\n";
            echo "   Messaggio: {$message}\n";
        } elseif (!empty($chests)) {
            echo "\n✅ [CHEST TROVATI] {$pageName}\n";
            echo "   URL: {$pageUrl}\n";
            echo "   {$message}\n";
            
            foreach ($chests as $i => $chestInfo) {
                echo "   " . ($i + 1) . ". Chest tag {$chestInfo['tag']}:\n";
                echo "      {$chestInfo['url']}\n";
            }
        } else {
            echo "\n❌ [NO CHEST] {$pageName}\n";
            echo "   URL: {$pageUrl}\n";
            echo "   {$message}\n";
        }
    }
    
    // Riepilogo finale
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "RIEPILOGO FINALE\n";
    echo str_repeat("=", 80) . "\n";
    echo "Pagine controllate: " . count($results) . "\n";
    echo "Pagine con chest: {$pagesWithChests}\n";
    echo "Pagine senza chest: " . (count($results) - $pagesWithChests) . "\n";
    echo "Totale chest trovati: {$totalChests}\n";
    
    // Output tutti i link in formato copiabile
    if ($totalChests > 0) {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TUTTI I LINK DEI CHEST TROVATI\n";
        echo str_repeat("=", 80) . "\n\n";
        
        foreach ($results as $result) {
            if (!empty($result['chests'])) {
                echo "{$result['page']}:\n";
                foreach ($result['chests'] as $chestInfo) {
                    echo "  {$chestInfo['url']}\n";
                }
                echo "\n";
            }
        }
        
        echo str_repeat("=", 80) . "\n";
        echo "LINK DA COPIARE (solo URL):\n";
        echo str_repeat("=", 80) . "\n";
        
        foreach ($results as $result) {
            foreach ($result['chests'] as $chestInfo) {
                echo "{$chestInfo['url']}\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "SCANSIONE COMPLETATA\n";
    echo str_repeat("=", 80) . "\n";
    
    return [
        'results' => $results,
        'total_pages' => count($results),
        'pages_with_chests' => $pagesWithChests,
        'total_chests' => $totalChests
    ];
}

// Versione della funzione per uso programmatico (senza output)
function trovaChestBidooSilent($pagineDaControllare = null) {
    global $PAGES;
    
    $pagesToCheck = $pagineDaControllare ?: $PAGES;
    $results = [];
    
    foreach ($pagesToCheck as $pageInfo) {
        $html = getHtml($pageInfo['url']);
        
        if ($html) {
            $chests = findVisibleChestLinks($html, $pageInfo['url']);
            $results[] = [
                'page' => $pageInfo['name'],
                'url' => $pageInfo['url'],
                'chests' => $chests,
                'found' => !empty($chests)
            ];
        } else {
            $results[] = [
                'page' => $pageInfo['name'],
                'url' => $pageInfo['url'],
                'chests' => [],
                'found' => false,
                'error' => true
            ];
        }
    }
    
    return $results;
}

// Esecuzione se chiamato direttamente
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    // Modalità CLI
    echo "Avvio ricerca chest Bidoo...\n";
    trovaChestBidoo();
} elseif (php_sapi_name() === 'cli') {
    // Modalità CLI diretta
    echo "Avvio ricerca chest Bidoo...\n";
    trovaChestBidoo();
}

// Esempio di utilizzo programmatico:
// $risultati = trovaChestBidooSilent();
// foreach ($risultati as $risultato) {
//     if ($risultato['found']) {
//         foreach ($risultato['chests'] as $chest) {
//             echo "Chest trovato: {$chest['url']}\n";
//         }
//     }
// }

?>