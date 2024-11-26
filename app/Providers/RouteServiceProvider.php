<?php

namespace App\Providers;

use App\Models\Vendor;
use App\Models\Section;
use App\Models\Category;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Observers\ProductItemObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\TextInput;
use Illuminate\Cache\RateLimiting\Limit;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Forms\Components\DateTimePicker;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->configureRouteModelBindings();

        $this->defineRoutes();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure the route model bindings for the application.
     */
    protected function configureRouteModelBindings(): void
    {
        Route::pattern('sectionId', '[0-9]+');
        Route::pattern('categoryId', '[0-9]+');
        Route::pattern('vendorId', '[0-9]+');
        Route::pattern('productId', '[0-9]+');

        // Route::bind('sectionId', function (string $id) {
        //     return Section::where('id', $id)->exists() ? $id : abort(404, 'Section not found');
        // });

        // Route::bind('vendorId', function (string $id) {
        //     return Vendor::where('id', $id)->exists() ? $id : abort(404, 'Vendor not found');
        // });

        // Route::bind('categoryId', function (string $id) {
        //     return Category::where('id', $id)->exists() ? $id : abort(404, 'Category not found');
        // });

        // Route::bind('productId', function (string $id) {
        //     return ProductItem::where('id', $id)->exists() ? $id : abort(404, 'Product not found');
        // });
    }

    /**
     * Define the routes for the application.
     */
    protected function defineRoutes(): void
    {
        //
    }
}
