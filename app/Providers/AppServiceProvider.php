<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\OpenAI\Contracts\ClientContract::class, function ($app) {
            $config = $app['config']->get('services.openai');

            if (empty($config['key'])) {
                throw new \RuntimeException('OpenAI API key is not configured.');
            }

            $factory = (new \OpenAI\Factory())
                ->withApiKey($config['key']);

            $handler = \GuzzleHttp\HandlerStack::create();
            $maxRetries = (int) ($config['max_retries'] ?? 3);
            $retryableStatus = [408, 409, 429, 500, 502, 503, 504];
            $delayMs = (int) ($config['retry_delay_ms'] ?? 200);

            $handler->push(\GuzzleHttp\Middleware::retry(
                function (int $retries, \Psr\Http\Message\RequestInterface $request, ?\Psr\Http\Message\ResponseInterface $response, ?\Throwable $exception) use ($maxRetries, $retryableStatus) {
                    if ($retries >= $maxRetries) {
                        return false;
                    }

                    if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                        return true;
                    }

                    if ($response) {
                        return in_array($response->getStatusCode(), $retryableStatus, true);
                    }

                    return false;
                },
                function (int $retries) use ($delayMs) {
                    $multiplier = max($retries, 1);

                    return $delayMs * (2 ** ($multiplier - 1));
                }
            ));

            $httpClient = new \GuzzleHttp\Client([
                'handler' => $handler,
                'timeout' => (float) ($config['timeout'] ?? 60),
                'connect_timeout' => (float) ($config['connect_timeout'] ?? 10),
            ]);

            $factory->withHttpClient($httpClient);

            if (! empty($config['base_uri'])) {
                $factory->withBaseUri($config['base_uri']);
            }

            return $factory->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
