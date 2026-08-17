<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Control Finanzas') : config('app.name', 'Control Finanzas') }}
</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Escrito a mano (no vía Tailwind/Lightning CSS): el minificador del build
     colapsa "backdrop-filter" + "-webkit-backdrop-filter" y se queda solo con
     la versión prefijada, que Chromium moderno ya no reconoce como alias. --}}
<style>
    [data-flux-card], .glass-panel {
        backdrop-filter: blur(20px);
    }
</style>
