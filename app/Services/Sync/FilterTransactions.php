<?php

/**
 * FilterTransactions.php
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


namespace App\Services\Sync;

use App\Services\Sync\JobStatus\ProgressInformation;
use Log;

/**
 * Class FilterTransactions
 */
class FilterTransactions
{
    use ProgressInformation;

    private array $keys;

    public function __construct()
    {
        $this->keys = [];
    }

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function filter(array $transactions): array
    {
        $start  = count($transactions);
        $return = [];
        /** @var array $transaction */
        foreach ($transactions as $group) {
            $transaction = $group['transactions'][0];
            if ('transfer' !== $transaction['type']) {
                $return[] = $group;
                continue;
            }
            // if it's a transfer, source + destination are ID.
            // amount always positive.
            // use datetime
            // use description:
            $format = '%d/%d(%s)%s@%s';
            // account ID's in the right order
            $low         = min($transaction['source_id'], $transaction['destination_id']);
            $high        = max($transaction['source_id'], $transaction['destination_id']);
            $amount      = (string) round($transaction['amount'], 2);
            $datetime    = substr($transaction['datetime'], 0, 19); // shave off the milli seconds.
            $description = $transaction['description'];
            $key         = sprintf($format, $low, $high, $amount, $description, $datetime);
            Log::debug(sprintf('Key is "%s"', $key));
            if (isset($this->keys[$key])) {
                // transaction is double:
                Log::info(sprintf('Key is already used: %s', $this->keys[$key]));
                continue;
            }

            if (!isset($this->keys[$key])) {
                $this->keys[$key] = sprintf('bunq transaction ID #%s', $transaction['bunq_payment_id']);

                // unset date time because the API doesn't use it anyway:
                unset($group['transactions'][0]['datetime']);
                $return[] = $group;
            }
        }
        $end = count($return);
        $this->addMessage(0, sprintf('Filtered down from %d (possibly duplicate) entries to %d unique transactions.', $start, $end));

        return $return;
    }

}
