// common variables
var iBytesUploaded = 0;
var iBytesTotal = 0;
var iPreviousBytesLoaded = 0;
var iMaxFilesize = 1024 * 1024; // 1MB
var oTimer = 0;
var sResultFileSize = '';

// we will use this function to convert seconds in normal time format
function secondsToTime(secs) {
    var hr = Math.floor(secs / 3600);
    var min = Math.floor((secs - (hr * 3600))/60);
    var sec = Math.floor(secs - (hr * 3600) -  (min * 60));

    if (hr < 10) {hr = "0" + hr; }
    if (min < 10) {min = "0" + min;}
    if (sec < 10) {sec = "0" + sec;}
    if (hr) {hr = "00";}
    return hr + ':' + min + ':' + sec;
}

function bytesToSize(bytes) {
    var sizes = ['Bytes', 'KB', 'MB'];
    if (bytes == 0) return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
}


function fileSelected() {

    // hide different warnings
    cResponse = 'none';
    cError    = 'none';
    cError2   = 'none';
    cAbort    = 'none';
    cWarnSize = 'none';

    // get selected file element
    var oFile = cZipFile.files[0];

    // filter for image files
    var rFilter = /^zip$/i;
    if (! rFilter.test(oFile.type)) {
        cError2.style.display = 'block';
        return;
    }

    // little test for filesize
    if (oFile.size > iMaxFilesize) {
        cWarnSize.style.display = 'block';
        return;
    }

    // get preview element
    // var oImage = document.getElementById('preview');

    // prepare HTML5 FileReader
    var oReader = new FileReader();
//    oReader.onload = function(e) {

        // e.target.result contains the DataURL which we will use as a source of the image
//        oImage.src = e.target.result;

//        oImage.onload = function () { // binding onload event

            // we are going to display some custom image information here
//            sResultFileSize = bytesToSize(oFile.size);
//            cFileInfo.style.display = 'block';
//            cFileName.innerHTML = 'Name: ' + oFile.name;
//            cFileSize.innerHTML = 'Size: ' + sResultFileSize;
//            cFileSize.innerHTML = 'Type: ' + oFile.type;
//        };
//    };

    // read selected file as DataURL
    oReader.readAsDataURL(oFile);
}

function startUploading() {
    // cleanup all temp states
    iPreviousBytesLoaded = 0;
    cResponse.style.display = 'none';
    cError.style.display = 'none';
    cError2.style.display = 'none';
    cAbort.style.display = 'none';
    cWarnSize.style.display = 'none';
    cProgressPct.innerHTML = '';
    var oProgress = cProgress;
    oProgress.style.display = 'block';
    oProgress.style.width = '0px';

    // get form data for POSTing
    //var vFD = document.getElementById('upload_form').getFormData(); // for FF3
    var vFD = new FormData(cUploadForm); 

    // create XMLHttpRequest object, adding few event listeners, and POSTing our data
    var oXHR = new XMLHttpRequest();
    oXHR.upload.addEventListener('progress', uploadProgress, false);
    oXHR.addEventListener('load', uploadFinish, false);
    oXHR.addEventListener('error', uploadError, false);
    oXHR.addEventListener('abort', uploadAbort, false);
    oXHR.open('POST', 'http://www.pgdpcanada.net/c/tools/upload.php');
    oXHR.send(vFD);

    // set inner timer
    oTimer = setInterval(doInnerUpdates, 300);
}

function doInnerUpdates() { // we will use this function to display upload speed
    var iCB = iBytesUploaded;
    var iDiff = iCB - iPreviousBytesLoaded;

    // if nothing new loaded - exit
    if (iDiff == 0)
        return;

    iPreviousBytesLoaded = iCB;
    iDiff = iDiff * 2;
    var iBytesRem = iBytesTotal - iPreviousBytesLoaded;
    var secondsRemaining = iBytesRem / iDiff;

    // update speed info
    var iSpeed = iDiff.toString() + 'B/s';
    if (iDiff > 1024 * 1024) {
        iSpeed = (Math.round(iDiff * 100/(1024*1024))/100).toString() + 'MB/s';
    } else if (iDiff > 1024) {
        iSpeed =  (Math.round(iDiff * 100/1024)/100).toString() + 'KB/s';
    }

    cSpeed.innerHTML = iSpeed;
    cRemaining.innerHTML = '| ' + secondsToTime(secondsRemaining);
}

/*
function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
        iBytesUploaded = e.loaded;
        iBytesTotal = e.total;
        var iPercentComplete = Math.round(e.loaded * 100 / e.total);
        var iBytesTransferred = bytesToSize(iBytesUploaded);

        cProgressPct.innerHTML = iPercentComplete.toString() + '%';
        cProgress.style.width = (iPercentComplete * 4).toString() + 'px';
        cBytesTransferred.innerHTML = iBytesTransferred;
        if (iPercentComplete == 100) {
            var oUploadResponse = cResponse;
            oUploadResponse.innerHTML = '<h1>Please wait...processing</h1>';
            oUploadResponse.style.display = 'block';
        }
    } else {
        cProgress.innerHTML = 'unable to compute';
    }
}
*/

function uploadFinish(e) { // upload successfully finished
//    var oUploadResponse = cResponse;
    cUploadResponse.innerHTML = e.target.responseText;
    cUploadResponse.style.display = 'block';

    cProgressPct.innerHTML = '100%';
    cProgress.style.width = '400px';
    cFileSize.innerHTML = sResultFileSize;
    cRemaining.innerHTML = '| 00:00:00';

    clearInterval(oTimer);
}

function uploadError(e) { // upload error
    cError2.style.display = 'block';
    clearInterval(oTimer);
}  

function uploadAbort(e) { // upload abort
    cAbort.style.display = 'block';
    clearInterval(oTimer);
}

var cResponse = document.getElementbyId("upload_response");
var cError    = document.getElementbyId("error");
var cError2   = document.getElementbyId("error2");
var cAbort    = document.getElementbyId("abort");
var cWarnSize = document.getElementbyId("warnsize");
var cFileInfo = document.getElementbyId("fileinfo");
var cZipFile  = document.getElementbyId("zipfile");
var cFileName = document.getElementbyId("filename");
var cFileSize = document.getElementbyId("filesize");
var cFileType = document.getElementbyId("filetype");
var cSpeed    = document.getElementbyId("speed");
var cRemaining = document.getElementbyId("remaining");
var cBytesTransferred = document.getElementbyId("b_transfered");
var cProgressPct = document.getElementbyId("progress_percent");
var cProgress = document.getElementById('progress');
var cUploadForm = document.getElementById('upload_form');

