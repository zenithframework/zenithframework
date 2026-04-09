// Zen Debugger Error Page JavaScript
(function() {
    'use strict';
    
    function showTab(tabName) {
        var tabs = document.querySelectorAll('.tab');
        var contents = document.querySelectorAll('.tab-content');
        
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove('active');
        }
        for (var j = 0; j < contents.length; j++) {
            contents[j].classList.remove('active');
        }
        
        var targetTab = document.querySelector('.tab[data-tab="' + tabName + '"]');
        if (targetTab) {
            targetTab.classList.add('active');
        }
        document.getElementById(tabName).classList.add('active');
    }
    
    function copyMarkdown() {
        if (typeof errorMarkdown !== 'undefined') {
            navigator.clipboard.writeText(errorMarkdown).then(function() {
                var btn = document.getElementById('btn-copy-markdown');
                var originalText = btn.textContent;
                btn.textContent = '\u2713 Copied!';
                btn.style.background = 'rgba(16, 185, 129, 0.3)';
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }
    }
    
    function copyErrorDetails() {
        if (typeof errorDetails !== 'undefined') {
            navigator.clipboard.writeText(errorDetails).then(function() {
                var btn = document.getElementById('btn-copy-details');
                var originalText = btn.textContent;
                btn.textContent = '\u2713 Copied!';
                btn.style.background = 'rgba(16, 185, 129, 0.3)';
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }
    }
    
    // Add event listeners when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Tab click handlers
            var tabs = document.querySelectorAll('.tab');
            for (var i = 0; i < tabs.length; i++) {
                (function(tab) {
                    tab.addEventListener('click', function() {
                        showTab(tab.getAttribute('data-tab'));
                    });
                })(tabs[i]);
            }
            
            // Copy button handlers
            var copyMarkdownBtn = document.getElementById('btn-copy-markdown');
            var copyDetailsBtn = document.getElementById('btn-copy-details');
            
            if (copyMarkdownBtn) {
                copyMarkdownBtn.addEventListener('click', copyMarkdown);
            }
            if (copyDetailsBtn) {
                copyDetailsBtn.addEventListener('click', copyErrorDetails);
            }
        });
    }
    
    // Make functions globally available
    window.showTab = showTab;
    window.copyMarkdown = copyMarkdown;
    window.copyErrorDetails = copyErrorDetails;
})();
