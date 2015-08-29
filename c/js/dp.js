/**
 * helpers
 * Created by don on 5/11/14.
 */

function $(id) {
    return document.getElementById(id);
}

function maybeDivert(url, prompt) {
    if(window.confirm(prompt)) {
        divert(url);
    }
}

function divert(url) {
    window.location = url;
}
