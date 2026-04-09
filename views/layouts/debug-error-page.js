// Debug Error Page JavaScript
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    });
}

function showTab(tabName) {
    document.querySelectorAll('.tab').forEach(function(t) { 
        t.classList.remove('active'); 
    });
    document.querySelectorAll('.tab-content').forEach(function(c) { 
        c.classList.remove('active'); 
    });
    event.target.classList.add('active');
    document.getElementById(tabName).classList.add('active');
}
