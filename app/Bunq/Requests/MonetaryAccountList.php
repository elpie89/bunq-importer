<?php
/**
 * MonetaryAccountList.php
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

declare(strict_types=1);

namespace App\Bunq\Requests;

use App\Exceptions\BunqImporterException;
use App\Exceptions\ImportException;
use bunq\Exception\BunqException;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\MonetaryAccount as BunqMonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\MonetaryAccountJoint;
use bunq\Model\Generated\Endpoint\MonetaryAccountSavings;
use bunq\Model\Generated\Object\Pointer;

/**
 * Class MonetaryAccount.
 *
 * @codeCoverageIgnore
 */
class MonetaryAccountList
{
    /**
     * Get list of monetary accounts from bunq. Format as necessary.
     *
     * @param array $params
     * @param array $customHeaders
     *
     * @throws ImportException
     * @return array
     */
    public function listing(array $params = null, array $customHeaders = null): array
    {
        app('log')->debug('Now calling bunq listing.');
        $params        = $params ?? [];
        $customHeaders = $customHeaders ?? [];
        $listing       = BunqMonetaryAccount::listing($params, $customHeaders);
        $return        = [];
        /** @var BunqMonetaryAccount $entry */
        foreach ($listing->getValue() as $entry) {
            try {
                $return[] = $this->processEntry($entry);
            } catch (BunqImporterException $e) {
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
                throw new ImportException($e);
            }
        }

        return $return;
    }

    /**
     * @param BunqModel $object
     *
     * @return string|null
     */
    private function getColor(BunqModel $object): ?string
    {
        return $object->getSetting()->getColor();
    }

    /**
     * @param BunqModel $object
     *
     * @return string|null
     */
    private function getIban(BunqModel $object): ?string
    {
        /** @var Pointer $pointer */
        foreach ($object->getAlias() as $pointer) {
            if ('IBAN' === $pointer->getType()) {
                return $pointer->getValue();
            }
        }

        return null;
    }

    /**
     * @param BunqMonetaryAccount $entry
     *
     * @throws ImportException
     * @return array
     */
    private function processEntry(BunqMonetaryAccount $entry): array
    {
        /** @var MonetaryAccountBank $object */
        try {
            $object = $entry->getReferencedObject();
        } catch (BunqException $e) {
            throw new ImportException($e->getMessage());
        }
        switch (get_class($object)) {
            case MonetaryAccountBank::class:
            case MonetaryAccountSavings::class:
            case MonetaryAccountJoint::class:
                $return          = [
                    'id'          => $object->getId(),
                    'currency'    => $object->getCurrency(),
                    'description' => $object->getDescription(),
                    'uuid'        => $object->getPublicUuid(),
                    'status'      => $object->getStatus(),
                    'sub_status'  => $object->getSubStatus(),
                    'iban'        => null,
                    'color'       => null,
                ];
                $return['iban']  = $this->getIban($object);
                $return['color'] = $this->getColor($object);

                return $return;
                break;
        }
        throw new ImportException(sprintf('Bunq monetary account is unexpectedly of type "%s".', get_class($object)));
    }
}
