<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- SMART CACHE CONTROL -->
    <meta http-equiv="Cache-Control" content="no-cache, public, must-revalidate" />
    <title>Answer Vault</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/manifest.json?v=<?php echo time(); ?>">
    <meta name="theme-color" content="#00d4ff">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f1419', darker: '#0a0d12', accent: '#00d4ff', text: '#b0b8c1' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <script>
      window.MathJax = {
        tex: {
          inlineMath: [['$', '$'], ['\\(', '\\)']]
        }
      };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?php echo time(); ?>">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0a0d12; color: white; overflow-x: hidden; }
        .glass-nav { background: rgba(15, 20, 25, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
    <script>
        // Register service worker for PWA with cache busting
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?v=<?php echo time(); ?>');
        }
    </script>
</head>
<body class="flex flex-col min-h-screen">
    
    <?php require 'header.php'; ?>

    <main class="flex-grow container mx-auto px-4 pb-20 pt-32 relative z-10">
        <?= $content ?>
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
        
        // Random Feedback Toast (10% chance, once per session)
        (function() {
            if (window.location.pathname === '/feedback') return;
            if (sessionStorage.getItem('feedbackToastShown')) return;
            
            if (Math.random() > 0.10) return;
            
            sessionStorage.setItem('feedbackToastShown', 'true');
            
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

