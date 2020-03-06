<?php
declare(strict_types=1);
/**
 * SyncController.php
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

namespace App\Http\Controllers\Import;


use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Sync\JobStatus\JobStatus;
use App\Services\Sync\JobStatus\JobStatusManager;
use App\Services\Sync\RoutineManager;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class SyncController
 */
class SyncController extends Controller
{
    /**
     * SyncController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Send data to Firefly III');
    }
    /**
     *
     */
    public function index()
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Send data to Firefly III';
        $subTitle  = 'After download, comes import.';

        // get download job ID so we have the data to send to FF3
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);

        // get sync ID so we have a separate track thing.
        $syncIdentifier = session()->get(Constants::SYNC_JOB_IDENTIFIER);

        if (null !== $syncIdentifier) {
            // create a new import job:
            Log::debug('SyncController is creating new routine manager with existing sync identifier');
            new RoutineManager($syncIdentifier);
        }
        if (null === $syncIdentifier) {
            Log::debug('SyncController is creating new routine manager with NEW sync identifier');
            // create a new import job:
            $routine        = new RoutineManager(null);
            $syncIdentifier = $routine->getSyncIdentifier();
        }

        Log::debug(sprintf('Sync routine manager job identifier is "%s"', $syncIdentifier));

        // store identifier in session so the status can get it.
        session()->put(Constants::SYNC_JOB_IDENTIFIER, $syncIdentifier);
        Log::debug(sprintf('Stored "%s" under "%s"', $syncIdentifier, Constants::SYNC_JOB_IDENTIFIER));

        return view('import.sync.index', compact('mainTitle', 'subTitle', 'syncIdentifier', 'downloadIdentifier'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        Log::debug(sprintf('Now at %s', __METHOD__));

        // get download job ID so we have the data to send to FF3
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);

        // get sync ID so we have a separate track thing.
        $syncIdentifier = session()->get(Constants::SYNC_JOB_IDENTIFIER);

        $routine    = new RoutineManager($syncIdentifier);

        // store identifier in session so the status can get it (should already be there)
        session()->put(Constants::SYNC_JOB_IDENTIFIER, $syncIdentifier);
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $downloadIdentifier);

        $downloadJobStatus = JobStatusManager::startOrFindJob($syncIdentifier);
        if (JobStatus::JOB_DONE === $downloadJobStatus->status) {
            // TODO DISABLED DURING DEVELOPMENT:
            //Log::debug('Job already done!');
            //return response()->json($downloadJobStatus->toArray());
        }
        JobStatusManager::setJobStatus(JobStatus::JOB_RUNNING);

        try {
            $config = session()->get(Constants::CONFIGURATION) ?? [];
            $routine->setConfiguration(Configuration::fromArray($config));
            $routine->setDownloadIdentifier($downloadIdentifier);
            $routine->setSyncIdentifier($syncIdentifier);
            $routine->start();
        } catch (ImportException $e) {
            // TODO better error handling.
            throw new RuntimeException($e->getMessage());
        }

        // set done:
        JobStatusManager::setJobStatus(JobStatus::JOB_DONE);

        return response()->json($downloadJobStatus->toArray());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $syncIdentifier = $request->get('syncIdentifier');
        Log::debug(sprintf('Now at %s(%s)', __METHOD__, $syncIdentifier));
        if (null === $syncIdentifier) {
            Log::warning('Identifier is NULL.');
            // no status is known yet because no identifier is in the session.
            // As a fallback, return empty status
            $fakeStatus = new JobStatus();

            return response()->json($fakeStatus->toArray());
        }
        $importJobStatus = JobStatusManager::startOrFindJob($syncIdentifier);

        return response()->json($importJobStatus->toArray());
    }

}
