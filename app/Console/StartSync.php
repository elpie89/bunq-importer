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

        if (count($errors) > 0) {
            /**
             * @var int   $index
             * @var array $error
             */
            foreach ($errors as $index => $error) {
                /** @var string $line */
                foreach ($error as $line) {
                    $this->error(sprintf('ERROR in line     #%d: %s', $index + 1, $line));
                }
            }
        }

        if (count($warnings) > 0) {
            /**
             * @var int   $index
             * @var array $warning
             */
            foreach ($warnings as $index => $warning) {
                /** @var string $line */
                foreach ($warning as $line) {
                    $this->warn(sprintf('Warning from line #%d: %s', $index + 1, $line));
                }
            }
        }

        if (count($messages) > 0) {
            /**
             * @var int   $index
             * @var array $message
             */
            foreach ($messages as $index => $message) {
                /** @var string $line */
                foreach ($message as $line) {
                    $this->info(sprintf('Message from line #%d: %s', $index + 1, strip_tags($line)));
                }
            }
        }

        return 0;
    }
}
