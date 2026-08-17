<?php

use App\Models\Category;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categorías')] class extends Component {
    public ?int $editingId = null;

    public string $nombre = '';

    public string $color = '#64748b';

    #[Computed]
    public function categorias()
    {
        return Category::where('user_id', Auth::id())->orderBy('name')->get();
    }

    public function nuevo(): void
    {
        $this->reset(['editingId', 'nombre']);
        $this->color = '#64748b';
        Flux::modal('category-form')->show();
    }

    public function editar(int $id): void
    {
        $categoria = Category::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $categoria->id;
        $this->nombre = $categoria->name;
        $this->color = $categoria->color;

        Flux::modal('category-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:7'],
        ]);

        if ($this->editingId) {
            Category::where('user_id', Auth::id())->findOrFail($this->editingId)->update([
                'name' => $validated['nombre'],
                'color' => $validated['color'],
            ]);
            Flux::toast(variant: 'success', text: 'Categoría actualizada.');
        } else {
            Category::create([
                'user_id' => Auth::id(),
                'name' => $validated['nombre'],
                'color' => $validated['color'],
            ]);
            Flux::toast(variant: 'success', text: 'Categoría creada.');
        }

        unset($this->categorias);
        Flux::modal('category-form')->close();
        $this->reset(['editingId', 'nombre']);
    }

    public function eliminar(int $id): void
    {
        $categoria = Category::where('user_id', Auth::id())->findOrFail($id);

        if ($categoria->hasExpenses()) {
            Flux::toast(variant: 'danger', text: 'No se puede eliminar: tiene gastos asociados.');

            return;
        }

        $categoria->delete();
        unset($this->categorias);
        Flux::toast(variant: 'success', text: 'Categoría eliminada.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <x-page-header :title="__('Categorías')" :subtitle="__('Organiza tus gastos por categoría')" icon="tag" color="amber" />
        <flux:button variant="primary" icon="plus" wire:click="nuevo">{{ __('Nueva categoría') }}</flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->categorias as $categoria)
            <flux:card class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="size-4 shrink-0 rounded-full" style="background-color: {{ $categoria->color }}"></span>
                    <span>{{ $categoria->name }}</span>
                </div>
                <div class="flex gap-1">
                    <flux:button size="sm" variant="subtle" icon="pencil" wire:click="editar({{ $categoria->id }})" aria-label="Editar" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $categoria->id }})" wire:confirm="¿Eliminar esta categoría?" aria-label="Eliminar" />
                </div>
            </flux:card>
        @empty
            <flux:text class="text-zinc-400">{{ __('Aún no tienes categorías.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="category-form" class="max-w-md">
        <form wire:submit="guardar" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Editar categoría') : __('Nueva categoría') }}</flux:heading>

            <flux:input wire:model="nombre" label="{{ __('Nombre') }}" />

            <div class="flex items-center gap-3">
                <flux:input type="color" wire:model="color" label="{{ __('Color') }}" class="w-20" />
                <span class="text-sm text-zinc-400">{{ $color }}</span>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
