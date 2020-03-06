<?php
declare(strict_types=1);

namespace App\Console;

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use App\Services\Sync\RoutineManager as SyncRoutineManager;
use Log;

/**
 * Trait StartSync
 *
 * @package App\Console
 */
trait StartSync
{
    /**
     * @param array  $configuration
     *
     * @return int
     */
    private function startSync(array $configuration): int
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $configObject = Configuration::fromFile($configuration);

        // first download from bunq
        $manager      = new SyncRoutineManager;
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
            foreach ($errors as $index => $error) {
                foreach ($error as $line) {
                    $this->error(sprintf('ERROR in line     #%d: %s', $index + 1, $line));
                }
            }
        }

        if (count($warnings) > 0) {
            foreach ($warnings as $index => $warning) {
                foreach ($warning as $line) {
                    $this->warn(sprintf('Warning from line #%d: %s', $index + 1, $line));
                }
            }
        }

        if (count($messages) > 0) {
            foreach ($messages as $index => $message) {
                foreach ($message as $line) {
                    $this->info(sprintf('Message from line #%d: %s', $index + 1, strip_tags($line)));
                }
            }
        }

        return 0;
    }
}
