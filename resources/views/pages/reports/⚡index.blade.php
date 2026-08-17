<?php

use App\Models\Expense;
use App\Models\MonthlyHistory;
use App\Models\Salary;
use App\Models\SavingMovement;
use App\Support\Money;
use App\Support\Period;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Reportes')] class extends Component {
    #[Url]
    public ?int $mes = null;

    #[Url]
    public ?int $anio = null;

    public function mount(): void
    {
        $resolved = Period::resolve($this->mes, $this->anio);
        $this->mes = $resolved['month'];
        $this->anio = $resolved['year'];
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
    public function ahorroPeriodo(): float
    {
        return SavingMovement::netByPeriod(Auth::id(), $this->mes, $this->anio);
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
    public function porCategoria()
    {
        return Expense::byCategory(Auth::id(), $this->mes, $this->anio);
    }

    #[Computed]
    public function tendencia()
    {
        return Expense::monthlySummary(Auth::id(), 6);
    }

    #[Computed]
    public function historial()
    {
        return MonthlyHistory::lastMonths(Auth::id(), 6);
    }

    #[Computed]
    public function porcGasto(): float
    {
        return $this->salario > 0 ? round(($this->totalGastos / $this->salario) * 100, 1) : 0.0;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-page-header :title="__('Reportes')" :subtitle="\App\Support\Money::monthName($mes).' '.$anio" icon="chart-bar" color="violet" />
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
        <x-stat-card
            :label="__('% del salario gastado')"
            value="{{ $this->porcGasto }}%"
            icon="chart-pie"
            :color="$this->porcGasto > 100 ? 'rose' : 'amber'"
            :value-class="$this->porcGasto > 100 ? 'text-rose-600 dark:text-rose-400' : ''"
        />
        <x-stat-card :label="__('Ahorro del periodo')" :value="\App\Support\Money::format($this->ahorroPeriodo)" icon="wallet" color="cyan" />
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <flux:heading>{{ __('Gastos por quincena') }}</flux:heading>
            @php $maxQ = max($this->q1, $this->q2, 1); @endphp
            <div class="flex flex-col gap-2">
                <div>
                    <div class="mb-1 flex justify-between text-sm"><span>{{ __('Quincena 1') }}</span><span>{{ \App\Support\Money::format($this->q1) }}</span></div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $this->q1 / $maxQ * 100 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-sm"><span>{{ __('Quincena 2') }}</span><span>{{ \App\Support\Money::format($this->q2) }}</span></div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $this->q2 / $maxQ * 100 }}%"></div>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading>{{ __('Gastos por categoría') }}</flux:heading>
            @php $maxCat = $this->porCategoria->max('total') ?: 1; @endphp
            @forelse ($this->porCategoria as $cat)
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full" style="background-color: {{ $cat->color }}"></span>
                            {{ $cat->name }}
                        </span>
                        <span>{{ \App\Support\Money::format((float) $cat->total) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full rounded-full" style="width: {{ $cat->total / $maxCat * 100 }}%; background-color: {{ $cat->color }}"></div>
                    </div>
                </div>
            @empty
                <flux:text class="text-zinc-400">{{ __('Sin gastos en este periodo.') }}</flux:text>
            @endforelse
        </flux:card>
    </div>

    <flux:card class="flex flex-col gap-3">
        <flux:heading>{{ __('Tendencia (últimos 6 meses)') }}</flux:heading>
        @php $maxTendencia = $this->tendencia->max('total') ?: 1; @endphp
        <div class="flex items-end gap-3" style="height: 140px">
            @forelse ($this->tendencia->reverse() as $t)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="flex h-full w-full items-end">
                        <div class="bg-brand-gradient w-full rounded-t" style="height: {{ max(4, $t->total / $maxTendencia * 100) }}%"></div>
                    </div>
                    <span class="text-xs text-zinc-400">{{ \App\Support\Money::monthName($t->month) }}</span>
                </div>
            @empty
                <flux:text class="text-zinc-400">{{ __('Sin datos suficientes.') }}</flux:text>
            @endforelse
        </div>
    </flux:card>

    <flux:card>
        <flux:heading class="mb-4">{{ __('Historial de meses cerrados') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Periodo') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Salario') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Gastos') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Ahorro') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Suscripciones') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->historial as $h)
                    <flux:table.row wire:key="hist-{{ $h->id }}">
                        <flux:table.cell>{{ \App\Support\Money::monthName($h->month) }} {{ $h->year }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $h->salary) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $h->total_expenses) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $h->total_savings) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $h->total_subscriptions) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400">{{ __('Aún no has cerrado ningún mes.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
