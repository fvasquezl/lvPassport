<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeName(Builder $query, string $value): void
    {
        $query->where('name', 'LIKE', "%$value%");
    }

    public function scopeSlug(Builder $query, string $value): void
    {
        $query->where('slug', 'LIKE', "%$value%");
    }

    public function scopeSearch(Builder $query, string $values): void
    {
        Str::of($values)->explode(' ')->each(function ($value) use ($query) {
            $query->orWhere('name', 'LIKE', "%$value%")
                ->orWhere('slug', 'LIKE', "%$value%");
        });
    }
}
