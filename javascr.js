// Versione aggiornata funzionante per 02/07/2026, ritorna url con tag, gli url sono sotto redirect sullap pagina con forziere
(async function() {
    const DOMAIN = window.location.origin;
    const urls = [];
    
    console.log("🔍 Ricerca API per tag 0-50...");
    
    for (let tag = 0; tag <= 50; tag++) {
        try {
            const response = await fetch(`${DOMAIN}/ajax/chest/get_chest_tag_url.php?tag=${tag}`, {
                headers: {
                    'Accept': 'application/json, text/javascript, */*; q=0.01',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                
                if (data && typeof data === 'object') {
                    let chestUrl = null;
                    const keys = ['url', 'link', 'href', 'chest_url', 'redirect'];
                    
                    for (const key of keys) {
                        if (data[key] && data[key].trim()) {
                            chestUrl = data[key];
                            break;
                        }
                    }
                    
                    if (chestUrl && chestUrl.includes('c=') && chestUrl.includes('sign=')) {
                        urls.push(`${DOMAIN}/?tag=${tag}`);
                        console.log(`   ✅ API tag ${tag}: trovato chest`);
                    }
                }
            }
        } catch (error) {
        }
    }
    
    console.log(`   📊 API: trovati ${urls.length} chest unici`);
    console.log("\n📋 REFERER TROVATI:");
    if (urls.length > 0) {
        urls.forEach((url, index) => {
            console.log(`${index + 1}. ${url}`);
        });
    } else {
        console.log("❌ Nessun chest trovato!");
    }
    
    return urls;
})();
