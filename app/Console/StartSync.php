<?php
/**
 * StartSync.php

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
}
