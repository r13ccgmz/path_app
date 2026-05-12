@php
    $isLogin = request()->routeIs('*login*');
    $lightModeStyle = $isLogin ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8)) drop-shadow(0 0 2px rgba(255, 255, 255, 1));' : '';
@endphp
<img src="/images/PATH-Logo.png" alt="PATH Logo" class="h-12 md:h-16 lg:h-[4.5rem] w-auto flex-shrink-0 mb-2 dark:hidden" style="{{ $lightModeStyle }}">
<img src="/images/PATH-Logo.png" alt="PATH Logo" class="h-12 md:h-16 lg:h-[4.5rem] w-auto flex-shrink-0 mb-2 hidden dark:block" style="filter: brightness(0) invert(1);">
