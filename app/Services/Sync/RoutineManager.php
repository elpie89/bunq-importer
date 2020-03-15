<?php

declare(strict_types=1);
/**
 * RoutineManager.php
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

namespace App\Services\Sync;

use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\JobStatusManager;
use Log;
use Storage;
use Str;

/**
 * Class RoutineManager.
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
    private $syncIdentifier;
    /** @var string */
    private $downloadIdentifier;

    /** @var ParseBunqDownload */
    private $bunqParser;
    /** @var GenerateTransactions */
    private $transactionGenerator;
    /** @var SendTransactions */
    private $transactionSender;

    /**
     * Collect info on the current job, hold it in memory.
     *
     * ImportRoutineManager constructor.
     *
     * @param null|string $syncIdentifier
     */
    public function __construct(?string $syncIdentifier = null)
    {
        Log::debug('Constructed RoutineManager for sync');

        $this->bunqParser           = new ParseBunqDownload;
        $this->transactionGenerator = new GenerateTransactions;
        $this->transactionSender    = new SendTransactions;

        // get line converter
        $this->allMessages = [];
        $this->allWarnings = [];
        $this->allErrors   = [];
        if (null === $syncIdentifier) {
            $this->generateSyncIdentifier();
        }
        if (null !== $syncIdentifier) {
            $this->syncIdentifier = $syncIdentifier;
        }
        $this->bunqParser->setIdentifier($this->syncIdentifier);
        $this->transactionSender->setIdentifier($this->syncIdentifier);
        $this->transactionGenerator->setIdentifier($this->syncIdentifier);

        JobStatusManager::startOrFindJob($this->syncIdentifier);
    }

    /**
     * @param string $downloadIdentifier
     */
    public function setDownloadIdentifier(string $downloadIdentifier): void
    {
        $this->downloadIdentifier = $downloadIdentifier;
    }

    /**
     * @param string $syncIdentifier
     */
    public function setSyncIdentifier(string $syncIdentifier): void
    {
        $this->syncIdentifier = $syncIdentifier;
    }

    /**
     * @return string
     */
    public function getSyncIdentifier(): string
    {
        return $this->syncIdentifier;
    }

    /**
     * @return string
     */
    public function getDownloadIdentifier(): string
    {
        return $this->downloadIdentifier;
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
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->transactionSender->setConfiguration($configuration);
        $this->transactionGenerator->setConfiguration($configuration);
    }

    /**
     * Start the import.
     */
    public function start(): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        // get JSON file from bunq download
        Log::debug('Going to parse bunq download.');
        $array = $this->bunqParser->getDownload($this->downloadIdentifier);
        Log::debug('Done parsing bunq download.');

        // generate Firefly III ready transactions:
        Log::debug('Generating Firefly III transactions.');
        $transactions = $this->transactionGenerator->getTransactions($array);
        Log::debug(sprintf('Generated %d Firefly III transactions.', count($transactions)));

        // send to Firefly III.
        Log::debug('Going to send them to Firefly III.');
        $sent = $this->transactionSender->send($transactions);
        // download and store transactions from bunq.
        // $transactions = $this->paymentList->getPaymentList();

        $count = count($sent);
        $this->mergeMessages($count);
        $this->mergeWarnings($count);
        $this->mergeErrors($count);
    }

    private function generateSyncIdentifier(): void
    {
        Log::debug('Going to generate sync job identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $syncIdentifier = Str::random(16);
            $count++;
            Log::debug(sprintf('Attempt #%d results in "%s"', $count, $syncIdentifier));
        } while ($count < 30 && $disk->exists($syncIdentifier));
        $this->syncIdentifier = $syncIdentifier;
        Log::info(sprintf('Sync job identifier is "%s"', $syncIdentifier));
    }

    /**
     * @param int $count
     */
    private function mergeErrors(int $count): void
    {
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
