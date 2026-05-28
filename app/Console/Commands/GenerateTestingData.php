<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('app:generate-testing-data {--force : Skip confirmation}')]
#[Description('Create test categories, articles and user fvasquez@local.com with permissions')]
class GenerateTestingData extends Command
{
    use ConfirmableTrait;
    /**
     * Execute the console command.
     */
    public function handle()
    {
         if (! $this->confirmToProceed()) {
            return 1;
        }

        User::query()->delete();
        Article::query()->delete();
        Category::query()->delete();

        $categories = collect([
            'laravel' => 'Laravel',
            'vuejs' => 'VueJS',
            'javascript' => 'Javascript',
            'nextjs' => 'NexJS',
            'python' => 'Python',
            
            'php' => 'PHP',
            'typescript' => 'TypeScript',
            'other' => 'Other',
        ])->map(fn (string $name, string $slug) => Category::factory()->create([
            'name' => $name,
            'slug' => $slug,
        ]));

        $user = User::factory()->hasArticles(1, [
            'category_id' => $categories->random()->id,
        ])->create([
            'name' => 'Faustino',
            'email' => 'fvasquez@local.com',
        ]);

        $articles = Article::factory()->count(14)
            ->sequence(fn () => [
                'category_id' => $categories->random()->id,
            ])->create();

        $this->info('User UUID:');
        $this->line($user->id);

        Artisan::call('generate:roles');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncRoles([Role::findByName('editor', 'api')]);

        $this->info('Token:');
        $this->line($user->createToken('fvasquez')->accessToken);

        $this->info('Article ID:');
        $this->line($user->articles->first()->slug);

        $this->info('Category ID:');
        $this->line($articles->first()->category->slug);
    }
}
