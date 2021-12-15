<?php

namespace Najm\AclPolicyMaker;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->commands([
            Commands\AclPolicyMaker::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            Commands\AclPolicyMaker::class
        ];
    }
}