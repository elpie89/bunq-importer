<?php
/**
 * Payment.php
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

declare(strict_types=1);

namespace App\Bunq\Requests;


use App\Exceptions\ImportException;
use bunq\Model\Generated\Endpoint\BunqResponsePaymentList;
use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use Exception;
use Log;

/**
 * Class Payment
 *
 * @codeCoverageIgnore
 */
class Payment
{
    /**
     * @param int|null   $monetaryAccountId
     * @param array|null $params
     * @param array|null $customHeaders
     *
     * @return BunqResponsePaymentList
     * @throws ImportException
     */
    public function listing(int $monetaryAccountId = null, array $params = null, array $customHeaders = null): BunqResponsePaymentList
    {
        Log::debug('Now in Payment::listing()');
        $monetaryAccountId = $monetaryAccountId ?? 0;
        $params            = $params ?? [];
        $customHeaders     = $customHeaders ?? [];
        try {
            $result = BunqPayment::listing($monetaryAccountId, $params, $customHeaders);
        } catch (Exception $e) {
            Log::error(sprintf('Exception: %s', $e->getMessage()));
            Log::error($e->getTraceAsString());
            throw new ImportException($e->getMessage());
        }

        return $result;
    }

}
