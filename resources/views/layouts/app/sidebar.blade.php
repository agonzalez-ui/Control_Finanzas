<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-page-gradient min-h-screen">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-white/40 bg-white/50 backdrop-blur-xl dark:border-white/10 dark:bg-black/20">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Finanzas')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="receipt-percent" :href="route('expenses.index')" :current="request()->routeIs('expenses.index')" wire:navigate>
                        {{ __('Gastos') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('categories.index')" :current="request()->routeIs('categories.index')" wire:navigate>
                        {{ __('Categorías') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('salary.index')" :current="request()->routeIs('salary.index')" wire:navigate>
                        {{ __('Salario') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" :href="route('subscriptions.index')" :current="request()->routeIs('subscriptions.index')" wire:navigate>
                        {{ __('Suscripciones') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('bills.index')" :current="request()->routeIs('bills.index')" wire:navigate>
                        {{ __('Cuentas por pagar') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wallet" :href="route('savings.index')" :current="request()->routeIs('savings.index')" wire:navigate>
                        {{ __('Ahorros') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="flag" :href="route('goals.index')" :current="request()->routeIs('goals.index')" wire:navigate>
                        {{ __('Metas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.index')" wire:navigate>
                        {{ __('Reportes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-white/40 bg-white/50 backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-black/20">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
