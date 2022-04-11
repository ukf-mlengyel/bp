function showAction(el){
    el.style.display = el.style.display === "none" ? "inline" : "none";
}

function showEditBox(el){
    const editBox = document.getElementById("edit-desc-textarea");
    if (editBox.innerText === "")
        editBox.innerText = document.getElementById("dump-description").innerText;

    showAction(el);
}

function markAsCleaned(id, s, root){
    msg = s ? "vyčistenú?" : "nevyčistenú";
    if(confirm("Chcete označiť túto skládku ako "+msg+"?")){
        const request = new XMLHttpRequest();
        request.open("POST", root+"web/dump/setcleaned.php", true);
        request.send(JSON.stringify({i: id, s: s}));
        request.addEventListener("load", () =>{
            location.reload();
        });
    }
}

function changeTypes(id, root){
    let val = 0;
    for (let i = 1; i <= 128; i*=2) if(document.getElementById('t'+i).checked) val+=i;

    const request = new XMLHttpRequest();
    request.open("POST", root+"web/bin/changeTypes.php", true);
    request.send(JSON.stringify({i: id, t: val}));
    request.addEventListener("load", () =>{
        location.reload();
    });
}

function deleteDump(id, root){
    if(confirm("Chcete odstrániť túto skládku?")){
        const request = new XMLHttpRequest();
        request.open("POST", root+"web/dump/remove.php", true);
        request.send(JSON.stringify({i: id}));
        request.onreadystatechange = function () {
            if(this.readyState == 4 && this.status == 200){
                if(this.responseText === "1"){
                    parent.hideDetails();
                }
            }
        }
    }
}

function deleteBin(id, root){
    if(confirm("Chcete odstrániť tento kôš?")){
        const request = new XMLHttpRequest();
        request.open("POST", root+"web/bin/remove.php", true);
        request.send(JSON.stringify({i: id}));
        request.onreadystatechange = function () {
            if(this.readyState == 4 && this.status == 200){
                if(this.responseText === "1"){
                    parent.hideDetails();
                }
            }
        }
    }
}