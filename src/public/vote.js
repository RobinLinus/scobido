function sendVote(vote) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/vote', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send('id=' + vote.id + '&vote=' + vote.up);
    var id = vote.el.id;
    updateNode(vote.el);

    xhr.onloadend = function() {
        console.log('voted!', vote);
        VoteStorage.save(id);
    };
}

function updateNode(node, noAnim) {
    if (!node)
        return;
    deactivateVoting(node);
    node.className += 'voted';
    if (noAnim)
        return;
    updateCounter(node);
    node.className += ' bounce';
}

function deactivateVoting(node) {
    var parent = node.parentNode;
    parent.className = 'vote';
    var children = parent.children;
    for (var i = 0; i < children.length; i++) {
        children[i].id = '';
    }
}

function updateCounter(node) {
    var el = node.childNodes[3];
    el.innerText = parseInt(el.innerText) + 1;
}

function checkIfVoteClick(node) {
    var idFrags = node.id.split('-');
    if (idFrags[0] !== 'vote')
        return;
    var id = parseInt(idFrags[1]);
    if (!id)
        return;
    return {
        id: id,
        up: node.id.split('-')[2] === 'up' ? 1 : 0,
        el: node
    }
}

function voteClickHandler(e) {
    e = e || window.event;
    var target = e.target || e.srcElement;
    if (vote = checkIfVoteClick(e.target))
        return sendVote(vote);
    if (vote = checkIfVoteClick(e.target.parentNode))
        return sendVote(vote);
}


if (document.addEventListener) {
    document.addEventListener('click', voteClickHandler, false);
} else {
    document.attachEvent('onclick', voteClickHandler); //for IE
}


var VoteStorage = (function() {
    var votes = [];
    _fetch();

    function _save(vote) {
        if (typeof(Storage) === "undefined") {
            return;
        }
        votes.push(vote);
        localStorage.setItem('votes', JSON.stringify(votes));
    }

    function _fetch() {
        if (typeof(Storage) === "undefined") {
            return [];
        }
        votes = JSON.parse(localStorage.votes || "[]");
        return votes;
    }

    return {
        save: _save,
        fetch: _fetch
    }
}());


VoteStorage.fetch().forEach(function(id) {
    updateNode(document.getElementById(id), true);
});


// function isYoutube(url){
//   var ID = '';
//   url = url.replace(/(>|<)/gi,'').split(/(vi\/|v=|\/v\/|youtu\.be\/|\/embed\/)/);
//   if(url[2] !== undefined) {
//     ID = url[2].split(/[^0-9a-z_\-]/i);
//     ID = ID[0];
//   }
//   else {
//     ID = url;
//   }
//     return ID;
// }

// Array.from(document.querySelectorAll('.card > a')).forEach(function(e){
//    var href = e.href.replace(location.origin+'/click?url=','');
//    href = decodeURIComponent(href);
//    if(typeof isYoutube(href) == 'string'){
//        replaceWithYoutube(isYoutube(href),e);
//    }
// });

// function replaceWithYoutube(id,el){
//    console.log(id);
//    var img = el.getElementsByClassName('image')[0];

//    var yt = document.createElement('iframe');
//    yt.width = 300;
//    yt.height = 200;
//    yt.src = "https://www.youtube.com/embed/"+id+"?modestbranding=1&autohide=1&showinfo=0&cc=0";;
//    yt.setAttribute('allowfullscreen',1);
//    yt.setAttribute('frameborder',0);
//    yt.style.display = 'none';
//    yt.onload = function(){
//        img.remove();
//        yt.style.display='block';
//    }
//    el.insertBefore(yt,img);
// }
 // window.addEventListener("load", function(){  
 //       if(document.height <= window.outerHeight)
 //       {
 //           document.body.style.height = (window.outerHeight + 50) + 'px';
 //           setTimeout( function(){ window.scrollTo(0, 1); }, 50 );
 //       }
 //       else
 //       {
 //           setTimeout( function(){ window.scrollTo(0, 1); }, 0 ); 
 //       }
 //   }
 //   );