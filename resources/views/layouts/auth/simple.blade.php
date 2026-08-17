<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-page-gradient min-h-screen antialiased">
        <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10">
            <div class="relative flex w-full max-w-sm flex-col gap-6">
                <a href="{{ route('home') }}" class="mb-2 flex flex-col items-center gap-3 font-medium" wire:navigate>
                    <span class="bg-brand-gradient flex size-14 items-center justify-center rounded-2xl shadow-lg shadow-violet-500/30">
                        <x-app-logo-icon class="size-7 fill-current text-white" />
                    </span>
                    <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ config('app.name', 'Control Finanzas') }}</span>
                </a>

                <div class="glass-panel flex flex-col gap-6 rounded-2xl border p-8 shadow-xl shadow-zinc-900/5 dark:shadow-black/20">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
