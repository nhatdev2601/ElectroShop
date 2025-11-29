<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Category;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Share categories chỉ với layouts để dùng trong header menu
        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $headerCategories = Category::where('category_is_display', 1)
                ->orderBy('category_id', 'asc')
                ->limit(12)
                ->get();

            $view->with('headerCategories', $headerCategories);
            
            // Share admin info for admin layout
            try {
                if (request()->is('admin/*')) {
                    $token = request()->cookie('admin_token') 
                        ?? request()->header('X-Admin-Token')
                        ?? request()->header('Authorization');
                    
                    if ($token) {
                        $token = str_replace('Bearer ', '', $token);
                        \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token);
                        $admin = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::authenticate();
                        $view->with('currentAdmin', $admin);
                    }
                }
            } catch (\Exception $e) {
                // Silently fail, admin will be null
            }
        });
    }
}
