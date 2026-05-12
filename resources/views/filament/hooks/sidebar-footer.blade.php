<div class="flex flex-col gap-5 px-4 pb-6 mt-auto border-t border-gray-100 dark:border-white/5 pt-4">
    
    <!-- Group: Theme Switcher & Logout (Expanded) -->
    <div class="flex items-center w-full gap-2">
        <!-- Native Filament Theme Switcher -->
        <div class="flex items-center justify-center flex-grow bg-gray-50 dark:bg-white/5 rounded-lg border border-gray-200 dark:border-white/10 h-10 w-full overflow-hidden">
            <x-filament-panels::theme-switcher class="w-full h-full flex items-center justify-center [&>button]:flex-1 [&>button]:h-full [&>button]:rounded-none [&>button:not(:last-child)]:border-r [&>button:not(:last-child)]:border-gray-200 dark:[&>button:not(:last-child)]:border-white/10" />
        </div>

        <!-- Sign Out Button -->
        <form action="{{ filament()->getLogoutUrl() }}" method="post" class="m-0 shrink-0">
            @csrf
            <button type="submit" class="flex items-center justify-center w-10 h-10 text-gray-500 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 focus:bg-gray-100 dark:bg-white/5 dark:border-white/10 dark:text-gray-400 dark:hover:bg-gray-800 dark:focus:bg-gray-800 transition-colors" title="Sign out">
                <x-filament::icon icon="heroicon-o-arrow-right-start-on-rectangle" class="w-5 h-5" />
            </button>
        </form>
    </div>

    <!-- User Profile Details (Centered) -->
    <div class="flex items-center justify-center gap-3">
        <div class="shrink-0">
            <x-filament-panels::avatar.user :user="filament()->auth()->user()" size="lg" />
        </div>
        <div class="flex flex-col overflow-hidden">
            <span class="text-base font-bold text-gray-900 truncate dark:text-white leading-tight">
                {{ filament()->auth()->user()->name ?? 'User' }}
            </span>
            <span class="text-sm text-gray-500 truncate dark:text-gray-400 leading-tight">
                <!-- Fetch Role specifically or default to Super Admin -->
                {{ filament()->auth()->user()->roles->first()->name ?? 'Super Admin' }}
            </span>
        </div>
    </div>
</div>
