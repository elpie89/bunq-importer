<?php

declare(strict_types=1);
/**
 * GenerateTransactions.php
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

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\ProgressInformation;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Account;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountRequest;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountResponse;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use Log;

/**
 * Class GenerateTransactions.
 */
class GenerateTransactions
{
    use ProgressInformation;
    /** @var array */
    private $accounts;
    /** @var Configuration */
    private $configuration;

    /** @var array */
    private $targetAccounts;
    /** @var array */
    private $targetTypes;

    /**
     * GenerateTransactions constructor.
     */
    public function __construct()
    {
        $this->targetAccounts = [];
        $this->targetTypes    = [];
    }

    /**
     *
     */
    public function collectTargetAccounts(): void
    {
        Log::debug('Going to collect all target accounts from Firefly III.');
        // send account list request to Firefly III.
        $token   = (string) config('bunq.access_token');
        $uri     = (string) config('bunq.uri');
        $request = new GetAccountsRequest($uri, $token);
        /** @var GetAccountsResponse $result */
        $result = $request->get();
        $return = [];
        $types  = [];
        /** @var Account $entry */
        foreach ($result as $entry) {
            $type = $entry->type;
            if ('reconciliation' === $type || 'initial-balance' === $type) {
                continue;
            }
            $iban = $entry->iban;
            if ('' === (string) $iban) {
                continue;
            }
            Log::debug(sprintf('Collected %s (%s) under ID #%d', $iban, $entry->type, $entry->id));
            $return[$iban] = $entry->id;
            $types[$iban]  = $entry->type;
        }
        $this->targetAccounts = $return;
        $this->targetTypes    = $types;
        Log::debug(sprintf('Collected %d accounts.', count($this->targetAccounts)));
    }

    /**
     * @param array $bunq
     *
     * @throws ImportException
     * @return array
     */
    public function getTransactions(array $bunq): array
    {
        $return = [];
        /** @var array $entry */
        foreach ($bunq as $bunqAccountId => $entries) {
            $bunqAccountId = (int) $bunqAccountId;
            app('log')->debug(sprintf('Going to parse account #%d', $bunqAccountId));
            foreach ($entries as $entry) {
                $return[] = $this->generateTransaction($bunqAccountId, $entry);
                // TODO error handling at this point.
            }
        }
        $this->addMessage(0, sprintf('Parsed %d bunq transactions for further processing.', count($return)));

        return $return;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->accounts      = $configuration->getAccounts();
    }

    /**
     * @param int   $bunqAccountId
     * @param array $entry
     *
     * @throws ImportException
     * @return array
     */
    private function generateTransaction(int $bunqAccountId, array $entry): array
    {
        $return = [
            'apply_rules'             => $this->configuration->isRules(),
            'error_if_duplicate_hash' => true,
            'transactions'            => [
                [
                    'type'          => 'withdrawal', // reverse
                    'date'          => substr($entry['created'], 0, 10),
                    'datetime'      => $entry['created'], // not used in API, only for transaction filtering.
                    'amount'        => 0,
                    'description'   => $entry['description'],
                    'order'         => 0,
                    'currency_code' => $entry['currency_code'],
                    'tags'          => [$entry['type'], $entry['sub_type']],
                ],
            ],
        ];

        // save meta:
        $return['transactions'][0]['bunq_payment_id']    = $entry['id'];
        $return['transactions'][0]['external_id']        = $entry['id'];
        $return['transactions'][0]['internal_reference'] = $bunqAccountId;

        // give "auto save" transactions a different description:
        if ('SAVINGS' === $entry['type'] && 'PAYMENT' === $entry['sub_type']) {
            $return['transactions'][0]['description'] = '(auto save transaction)';
        }

        if (1 === bccomp($entry['amount'], '0')) {
            // amount is positive: deposit or transfer. Bunq account is destination
            $return['transactions'][0]['type']   = 'deposit';
            $return['transactions'][0]['amount'] = $entry['amount'];

            // destination is bunq
            $return['transactions'][0]['destination_id'] = (int) $this->accounts[$bunqAccountId];

            // source is the other side:
            $return['transactions'][0]['source_iban'] = $entry['counter_party']['iban'];
            $return['transactions'][0]['source_name'] = $entry['counter_party']['display_name'];

            $mappedId = $this->getMappedId($entry['counter_party']['display_name'], (string) $entry['counter_party']['iban']);
            if (null !== $mappedId && 0 !== $mappedId) {
                $mappedType                             = $this->getMappedType($mappedId);
                $return['transactions'][0]['type']      = $this->getTransactionType($mappedType, 'asset');
                $return['transactions'][0]['source_id'] = $mappedId;
                unset($return['transactions'][0]['source_iban'], $return['transactions'][0]['source_name']);
            }
            //Log::debug(sprintf('Mapped ID is %s', var_export($mappedId, true)));
            // check target accounts as well:
            $iban = $entry['counter_party']['iban'];
            if ((null === $mappedId || 0 === $mappedId) && isset($this->targetAccounts[$iban])) {
                Log::debug(sprintf('Found IBAN %s in target accounts (ID %d). Type is %s', $iban, $this->targetAccounts[$iban], $this->targetTypes[$iban]));

                // type: source comes from $targetTypes, destination is asset (see above).
                $return['transactions'][0]['type']      = $this->getTransactionType($this->targetTypes[$iban] ?? '', 'asset');
                $return['transactions'][0]['source_id'] = $this->targetAccounts[$iban];
                unset($return['transactions'][0]['source_iban'], $return['transactions'][0]['source_name']);
                Log::debug(sprintf('Replaced source IBAN %s with ID #%d (type %s).', $iban, $this->targetAccounts[$iban], $this->targetTypes[$iban]));
            }
            unset($iban);
        }

        // TODO these two if statements are mirrors of each other.

        if (-1 === bccomp($entry['amount'], '0')) {
            // amount is negative: withdrawal or transfer.
            $return['transactions'][0]['amount'] = bcmul($entry['amount'], '-1');

            // source is bunq:
            $return['transactions'][0]['source_id'] = (int) $this->accounts[$bunqAccountId];

            // dest is shop
            $return['transactions'][0]['destination_iban']   = $entry['counter_party']['iban'];
            $return['transactions'][0]['destination_name']   = $entry['counter_party']['display_name'];

            $mappedId = $this->getMappedId($entry['counter_party']['display_name'], (string) $entry['counter_party']['iban']);
            //Log::debug(sprintf('Mapped ID is %s', var_export($mappedId, true)));
            if (null !== $mappedId && 0 !== $mappedId) {
                $return['transactions'][0]['destination_id'] = $mappedId;
                $mappedType                                  = $this->getMappedType($mappedId);
                $return['transactions'][0]['type']           = $this->getTransactionType('asset', $mappedType);
                unset($return['transactions'][0]['destination_iban'], $return['transactions'][0]['destination_name']);
            }

            // check target accounts as well:
            $iban = $entry['counter_party']['iban'];
            if ((null === $mappedId || 0 === $mappedId) && isset($this->targetAccounts[$iban])) {
                Log::debug(sprintf('Found IBAN %s in target accounts (ID %d). Type is %s', $iban, $this->targetAccounts[$iban], $this->targetTypes[$iban]));

                // source is always asset, destination depends on $targetType.
                $return['transactions'][0]['type']           = $this->getTransactionType('asset', $this->targetTypes[$iban] ?? '');
                $return['transactions'][0]['destination_id'] = $this->targetAccounts[$iban];
                unset($return['transactions'][0]['destination_iban'], $return['transactions'][0]['destination_name']);
                Log::debug(sprintf('Replaced source IBAN %s with ID #%d (type %s).', $iban, $this->targetAccounts[$iban], $this->targetTypes[$iban]));
            }
            unset($iban);
        }
        app('log')->debug(sprintf('Parsed bunq transaction #%d', $entry['id']));

        return $return;
    }

    /**
     * @param int $accountId
     *
     * @throws ApiHttpException
     * @return string
     */
    private function getAccountType(int $accountId): string
    {
        $uri   = (string) config('bunq.uri');
        $token = (string) config('bunq.access_token');
        app('log')->debug(sprintf('Going to download account #%d', $accountId));
        $request = new GetAccountRequest($uri, $token);
        $request->setId($accountId);
        /** @var GetAccountResponse $result */
        $result = $request->get();
        $type   = $result->getAccount()->type;

        app('log')->debug(sprintf('Discovered that account #%d is of type "%s"', $accountId, $type));

        return $type;
    }

    /**
     * @param string $name
     * @param string $iban
     *
     * @return int|null
     */
    private function getMappedId(string $name, string $iban): ?int
    {
        $fullName = $name;
        if ('' !== $iban) {
            $fullName = sprintf('%s (%s)', $name, $iban);
        }
        if (isset($this->configuration->getMapping()[$fullName])) {
            return (int) $this->configuration->getMapping()[$fullName];
        }

        return null;
    }

    /**
     * @param int $mappedId
     *
     * @return string
     */
    private function getMappedType(int $mappedId): string
    {
        if (!isset($this->configuration->getAccountTypes()[$mappedId])) {
            app('log')->warning(sprintf('Cannot find account type for Firefly III account #%d.', $mappedId));
            $accountType             = $this->getAccountType($mappedId);
            $accountTypes            = $this->configuration->getAccountTypes();
            $accountTypes[$mappedId] = $accountType;
            $this->configuration->setAccountTypes($accountTypes);

            return $accountType;
        }

        return $this->configuration->getAccountTypes()[$mappedId] ?? 'expense';
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @throws ImportException
     * @return string
     */
    private function getTransactionType(string $source, string $destination): string
    {
        $combination = sprintf('%s-%s', $source, $destination);
        switch ($combination) {
            default:
                throw new ImportException(sprintf('Unknown combination: %s and %s', $source, $destination));
            case 'asset-expense':
                return 'withdrawal';
            case 'asset-asset':
                return 'transfer';
            case 'revenue-asset':
                return 'deposit';
        }
    }
}
