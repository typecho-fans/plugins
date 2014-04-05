; /* https://github.com/myst729/Reposidget */

function reposidget(obj) {
    var repoinfo = obj.data;
    var repohook = document.getElementById('repo-' + repoinfo.full_name.replace('/', '_'));
    var repojson = document.getElementById('json-' + repoinfo.full_name.replace('/', '_'));
    var container = document.createElement('div');
    var repoContent;
    if(repoinfo.description && repoinfo.homepage) {
        repoContent = '<p>' + repoinfo.description + '</p><p class="homepage"><strong><a href="' + repoinfo.homepage + '">' + repoinfo.homepage + '</a></strong></p>';
    } else if(repoinfo.description) {
        repoContent = '<p>' + repoinfo.description + '</p>';
    } else if(repoinfo.homepage) {
        repoContent = '<p class="homepage"><strong><a href="' + repoinfo.homepage + '">' + repoinfo.homepage + '</a></strong></p>';
    } else {
        repoContent = '<p class="none">No description or homepage.</p>';
    }
    container.className = 'reposidget';
    container.innerHTML = '<div class="reposidget-header"><h2><a href="https://github.com/' + repoinfo.owner.login + '">' + repoinfo.owner.login + '</a>&nbsp;/&nbsp;<strong><a href="' + repoinfo.html_url + '">' + repoinfo.name + '</a></strong></h2></div><div class="reposidget-content">' + repoContent + '</div><div class="reposidget-footer"><span class="social"><span class="star">' + format(repoinfo.watchers_count) + '</span><span class="fork">' + format(repoinfo.forks_count) + '</span></span><a href="' + repoinfo.html_url + '/archive/' + repoinfo.master_branch + '.zip">Download as zip</a></div>';
    repohook.parentNode.insertBefore(container, repohook);
    repohook.style.display = 'none';
    document.body.removeChild(repojson);
}

function format(num) {
    return (num + '').replace(/\B(?=(?:\d{3})+(?!\d))/g, ',');
}

if(!document.getElementsByClassName) {
    document.getElementsByClassName = function(cName) {
        var result = [];
        var nodes = document.getElementsByTagName('*');
        for(var i = 0, len = nodes.length; i < len; i++) {
            if((' ' + nodes[i].className.toLowerCase() + ' ').indexOf(' ' + cName.toLowerCase() + ' ') > -1) {
                result.push(nodes[i]);
            }
        }
        return result;
    };
}

(function() {
    var repository = document.getElementsByClassName('reposidget');
    var address;
    var jsonp;
    for(var i = 0, len = repository.length; i < len; i++) {
        address = repository[i].href.slice(repository[i].href.indexOf('github.com/') + 11);
        jsonp = document.createElement('script');
        jsonp.type = 'text/javascript';
        jsonp.src = 'https://api.github.com/repos/' + address + '?callback=reposidget';
        jsonp.id = 'json-' + address.replace('/', '_');
        repository[i].id = 'repo-' + address.replace('/', '_');
        document.body.appendChild(jsonp);
    }
})();