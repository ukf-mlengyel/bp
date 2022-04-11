const dumpDetailBg = document.getElementById('dump-detail-background');
//const dumpDetailFrame = document.getElementById('dump-detail-frame');
const dumpDetailIframe = document.getElementById('dump-detail-iframe');
const dumpDetailSpinner = document.getElementById('dump-detail-spinner');
const closeButton = document.getElementById('close-button');
dumpDetailBg.style.display = 'none';

const animOpen = [{opacity: 0}, {opacity: 1}];
const animClose = animOpen.slice().reverse();

const animOptions = {duration: 200, easing: 'ease-out', iterations: 1};

dumpDetailIframe.addEventListener('load', () => {
    dumpDetailSpinner.style.display = 'none';
    dumpDetailIframe.style.display = 'inline';
});

function showDetails(id, usecoords, type){
    dumpDetailBg.style.display = 'inline';
    let newsrc = usecoords ? 'web/'+type+'/view.php?'+type+'id='+id+'&lon='+init_coords[0]+'&lat='+init_coords[1] : 'web/'+type+'/view.php?'+type+'id='+id;
    if(!dumpDetailIframe.src.includes(newsrc)){
        dumpDetailSpinner.style.display = 'inline';
        dumpDetailIframe.style.display = 'none';
        dumpDetailIframe.src = newsrc;
    }
    closeButton.style.pointerEvents = 'auto';
    dumpDetailBg.animate(animOpen, animOptions);
}

function toggleFilter(){
    const picker = document.getElementById('filter-picker');
    picker.style.display = picker.style.display === "none" ? "inline" : "none";
}

function filterBins(){
    let f = 0;
    for(let i = 1; i <= 128; i*=2) if(document.getElementById("t"+i).checked === true) f+=i;
    location.replace("bins.php?filter="+f);
}

function hideDetails(){
    closeButton.style.pointerEvents = 'none';
    dumpDetailBg.animate(animClose, animOptions);
    setTimeout(() => {
        dumpDetailBg.style.display = 'none';
    }, animOptions.duration-50);
}