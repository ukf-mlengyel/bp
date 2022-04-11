function updateTextCounter(textarea, limit, counter){
    document.getElementById(counter).innerText = String(limit - textarea.value.length); dataChanged = true;
}