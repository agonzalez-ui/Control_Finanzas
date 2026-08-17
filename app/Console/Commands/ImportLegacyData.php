<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa los datos reales de la app legacy (finanza) desde una base de datos
 * de staging (`finanza_legacy_staging`, cargada previamente con el dump
 * u500478428_finanzas_app.sql vía `mysql ... < dump.sql`) hacia el esquema
 * nuevo de control_finanza, preservando los IDs originales para que todas
 * las relaciones (gastos->categorías, ahorros->cuentas, etc.) sigan intactas.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'finanza:import-legacy {--fresh : Vacía las tablas de destino antes de importar}';

    protected $description = 'Importa los datos de la app legacy (finanza) desde la BD de staging finanza_legacy_staging';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        try {
            $legacy->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se pudo conectar a la BD de staging "finanza_legacy_staging": '.$e->getMessage());
            $this->line('Créala e impórtale el dump legacy antes de correr este comando.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncateDestinationTables();
        }

        DB::transaction(function () use ($legacy) {
            $this->importUsers($legacy);
            $this->importCategories($legacy);
            $this->importSalaries($legacy);
            $this->importSubscriptions($legacy);
            $this->importSavingAccounts($legacy);
            $this->importExpenses($legacy);
            $this->importSavingMovements($legacy);
            $this->importGoals($legacy);
            $this->importMonthlyHistory($legacy);
        });

        $this->newLine();
        $this->info('Importación completa. Conteos en destino:');
        $this->reportCounts();

        return self::SUCCESS;
    }

    private function truncateDestinationTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (array_reverse($this->tableMap()) as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function tableMap(): array
    {
        return [
            'users', 'categories', 'salaries', 'subscriptions',
            'saving_accounts', 'expenses', 'saving_movements', 'goals', 'monthly_history',
        ];
    }

    private function importUsers($legacy): void
    {
        $rows = $legacy->table('usuarios')->get();

        foreach ($rows as $row) {
            DB::table('users')->updateOrInsert(
                ['id' => $row->id],
                [
                    'name' => $row->nombre,
                    'email' => $row->email,
                    'password' => $row->password,
                    'email_verified_at' => now(),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ]
            );
        }

        $this->line("usuarios -> users: {$rows->count()}");
    }

    private function importCategories($legacy): void
    {
        $rows = $legacy->table('categorias')->get();

        foreach ($rows as $row) {
            DB::table('categories')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'name' => $row->nombre,
                    'color' => $row->color,
                    'created_at' => now(),
                ]
            );
        }

        $this->line("categorias -> categories: {$rows->count()}");
    }

    private function importSalaries($legacy): void
    {
        $rows = $legacy->table('salarios')->get();

        foreach ($rows as $row) {
            DB::table('salaries')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'amount' => $row->monto,
                    'month' => $row->mes,
                    'year' => $row->anio,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("salarios -> salaries: {$rows->count()}");
    }

    private function importSubscriptions($legacy): void
    {
        $rows = $legacy->table('suscripciones')->get();

        foreach ($rows as $row) {
            DB::table('subscriptions')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'name' => $row->nombre,
                    'amount' => $row->monto,
                    'billing_day' => $row->dia_cobro,
                    'active' => (bool) $row->activa,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("suscripciones -> subscriptions: {$rows->count()}");
    }

    private function importSavingAccounts($legacy): void
    {
        $rows = $legacy->table('cuentas_ahorro')->get();

        foreach ($rows as $row) {
            DB::table('saving_accounts')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'name' => $row->nombre,
                    'type' => $row->tipo,
                    'balance' => $row->saldo,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("cuentas_ahorro -> saving_accounts: {$rows->count()}");
    }

    private function importExpenses($legacy): void
    {
        $rows = $legacy->table('gastos')->get();

        foreach ($rows as $row) {
            DB::table('expenses')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'category_id' => $row->categoria_id,
                    'description' => $row->descripcion,
                    'amount' => $row->monto,
                    'date' => $row->fecha,
                    'biweek' => $row->quincena,
                    'month' => $row->mes,
                    'year' => $row->anio,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("gastos -> expenses: {$rows->count()}");
    }

    private function importSavingMovements($legacy): void
    {
        $rows = $legacy->table('ahorros')->get();

        foreach ($rows as $row) {
            DB::table('saving_movements')->updateOrInsert(
                ['id' => $row->id],
                [
                    'saving_account_id' => $row->cuenta_ahorro_id,
                    'user_id' => $row->usuario_id,
                    'amount' => $row->monto,
                    'description' => $row->descripcion,
                    'date' => $row->fecha,
                    'biweek' => $row->quincena,
                    'month' => $row->mes,
                    'year' => $row->anio,
                    'affects_available' => (bool) $row->afecta_disponible,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("ahorros -> saving_movements: {$rows->count()}");
    }

    private function importGoals($legacy): void
    {
        $rows = $legacy->table('metas')->get();

        foreach ($rows as $row) {
            DB::table('goals')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'name' => $row->nombre,
                    'description' => $row->descripcion,
                    'target_amount' => $row->monto_objetivo,
                    'current_amount' => $row->monto_actual,
                    'due_date' => $row->fecha_limite,
                    'completed' => (bool) $row->completada,
                    'created_at' => $row->created_at,
                ]
            );
        }

        $this->line("metas -> goals: {$rows->count()}");
    }

    private function importMonthlyHistory($legacy): void
    {
        $rows = $legacy->table('historial_mensual')->get();

        foreach ($rows as $row) {
            DB::table('monthly_history')->updateOrInsert(
                ['id' => $row->id],
                [
                    'user_id' => $row->usuario_id,
                    'month' => $row->mes,
                    'year' => $row->anio,
                    'salary' => $row->salario,
                    'total_expenses' => $row->total_gastos,
                    'total_savings' => $row->total_ahorros,
                    'total_subscriptions' => $row->total_subs,
                    'closed_at' => $row->cerrado_en,
                ]
            );
        }

        $this->line("historial_mensual -> monthly_history: {$rows->count()}");
    }

    private function reportCounts(): void
    {
        foreach ($this->tableMap() as $table) {
            $this->line(str_pad($table, 20).DB::table($table)->count());
        }
    }
}
