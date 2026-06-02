<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#A202BB" />

        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <style>
            html {
                background-color: #ffffff;
                color: #0e0e0f;
            }

            html.dark {
                background-color: #0e0e0f;
                color: #e5e2e3;
            }

            /* FOUC prevention: invisible until React mounts and CSS paints */
            #app:not(.hydrated) { opacity: 0; }
            #app.hydrated { opacity: 1; transition: opacity 0.08s ease-out; }
        </style>
        <noscript><style>#app:not(.hydrated) { opacity: 1 !important; }</style></noscript>

        <link rel="icon" type="favicon.ico" href="/favicon.ico" />
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'TraceMem') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        <script>
            // Safety: reveal app after 4s even if JS hydration stalls
            setTimeout(function() {
                var el = document.getElementById('app');
                if (el && !el.classList.contains('hydrated')) el.classList.add('hydrated');
            }, 4000);
        </script>
    </body>
</html>
