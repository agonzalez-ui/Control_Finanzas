<?php

use App\Models\Category;
use App\Models\Expense;
use App\Support\ExpenseClassifier;
use App\Support\Money;
use App\Support\Period;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Gastos')] class extends Component {
    #[Url]
    public ?int $mes = null;

    #[Url]
    public ?int $anio = null;

    #[Url]
    public int $q = 0;

    #[Url]
    public string $tipo = 'todos';

    public ?int $editingId = null;

    public string $descripcion = '';

    public string $monto = '';

    public string $fecha = '';

    public ?int $categoria_id = null;

    public function mount(): void
    {
        $resolved = Period::resolve($this->mes, $this->anio);
        $this->mes = $resolved['month'];
        $this->anio = $resolved['year'];
    }

    #[Computed]
    public function categorias()
    {
        return Category::where('user_id', Auth::id())->orderBy('name')->get();
    }

    #[Computed]
    public function gastosMes()
    {
        return Expense::byMonth(Auth::id(), $this->mes, $this->anio)
            ->filter(fn ($g) => ExpenseClassifier::matches($g->category->name, $g->description, $this->tipo));
    }

    #[Computed]
    public function gastos()
    {
        return $this->q > 0
            ? $this->gastosMes->filter(fn ($g) => (int) $g->biweek === $this->q)->values()
            : $this->gastosMes->values();
    }

    #[Computed]
    public function totalMes(): float
    {
        return (float) $this->gastosMes->sum('amount');
    }

    #[Computed]
    public function totalQ1(): float
    {
        return (float) $this->gastosMes->where('biweek', 1)->sum('amount');
    }

    #[Computed]
    public function totalQ2(): float
    {
        return (float) $this->gastosMes->where('biweek', 2)->sum('amount');
    }

    #[Computed]
    public function porCategoria()
    {
        return $this->gastosMes
            ->groupBy('category_id')
            ->map(fn ($grupo) => [
                'nombre' => $grupo->first()->category->name,
                'color' => $grupo->first()->category->color,
                'total' => (float) $grupo->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values();
    }

    public function nuevo(): void
    {
        $this->reset(['editingId', 'descripcion', 'monto', 'categoria_id']);
        $sugerido = Period::suggested();
        $this->fecha = now()->toDateString();
        $this->categoria_id = $this->categorias->first()?->id;
        Flux::modal('expense-form')->show();
    }

    public function editar(int $id): void
    {
        $gasto = Expense::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $gasto->id;
        $this->descripcion = $gasto->description;
        $this->monto = (string) $gasto->amount;
        $this->fecha = $gasto->date->toDateString();
        $this->categoria_id = $gasto->category_id;

        Flux::modal('expense-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha' => ['required', 'date'],
            'categoria_id' => ['required', 'integer'],
        ]);

        if (! Category::where('user_id', Auth::id())->where('id', $validated['categoria_id'])->exists()) {
            Flux::toast(variant: 'danger', text: 'Categoría inválida.');

            return;
        }

        $data = [
            'user_id' => Auth::id(),
            'category_id' => $validated['categoria_id'],
            'description' => $validated['descripcion'],
            'amount' => $validated['monto'],
            'date' => $validated['fecha'],
            'biweek' => Expense::quincenaFromDate($validated['fecha']),
            'month' => (int) date('n', strtotime($validated['fecha'])),
            'year' => (int) date('Y', strtotime($validated['fecha'])),
        ];

        if ($this->editingId) {
            Expense::where('user_id', Auth::id())->findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: 'Gasto actualizado.');
        } else {
            Expense::create($data);
            Flux::toast(variant: 'success', text: 'Gasto registrado correctamente.');
        }

        unset($this->gastosMes);
        Flux::modal('expense-form')->close();
        $this->reset(['editingId', 'descripcion', 'monto', 'categoria_id']);
    }

    public function eliminar(int $id): void
    {
        Expense::where('user_id', Auth::id())->findOrFail($id)->delete();
        unset($this->gastosMes);
        Flux::toast(variant: 'success', text: 'Gasto eliminado.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <x-page-header :title="__('Gastos')" :subtitle="\App\Support\Money::monthName($mes).' '.$anio" icon="receipt-percent" color="rose" />
        <flux:button variant="primary" icon="plus" wire:click="nuevo">{{ __('Nuevo gasto') }}</flux:button>
    </div>

    <div class="flex flex-wrap items-end gap-2">
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
        <flux:select wire:model.live="q" label="Quincena" class="w-32">
            <flux:select.option value="0">{{ __('Todas') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Quincena 1') }}</flux:select.option>
            <flux:select.option value="2">{{ __('Quincena 2') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="tipo" label="Tipo" class="w-36">
            <flux:select.option value="todos">{{ __('Todos') }}</flux:select.option>
            <flux:select.option value="fijo">{{ __('Pago fijo') }}</flux:select.option>
            <flux:select.option value="variable">{{ __('Variable') }}</flux:select.option>
            <flux:select.option value="suscripcion">{{ __('Suscripción') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card :label="__('Total del mes')" :value="\App\Support\Money::format($this->totalMes)" icon="receipt-percent" color="rose" />
        <x-stat-card :label="__('Quincena 1')" :value="\App\Support\Money::format($this->totalQ1)" icon="calendar" color="amber" />
        <x-stat-card :label="__('Quincena 2')" :value="\App\Support\Money::format($this->totalQ2)" icon="calendar" color="blue" />
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Fecha') }}</flux:table.column>
                <flux:table.column>{{ __('Descripción') }}</flux:table.column>
                <flux:table.column>{{ __('Categoría') }}</flux:table.column>
                <flux:table.column>{{ __('Quincena') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Monto') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->gastos as $gasto)
                    <flux:table.row wire:key="gasto-{{ $gasto->id }}">
                        <flux:table.cell>{{ $gasto->date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $gasto->description }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="size-2 rounded-full" style="background-color: {{ $gasto->category->color }}"></span>
                                {{ $gasto->category->name }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>{{ $gasto->biweek }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money::format((float) $gasto->amount) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="subtle" icon="pencil" wire:click="editar({{ $gasto->id }})" aria-label="Editar" />
                                <flux:button size="sm" variant="subtle" icon="trash" wire:click="eliminar({{ $gasto->id }})" wire:confirm="¿Eliminar este gasto?" aria-label="Eliminar" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-400">{{ __('Sin gastos para este filtro.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="expense-form" class="max-w-md">
        <form wire:submit="guardar" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? __('Editar gasto') : __('Nuevo gasto') }}</flux:heading>

            <flux:input wire:model="descripcion" label="{{ __('Descripción') }}" />
            <flux:input wire:model="monto" type="number" step="0.01" min="0.01" label="{{ __('Monto') }}" />
            <flux:input wire:model="fecha" type="date" label="{{ __('Fecha') }}" />
            <flux:select wire:model="categoria_id" label="{{ __('Categoría') }}">
                @foreach ($this->categorias as $categoria)
                    <flux:select.option value="{{ $categoria->id }}">{{ $categoria->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
