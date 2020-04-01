<?php
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

    /** @var array */
    private $keys;

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
        foreach ($transactions as $index => $group) {
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
        $this->addMessage(0, sprintf('Filtered down from %d duplicate entries to %d unique transactions.', $start, $end));

        return $return;
    }

}