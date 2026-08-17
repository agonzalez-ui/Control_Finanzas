<?php

use App\Models\Expense;
use App\Models\SavingAccount;
use App\Models\SavingMovement;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ahorros')] class extends Component {
    // -- Cuenta ---------------------------------------------------
    public string $cuentaNombre = '';

    public string $cuentaTipo = 'banco';

    // -- Movimiento -------------------------------------------------
    public ?int $cuenta_ahorro_id = null;

    public string $monto = '';

    public string $tipo_mov = 'deposito';

    public string $descripcion = '';

    public string $fecha = '';

    public bool $afecta_disponible = true;

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    #[Computed]
    public function cuentas()
    {
        return SavingAccount::where('user_id', Auth::id())->orderBy('name')->get();
    }

    #[Computed]
    public function total(): float
    {
        return SavingAccount::totalSavings(Auth::id());
    }

    #[Computed]
    public function movimientos()
    {
        return SavingMovement::recentByUser(Auth::id());
    }

    public function nuevaCuenta(): void
    {
        $this->reset(['cuentaNombre']);
        $this->cuentaTipo = 'banco';
        Flux::modal('account-form')->show();
    }

    public function guardarCuenta(): void
    {
        $validated = $this->validate([
            'cuentaNombre' => ['required', 'string', 'max:100'],
            'cuentaTipo' => ['required', 'in:efectivo,banco,otro'],
        ]);

        SavingAccount::create([
            'user_id' => Auth::id(),
            'name' => $validated['cuentaNombre'],
            'type' => $validated['cuentaTipo'],
        ]);

        unset($this->cuentas, $this->total);
        Flux::modal('account-form')->close();
        $this->reset(['cuentaNombre']);
        Flux::toast(variant: 'success', text: 'Cuenta de ahorro creada.');
    }

    public function eliminarCuenta(int $id): void
    {
        SavingAccount::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->cuentas, $this->total, $this->movimientos);
        Flux::toast(variant: 'success', text: 'Cuenta eliminada.');
    }

    public function nuevoMovimiento(): void
    {
        $this->reset(['monto', 'descripcion']);
        $this->tipo_mov = 'deposito';
        $this->afecta_disponible = true;
        $this->fecha = now()->toDateString();
        $this->cuenta_ahorro_id = $this->cuentas->first()?->id;
        Flux::modal('movement-form')->show();
    }

    public function guardarMovimiento(): void
    {
        $validated = $this->validate([
            'cuenta_ahorro_id' => ['required', 'integer'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'tipo_mov' => ['required', 'in:deposito,retiro'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
        ]);

        if (! SavingAccount::where('user_id', Auth::id())->where('id', $validated['cuenta_ahorro_id'])->exists()) {
            Flux::toast(variant: 'danger', text: 'Cuenta no válida.');

            return;
        }

        $montoFinal = $validated['tipo_mov'] === 'retiro' ? -abs((float) $validated['monto']) : abs((float) $validated['monto']);

        SavingMovement::record([
            'saving_account_id' => $validated['cuenta_ahorro_id'],
            'user_id' => Auth::id(),
            'amount' => $montoFinal,
            'description' => $validated['descripcion'],
            'date' => $validated['fecha'],
            'biweek' => Expense::quincenaFromDate($validated['fecha']),
            'month' => (int) date('n', strtotime($validated['fecha'])),
            'year' => (int) date('Y', strtotime($validated['fecha'])),
            'affects_available' => $this->afecta_disponible,
        ]);

        unset($this->cuentas, $this->total, $this->movimientos);
        Flux::modal('movement-form')->close();
        $this->reset(['monto', 'descripcion']);
        Flux::toast(variant: 'success', text: 'Movimiento registrado.');
    }

    public function eliminarMovimiento(int $id): void
    {
        $mov = SavingMovement::where('user_id', Auth::id())->findOrFail($id);
        $mov->deleteAndRevert();

        unset($this->cuentas, $this->total, $this->movimientos);
        Flux::toast(variant: 'success', text: 'Movimiento eliminado.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-page-header :title="__('Ahorros')" :subtitle="__('Total ahorrado').': '.\App\Support\Money::format($this->total)" icon="wallet" color="cyan" />
        <div class="flex gap-2">
            <flux:button variant="filled" icon="plus" wire:click="nuevaCuenta">{{ __('Nueva cuenta') }}</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="nuevoMovimiento">{{ __('Nuevo movimiento') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->cuentas as $cuenta)
            <flux:card class="flex flex-col gap-2">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>{{ $cuenta->name }}</flux:heading>
                        <flux:badge size="sm" color="zinc">{{ ucfirst($cuenta->type) }}</flux:badge>
                    </div>
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminarCuenta({{ $cuenta->id }})" wire:confirm="¿Eliminar esta cuenta y sus movimientos?" aria-label="Eliminar" />
                </div>
                <flux:heading size="lg">{{ \App\Support\Money::format((float) $cuenta->balance) }}</flux:heading>
            </flux:card>
        @empty
            <flux:text class="text-zinc-400">{{ __('Aún no tienes cuentas de ahorro.') }}</flux:text>
        @endforelse
    </div>

    @if ($this->cuentas->count() > 1 && $this->total > 0)
        @php
            $paleta = ['#06b6d4', '#4f46e5', '#7c3aed', '#c026d3', '#f97316', '#10b981'];
        @endphp
        <flux:card class="flex items-center gap-5">
            <x-donut-chart :segments="$this->cuentas->values()->map(fn ($c, $i) => ['value' => max((float) $c->balance, 0), 'color' => $paleta[$i % count($paleta)]])->all()" />
            <div class="flex flex-1 flex-col gap-2">
                <flux:heading class="mb-1">{{ __('Distribución por cuenta') }}</flux:heading>
                @foreach ($this->cuentas as $i => $cuenta)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $paleta[$i % count($paleta)] }}"></span>
                        <span class="flex-1 truncate">{{ $cuenta->name }}</span>
                        <span>{{ \App\Support\Money::format((float) $cuenta->balance) }}</span>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    <flux:card>
        <flux:heading class="mb-4">{{ __('Movimientos recientes') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Cuenta') }}</flux:table.column>
                <flux:table.column>{{ __('Descripción') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->movimientos as $mov)
                    <flux:table.row wire:key="mov-{{ $mov->id }}">
                        <flux:table.cell>{{ $mov->date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $mov->savingAccount->name }}</flux:table.cell>
                        <flux:table.cell>{{ $mov->description ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="{{ $mov->amount < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ \App\Support\Money::format((float) $mov->amount) }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminarMovimiento({{ $mov->id }})" wire:confirm="¿Eliminar este movimiento?" aria-label="Eliminar" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400">{{ __('Sin movimientos registrados.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="account-form" class="max-w-md">
        <form wire:submit="guardarCuenta" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Nueva cuenta de ahorro') }}</flux:heading>
            <flux:input wire:model="cuentaNombre" label="{{ __('Nombre') }}" />
            <flux:select wire:model="cuentaTipo" label="{{ __('Tipo') }}">
                <flux:select.option value="efectivo">{{ __('Efectivo') }}</flux:select.option>
                <flux:select.option value="banco">{{ __('Banco') }}</flux:select.option>
                <flux:select.option value="otro">{{ __('Otro') }}</flux:select.option>
            </flux:select>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="movement-form" class="max-w-md">
        <form wire:submit="guardarMovimiento" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Nuevo movimiento') }}</flux:heading>

            <flux:select wire:model="cuenta_ahorro_id" label="{{ __('Cuenta') }}">
                @foreach ($this->cuentas as $cuenta)
                    <flux:select.option value="{{ $cuenta->id }}">{{ $cuenta->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model="tipo_mov" label="{{ __('Tipo') }}" variant="segmented">
                <flux:radio value="deposito" label="{{ __('Depósito') }}" />
                <flux:radio value="retiro" label="{{ __('Retiro') }}" />
            </flux:radio.group>

            <flux:input wire:model="monto" type="number" step="0.01" min="0.01" label="{{ __('Monto') }}" />
            <flux:input wire:model="fecha" type="date" label="{{ __('Fecha') }}" />
            <flux:input wire:model="descripcion" label="{{ __('Descripción') }}" />
            <flux:checkbox wire:model="afecta_disponible" label="{{ __('Afecta el disponible del periodo') }}" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
