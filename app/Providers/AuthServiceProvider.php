<?php

namespace App\Providers;

use App\Models\Joueur;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::viaRequest('custom-auth', function ($request) {
            // Any custom user-lookup logic here. For example:
            if ($request->header('channel') == "presence") {
                return Joueur::find($request["id"]);

        }
        });
    }
}
