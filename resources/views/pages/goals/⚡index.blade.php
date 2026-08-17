<?php

use App\Models\Goal;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Metas')] class extends Component {
    public ?int $editingId = null;

    public string $nombre = '';

    public string $descripcion = '';

    public string $monto_objetivo = '';

    public string $fecha_limite = '';

    public ?int $abonandoId = null;

    public string $abonoMonto = '';

    #[Computed]
    public function metas()
    {
        return Goal::where('user_id', Auth::id())->orderBy('completed')->orderBy('name')->get();
    }

    public function nuevo(): void
    {
        $this->reset(['editingId', 'nombre', 'descripcion', 'monto_objetivo', 'fecha_limite']);
        Flux::modal('goal-form')->show();
    }

    public function editar(int $id): void
    {
        $meta = Goal::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $meta->id;
        $this->nombre = $meta->name;
        $this->descripcion = (string) $meta->description;
        $this->monto_objetivo = (string) $meta->target_amount;
        $this->fecha_limite = $meta->due_date?->toDateString() ?? '';

        Flux::modal('goal-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto_objetivo' => ['required', 'numeric', 'gt:0'],
            'fecha_limite' => ['nullable', 'date'],
        ]);

        $data = [
            'name' => $validated['nombre'],
            'description' => $validated['descripcion'],
            'target_amount' => $validated['monto_objetivo'],
            'due_date' => $validated['fecha_limite'] ?: null,
        ];

        if ($this->editingId) {
            Goal::where('user_id', Auth::id())->findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: 'Meta actualizada.');
        } else {
            Goal::create($data + ['user_id' => Auth::id(), 'current_amount' => 0, 'completed' => false]);
            Flux::toast(variant: 'success', text: 'Meta financiera creada.');
        }

        unset($this->metas);
        Flux::modal('goal-form')->close();
        $this->reset(['editingId', 'nombre', 'descripcion', 'monto_objetivo', 'fecha_limite']);
    }

    public function abonar(int $id): void
    {
        $this->abonandoId = $id;
        $this->abonoMonto = '';
        Flux::modal('goal-contribute')->show();
    }

    public function guardarAbono(): void
    {
        $validated = $this->validate([
            'abonoMonto' => ['required', 'numeric', 'gt:0'],
        ]);

        $meta = Goal::where('user_id', Auth::id())->findOrFail($this->abonandoId);
        $meta->contribute((float) $validated['abonoMonto']);

        unset($this->metas);
        Flux::modal('goal-contribute')->close();
        $this->reset(['abonandoId', 'abonoMonto']);
        Flux::toast(variant: 'success', text: 'Abono registrado.');
    }

    public function eliminar(int $id): void
    {
        Goal::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->metas);
        Flux::toast(variant: 'success', text: 'Meta eliminada.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <x-page-header :title="__('Metas financieras')" :subtitle="__('Ahorra hacia tus objetivos')" icon="flag" color="violet" />
        <flux:button variant="primary" icon="plus" wire:click="nuevo">{{ __('Nueva meta') }}</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->metas as $meta)
            <flux:card class="flex flex-col gap-3">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>{{ $meta->name }}</flux:heading>
                        @if ($meta->description)
                            <flux:subheading>{{ $meta->description }}</flux:subheading>
                        @endif
                    </div>
                    @if ($meta->completed)
                        <flux:badge color="green" size="sm">{{ __('Completada') }}</flux:badge>
                    @endif
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex justify-between text-sm">
                        <span>{{ \App\Support\Money::format((float) $meta->current_amount) }}</span>
                        <span class="text-zinc-400">{{ \App\Support\Money::format((float) $meta->target_amount) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="bg-brand-gradient h-full rounded-full" style="width: {{ $meta->progressPercent() }}%"></div>
                    </div>
                    <span class="text-xs text-zinc-400">{{ $meta->progressPercent() }}%</span>
                </div>

                @if ($meta->due_date)
                    <flux:text class="text-xs text-zinc-400">{{ __('Fecha límite') }}: {{ $meta->due_date->format('d/m/Y') }}</flux:text>
                @endif

                <div class="mt-auto flex justify-end gap-1">
                    <flux:button size="sm" variant="filled" wire:click="abonar({{ $meta->id }})">{{ __('Abonar') }}</flux:button>
                    <flux:button size="sm" variant="subtle" icon="pencil" wire:click="editar({{ $meta->id }})" aria-label="Editar" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $meta->id }})" wire:confirm="¿Eliminar esta meta?" aria-label="Eliminar" />
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-400">{{ __('Aún no tienes metas financieras.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="goal-form" class="max-w-md">
        <form wire:submit="guardar" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Editar meta') : __('Nueva meta') }}</flux:heading>
            <flux:input wire:model="nombre" label="{{ __('Nombre') }}" />
            <flux:input wire:model="descripcion" label="{{ __('Descripción') }}" />
            <flux:input wire:model="monto_objetivo" type="number" step="0.01" min="0.01" label="{{ __('Monto objetivo') }}" />
            <flux:input wire:model="fecha_limite" type="date" label="{{ __('Fecha límite (opcional)') }}" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="goal-contribute" class="max-w-sm">
        <form wire:submit="guardarAbono" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Abonar a la meta') }}</flux:heading>
            <flux:input wire:model="abonoMonto" type="number" step="0.01" min="0.01" label="{{ __('Monto a abonar') }}" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Abonar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
