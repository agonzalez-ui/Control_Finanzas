<?php

use App\Models\Subscription;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Suscripciones')] class extends Component {
    public ?int $editingId = null;

    public string $nombre = '';

    public string $monto = '';

    public int $dia_cobro = 1;

    #[Computed]
    public function suscripciones()
    {
        return Subscription::where('user_id', Auth::id())->orderBy('name')->get();
    }

    #[Computed]
    public function totalActivo(): float
    {
        return Subscription::totalActive(Auth::id());
    }

    public function nuevo(): void
    {
        $this->reset(['editingId', 'nombre', 'monto']);
        $this->dia_cobro = 1;
        Flux::modal('subscription-form')->show();
    }

    public function editar(int $id): void
    {
        $sub = Subscription::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $sub->id;
        $this->nombre = $sub->name;
        $this->monto = (string) $sub->amount;
        $this->dia_cobro = $sub->billing_day;

        Flux::modal('subscription-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'dia_cobro' => ['required', 'integer', 'between:1,31'],
        ]);

        if ($this->editingId) {
            Subscription::where('user_id', Auth::id())->findOrFail($this->editingId)->update([
                'name' => $validated['nombre'],
                'amount' => $validated['monto'],
                'billing_day' => $validated['dia_cobro'],
            ]);
            Flux::toast(variant: 'success', text: 'Suscripción actualizada.');
        } else {
            Subscription::create([
                'user_id' => Auth::id(),
                'name' => $validated['nombre'],
                'amount' => $validated['monto'],
                'billing_day' => $validated['dia_cobro'],
                'active' => true,
            ]);
            Flux::toast(variant: 'success', text: 'Suscripción agregada.');
        }

        unset($this->suscripciones, $this->totalActivo);
        Flux::modal('subscription-form')->close();
        $this->reset(['editingId', 'nombre', 'monto']);
    }

    public function toggle(int $id): void
    {
        Subscription::where('user_id', Auth::id())->findOrFail($id)->toggle();
        unset($this->suscripciones, $this->totalActivo);
    }

    public function eliminar(int $id): void
    {
        Subscription::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->suscripciones, $this->totalActivo);
        Flux::toast(variant: 'success', text: 'Suscripción eliminada.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-page-header :title="__('Suscripciones')" :subtitle="__('Total activo mensual').': '.\App\Support\Money::format($this->totalActivo)" icon="credit-card" color="blue" />
        <flux:button variant="primary" icon="plus" wire:click="nuevo">{{ __('Nueva suscripción') }}</flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nombre') }}</flux:table.column>
                <flux:table.column>{{ __('Día de cobro') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column>{{ __('Estado') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->suscripciones as $sub)
                    <flux:table.row wire:key="sub-{{ $sub->id }}">
                        <flux:table.cell>{{ $sub->name }}</flux:table.cell>
                        <flux:table.cell>{{ $sub->billing_day }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $sub->amount) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$sub->active ? 'green' : 'zinc'" size="sm">
                                {{ $sub->active ? __('Activa') : __('Inactiva') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="subtle" wire:click="toggle({{ $sub->id }})">
                                    {{ $sub->active ? __('Desactivar') : __('Activar') }}
                                </flux:button>
                                <flux:button size="sm" variant="subtle" icon="pencil" wire:click="editar({{ $sub->id }})" aria-label="Editar" />
                                <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $sub->id }})" wire:confirm="¿Eliminar esta suscripción?" aria-label="Eliminar" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400">{{ __('Sin suscripciones registradas.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="subscription-form" class="max-w-md">
        <form wire:submit="guardar" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Editar suscripción') : __('Nueva suscripción') }}</flux:heading>

            <flux:input wire:model="nombre" label="{{ __('Nombre') }}" />
            <flux:input wire:model="monto" type="number" step="0.01" min="0.01" label="{{ __('Monto') }}" />
            <flux:input wire:model="dia_cobro" type="number" min="1" max="31" label="{{ __('Día de cobro') }}" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
