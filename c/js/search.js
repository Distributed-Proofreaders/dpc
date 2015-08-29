function $(str) {
    return document.getElementById(str);
}

function eclear() {
    $('qgenre[]').selectedIndex = -1;
    $('qlang[]').selectedIndex = -1;
    $('qpm[]').selectedIndex = -1;
    $('qroundid[]').selectedIndex = -1;
    $('qtitle').value = '';
    $('qauthor').value = '';
}
// called by clicking a column caption
function eSetSort(e) {
    if(!e) e = window.event;

    // may only sort on some columns
    var sort = $('sort');
    var desc = $('desc');
    var tgt = e.target ? e.target : e.srcElement;
    var key = tgt.id.substr(2);
    switch(key) {
        case "title":
        case "author":
        case "lang":
        case "projid":
        case "genre":
        case "pm":
        case "diff":
        case "round":
            if(sort == key) {
                $('desc').value = !$('desc').value;
            }
            else {
                $('sort').value = key;
                $('desc').value = '0';
            }
            $('searchform').submit();
            break;
        default:
            return;
    }
    // pull values of hidden inputs
    var vsort = $("sort");
    var vdesc = $("desc");


    // if clicked current sort column, reverse direction
    if( vsort.value === key ) {
        vdesc.value = (vdesc.value == '0') ? '1' : '0' ;
    }
    else {
        vsort.value = key ;
        vdesc.value = '0' ;
    }
    // submit the form
    var sf = $('searchform');
    sf.submit();
}
