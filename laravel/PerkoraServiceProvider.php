<?php

declare(strict_types=1);

namespace Perkora\SDK\Laravel;

use Illuminate\Support\ServiceProvider;
use Perkora\SDK\PerkoraClient;

final class PerkoraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/perkora.php',
            'perkora'
        );

        $this->app->singleton('perkora', function ($app): PerkoraClient {
            $config = $app['config']->get('perkora', []);

            foreach (['base_url', 'key_id', 'secret'] as $key) {
                if (!is_string($config[$key] ?? null) || trim($config[$key]) === '') {
                    throw new \InvalidArgumentException(
                        "Perkora configuration [{$key}] is missing. Check your environment variables."
                    );
                }
            }

            return PerkoraClient::create(
                $config['base_url'],
                $config['key_id'],
                $config['secret'],
                (int) ($config['timeout'] ?? 20)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/perkora.php' => config_path('perkora.php'),
        ], 'perkora-config');
    }
}
