<?php
/**
 * bunq.php
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

return [
    'version'         => '1.0.0-beta.2',
    'access_token'    => env('FIREFLY_III_ACCESS_TOKEN', ''),
    'uri'             => env('FIREFLY_III_URI', ''),
    'api_code'        => env('BUNQ_API_CODE', ''),
    'api_uri'         => env('BUNQ_API_URI', ''),
    'minimum_version' => '5.1.1',
    'use_sandbox'     => 'https://api.bunq.com' !== env('BUNQ_API_URI', ''),
    'use_production'  => 'https://api.bunq.com' === env('BUNQ_API_URI', ''),
];
