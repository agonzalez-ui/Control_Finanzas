<?php

namespace App\Support;

/**
 * Clasifica un gasto en Suscripción / Pago fijo / Variable según palabras
 * clave en su categoría y descripción (heurística, sin columna dedicada
 * en la base de datos — igual que en la app legacy).
 */
class ExpenseClassifier
{
    private const SUBSCRIPTION_TERMS = [
        'chatgpt', 'claude', 'youtube', 'premium', 'spotify', 'netflix',
        'disney', 'hbo', 'amazon prime', 'icloud', 'google one',
        'suscripcion', 'subscription',
    ];

    private const FIXED_TERMS = [
        'alquiler', 'renta', 'internet', 'telefono', 'celular',
        'agua', 'luz', 'electricidad', 'seguro', 'prestamo', 'colegio',
    ];

    public static function meta(string $category = '', string $description = ''): array
    {
        $text = mb_strtolower(trim($category.' '.$description));

        foreach (self::SUBSCRIPTION_TERMS as $term) {
            if ($term !== '' && str_contains($text, $term)) {
                return ['label' => 'Suscripción', 'type' => 'suscripcion', 'bg' => '#fef2f2', 'color' => '#991b1b'];
            }
        }

        foreach (self::FIXED_TERMS as $term) {
            if ($term !== '' && str_contains($text, $term)) {
                return ['label' => 'Pago fijo', 'type' => 'fijo', 'bg' => '#fffbeb', 'color' => '#92400e'];
            }
        }

        return ['label' => 'Variable', 'type' => 'variable', 'bg' => '#eff6ff', 'color' => '#1d4ed8'];
    }

    public static function matches(string $category, string $description, string $type = 'todos'): bool
    {
        if ($type === 'todos' || $type === '') {
            return true;
        }

        return self::meta($category, $description)['type'] === $type;
    }
}
