<?php

namespace faisalijaz\laravelpassportsocialauthgrant\src;

use faisalijaz\laravelpassportsocialauthgrant\src\Bridge\Grant\SocialGrant;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class SocialAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->afterResolving(AuthorizationServer::class, function (AuthorizationServer $server) {
            $grant = $this->makeSocialGrant();
            $server->enableGrantType($grant, Passport::tokensExpireIn());
        });

        $this->app->make('faisalijaz\laravelpassportsocialauthgrant\src\Http\SocialAuth');
    }


    /**
     * @return SocialGrant
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function makeSocialGrant()
    {
        $grant = new SocialGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }
}
