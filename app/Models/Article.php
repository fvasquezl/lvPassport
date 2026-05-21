<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
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
            'category_id' => 'integer',
            'user_id' => 'string',
        ];
    }

    public array $jsonApiTypes = ['user' => 'authors'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCategories(Builder $query, $values): void
    {
        $query->whereHas('category', function ($q) use ($values) {
            $q->whereIn('slug', explode(',', $values));
        });
    }
    public function scopeAuthors(Builder $query, $values): void
    {
        $query->whereHas('user', function ($q) use ($values) {
            $q->whereIn('name', explode(',', $values));
        });
    }
}
