<?php


/**
 * SendTransactions.php
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
 * SendTransactions.php

 */

namespace App\Services\Sync;

use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\ProgressInformation;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Transaction;
use GrumpyDictator\FFIIIApiSupport\Model\TransactionGroup;
use GrumpyDictator\FFIIIApiSupport\Request\PostTransactionRequest;
use GrumpyDictator\FFIIIApiSupport\Response\PostTransactionResponse;
use GrumpyDictator\FFIIIApiSupport\Response\ValidationErrorResponse;

/**
 * Class SendTransactions.
 */
class SendTransactions
{
    use ProgressInformation;

    private Configuration $configuration;
    private string        $rootURL;

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function send(array $transactions): array
    {
        $url   = (string) config('bunq.url');
        $token = (string) config('bunq.access_token');

        $this->rootURL = config('bunq.url') ?? '';
        if ('' !== (string) config('bunq.vanity_url')) {
            $this->rootURL = config('bunq.vanity_url');
        }
        app('log')->debug(sprintf('The root URL is "%s"', $this->rootURL));

        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('Trying to send transaction #%d', $index), $transaction);
            $this->sendTransaction($url, $token, $index, $transaction);
        }

        return [];
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $url
     * @param string $token
     * @param int    $index
     * @param array  $transaction
     *
     * @return array
     */
    private function sendTransaction(string $url, string $token, int $index, array $transaction): array
    {
        $request = new PostTransactionRequest($url, $token);
        $request->setBody($transaction);
        try {
            /** @var PostTransactionResponse $response */
            $response = $request->post();
        } catch (ApiHttpException $e) {
            app('log')->error($e->getMessage());
            $this->addError($index, $e->getMessage());

            return [];
        }
        if ($response instanceof ValidationErrorResponse) {
            /** ValidationErrorResponse $error */
            foreach ($response->errors->getMessages() as $errors) {
                foreach ($errors as $error) {
                    // +1 so the line numbers match.
                    $this->addError($index + 1, $error);
                    app('log')->error(sprintf('Could not create transaction: %s', $error), $transaction);
                }
            }

            return [];
        }
        /** @var TransactionGroup|null $group */
        $group = $response->getTransactionGroup();
        if (null === $group) {
            $this->addError($index + 1, 'Group is unexpectedly NULL.');

            return [];
        }
        $groupId  = $group->id;
        $groupUrl = (string) sprintf('%s/transactions/show/%d', $this->rootURL, $groupId);

        /** @var Transaction $tr */
        foreach ($group->transactions as $tr) {
            $this->addMessage(
                $index + 1,
                sprintf(
                    'Created transaction #%d: <a href="%s">%s</a> (%s %s)', $groupId, $groupUrl, $tr->description, $tr->currencyCode,
                    round((float) $tr->amount, 2)
                )
            );
        }

        return [];
    }
}
