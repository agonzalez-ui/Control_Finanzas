<?php

use App\Models\Bill;
use App\Support\Money;
use App\Support\Period;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Cuentas por pagar')] class extends Component {
    #[Url]
    public ?int $mes = null;

    #[Url]
    public ?int $anio = null;

    public ?int $editingId = null;

    public string $nombre = '';

    public string $descripcion = '';

    public string $monto_total = '';

    public int $cuotas = 1;

    public int $cuotas_pagadas = 0;

    public int $dia_pago = 1;

    public string $fecha_inicio = '';

    public function mount(): void
    {
        $resolved = Period::resolve($this->mes, $this->anio);
        $this->mes = $resolved['month'];
        $this->anio = $resolved['year'];
    }

    #[Computed]
    public function cuentas()
    {
        return Bill::where('user_id', Auth::id())
            ->with(['payments' => fn ($q) => $q->where('month', $this->mes)->where('year', $this->anio)])
            ->orderBy('completed')
            ->orderBy('due_day')
            ->get();
    }

    #[Computed]
    public function totalPendienteMes(): float
    {
        return Bill::totalDueInPeriod(Auth::id(), $this->mes, $this->anio);
    }

    public function nuevo(): void
    {
        $this->reset(['editingId', 'nombre', 'descripcion', 'monto_total']);
        $this->cuotas = 1;
        $this->cuotas_pagadas = 0;
        $this->dia_pago = 1;
        $this->fecha_inicio = now()->toDateString();
        Flux::modal('bill-form')->show();
    }

    public function editar(int $id): void
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $bill->id;
        $this->nombre = $bill->name;
        $this->descripcion = (string) $bill->description;
        $this->monto_total = (string) $bill->total_amount;
        $this->cuotas = $bill->installments;
        $this->cuotas_pagadas = $bill->paid_installments;
        $this->dia_pago = $bill->due_day;
        $this->fecha_inicio = $bill->start_date->toDateString();

        Flux::modal('bill-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto_total' => ['required', 'numeric', 'gt:0'],
            'cuotas' => ['required', 'integer', 'min:1', 'max:600'],
            'cuotas_pagadas' => ['required', 'integer', 'min:0', 'lte:cuotas'],
            'dia_pago' => ['required', 'integer', 'between:1,31'],
            'fecha_inicio' => ['required', 'date'],
        ]);

        $montoCuota = round($validated['monto_total'] / $validated['cuotas'], 2);
        $completada = $validated['cuotas_pagadas'] >= $validated['cuotas'];

        if ($this->editingId) {
            $bill = Bill::where('user_id', Auth::id())->findOrFail($this->editingId);
            $bill->update([
                'name' => $validated['nombre'],
                'description' => $validated['descripcion'],
                'total_amount' => $validated['monto_total'],
                'installments' => $validated['cuotas'],
                'installment_amount' => $montoCuota,
                'due_day' => $validated['dia_pago'],
                'start_date' => $validated['fecha_inicio'],
                'paid_installments' => $validated['cuotas_pagadas'],
                'completed' => $completada,
            ]);
            Flux::toast(variant: 'success', text: 'Cuenta por pagar actualizada.');
        } else {
            Bill::create([
                'user_id' => Auth::id(),
                'name' => $validated['nombre'],
                'description' => $validated['descripcion'],
                'total_amount' => $validated['monto_total'],
                'installments' => $validated['cuotas'],
                'installment_amount' => $montoCuota,
                'due_day' => $validated['dia_pago'],
                'start_date' => $validated['fecha_inicio'],
                'paid_installments' => $validated['cuotas_pagadas'],
                'completed' => $completada,
            ]);
            Flux::toast(variant: 'success', text: 'Cuenta por pagar creada.');
        }

        unset($this->cuentas, $this->totalPendienteMes);
        Flux::modal('bill-form')->close();
        $this->reset(['editingId', 'nombre', 'descripcion', 'monto_total']);
    }

    public function marcarPagada(int $id): void
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);
        $bill->markPaid($this->mes, $this->anio);

        unset($this->cuentas, $this->totalPendienteMes);
        Flux::toast(variant: 'success', text: 'Cuota marcada como pagada.');
    }

    public function deshacerPago(int $id): void
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);
        $pago = $bill->payments()->where('month', $this->mes)->where('year', $this->anio)->first();

        if ($pago) {
            $bill->undoPayment($pago);
        }

        unset($this->cuentas, $this->totalPendienteMes);
    }

    public function eliminar(int $id): void
    {
        Bill::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->cuentas, $this->totalPendienteMes);
        Flux::toast(variant: 'success', text: 'Cuenta por pagar eliminada.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-page-header :title="__('Cuentas por pagar')" :subtitle="__('Deudas a plazos: financiamientos, electrodomésticos, etc.')" icon="calendar-days" color="amber" />
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
            <flux:button variant="primary" icon="plus" wire:click="nuevo">{{ __('Nueva cuenta') }}</flux:button>
        </div>
    </div>

    <x-stat-card
        :label="__('Pendiente de pagar este periodo')"
        :value="\App\Support\Money::format($this->totalPendienteMes)"
        icon="calendar-days"
        color="rose"
        value-class="text-rose-600 dark:text-rose-400"
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->cuentas as $bill)
            @php $pagadaEstePeriodo = $bill->isPaidForPeriod($mes, $anio); @endphp
            <flux:card class="flex flex-col gap-3">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>{{ $bill->name }}</flux:heading>
                        @if ($bill->description)
                            <flux:subheading>{{ $bill->description }}</flux:subheading>
                        @endif
                    </div>
                    @if ($bill->completed)
                        <flux:badge color="green" size="sm">{{ __('Pagada') }}</flux:badge>
                    @elseif ($pagadaEstePeriodo)
                        <flux:badge color="blue" size="sm">{{ __('Al día') }}</flux:badge>
                    @else
                        <flux:badge color="amber" size="sm">{{ __('Pendiente') }}</flux:badge>
                    @endif
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex justify-between text-sm">
                        <span>{{ __('Cuota') }}: {{ \App\Support\Money::format((float) $bill->installment_amount) }}</span>
                        <span class="text-zinc-400">{{ $bill->paid_installments }}/{{ $bill->installments }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $bill->progressPercent() }}%"></div>
                    </div>
                    <span class="text-xs text-zinc-400">{{ __('Resta') }}: {{ \App\Support\Money::format($bill->remainingAmount()) }} &middot; {{ __('día de pago') }} {{ $bill->due_day }}</span>
                </div>

                <div class="mt-auto flex flex-wrap justify-end gap-1">
                    @if (! $bill->completed)
                        @if ($pagadaEstePeriodo)
                            <flux:button size="sm" variant="filled" wire:click="deshacerPago({{ $bill->id }})">{{ __('Deshacer pago') }}</flux:button>
                        @else
                            <flux:button size="sm" variant="primary" wire:click="marcarPagada({{ $bill->id }})">{{ __('Marcar cuota pagada') }}</flux:button>
                        @endif
                    @endif
                    <flux:button size="sm" variant="subtle" icon="pencil" wire:click="editar({{ $bill->id }})" aria-label="Editar" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $bill->id }})" wire:confirm="¿Eliminar esta cuenta por pagar y su historial de pagos?" aria-label="Eliminar" />
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-400">{{ __('Aún no tienes cuentas por pagar.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="bill-form" class="max-w-md">
        <form wire:submit="guardar" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Editar cuenta por pagar') : __('Nueva cuenta por pagar') }}</flux:heading>

            <flux:input wire:model="nombre" label="{{ __('Nombre') }}" placeholder="{{ __('Ej. Refrigeradora') }}" />
            <flux:input wire:model="descripcion" label="{{ __('Descripción (opcional)') }}" />
            <flux:input wire:model="monto_total" type="number" step="0.01" min="0.01" label="{{ __('Monto total') }}" />
            <div class="grid grid-cols-2 gap-2">
                <flux:input wire:model="cuotas" type="number" min="1" max="600" label="{{ __('Número de cuotas') }}" />
                <flux:input wire:model="dia_pago" type="number" min="1" max="31" label="{{ __('Día de pago') }}" />
            </div>
            <flux:input wire:model="fecha_inicio" type="date" label="{{ __('Fecha de inicio') }}" />
            <flux:input wire:model="cuotas_pagadas" type="number" min="0" label="{{ __('Cuotas ya pagadas') }}" description="{{ __('Ajusta este número si ya llevas cuotas pagadas (por fuera de la app o de meses anteriores). No puede ser mayor al número de cuotas.') }}" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
