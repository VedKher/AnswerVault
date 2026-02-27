<!-- Hero Section -->
<header class="text-center px-4 relative overflow-hidden mb-24 pt-20 pb-10">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-4xl opacity-20 pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-20 right-10 w-72 h-72 bg-cyan-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
    </div>
    
    <h1 class="text-6xl md:text-8xl font-bold text-white mb-8 relative z-10 tracking-tight">
        Answer <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-blue-600">Vault</span>
    </h1>
    <p class="text-2xl text-brand-text max-w-3xl mx-auto mb-12 relative z-10 leading-relaxed font-light">
        Your premium destination for complete Maharashtra State Board solutions. Clean, fast, and accessible.
    </p>
</header>

<section>
    <h2 class="text-3xl font-bold text-center text-white mb-16 relative">
        <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-400">Select Your Standard</span>
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-8 max-w-6xl mx-auto">
        <?php for ($i = 5; $i <= 10; $i++): ?>
            <a href="/std-<?= $i ?>" 
               class="group block relative p-1 rounded-2xl transition-all duration-500 hover:scale-105 active:scale-95">
                <div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-blue-600 rounded-2xl opacity-0 group-hover:opacity-75 blur-md transition-opacity duration-500"></div>
                <div class="relative h-full bg-gray-900 border border-gray-800 rounded-xl p-6 flex flex-col items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="text-5xl font-black mb-2 bg-clip-text text-transparent bg-gradient-to-br from-white to-gray-500 group-hover:from-cyan-300 group-hover:to-blue-500 transition-all duration-300 transform group-hover:scale-110">
                        <?= $i ?>
                    </div>
                    <div class="text-sm font-medium text-brand-text uppercase tracking-widest group-hover:text-cyan-300 transition-colors duration-300">
                        Standard
                    </div>
                </div>
            </a>
        <?php endfor; ?>
    </div>
</section>
