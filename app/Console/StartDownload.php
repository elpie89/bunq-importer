<?php
/**
 * StartDownload.php

 */

declare(strict_types=1);

namespace App\Console;

use App\Bunq\Download\JobStatus\JobStatusManager;
use App\Bunq\Download\RoutineManager as DownloadRoutineMananger;
use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;

/**
 * Trait StartDownload.
 */
trait StartDownload
{
    /**
     * @param array $configuration
     *
     * @return int
     */
    protected function startDownload(array $configuration): int
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $configObject = Configuration::fromFile($configuration);

        // first download from bunq
        $manager = new DownloadRoutineMananger;

        // start or find job using the downloadIdentifier:
        JobStatusManager::startOrFindJob($manager->getDownloadIdentifier());

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

        $this->downloadIdentifier = $manager->getDownloadIdentifier();

        return 0;
    }
}
