# Versione aggiornata funzionante per 02/07/2026, ritorna url con tag, gli url sono sotto redirect sullap pagina con forziere

import requests
from concurrent.futures import ThreadPoolExecutor, as_completed
import json

USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
HEADERS_BASE = {
    "User-Agent": USER_AGENT,
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
    "Accept-Encoding": "gzip, deflate, br, zstd",
    "Accept-Language": "it-IT,it;q=0.9,uk;q=0.8,ru;q=0.7,en-US;q=0.6,en;q=0.5",
    "Cache-Control": "no-cache"
}
DESS_COOKIE = "tuo_biscotto_qui=)"
DOMAIN = "https://it.bidoo.com"

def find_chests_via_api():
    """Cerca chest tramite API get_chest_tag_url per tag 0-50"""
    chest_data = {} 
    
    headers = {
        "Cookie": f"dess={DESS_COOKIE};",
        "User-Agent": USER_AGENT,
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "X-Requested-With": "XMLHttpRequest",
        "Referer": DOMAIN + "/",
    }
    
    print("🔍 Ricerca API per tag 0-50...")
    
    for tag in range(0, 51):
        try:
            api_url = f"{DOMAIN}/ajax/chest/get_chest_tag_url.php?tag={tag}"
            resp = requests.get(api_url, headers=headers, timeout=5)
            
            if resp.status_code == 200:
                try:
                    text = resp.text
                    data = json.loads(text) if text else None
                    
                    if data and isinstance(data, dict):
                        chest_url = None
                        for key in ['url', 'link', 'href', 'chest_url', 'redirect']:
                            if key in data and data[key]:
                                chest_url = data[key]
                                break
                        
                        if chest_url and 'c=' in chest_url and 'sign=' in chest_url:
                            if chest_url.startswith('/'):
                                chest_url = f"{DOMAIN}{chest_url}"
                            chest_data[tag] = chest_url
                            print(f"   ✅ API tag {tag}: trovato chest")
                except:
                    pass
        except:
            pass
    
    print(f"   📊 API: trovati {len(chest_data)} chest unici")
    return chest_data

def find_all_chests():
    """Trova tutti i chest usando l'API"""
    valid_chests = []
    
    print("="*60)
    print("🔍 RICERCA CHEST BIDOO")
    print("="*60)
    print()
    
    api_chests = find_chests_via_api()
    
    print(f"\n📊 Totale chest trovati: {len(api_chests)}")
    print()
    
    for tag, chest_url in api_chests.items():
        valid_chests.append({
            "name": f"tag_{tag}",
            "tag": tag,
            "referer": f"{DOMAIN}/?tag={tag}",
            "chest_url": chest_url
        })
    
    return valid_chests

if __name__ == "__main__":
    results = find_all_chests()
    
    print(f"\n{'='*60}")
    print(f"📊 RIEPILOGO FINALE")
    print(f"   Chest trovati: {len(results)}")
    print('='*60)
    print()
    
    if results:
        for i, chest in enumerate(results, 1):
            print(f"{i}. 📦 {chest['name']}")
            print(f"   Tag: {chest['tag']}")
            print(f"   Referer: {chest['referer']}")
            print(f"   URL: {chest['chest_url']}")
            print()
    else:
        print("❌ Nessun chest trovato!")
