<?php

declare(strict_types=1);
/**
 * ApiContextManager.php
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

namespace App\Bunq\ApiContext;

use App\Exceptions\ImportException;
use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\BadRequestException;
use bunq\Exception\BunqException;
use bunq\Util\BunqEnumApiEnvironmentType;
use Log;

/**
 * Class ApiContextManager.
 */
class ApiContextManager
{
    /**
     * @throws ImportException
     */
    public static function getApiContext(): ApiContext
    {
        $contextFile = '';
        $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
        if (config('bunq.use_sandbox')) {
            $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
            $contextFile = storage_path('context/bunq_sandbox.context');
            Log::debug('Will create sandbox bunq API Context');
        }
        if (config('bunq.use_production')) {
            $environmentType = BunqEnumApiEnvironmentType::PRODUCTION();
            $contextFile = storage_path('context/bunq_pr.context');
            Log::debug('Will create PR bunq API Context');
        }
        // restore if exists.
        if (file_exists($contextFile)) {
            $apiContext = ApiContext::restore($contextFile);
            BunqContext::loadApiContext($apiContext);
            Log::debug('Restored existing bunq context.');

            return $apiContext;
        }
        // create if not.
        $apiKey = config('bunq.api_code');
        $deviceDescription = sprintf('Firefly III bunq importer v%s', config('bunq.version'));
        $permittedIps = []; // List the real expected IPs of this device or leave empty to use the current IP
        try {
            Log::debug('Try to build API context with given parameters.');
            $apiContext = ApiContext::create(
                $environmentType,
                $apiKey,
                $deviceDescription,
                $permittedIps
            );
        } catch (BadRequestException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new ImportException($e->getMessage());
        }

        BunqContext::loadApiContext($apiContext);
        try {
            Log::debug('Trying to save API context.');
            $apiContext->save($contextFile);
        } catch (BunqException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new ImportException($e->getMessage());
        }
        Log::debug('Done! return API context.');

        return $apiContext;
    }
}
