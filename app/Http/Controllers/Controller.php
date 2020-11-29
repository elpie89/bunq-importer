<?php
/**
 * Controller.php

 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;

/**
 * Class Controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $variables = [
            'FIREFLY_III_ACCESS_TOKEN' => 'bunq.access_token',
            'FIREFLY_III_URL'          => 'bunq.url',
            'BUNQ_API_CODE'            => 'bunq.api_code',
            'BUNQ_API_URL'             => 'bunq.api_url',
        ];
        foreach ($variables as $env => $config) {
            $value = (string) config($config);
            if ('' === $value) {
                echo sprintf('Please set a valid value for "%s" in the env file.', $env);
                Artisan::call('config:clear');
                exit;
            }
        }
        if (
            false === strpos(config('bunq.url'), 'http://')
            && false === strpos(config('bunq.url'), 'https://')
        ) {
            echo 'The URL to your Firefly III instance must begin with "http://" or "https://".';
            exit;
        }

        app('view')->share('version', config('bunq.version'));
    }
}
