<?php

declare(strict_types=1);
/**
 * ParseBunqDownload.php
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

use App\Services\Sync\JobStatus\ProgressInformation;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use JsonException;
use Log;
use Storage;

/**
 * Class ParseBunqDownload.
 */
class ParseBunqDownload
{
    use ProgressInformation;

    /**
     * @param string $downloadIdentifier
     *
     * @return array
     */
    public function getDownload(string $downloadIdentifier): array
    {
        $disk = Storage::disk('downloads');
        $result = [];
        $count = 0;
        if ($disk->exists($downloadIdentifier)) {
            try {
                $this->addMessage(0, 'Decoded bunq download.');
                $result = json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR);
            } catch (FileNotFoundException | JsonException $e) {
                $this->addError(0, 'Could not decode bunq download.');
            }
        }
        foreach ($result as $transactions) {
            $count += count($transactions);
        }

        Log::debug(sprintf('Parsed %d bunq account transactions.', $count));

        return $result;
    }
}
