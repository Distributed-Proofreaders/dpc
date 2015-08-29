var col;
// function to call on body.load
function eSortInit() {
    makeAllSortable(document.body);
}

function aclick(e) {
    if(! e) var e = window.event;
    var tgt = e.target;
    if(col == tgt.cellIndex) {
        tgt.reverse = ! tgt.reverse;
    }
    else {
        col = tgt.cellIndex;
        tgt.reverse = false;
    }
    var rows = tgt.parentElement.parentElement.rows;
    rows.sort(
        function(a, b) {
        return tgt.reverse *
            a.cells[tgt.col].textContent.trim()
                .localeCompare(b.cells[tgt.col].textContent.trim())});
}
function nclick(e) {
    if(! e) var e = window.event;
    var tgt = e.target;     // tHead.row[0]
    if(tgt.col == tgt.cellIndex) {
        tgt.reverse = ! tgt.reverse;
    }
    else {
        tgt.col = tgt.cellIndex;
        tgt.reverse = false;
    }
    var rows = tgt.parentElement.parentElement.rows;
    rows.sort(
            function(a, b) {
                return tgt.reverse *
                   (parseFloat(a.cells[tgt.col].textContent) -
                    parseFloat(b.cells[tgt.col].textContent));
            });
}
function makeSortable(table) {
    // descend from tHead to cells
    if(! table.tHead) {
        return;
    }
    var r = table.tHead.rows[0];
    for(var i = 0; i < r.cells.length; i++) {
        if(r.cells[i].classList.contains('asort')) {
            r.cells[i].addEventListener('click', aclick );
        }
        else if(r.cells[i].classList.contains('nsort')) {
            r.cells[i].addEventListener('click', nclick );
        }
    }
}

function makeAllSortable(doc) {
//    parent = parent.body || document.body;
    var t = doc.getElementsByTagName('table'), i = t.length;
    while (--i >= 0) 
        makeSortable(t[i]);
}

