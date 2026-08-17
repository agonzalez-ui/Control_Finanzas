<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    // -- Relaciones de finanzas ---------------------------------------

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function savingAccounts(): HasMany
    {
        return $this->hasMany(SavingAccount::class);
    }

    public function savingMovements(): HasMany
    {
        return $this->hasMany(SavingMovement::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function monthlyHistory(): HasMany
    {
        return $this->hasMany(MonthlyHistory::class);
    }

    /** Categorías por defecto que se crean para toda cuenta nueva. */
    public static function defaultCategories(): array
    {
        return [
            ['name' => 'Alimentación', 'color' => '#f59e0b'],
            ['name' => 'Transporte', 'color' => '#3b82f6'],
            ['name' => 'Salud', 'color' => '#10b981'],
            ['name' => 'Entretenimiento', 'color' => '#8b5cf6'],
            ['name' => 'Educación', 'color' => '#06b6d4'],
            ['name' => 'Hogar', 'color' => '#f97316'],
            ['name' => 'Ropa', 'color' => '#ec4899'],
            ['name' => 'Otros', 'color' => '#64748b'],
        ];
    }
}
