<?php
// Function to determine if link is active (optional, for future use)
function isActive($path) {
    return $_SERVER['REQUEST_URI'] === $path ? 'text-brand-accent' : 'text-white';
}
?>
<nav class="fixed w-full z-50 glass-nav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <div class="flex-shrink-0 flex items-center group">
                <a href="/" class="flex items-center gap-4">
                    <div class="w-12 h-12 filter drop-shadow-[0_4px_10px_rgba(0,0,0,0.5)] group-hover:scale-110 transition-transform duration-300">
                        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Blue Book (Back) -->
                            <rect x="42" y="32" width="48" height="58" rx="6" fill="url(#blueGrad)" />
                            <rect x="42" y="78" width="48" height="6" rx="3" fill="#cbd5e1" opacity="0.8"/>
                            
                            <!-- Pink Book (Middle) -->
                            <rect x="22" y="22" width="48" height="58" rx="6" fill="url(#pinkGrad)" />
                            <rect x="22" y="68" width="48" height="6" rx="3" fill="#cbd5e1" opacity="0.8"/>
                            
                            <!-- Green Book (Front) -->
                            <rect x="2" y="12" width="48" height="58" rx="6" fill="url(#greenGrad)" />
                            <rect x="2" y="58" width="48" height="6" rx="3" fill="#cbd5e1" opacity="0.8"/>
                            
                            <defs>
                                <linearGradient id="blueGrad" x1="42" y1="32" x2="90" y2="90" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#38bdf8"/>
                                    <stop offset="1" stop-color="#0284c7"/>
                                </linearGradient>
                                <linearGradient id="pinkGrad" x1="22" y1="22" x2="70" y2="80" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#fb7185"/>
                                    <stop offset="1" stop-color="#e11d48"/>
                                </linearGradient>
                                <linearGradient id="greenGrad" x1="2" y1="12" x2="50" y2="70" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#a3e635"/>
                                    <stop offset="1" stop-color="#65a30d"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <span class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-white via-cyan-400 to-blue-500 tracking-tight">
                        Answer Vault
                    </span>
                </a>
            </div>
            
            <!-- Search Bar (Desktop) with Dropdown Results -->
            <div class="hidden md:flex flex-1 max-w-md mx-8 relative">
                <div class="relative w-full">
                    <input type="text" id="search-input" placeholder="Search topics..." 
                           class="w-full bg-gray-800/50 text-white border border-gray-700 rounded-full px-5 py-2 pl-12 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all placeholder-gray-500"
                           autocomplete="off">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                </div>
                <!-- Search Results Dropdown -->
                <div id="search-dropdown" class="hidden absolute top-full left-0 right-0 mt-2 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl max-h-80 overflow-y-auto z-50">
                    <div id="search-results" class="p-2">
                        <!-- Results will be populated here -->
                    </div>
                </div>
            </div>
            
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="/" class="text-white hover:text-brand-accent transition-colors px-3 py-2 rounded-md font-medium">Home</a>
                <a href="/about" class="text-brand-text hover:text-brand-accent transition-colors px-3 py-2 rounded-md font-medium">About</a>
                <a href="/whats-new" class="text-brand-text hover:text-brand-accent transition-colors px-3 py-2 rounded-md font-medium">What's New</a>
                <a href="/feedback" class="text-brand-text hover:text-brand-accent transition-colors px-3 py-2 rounded-md font-medium">Feedback</a>
            </div>
            
            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center space-x-3">
                <button id="mobile-menu-btn" class="text-gray-300 hover:text-white focus:outline-none">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobile-menu" class="hidden md:hidden bg-brand-dark border-t border-gray-800">
        <!-- Mobile Search -->
        <div class="px-4 py-3 relative">
            <div class="relative">
                <input type="text" id="search-input-mobile" placeholder="Search topics..." 
                       class="w-full bg-gray-800 text-white border border-gray-700 rounded-full px-5 py-2 pl-12 focus:outline-none focus:border-cyan-500"
                       autocomplete="off">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            </div>
            <!-- Mobile Search Results Dropdown -->
            <div id="search-dropdown-mobile" class="hidden absolute left-4 right-4 mt-2 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto z-50">
                <div id="search-results-mobile" class="p-2">
                    <!-- Results will be populated here -->
                </div>
            </div>
        </div>
        <div class="px-2 pb-3 space-y-1 sm:px-3">
            <a href="/" class="block text-white bg-gray-900 px-3 py-2 rounded-md text-base font-medium">Home</a>
            <a href="/about" class="block text-brand-text hover:text-brand-accent px-3 py-2 rounded-md text-base font-medium">About</a>
            <a href="/whats-new" class="block text-brand-text hover:text-brand-accent px-3 py-2 rounded-md text-base font-medium">What's New</a>
            <a href="/feedback" class="block text-brand-text hover:text-brand-accent px-3 py-2 rounded-md text-base font-medium">Feedback</a>
        </div>
    </div>
</nav>

<script>
    // Search with dropdown results
    let searchTimeout;
    
    function setupSearch(inputId, dropdownId, resultsId) {
        const input = document.getElementById(inputId);
        const dropdown = document.getElementById(dropdownId);
        const results = document.getElementById(resultsId);
        
        if (!input || !dropdown || !results) return;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                dropdown.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                results.innerHTML = '<p class="text-gray-400 text-center py-3 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</p>';
                dropdown.classList.remove('hidden');
                
                try {
                    const res = await fetch(`/admin/search_api.php?q=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    
                    if (data.results && data.results.length > 0) {
                        results.innerHTML = data.results.map(r => `
                            <a href="${r.url}" class="block px-4 py-3 hover:bg-gray-800 rounded-lg transition-colors border-b border-gray-800 last:border-0">
                                <div class="text-white font-medium text-sm">${r.title}</div>
                                <div class="text-xs text-gray-500 truncate">${r.path}</div>
                            </a>
                        `).join('');
                    } else {
                        results.innerHTML = '<p class="text-gray-500 text-center py-4 text-sm">No results found</p>';
                    }
                } catch (e) {
                    results.innerHTML = '<p class="text-red-400 text-center py-4 text-sm">Search failed</p>';
                }
            }, 300);
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Show dropdown on focus if there's content
        input.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                dropdown.classList.remove('hidden');
            }
        });
    }
    
    // Initialize search for desktop and mobile
    document.addEventListener('DOMContentLoaded', function() {
        setupSearch('search-input', 'search-dropdown', 'search-results');
        setupSearch('search-input-mobile', 'search-dropdown-mobile', 'search-results-mobile');
    });
</script>
