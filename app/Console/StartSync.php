<?php
/**
 * StartSync.php
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

namespace App\Console;

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\JobStatusManager;
use App\Services\Sync\RoutineManager as SyncRoutineManager;

/**
 * Trait StartSync.
 */
trait StartSync
{
    /**
     * @param array $configuration
     *
     * @return int
     */
    private function startSync(array $configuration): int
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $configObject = Configuration::fromFile($configuration);

        // first download from bunq
        $manager = new SyncRoutineManager;
        $manager->setDownloadIdentifier($this->downloadIdentifier);

        // start or find job:
        JobStatusManager::startOrFindJob($manager->getSyncIdentifier());

        try {
            $manager->setConfiguration($configObject);
        } catch (ImportException $e) {
            $this->error($e->getMessage());

            return 1;
        }
        try {
            $manager->start();
        } catch (ImportException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $messages = $manager->getAllMessages();
        $warnings = $manager->getAllWarnings();
        $errors   = $manager->getAllErrors();

        $this->listMessages('ERROR', $errors);
        $this->listMessages('Warning', $warnings);
        $this->listMessages('Message', $messages);

        return 0;
    }

    /**
     * @param string $key
     * @param array  $messages
     */
    protected function listMessages(string $key, array $messages): void
    {
        if (count($messages) > 0) {
            /**
             * @var int   $index
             * @var array $error
             */
            foreach ($messages as $index => $list) {
                /** @var string $line */
                foreach ($list as $line) {
                    $this->error(sprintf('%s in line #%d: %s', $key, $index + 1, $line));
                }
            }
        }
    }
}
