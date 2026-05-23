<?php

namespace App\JsonApi\V1\Categories;

use App\Models\Category;
use App\Rules\Slug;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CategoryRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $slug = [
            'required',
            'alpha_dash',
            new Slug,
            Rule::unique(Category::class, 'slug')->ignore($this->model()),
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slug,
        ];
    }
}
