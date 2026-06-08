@if(request()->routeIs('*login*'))
<style>
    /* ── Top Bar & Modal Animations ── */
    @keyframes topBarSlideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes staggerFadeUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Shine effect for the logo */
    @keyframes logoShine {
        0% { left: -100%; }
        20% { left: 100%; }
        100% { left: 100%; }
    }

    .animate-top-bar { animation: topBarSlideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    
    .modal-stagger > div:nth-child(1) { animation: staggerFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.1s forwards; opacity: 0; }
    .modal-stagger > div:nth-child(2) { animation: staggerFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards; opacity: 0; }
    .modal-stagger > div:nth-child(3) { animation: staggerFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.3s forwards; opacity: 0; }
    
    .logo-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
        transform: skewX(-25deg);
        animation: logoShine 6s infinite;
    }
</style>

<div>
    <div class="animate-top-bar fixed top-0 inset-x-0 z-[40] flex items-center justify-between px-8 py-4 text-white border-b border-white/10 pointer-events-auto shadow-xl" style="background-color: rgba(15, 20, 30, 0.65) !important;">
        
        <div class="logo-container relative overflow-hidden flex items-center gap-3 drop-shadow-md transition-transform duration-500 hover:scale-[1.02] rounded-lg px-2 py-1">
            <img src="/images/sidebar-branding-dark-mode.png" class="h-10 w-auto relative z-10" alt="Branding">
        </div>

        <div class="flex items-center gap-6 text-[16px] font-medium drop-shadow-md" x-data="{ aboutOpen: false }">
            
            <a href="https://cpaf.uplb.edu.ph/" target="_blank" class="group relative flex items-center gap-2 text-white/90 hover:text-white transition-colors duration-300 active:scale-95 py-1">
                @svg('heroicon-o-building-office', 'w-6 h-6 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:scale-110')
                <span class="relative">
                    UPLB CPAf
                    <span class="absolute -bottom-1 left-0 w-full h-px bg-white origin-right scale-x-0 transition-transform duration-300 ease-out group-hover:origin-left group-hover:scale-x-100"></span>
                </span>
            </a>
            
            <a href="https://sites.google.com/up.edu.ph/uplbcpaflibrary" target="_blank" class="group relative flex items-center gap-2 text-white/90 hover:text-white transition-colors duration-300 active:scale-95 py-1">
                @svg('heroicon-o-book-open', 'w-6 h-6 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:scale-110')
                <span class="relative">
                    CPAf Library
                    <span class="absolute -bottom-1 left-0 w-full h-px bg-white origin-right scale-x-0 transition-transform duration-300 ease-out group-hover:origin-left group-hover:scale-x-100"></span>
                </span>
            </a>
            
            <button type="button" @click="aboutOpen = true" class="group relative flex items-center gap-2 text-white/90 hover:text-white transition-colors duration-300 active:scale-95 py-1">
                @svg('heroicon-o-information-circle', 'w-6 h-6 transition-transform duration-300 group-hover:rotate-12 group-hover:scale-110')
                <span class="relative">
                    About
                    <span class="absolute -bottom-1 left-0 w-full h-px bg-white origin-right scale-x-0 transition-transform duration-300 ease-out group-hover:origin-left group-hover:scale-x-100"></span>
                </span>
            </button>
            
            <div class="w-px h-6 bg-white/30 mx-2 rounded-full"></div>
            
            <button type="button" 
                    @click="$dispatch('theme-changed', $store.theme === 'light' ? 'dark' : 'light')" 
                    class="relative inline-flex h-8 w-16 flex-shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-500 ease-in-out focus:outline-none hover:scale-105 active:scale-95 shadow-inner"
                    :class="$store.theme === 'dark' ? 'bg-indigo-900/80' : 'bg-black/40'">
                <span class="sr-only">Toggle theme</span>
                
                <span class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2 flex h-6 w-6 items-center justify-center">
                    @svg('heroicon-s-sun', 'h-4 w-4 text-amber-500/40')
                </span>
                <span class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 flex h-6 w-6 items-center justify-center">
                    @svg('heroicon-s-moon', 'h-4 w-4 text-indigo-300/40')
                </span>

                <span class="pointer-events-none relative inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-500 ease-in-out z-10"
                      :class="$store.theme === 'dark' ? 'translate-x-9' : 'translate-x-0'">
                    
                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-all duration-500 ease-in-out"
                          :class="$store.theme === 'dark' ? 'opacity-0 rotate-90 scale-50' : 'opacity-100 rotate-0 scale-100'">
                        @svg('heroicon-s-sun', 'h-4 w-4 text-amber-500')
                    </span>
                    
                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-all duration-500 ease-in-out"
                          :class="$store.theme === 'dark' ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 -rotate-90 scale-50'">
                        @svg('heroicon-s-moon', 'h-4 w-4 text-indigo-600')
                    </span>
                </span>
            </button>

            <template x-teleport="body">
                <div x-show="aboutOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center pointer-events-auto" style="background-color: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);" @keydown.escape.window="aboutOpen = false" 
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    
                    <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 p-8 rounded-2xl shadow-2xl max-w-xl w-full mx-4 relative border border-gray-200 dark:border-gray-700" @click.away="aboutOpen = false" 
                         x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-500" x-transition:enter-start="opacity-0 translate-y-10 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                        
                        <button @click="aboutOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-800 dark:hover:text-white transition-all duration-300 hover:rotate-90 hover:scale-110 active:scale-95 bg-gray-100 dark:bg-gray-700/50 p-1.5 rounded-full">
                            @svg('heroicon-c-x-mark', 'w-5 h-5')
                        </button>
                        
                        <div class="flex flex-col items-center mb-6 pt-2">
                            <img src="/images/PATH-Logo.png" class="h-16 w-auto mb-4 drop-shadow-sm transition-transform duration-500 hover:scale-105 dark:hidden" />
                            <img src="/images/PATH-Logo-White.png" class="h-16 w-auto mb-4 drop-shadow-sm transition-transform duration-500 hover:scale-105 hidden dark:block" />
                            <h2 class="text-2xl font-extrabold text-center tracking-tight">About PATH</h2>
                        </div>

                        <div class="modal-stagger space-y-4 text-[14px] leading-relaxed relative z-10 text-left">
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4.5 rounded-xl border border-gray-100 dark:border-gray-700/50 hover:border-gray-200 dark:hover:border-gray-600 hover:-translate-y-1 transition-all duration-300 hover:shadow-md">
                                <h3 class="font-bold mb-1.5 flex items-center gap-2 text-primary-600 dark:text-primary-400">
                                    @svg('heroicon-o-academic-cap', 'w-5 h-5') System Purpose
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">PATH (Progress and Academic Tracking Hub) is a comprehensive information system designed to centralize and streamline graduate student records management for the Institute for Governance and Rural Development (IGRD) at the College of Public Affairs and Development (CPAf), University of the Philippines Los Baños. It provides end-to-end tracking of student enrollment, academic progress, curriculum mapping, milestones, academic outputs, and graduation records.</p>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4.5 rounded-xl border border-gray-100 dark:border-gray-700/50 hover:border-gray-200 dark:hover:border-gray-600 hover:-translate-y-1 transition-all duration-300 hover:shadow-md">
                                <h3 class="font-bold mb-1.5 flex items-center gap-2 text-primary-600 dark:text-primary-400">
                                    @svg('heroicon-o-code-bracket', 'w-5 h-5') Development
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">Built for the Institute for Governance and Rural Development (IGRD) under the College of Public Affairs and Development (CPAf), University of the Philippines Los Baños. Powered by the Laravel TALL stack with Filament.</p>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4.5 rounded-xl border border-gray-100 dark:border-gray-700/50 hover:border-gray-200 dark:hover:border-gray-600 hover:-translate-y-1 transition-all duration-300 hover:shadow-md">
                                <h3 class="font-bold mb-1.5 flex items-center gap-2 text-primary-600 dark:text-primary-400">
                                    @svg('heroicon-o-shield-check', 'w-5 h-5') Data Privacy & Support
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 mb-3">This system contains confidential academic records governed by the Philippine Data Privacy Act and UP System IT policies.</p>
                                <p class="text-gray-600 dark:text-gray-300">For technical assistance or account concerns, please contact the IGRD office: <a href="mailto:cpafigrd.uplb@up.edu.ph" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline transition-colors duration-200">cpafigrd.uplb@up.edu.ph</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
        </div>
    </div>
</div>
@endif