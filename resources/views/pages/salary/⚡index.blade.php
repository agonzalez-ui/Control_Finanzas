<?php

use App\Models\Salary;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Salario')] class extends Component {
    public string $monto = '';

    public int $mes;

    public int $anio;

    public function mount(): void
    {
        $this->mes = (int) now()->month;
        $this->anio = (int) now()->year;
    }

    #[Computed]
    public function historial()
    {
        return Salary::where('user_id', Auth::id())->orderByDesc('year')->orderByDesc('month')->get();
    }

    #[Computed]
    public function actual()
    {
        return Salary::byMonth(Auth::id(), (int) now()->month, (int) now()->year);
    }

    #[Computed]
    public function tendencia()
    {
        return $this->historial->take(12)->reverse()->values();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2020,2099'],
        ]);

        Salary::upsertForPeriod(Auth::id(), $validated['mes'], $validated['anio'], (float) $validated['monto']);

        unset($this->historial, $this->actual);
        $this->reset(['monto']);
        Flux::toast(variant: 'success', text: 'Salario guardado correctamente.');
    }

    public function eliminar(int $id): void
    {
        Salary::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->historial, $this->actual);
        Flux::toast(variant: 'success', text: 'Salario eliminado.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header :title="__('Salario')" :subtitle="__('Registra tu salario mensual')" icon="banknotes" color="emerald" />

    @if ($this->tendencia->count() > 1)
        <flux:card class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <flux:heading>{{ __('Tendencia salarial') }}</flux:heading>
                <flux:text class="text-emerald-600 dark:text-emerald-400">{{ \App\Support\Money::format((float) $this->tendencia->last()->amount) }}</flux:text>
            </div>
            <div class="text-emerald-600 dark:text-emerald-400">
                <x-line-chart :values="$this->tendencia->pluck('amount')->map(fn ($v) => (float) $v)->all()" :height="90" />
            </div>
            <div class="flex justify-between text-xs text-zinc-400">
                @foreach ($this->tendencia as $s)
                    <span>{{ \Illuminate\Support\Str::limit(\App\Support\Money::monthName($s->month), 3, '') }}</span>
                @endforeach
            </div>
        </flux:card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <flux:card class="flex flex-col gap-4 lg:col-span-1">
            <flux:heading>{{ __('Registrar / actualizar') }}</flux:heading>
            <form wire:submit="guardar" class="flex flex-col gap-4">
                <flux:input wire:model="monto" type="number" step="0.01" min="0.01" label="{{ __('Monto') }}" />
                <div class="grid grid-cols-2 gap-2">
                    <flux:select wire:model="mes" label="{{ __('Mes') }}">
                        @foreach (range(1, 12) as $m)
                            <flux:select.option value="{{ $m }}">{{ \App\Support\Money::monthName($m) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="anio" label="{{ __('Año') }}">
                        @foreach (range((int) date('Y'), (int) date('Y') - 4) as $y)
                            <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </form>

            @if ($this->actual)
                <flux:callout variant="success" heading="{{ __('Ya registraste el salario de este mes') }}" text="{{ \App\Support\Money::format((float) $this->actual->amount) }}" />
            @endif
        </flux:card>

        <flux:card class="lg:col-span-2">
            <flux:heading class="mb-4">{{ __('Historial') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Periodo') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->historial as $salario)
                        <flux:table.row wire:key="salario-{{ $salario->id }}">
                            <flux:table.cell>{{ \App\Support\Money::monthName($salario->month) }} {{ $salario->year }}</flux:table.cell>
                            <flux:table.cell align="end">{{ \App\Support\Money::format((float) $salario->amount) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $salario->id }})" wire:confirm="¿Eliminar este registro?" aria-label="Eliminar" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-400">{{ __('Sin registros de salario.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
