@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Control Finanzas')" {{ $attributes }}>
        <x-slot name="logo" class="bg-brand-gradient flex aspect-square size-8 items-center justify-center rounded-lg shadow-sm shadow-violet-500/30">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Control Finanzas')" {{ $attributes }}>
        <x-slot name="logo" class="bg-brand-gradient flex aspect-square size-8 items-center justify-center rounded-lg shadow-sm shadow-violet-500/30">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:brand>
@endif
