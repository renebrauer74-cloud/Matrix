<?php

namespace App\Models;

use Database\Factories\JobPositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Stelle auf der Jobs-Seite (z. B. Theke, Garderobe, Kasse).
 */
#[Fillable(['title', 'summary', 'description', 'requirements', 'employment', 'is_active', 'sort_order'])]
class JobPosition extends Model
{
    /** @use HasFactory<JobPositionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Nur Stellen, die gerade auf der Website stehen.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Reihenfolge wie auf der Jobs-Seite.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('title');
    }
}
