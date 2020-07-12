<?php
/**
 * Payment.php

 */

declare(strict_types=1);

namespace App\Bunq\Requests;

use App\Exceptions\ImportException;
use bunq\Model\Generated\Endpoint\BunqResponsePaymentList;
use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use Exception;

/**
 * Class Payment.
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
     * @throws ImportException
     * @return BunqResponsePaymentList
     */
    public function listing(int $monetaryAccountId = null, array $params = null, array $customHeaders = null): BunqResponsePaymentList
    {
        app('log')->debug('Now in Payment::listing()');
        $monetaryAccountId = $monetaryAccountId ?? 0;
        $params            = $params ?? [];
        $customHeaders     = $customHeaders ?? [];
        try {
            $result = BunqPayment::listing($monetaryAccountId, $params, $customHeaders);
        } catch (Exception $e) {
            app('log')->error(sprintf('Exception: %s', $e->getMessage()));
            app('log')->error($e->getTraceAsString());
            throw new ImportException($e->getMessage());
        }

        return $result;
    }
}
