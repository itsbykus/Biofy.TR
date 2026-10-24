<?php

namespace App\Providers;

use App\View\Composers\LayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /** One composer per application instance: brand data is built once per request. */
    public array $singletons = [LayoutComposer::class => LayoutComposer::class];

    public function boot(): void
    {
        View::composer(['layouts.*', 'site.*', 'auth.*', 'errors.*', 'components.logo', 'profile.show'], LayoutComposer::class);
    }
}
