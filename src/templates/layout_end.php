    </main>

    <?php require 'footer.php'; ?>

    <script>
        // Global Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        if(btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        }
        
        // Random Feedback Toast (5% chance, once per session)
        (function() {
            if (window.location.pathname === '/feedback') return; // Don't show on feedback page
            if (sessionStorage.getItem('feedbackToastShown')) return; // Already shown this session
            
            // 10% chance to show
            if (Math.random() > 0.10) return;
            
            sessionStorage.setItem('feedbackToastShown', 'true');
            
            // Wait 3 seconds before showing
            setTimeout(() => {
                const toast = document.createElement('div');
                toast.id = 'feedback-toast';
                toast.innerHTML = `
                    <div class="fixed bottom-6 right-6 z-50 animate-slide-up">
                        <div class="bg-gray-900/95 backdrop-blur-xl border border-gray-700 rounded-2xl shadow-2xl p-5 max-w-sm">
                            <button onclick="this.parentElement.parentElement.remove()" class="absolute top-3 right-3 text-gray-500 hover:text-white">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="flex items-start gap-4">
                                <div class="text-3xl">💬</div>
                                <div>
                                    <h4 class="font-bold text-white mb-1">Enjoying Answer Vault?</h4>
                                    <p class="text-sm text-gray-400 mb-3">We'd love to hear your feedback!</p>
                                    <a href="/feedback" class="inline-block px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">
                                        Give Feedback
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Auto-dismiss after 10 seconds
                setTimeout(() => {
                    const toastEl = document.getElementById('feedback-toast');
                    if (toastEl) toastEl.remove();
                }, 10000);
            }, 3000);
        })();
    </script>
    
    <style>
        @keyframes slide-up {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up { animation: slide-up 0.4s ease-out; }
    </style>
</body>
</html>
