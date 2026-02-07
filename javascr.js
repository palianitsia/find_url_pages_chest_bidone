(function() {
    const chest = document.querySelector('.huntChest.huntChestTag a.visible');
    if (!chest) {
        console.log("❌ Nessun chest visibile trovato");
        return null;
    }
    
    const tagClass = Array.from(chest.classList).find(c => c.startsWith('chest-tag'));
    const tag = tagClass ? tagClass.replace('chest-tag', '') : '?';
    const href = chest.getAttribute('href');
    
    console.log(`🎯 Chest tag ${tag}:`, href);
    
    // Copia il link (metodo cross-browser)
    const copyToClipboard = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            console.log("📋 Link copiato negli appunti!");
        } catch (err) {
            console.log("❌ Impossibile copiare, copia manualmente:");
            console.log(href);
        }
        document.body.removeChild(textarea);
    };
    
    copyToClipboard(href);
    
    // Restituisce l'oggetto chest per ulteriori operazioni
    return {
        element: chest,
        tag: tag,
        href: href,
        open: function() {
            window.open(href, '_blank');
        }
    };
})();