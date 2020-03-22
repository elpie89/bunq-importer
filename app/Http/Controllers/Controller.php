<?php
/**
 * Controller.php
 * Copyright (c) 2020 james@firefly-iii.org.
 *
 * This file is part of the Firefly III bunq importer
 * (https://github.com/firefly-iii/bunq-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Artisan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

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
            'FIREFLY_III_URI'          => 'bunq.uri',
            'BUNQ_API_CODE'            => 'bunq.api_code',
            'BUNQ_API_URI'             => 'bunq.api_uri',
        ];
        foreach ($variables as $env => $config) {
            $value = (string) config($config);
            if ('' === $value) {
                echo sprintf('Please set a valid value for "%s" in the env file.', $env);
                Artisan::call('config:clear');
                exit;
            }
        }
        if(
            false === strpos(config('bunq.uri'),'http://') ||
            false === strpos(config('bunq.uri'),'https://')
        ) {
            echo 'The URL to your Firefly III instance must begin with "http://" or "https://".';
            exit;
        }

        app('view')->share('version', config('bunq.version'));
    }
}
