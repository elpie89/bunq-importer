<?php
/**
 * RoutineManager.php
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

namespace App\Services\Sync;

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use Log;
use Storage;
use Str;

/**
 * Class RoutineManager
 */
class RoutineManager
{
    /** @var array */
    private $allErrors;
    /** @var array */
    private $allMessages;
    /** @var array */
    private $allWarnings;
    /** @var Configuration */
    private $configuration;
    /** @var string */
    private $identifier;

    /**
     * Collect info on the current job, hold it in memory.
     *
     * ImportRoutineManager constructor.
     *
     * @param string|null $identifier
     */
    public function __construct(string $identifier = null)
    {
        Log::debug('Constructed RoutineManager for sync');

        // get line converter
        $this->allMessages = [];
        $this->allWarnings = [];
        $this->allErrors   = [];
        if (null === $identifier) {
            $this->generateIdentifier();
        }
        if (null !== $identifier) {
            $this->identifier = $identifier;
        }
        JobStatusManager::startOrFindJob($this->identifier);
    }

    /**
     * @return array
     */
    public function getAllErrors(): array
    {
        return $this->allErrors;
    }

    /**
     * @return array
     */
    public function getAllMessages(): array
    {
        return $this->allMessages;
    }

    /**
     * @return array
     */
    public function getAllWarnings(): array
    {
        return $this->allWarnings;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param Configuration $configuration
     *
     * @throws ImportException
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;

        //        $this->paymentList   = new PaymentList($configuration);
        //        $this->paymentList->setIdentifier($this->identifier);
    }

    /**
     * Start the import.
     *
     * @throws ImportException
     */
    public function start(): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        // download and store transactions from bunq.
        // $transactions = $this->paymentList->getPaymentList();

        // $count = count($transactions);
        $count = 0;
        $this->mergeMessages($count);
        $this->mergeWarnings($count);
        $this->mergeErrors($count);
    }

    /**
     *
     */
    private function generateIdentifier(): void
    {
        Log::debug('Going to generate identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $identifier = Str::random(16);
            $count++;
            Log::debug(sprintf('Attempt #%d results in "%s"', $count, $identifier));
        } while ($count < 30 && $disk->exists($identifier));
        $this->identifier = $identifier;
        Log::info(sprintf('Job identifier is "%s"', $identifier));
    }

    /**
     * @param int $count
     */
    private function mergeErrors(int $count): void
    {
        //        $one   = $this->csvFileProcessor->getErrors();
        //        $two   = $this->lineProcessor->getErrors();
        //        $three = $this->columnValueConverter->getErrors();
        //        $four  = $this->pseudoTransactionProcessor->getErrors();
        //        $five  = $this->apiSubmitter->getErrors();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge(
                $one[$i] ?? [],
                $two[$i] ?? [],
                $three[$i] ?? [],
                $four[$i] ?? [],
                $five[$i] ?? []
            );
        }

        $this->allErrors = $total;
    }

    /**
     * @param int $count
     */
    private function mergeMessages(int $count): void
    {
        //$one   = $this->csvFileProcessor->getMessages();
        //$two   = $this->lineProcessor->getMessages();
        //$three = $this->columnValueConverter->getMessages();
        //$four  = $this->pseudoTransactionProcessor->getMessages();
        //$five  = $this->apiSubmitter->getMessages();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge(
                $one[$i] ?? [],
                $two[$i] ?? [],
                $three[$i] ?? [],
                $four[$i] ?? [],
                $five[$i] ?? []
            );
        }

        $this->allMessages = $total;
    }

    /**
     * @param int $count
     */
    private function mergeWarnings(int $count): void
    {
        //        $one   = $this->csvFileProcessor->getWarnings();
        //        $two   = $this->lineProcessor->getWarnings();
        //        $three = $this->columnValueConverter->getWarnings();
        //        $four  = $this->pseudoTransactionProcessor->getWarnings();
        //        $five  = $this->apiSubmitter->getWarnings();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge(
                $one[$i] ?? [],
                $two[$i] ?? [],
                $three[$i] ?? [],
                $four[$i] ?? [],
                $five[$i] ?? []
            );
        }

        $this->allWarnings = $total;
    }
}