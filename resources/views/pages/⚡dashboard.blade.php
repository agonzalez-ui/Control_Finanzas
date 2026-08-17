<?php

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Salary;
use App\Models\SavingAccount;
use App\Models\SavingMovement;
use App\Models\Subscription;
use App\Support\Money;
use App\Support\Period;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Url(as: 'mes')]
    public ?int $mes = null;

    #[Url(as: 'anio')]
    public ?int $anio = null;

    public bool $usaPeriodoSugerido = false;

    public function mount(): void
    {
        $this->usaPeriodoSugerido = Period::isUsingSuggested($this->mes !== null, $this->anio !== null);

        $resolved = Period::resolve($this->mes, $this->anio);
        $this->mes = $resolved['month'];
        $this->anio = $resolved['year'];
    }

    public function updatedMes(): void
    {
        $this->usaPeriodoSugerido = false;
    }

    public function updatedAnio(): void
    {
        $this->usaPeriodoSugerido = false;
    }

    #[Computed]
    public function salario(): float
    {
        $salary = Salary::byMonth(Auth::id(), $this->mes, $this->anio);

        return $salary ? (float) $salary->amount : 0.0;
    }

    #[Computed]
    public function totalGastos(): float
    {
        return Expense::totalByMonth(Auth::id(), $this->mes, $this->anio);
    }

    #[Computed]
    public function totalSubs(): float
    {
        return Subscription::totalActive(Auth::id());
    }

    #[Computed]
    public function totalAhorros(): float
    {
        return SavingAccount::totalSavings(Auth::id());
    }

    #[Computed]
    public function ahorroPeriodo(): float
    {
        return SavingMovement::netByPeriod(Auth::id(), $this->mes, $this->anio);
    }

    #[Computed]
    public function totalCuentasPorPagar(): float
    {
        return Bill::totalDueInPeriod(Auth::id(), $this->mes, $this->anio);
    }

    #[Computed]
    public function disponible(): float
    {
        return $this->salario - $this->totalGastos - $this->ahorroPeriodo - $this->totalCuentasPorPagar;
    }

    #[Computed]
    public function q1(): float
    {
        return Expense::totalByQuincena(Auth::id(), $this->mes, $this->anio, 1);
    }

    #[Computed]
    public function q2(): float
    {
        return Expense::totalByQuincena(Auth::id(), $this->mes, $this->anio, 2);
    }

    #[Computed]
    public function ahorroQ1(): float
    {
        return SavingMovement::netByQuincena(Auth::id(), $this->mes, $this->anio, 1);
    }

    #[Computed]
    public function ahorroQ2(): float
    {
        return SavingMovement::netByQuincena(Auth::id(), $this->mes, $this->anio, 2);
    }

    #[Computed]
    public function disponibleQ1(): float
    {
        return ($this->salario / 2) - $this->q1 - $this->ahorroQ1;
    }

    #[Computed]
    public function disponibleQ2(): float
    {
        return ($this->salario / 2) - $this->q2 - $this->ahorroQ2;
    }

    #[Computed]
    public function porCategoria()
    {
        return Expense::byCategory(Auth::id(), $this->mes, $this->anio);
    }

    #[Computed]
    public function ultimosGastos()
    {
        return Expense::byMonth(Auth::id(), $this->mes, $this->anio)->take(5);
    }

    #[Computed]
    public function metas()
    {
        return Goal::where('user_id', Auth::id())->where('completed', false)->orderBy('name')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-page-header
            :title="__('Dashboard')"
            icon="home"
            color="violet"
        >
            <flux:subheading>
                {{ \App\Support\Money::monthName($mes) }} {{ $anio }}
                @if ($usaPeriodoSugerido)
                    <span class="text-zinc-400">&middot; {{ __('mes anterior sugerido') }}</span>
                @endif
            </flux:subheading>
        </x-page-header>

        <div class="flex items-end gap-2">
            <flux:select wire:model.live="mes" label="Mes" class="w-40">
                @foreach (range(1, 12) as $m)
                    <flux:select.option value="{{ $m }}" :selected="$m === $mes">{{ \App\Support\Money::monthName($m) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="anio" label="Año" class="w-28">
                @foreach (range((int) date('Y'), (int) date('Y') - 4) as $y)
                    <flux:select.option value="{{ $y }}" :selected="$y === $anio">{{ $y }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card :label="__('Salario')" :value="\App\Support\Money::format($this->salario)" icon="banknotes" color="emerald" />
        <x-stat-card :label="__('Gastos')" :value="\App\Support\Money::format($this->totalGastos)" icon="receipt-percent" color="rose" />
        <x-stat-card :label="__('Ahorrado (total)')" :value="\App\Support\Money::format($this->totalAhorros)" icon="wallet" color="cyan" />
        <x-stat-card
            :label="__('Disponible del periodo')"
            :value="\App\Support\Money::format($this->disponible)"
            icon="chart-bar"
            :color="$this->disponible < 0 ? 'rose' : 'violet'"
            :value-class="$this->disponible < 0 ? 'text-rose-600 dark:text-rose-400' : ''"
        />
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <flux:heading>{{ __('Por quincena') }}</flux:heading>
            @php $maxQ = max($this->q1, $this->q2, 1); @endphp
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between text-sm">
                    <span>{{ __('Quincena 1') }}</span>
                    <span>{{ \App\Support\Money::format($this->q1) }} &middot; disp. {{ \App\Support\Money::format($this->disponibleQ1) }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                    <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $this->q1 / $maxQ * 100 }}%"></div>
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between text-sm">
                    <span>{{ __('Quincena 2') }}</span>
                    <span>{{ \App\Support\Money::format($this->q2) }} &middot; disp. {{ \App\Support\Money::format($this->disponibleQ2) }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                    <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $this->q2 / $maxQ * 100 }}%"></div>
                </div>
            </div>
            <flux:separator variant="subtle" />
            <div class="flex justify-between text-sm">
                <span>{{ __('Suscripciones activas') }}</span>
                <span>{{ \App\Support\Money::format($this->totalSubs) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>
                    <flux:link href="{{ route('bills.index') }}" wire:navigate>{{ __('Cuentas por pagar (pendiente)') }}</flux:link>
                </span>
                <span>{{ \App\Support\Money::format($this->totalCuentasPorPagar) }}</span>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading>{{ __('Gastos por categoría') }}</flux:heading>
            @if ($this->porCategoria->isNotEmpty())
                <div class="flex items-center gap-5">
                    <x-donut-chart :segments="$this->porCategoria->map(fn ($c) => ['value' => (float) $c->total, 'color' => $c->color])->all()" />
                    <div class="flex flex-1 flex-col gap-2">
                        @foreach ($this->porCategoria as $cat)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $cat->color }}"></span>
                                <span class="flex-1 truncate">{{ $cat->name }}</span>
                                <span>{{ \App\Support\Money::format((float) $cat->total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <flux:text class="text-zinc-400">{{ __('Sin gastos en este periodo.') }}</flux:text>
            @endif
        </flux:card>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading>{{ __('Últimos gastos') }}</flux:heading>
                <flux:link href="{{ route('expenses.index') }}" wire:navigate>{{ __('Ver todos') }}</flux:link>
            </div>
            @forelse ($this->ultimosGastos as $gasto)
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <div>{{ $gasto->description }}</div>
                        <div class="text-zinc-400">{{ $gasto->category->name }} &middot; {{ $gasto->date->format('d/m/Y') }}</div>
                    </div>
                    <span>{{ \App\Support\Money::format((float) $gasto->amount) }}</span>
                </div>
            @empty
                <flux:text class="text-zinc-400">{{ __('Sin gastos registrados.') }}</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading>{{ __('Metas activas') }}</flux:heading>
                <flux:link href="{{ route('goals.index') }}" wire:navigate>{{ __('Ver todas') }}</flux:link>
            </div>
            @forelse ($this->metas as $meta)
                <div class="flex flex-col gap-1 text-sm">
                    <div class="flex justify-between">
                        <span>{{ $meta->name }}</span>
                        <span class="text-zinc-400">{{ $meta->progressPercent() }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $meta->progressPercent() }}%"></div>
                    </div>
                </div>
            @empty
                <flux:text class="text-zinc-400">{{ __('No tienes metas activas.') }}</flux:text>
            @endforelse
        </flux:card>
    </div>
</div>
