function addComment(btn, t, id, root, uid, uname, uimageid) {

    let parent = btn.parentNode;
    let content = document.getElementById("main-textarea").value;
    show(parent, false);
    spinner(true);

    const request = new XMLHttpRequest();
    request.onreadystatechange = function () {
        if(this.readyState == 4 && this.status == 200){
            if(this.responseText.substr(0,1) === "1"){
                document.getElementById("main-textarea").value="";
                document.getElementById("textCounter").innerHTML="";

                spinner(false);
                const date = new Date();
                const datestr = date.getDate() +"."+ parseInt(date.getMonth()+1) +"."+ date.getFullYear() +", "+ date.getHours() +":"+ (date.getMinutes() < 10 ? "0"+date.getMinutes() : date.getMinutes());
                let newid = this.responseText.substr(2);

                // vytvorime html
                let commHTML = "<div><hr>" +
                    "<div id='new-comment' class='row anim-new-comment'>" +
                        "<div class='col-auto'>" +
                            "<a href='"+root+"profile.php?userid="+uid+"'><img class='profilepic' src='"+root+"images/user_thumb/"+uimageid+".jpg'></a>" +
                        "</div>" +
                        "<div class='col'>" +
                        "<h6>"+uname+" <span class='date-small'><i>"+datestr+"</i></span>" +
                        "<button onclick='like(this, 2, "+newid+", \""+root+"\")' class='float-end btn btn-secondary btn-small btn-small-margin' data-s='0' data-c='0'>&#x1F90D 0</button>" +
                        "<button onclick='removeComment(this.parentElement.parentElement.parentElement.parentElement, this, "+newid+", \""+root+"\")' class='float-end btn btn-secondary btn-small' >&#x1F5D1 Odstrániť</button></h6>" +
                        "<p class='no-p-margin'>"+content+"</p>" +
                        "</div> " +
                        "</div><script src='js/Like.js'></script></div></div>";

                let commList = document.getElementById("comment-list");
                commList.innerHTML = commHTML + commList.innerHTML;

                // odstranime classu (animacia)
                setTimeout(()=>{
                    let newc = document.getElementById('new-comment');
                    newc.classList.remove("anim-new-comment");
                    newc.removeAttribute("id");
                }, 1000);

                // zobrazime comment box s oneskorenim
                setTimeout(()=>{
                    show(parent, true);
                }, 10000);
            }else{
                show(parent, true);
                spinner(false);
                alert("Chyba");
            }
        }
    }

    request.open("POST", root + "web/comment.php", true);
    request.send(JSON.stringify({t: t, i: id, c: content}));
}

function removeComment(el, btn, id, root){
    btn.disabled = true;

    if(confirm("Chcete odstrániť tento komentár?")){
        const request = new XMLHttpRequest();

        request.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                if (this.responseText === "1") {
                    el.parentNode.removeChild(el);
                } else {
                    btn.disabled = false;
                }
            }
        }

        request.open("POST", root + "web/removecomment.php", true);
        request.send(JSON.stringify({i: id}));
    }else{
        btn.disabled = false;
    }
}

function show(tag, state){
    if(!state){
        tag.style.pointerEvents = "none";
        tag.style.opacity = "0.8"
    }else{
        tag.style.pointerEvents = "auto";
        tag.style.opacity = "1"
    }
}

function spinner(state){
    document.getElementById("spinner").style.display = state ? "inline-block" : "none";
}