@if(request()->routeIs('*login*'))
    <style>
        /* ── Base & Smooth Scroll ── */
        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: transparent !important;
            background-image: url('/images/login-background-light-mode.png') !important;
            background-size: cover !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-repeat: no-repeat !important;
            transition: background-image 0.5s ease-in-out;
        }

        html.dark body {
            background-image: url('/images/login-background-dark-mode.png') !important;
        }

        /* ── Main Card Container ── */
        .fi-simple-main {
            background: rgba(15, 20, 30, 0.65) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3), 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            border-radius: 1.5rem !important;
            padding: 1.75rem !important;
            animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        html.dark .fi-simple-main {
            background: rgba(8, 12, 24, 0.65) !important;
        }

        /* ── Staggered Form Elements Entrance ── */
        .fi-simple-main form > * {
            opacity: 0;
            animation: formElementsAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        .fi-simple-main form > *:nth-child(1) { animation-delay: 0.15s; }
        .fi-simple-main form > *:nth-child(2) { animation-delay: 0.25s; }
        .fi-simple-main form > *:nth-child(3) { animation-delay: 0.35s; }
        .fi-simple-main form > *:nth-child(4) { animation-delay: 0.45s; }

        /* ── Strip inner backgrounds ── */
        .fi-simple-page, .fi-simple-main-content, .fi-simple-main>div>section,
        .fi-simple-main form, .fi-simple-page>div {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fi-simple-layout, .fi-simple-main-ctn {
            background: transparent !important;
        }

        /* ── Inputs & Breathing Focus ── */
        .fi-simple-main .fi-input, .fi-simple-main input[type="email"],
        .fi-simple-main input[type="password"], .fi-simple-main input[type="text"] {
            background: rgba(255, 255, 255, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            border-radius: 0.6rem !important;
            color: #ffffff !important;
            caret-color: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
        }

        .fi-simple-main input::placeholder {
            color: rgba(255, 255, 255, 0.40) !important;
        }

        .fi-simple-main input:focus {
            background: rgba(255, 255, 255, 0.20) !important;
            border-color: rgba(255, 255, 255, 0.40) !important;
            outline: none !important;
            animation: breathingFocus 2.5s infinite alternate ease-in-out;
        }

        .fi-simple-main .fi-input-wrp, .fi-simple-main [class*="input-wrapper"] {
            background: rgba(255, 255, 255, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            border-radius: 0.6rem !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        }
        
        .fi-simple-main .fi-input-wrp:focus-within, .fi-simple-main [class*="input-wrapper"]:focus-within {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
        }

        /* ── "If Unauthorized Access" info box ── */
        .fi-simple-main .fi-simple-page>div:last-child,
        .fi-simple-main [class*="rounded"][class*="bg-"] {
            background: rgba(0, 0, 0, 0.40) !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            border-radius: 0.75rem !important;
        }

        /* ── Text & Animated Links ── */
        .fi-simple-main label, .fi-simple-main label>span, .fi-simple-main p,
        .fi-simple-main span, .fi-simple-main .fi-checkbox-label,
        .fi-simple-main .text-gray-700, .fi-simple-main .text-gray-500,
        .fi-simple-main .text-gray-400 {
            color: rgba(255, 255, 255, 0.90) !important;
        }

        .fi-simple-main h1, .fi-simple-main h2, .fi-simple-main .fi-heading {
            color: #ffffff !important;
        }

        .fi-simple-main a {
            position: relative;
            text-decoration: none !important;
            transition: color 0.3s ease;
        }

        .fi-simple-main a::after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: currentColor;
            transform-origin: bottom right;
            transition: transform 0.3s cubic-bezier(0.86, 0, 0.07, 1);
        }

        .fi-simple-main a:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* ── Sign In button ── */
        .fi-simple-main .fi-btn {
            background: rgba(255, 255, 255, 0.18) !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            overflow: hidden;
        }

        .fi-simple-main .fi-btn:hover {
            background: rgba(255, 255, 255, 0.28) !important;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
        }

        .fi-simple-main .fi-btn:active {
            transform: translateY(1px) scale(0.99);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
        }

        /* ── Checkbox with Pop Animation ── */
        .fi-simple-main input[type="checkbox"] {
            appearance: none !important;
            -webkit-appearance: none !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 0.25rem !important;
            width: 1.25rem !important;
            height: 1.25rem !important;
            display: inline-block !important;
            position: relative !important;
            cursor: pointer !important;
            transition: border-color 0.2s ease, background-color 0.2s ease !important;
        }

        .fi-simple-main input[type="checkbox"]:checked {
            background-color: #1A5C38 !important;
            border-color: #1A5C38 !important;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E") !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            animation: checkmarkPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards !important;
        }

        .fi-simple-main input[type="checkbox"]:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2) !important;
            outline: none !important;
        }
        
        .fi-simple-main input[type="checkbox"]:hover {
            border-color: rgba(255, 255, 255, 0.6) !important;
        }

        /* ── Logo ── */
        .fi-simple-main .fi-header-heading { display: none !important; }
        .fi-simple-main .fi-logo { margin: 0 auto; }
        .fi-login-logo-glow {
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8)) drop-shadow(0 0 2px rgba(255, 255, 255, 1));
        }

        /* ── Keyframes ── */
        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes formElementsAppear {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes breathingFocus {
            0% { box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05); }
            100% { box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.15); }
        }

        @keyframes checkmarkPop {
            0% { background-size: 0% 0%; }
            50% { background-size: 130% 130%; }
            100% { background-size: 100% 100%; }
        }
    </style>
@endif