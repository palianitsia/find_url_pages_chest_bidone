import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from concurrent.futures import ThreadPoolExecutor, as_completed

USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
HEADERS = {"User-Agent": USER_AGENT}
DESS_COOKIE = "incola qui il tuo dess :)"

PAGES = [
    {"url": "https://it.bidoo.com/?tab=BIDS", "name": "BIDS"},
    {"url": "https://it.bidoo.com/?tab=MANUAL", "name": "MANUAL"},
    {"url": "https://it.bidoo.com/?tag=buoni", "name": "buoni"},
    {"url": "https://it.bidoo.com/?tag=smartphone", "name": "smartphone"},
    {"url": "https://it.bidoo.com/?tag=apple", "name": "apple"},
    {"url": "https://it.bidoo.com/?tag=bellezza", "name": "bellezza"},
    {"url": "https://it.bidoo.com/?tag=cucina", "name": "cucina"},
    {"url": "https://it.bidoo.com/?tab=casa_e_giardino", "name": "casa_e_giardino"},
    {"url": "https://it.bidoo.com/?tag=elettrodomestici", "name": "elettrodomestici"},
    {"url": "https://it.bidoo.com/?tag=videogame", "name": "videogame"},
    {"url": "https://it.bidoo.com/?tag=giocattoli", "name": "giocattoli"},
    {"url": "https://it.bidoo.com/?tag=tablet-e-pc", "name": "tablet-e-pc"},
    {"url": "https://it.bidoo.com/?tag=hobby", "name": "hobby"},
    {"url": "https://it.bidoo.com/?tag=smartwatch", "name": "smartwatch"},
    {"url": "https://it.bidoo.com/?tag=animali_domestici", "name": "animali_domestici"},
    {"url": "https://it.bidoo.com/?tag=moda", "name": "moda"},
    {"url": "https://it.bidoo.com/?tag=smart-tv", "name": "smart-tv"},
    {"url": "https://it.bidoo.com/?tag=fai_da_te", "name": "fai_da_te"},
    {"url": "https://it.bidoo.com/?tag=luxury", "name": "luxury"},
    {"url": "https://it.bidoo.com/?tag=cuffie-e-audio", "name": "cuffie-e-audio"},
    {"url": "https://it.bidoo.com/?tag=back-to-school", "name": "back-to-school"},
    {"url": "https://it.bidoo.com/?tag=prima-infanzia", "name": "prima-infanzia"},
    {"url": "https://it.bidoo.com/", "name": "homepage"}
]

def get_html(url):
    try:
        resp = requests.get(url, headers=HEADERS, cookies={"dess": DESS_COOKIE}, timeout=10)
        return resp.text if resp.status_code == 200 else None
    except Exception as e:
        print(f"Errore nel caricare {url}: {e}")
        return None

def find_visible_chest_links(html, base_url):
    """
    Cerca chest visibili seguendo esattamente la logica del JavaScript:
    .huntChest.huntChestTag a.visible
    """
    if not html:
        return []
    
    soup = BeautifulSoup(html, 'html.parser')
    chest_links = []
    
    # Cerco esattamente come nel JavaScript: .huntChest.huntChestTag a.visible
    # Prima cerco elementi con classe huntChest E huntChestTag
    hunt_chests = soup.find_all(class_=["huntChest", "huntChestTag"])
    
    # Filtro quelli che hanno entrambe le classi
    valid_chest_containers = []
    for element in hunt_chests:
        classes = element.get('class', [])
        if 'huntChest' in classes and 'huntChestTag' in classes:
            valid_chest_containers.append(element)
    
    # Per ogni container valido, cerco i link a.visible
    for container in valid_chest_containers:
        visible_links = container.find_all('a', class_='visible')
        for link in visible_links:
            href = link.get('href', '')
            if href and 'chest.php' in href and 'c=chest_hunt_tag' in href:
                # Ottengo il tag dal chest
                tag = '?'
                for cls in link.get('class', []):
                    if cls.startswith('chest-tag'):
                        tag = cls.replace('chest-tag', '')
                        break
                
                full_url = href if href.startswith('http') else urljoin(base_url, href)
                chest_links.append({
                    'url': full_url,
                    'tag': tag,
                    'element': str(link)[:100] + '...' if len(str(link)) > 100 else str(link)
                })
    
    # Se non trovo con il metodo sopra, provo una ricerca più diretta
    if not chest_links:
        # Cerco direttamente a.visible dentro elementi con le classi corrette
        visible_chest_links = soup.select('.huntChest.huntChestTag a.visible')
        for link in visible_chest_links:
            href = link.get('href', '')
            if href and 'chest.php' in href and 'c=chest_hunt_tag' in href:
                # Ottengo il tag dal chest
                tag = '?'
                for cls in link.get('class', []):
                    if cls.startswith('chest-tag'):
                        tag = cls.replace('chest-tag', '')
                        break
                
                full_url = href if href.startswith('http') else urljoin(base_url, href)
                chest_links.append({
                    'url': full_url,
                    'tag': tag,
                    'element': str(link)[:100] + '...' if len(str(link)) > 100 else str(link)
                })
    
    return chest_links

def check_page(page_info):
    """
    Controlla una pagina e restituisce informazioni sui chest trovati
    """
    url = page_info["url"]
    name = page_info["name"]
    
    html = get_html(url)
    if not html:
        return {
            "page": name, 
            "url": url, 
            "chests": [], 
            "status": "error",
            "message": "Impossibile caricare la pagina"
        }
    
    chests = find_visible_chest_links(html, url)
    
    if chests:
        return {
            "page": name,
            "url": url,
            "chests": chests,
            "status": "success",
            "message": f"Trovati {len(chests)} chest"
        }
    else:
        return {
            "page": name,
            "url": url,
            "chests": [],
            "status": "no_chest",
            "message": "Nessun chest visibile trovato"
        }

def find_all_chests():
    """
    Trova tutti i chest validi nelle pagine specificate
    """
    all_results = []
    
    with ThreadPoolExecutor(max_workers=5) as executor:
        futures = {executor.submit(check_page, page_info): page_info for page_info in PAGES}
        
        for future in as_completed(futures):
            try:
                result = future.result()
                all_results.append(result)
            except Exception as e:
                page_info = futures[future]
                all_results.append({
                    "page": page_info["name"], 
                    "url": page_info["url"], 
                    "chests": [],
                    "status": "error",
                    "message": str(e)
                })
    
    return all_results

if __name__ == "__main__":
    print("=" * 80)
    print("RICERCA CHEST VISIBILI SU BIDOO")
    print("Ricerca: .huntChest.huntChestTag a.visible")
    print("=" * 80)
    
    results = find_all_chests()
    
    # Statistiche
    total_pages = len(results)
    pages_with_chests = 0
    total_chests = 0
    
    print("\n" + "=" * 80)
    print("RISULTATI DETTAGLIATI")
    print("=" * 80)
    
    for result in results:
        page_name = result["page"]
        page_url = result["url"]
        chests = result["chests"]
        status = result["status"]
        message = result["message"]
        
        if status == "error":
            print(f"\n❌ [ERRORE] {page_name}")
            print(f"   URL: {page_url}")
            print(f"   Messaggio: {message}")
        elif chests:
            pages_with_chests += 1
            total_chests += len(chests)
            
            print(f"\n✅ [CHEST TROVATI] {page_name}")
            print(f"   URL: {page_url}")
            print(f"   {message}")
            
            for i, chest_info in enumerate(chests, 1):
                print(f"   {i}. Chest tag {chest_info['tag']}:")
                print(f"      {chest_info['url']}")
        else:
            print(f"\n❌ [NO CHEST] {page_name}")
            print(f"   URL: {page_url}")
            print(f"   {message}")
    
    # Riepilogo finale
    print("\n" + "=" * 80)
    print("RIEPILOGO FINALE")
    print("=" * 80)
    print(f"Pagine controllate: {total_pages}")
    print(f"Pagine con chest: {pages_with_chests}")
    print(f"Pagine senza chest: {total_pages - pages_with_chests}")
    print(f"Totale chest trovati: {total_chests}")
    
    # Stampa tutti i link trovati in un formato facilmente copiabile
    if total_chests > 0:
        print("\n" + "=" * 80)
        print("TUTTI I LINK DEI CHEST TROVATI")
        print("=" * 80)
        
        for result in results:
            if result['chests']:
                print(f"\n{result['page']}:")
                for chest_info in result['chests']:
                    print(f"  {chest_info['url']}")
        
        print("\n" + "=" * 80)
        print("LINK DA COPIARE:")
        print("=" * 80)
        for result in results:
            for chest_info in result['chests']:
                print(chest_info['url'])
    
    print("\n" + "=" * 80)
    print("SCANSIONE COMPLETATA")
    print("=" * 80)
