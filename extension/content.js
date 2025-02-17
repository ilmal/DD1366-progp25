window.addEventListener("load", function() {
    const xpath = "/html/body/div[4]/div[2]/div[2]/div[3]/div[1]/div/div[1]/div/div[3]/p[1]/img";  
    const result = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
    const node = result.singleNodeValue;
    if (node) {
        node.src = chrome.runtime.getURL("katt.jpg");
    }

    const xpath2 = "/html/body/div[4]/div[2]/div[2]/div[3]/div[1]/div/div[1]/div/div[3]/p[1]/text()[1]";  
    const result2 = document.evaluate(xpath2, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
    const node2 = result2.singleNodeValue;
    if (node2) {
        node2.nodeValue = "Bilden är INTE generad av Dall E 3. Utan faktiskt en riktig katt!";
    }

    console.log("Saker är fixade!")
});