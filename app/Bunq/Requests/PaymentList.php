<?php
/**
 * PaymentList.php
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

namespace App\Bunq\Requests;

use App\Bunq\ApiContext\ApiContextManager;
use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use Carbon\Carbon;
use Log;
use Storage;

/**
 * Class PaymentList
 */
class PaymentList
{
    /** @var Configuration */
    private $configuration;
    /** @var int */
    private $count;
    /** @var string */
    private $identifier;
    /** @var Carbon */
    private $notAfter;
    /** @var Carbon */
    private $notBefore;

    /**
     * PaymentList constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->count         = 0;
        $this->notBefore     = null === $configuration->getDateNotBefore() ? null : Carbon::createFromFormat('Y-m-d', $configuration->getDateNotBefore());
        $this->notAfter      = null === $configuration->getDateNotAfter() ? null : Carbon::createFromFormat('Y-m-d', $configuration->getDateNotAfter());
        $this->notBefore->startOfDay();
        $this->notAfter->endOfDay();
    }

    /**
     *
     * @throws ImportException
     */
    public function getPaymentList(): array
    {
        Log::debug('Start of getPaymentList()');

        if ($this->hasDownload()) {
            Log::info('Already downloaded content for this job. Return it.');

            return $this->getDownload();
        }

        $return = [];
        // todo test what happens if we forget this
        ApiContextManager::getApiContext();

        foreach (array_keys($this->configuration->getAccounts()) as $bunqAccountId) {
            $bunqAccountId          = (int)$bunqAccountId;
            $return[$bunqAccountId] = $this->getForAccount($bunqAccountId);
        }

        // store the result somewhere so it can be processed easily.
        $this->storeDownload($return);

        return $return;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getDownload(): array
    {
        $disk    = Storage::disk('downloads');
        $content = $disk->get($this->identifier);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param int $bunqAccountId
     *
     * @return array
     * @throws ImportException
     */
    private function getForAccount(int $bunqAccountId): array
    {
        Log::debug(sprintf('Now in getForAccount(%d)', $bunqAccountId));
        $hasMoreTransactions = true;
        $olderId             = null;
        $return              = [];

        /*
         * Do a loop during which we run:
         */
        while ($hasMoreTransactions && $this->count <= 45) {
            Log::debug(sprintf('Now in loop #%d for account %d', $this->count, $bunqAccountId));
            $previousLoop = count($return);
            /*
             * Send request to bunq.
             */
            /** @var Payment $paymentRequest */
            $paymentRequest = app(Payment::class);
            $params         = ['count' => 197, 'older_id' => $olderId];
            $response       = $paymentRequest->listing($bunqAccountId, $params);
            $pagination     = $response->getPagination();
            Log::debug('Params for the request to bunq are: ', $params);

            /*
             * If pagination is not null, we can go back even further.
             */
            if (null !== $pagination) {
                $olderId = $pagination->getOlderId();
                Log::debug(sprintf('Pagination object is not null, new olderID is "%s"', $olderId));
            }

            /*
             * Loop the results from bunq
             */
            Log::debug('Now looping results from bunq...');
            /** @var BunqPayment $payment */
            foreach ($response->getValue() as $index => $payment) {
                Log::debug(sprintf('Going to process payment on index #%d', $index));
                $array = $this->processBunqPayment($index, $payment);
                if (null !== $array) {
                    $return[] = $array;
                }
            }

            /*
             * After the loop, check if must loop again.
             */
            Log::debug(sprintf('Count of return is now %d', count($return)));
            $this->count++;
            if (null === $olderId) {
                Log::debug('Older ID is NULL, so stop looping cause we are done!');
                $hasMoreTransactions = false;
            }

            if (null === $pagination) {
                Log::debug('No pagination object, stop looping.');
            }
            if ($previousLoop === count($return)) {
                Log::info('No new transactions were added to the array.');
                $hasMoreTransactions = false;
            }

            // sleep 2 seconds to prevent hammering bunq.
            sleep(2);
        }
        // store newest and oldest tranasction ID to be used later:
        Log::info(sprintf('Downloaded and parsed %d transactions from bunq.', count($return)));

        return $return;
    }

    /**
     * @return bool
     */
    private function hasDownload(): bool
    {
        $disk = Storage::disk('downloads');

        return $disk->exists($this->identifier);
    }

    /**
     * @param int         $index
     * @param BunqPayment $payment
     *
     * @return array
     */
    private function processBunqPayment(int $index, BunqPayment $payment): ?array
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s.u', $payment->getCreated());
        if (null !== $this->notBefore && $created->lte($this->notBefore)) {
            Log::info(
                sprintf(
                    'Skip transaction because %s is before %s',
                    $created->format('Y-m-d H:i:s'),
                    $this->notBefore->format('Y-m-d H:i:s')
                )
            );

            return null;
        }
        if(null !== $this->notAfter && $created->gte($this->notAfter)) {
            Log::info(
                sprintf(
                    'Skip transaction because %s is after %s',
                    $created->format('Y-m-d H:i:s'),
                    $this->notAfter->format('Y-m-d H:i:s')
                )
            );

            return null;
        }


        //Log::debug(sprintf('Processing bunq payment: %s', str_replace("\n", ' ', print_r($payment, true))));
        $transaction                                  = [
            // TODO country, bunqMe, isLight, swiftBic, swiftAccountNumber, transferwiseAccountNumber, transferwiseBankCode
            // TODO merchantCategoryCode, bunqtoStatus, bunqtoSubStatus, bunqtoExpiry, bunqtoTimeResponded
            // TODO merchantReference, batchId, scheduledId, addressShipping, addressBilling, geolocation, allowChat,
            // TODO requestReferenceSplitTheBill
            'id'              => $payment->getId(),
            'created'         => $created,
            'updated'         => Carbon::createFromFormat('Y-m-d H:i:s.u', $payment->getUpdated()),
            'bunq_account_id' => $payment->getMonetaryAccountId(),
            'amount'          => $payment->getAmount()->getValue(),
            'currency_code'   => $payment->getAmount()->getCurrency(),
            'counter_party'   => [
                'iban'         => null,
                'display_name' => null,
                'nick_name'    => null,
                'country'      => null,
            ],
            'description'     => trim($payment->getDescription()),
            'type'            => $payment->getType(),
            'sub_type'        => $payment->getSubType(),
            'balance_after'   => $payment->getBalanceAfterMutation()->getValue(),
        ];
        $counterParty                                 = $payment->getCounterpartyAlias();
        $transaction['counter_party']['iban']         = $counterParty->getIban();
        $transaction['counter_party']['display_name'] = $counterParty->getDisplayName();
        $transaction['counter_party']['nick_name']    = $counterParty->getLabelUser()->getDisplayName();
        $transaction['counter_party']['country']      = $counterParty->getCountry();
        if('' === $transaction['description']) {
            $transaction['description'] = '(empty description)';
        }

        //Log::debug('Converted result', $transaction);
        Log::debug(
            sprintf(
                'Downloaded and parsed transaction #%d (%s) "%s" (%s %s).',
                $transaction['id'], $transaction['created']->format('Y-m-d'),
                $transaction['description'], $transaction['currency_code'], $transaction['amount']
            )
        );

        return $transaction;
    }

    /**
     * @param array $data
     */
    private function storeDownload(array $data): void
    {
        $disk = Storage::disk('downloads');
        $disk->put($this->identifier, json_encode($data, JSON_THROW_ON_ERROR, 512));
    }


}