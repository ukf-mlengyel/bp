function like(btn, t, id, root) {
    let state = btn.dataset.s;
    let count = parseInt(btn.dataset.c);

    let internalState = state == 1 ? 0 : 1;

    const request = new XMLHttpRequest();
    request.onreadystatechange = function () {
        if(this.readyState == 4 && this.status == 200){
            if(this.responseText === "1"){
                state = internalState;
                count = state === 1 ? count+1 : count-1;
                updateBtn(btn, state, count);
            }
        }
    }

    request.open("POST", root + "web/like.php", true);
    request.send(JSON.stringify({t: t, i: id, s: internalState}));
}

function updateBtn(btn, s, c){
    const states = ["&#x1F90D", "&#x2764"];

    btn.dataset.s = s;
    btn.dataset.c = c;
    btn.innerHTML = states[s] + " " + c;
}