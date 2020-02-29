<?php
/**
 * ApiContextManager.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\BadRequestException;
use bunq\Exception\BunqException;
use bunq\Util\BunqEnumApiEnvironmentType;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;

/**
 * Class ApiContextManager
 */
class ApiContextManager
{
    /**
     *
     * @throws ApiHttpException
     */
    public static function getApiContext(): ApiContext
    {
        $contextFile     = '';
        $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
        if (config('bunq.use_sandbox')) {
            $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
            $contextFile     = storage_path('context/bunq_sandbox.context');
        }
        if (config('bunq.use_production')) {
            $environmentType = BunqEnumApiEnvironmentType::PRODUCTION();
            $contextFile     = storage_path('context/bunq_pr.context');
        }
        // restore if exists.
        if (file_exists($contextFile)) {
            $apiContext = ApiContext::restore($contextFile);
            BunqContext::loadApiContext($apiContext);

            return $apiContext;
        }
        // create if not.
        $apiKey            = config('bunq.api_code');
        $deviceDescription = sprintf('Firefly III bunq importer v%s', config('bunq.version'));
        $permittedIps      = []; // List the real expected IPs of this device or leave empty to use the current IP
        try {
            $apiContext = ApiContext::create(
                $environmentType,
                $apiKey,
                $deviceDescription,
                $permittedIps
            );
        } catch (BadRequestException $e) {
            throw new ApiHttpException($e->getMessage());
        }

        BunqContext::loadApiContext($apiContext);
        try {
            $apiContext->save($contextFile);
        } catch (BunqException $e) {
            throw new ApiHttpException($e->getMessage());
        }

        return $apiContext;
    }

}