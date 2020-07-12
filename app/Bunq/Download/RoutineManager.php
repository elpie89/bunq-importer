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

declare(strict_types=1);
/**
 * RoutineManager.php

 */

namespace App\Bunq\Download;

use App\Bunq\Download\JobStatus\JobStatusManager;
use App\Bunq\Requests\PaymentList;
use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class ImportRoutineManager.
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
    private $downloadIdentifier;
    /** @var PaymentList */
    private $paymentList;

    /**
     * Collect info on the current job, hold it in memory.
     *
     * TODO the action to start or find the download job should not be in the constructor but in the start() method.
     *
     * ImportRoutineManager constructor.
     *
     * @param string|null $downloadIdentifier
     */
    public function __construct(string $downloadIdentifier = null)
    {
        app('log')->debug('Constructed ImportRoutineManager');

        // get line converter
        $this->allMessages = [];
        $this->allWarnings = [];
        $this->allErrors   = [];
        if (null === $downloadIdentifier) {
            app('log')->debug('Was given no download identifier, will generate one.');
            $this->generateDownloadIdentifier();
        }
        if (null !== $downloadIdentifier) {
            app('log')->debug('Was given download identifier, will use it.');
            $this->downloadIdentifier = $downloadIdentifier;
        }
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
    public function getDownloadIdentifier(): string
    {
        return $this->downloadIdentifier;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->paymentList   = new PaymentList($configuration);
        $this->paymentList->setDownloadIdentifier($this->downloadIdentifier);

        app('log')->debug(sprintf('Download ImportRoutineManager: created new payment list with download identifier "%s"', $this->downloadIdentifier));
    }

    public function start(): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        // download and store transactions from bunq.
        try {
            $transactions = $this->paymentList->getPaymentList();
        } catch (ImportException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }

        $count = count($transactions);
        $this->mergeMessages($count);
        $this->mergeWarnings($count);
        $this->mergeErrors($count);
    }

    private function generateDownloadIdentifier(): void
    {
        app('log')->debug('Going to generate download identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $downloadIdentifier = Str::random(16);
            $count++;
            app('log')->debug(sprintf('Attempt #%d results in "%s"', $count, $downloadIdentifier));
        } while ($count < 30 && $disk->exists($downloadIdentifier));
        $this->downloadIdentifier = $downloadIdentifier;
        app('log')->info(sprintf('Download job identifier is "%s"', $downloadIdentifier));
    }

    /**
     * @param int $count
     */
    private function mergeErrors(int $count): void
    {
        $one   = $this->paymentList->getErrors();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge($one[$i] ?? []);
        }

        $this->allErrors = $total;
    }

    /**
     * @param int $count
     */
    private function mergeMessages(int $count): void
    {
        $one   = $this->paymentList->getMessages();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge($one[$i] ?? []);
        }

        $this->allMessages = $total;
    }

    /**
     * @param int $count
     */
    private function mergeWarnings(int $count): void
    {
        $one   = $this->paymentList->getWarnings();
        $total = [];
        for ($i = 0; $i < $count; $i++) {
            $total[$i] = array_merge($one[$i] ?? []);
        }

        $this->allWarnings = $total;
    }
}
