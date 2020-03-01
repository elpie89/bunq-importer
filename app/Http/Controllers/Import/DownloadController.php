<?php
/**
 * DownloadController.php
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


use App\Bunq\Download\JobStatus\JobStatus;
use App\Bunq\Download\JobStatus\JobStatusManager;
use App\Bunq\Download\RoutineManager;
use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class DownloadController
 */
class DownloadController extends Controller
{

    /**
     *
     */
    public function index()
    {
        $mainTitle = 'Download transactions';
        $subTitle  = 'Connect to bunq and download your data';

        // job ID may be in session:
        $identifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        if (null !== $identifier) {
            // create a new import job:
            $routine = new RoutineManager($identifier);
        }
        if (null === $identifier) {
            // create a new import job:
            $routine    = new RoutineManager();
            $identifier = $routine->getIdentifier();
        }

        Log::debug(sprintf('Download routine manager identifier is "%s"', $identifier));

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $identifier);
        Log::debug(sprintf('Stored "%s" under "%s"', $identifier, Constants::DOWNLOAD_JOB_IDENTIFIER));

        return view('import.download.index', compact('mainTitle', 'subTitle', 'identifier'));
    }

    /**
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $identifier = $request->get('identifier');
        $routine    = new RoutineManager($identifier);

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $identifier);

        $downloadJobStatus = JobStatusManager::startOrFindJob($identifier);
        if (JobStatus::JOB_DONE === $downloadJobStatus->status) {
            // TODO DISABLED DURING DEVELOPMENT:
            //Log::debug('Job already done!');
            //return response()->json($downloadJobStatus->toArray());
        }
        JobStatusManager::setJobStatus(JobStatus::JOB_RUNNING);

        try {
            $config = session()->get(Constants::CONFIGURATION) ?? [];
            $routine->setConfiguration(Configuration::fromArray($config));
            $routine->start();
        } catch (ImportException $e) {
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
        $identifier = $request->get('identifier');
        Log::debug(sprintf('Now at %s(%s)', __METHOD__, $identifier));
        if (null === $identifier) {
            Log::warning('Identifier is NULL.');
            // no status is known yet because no identifier is in the session.
            // As a fallback, return empty status
            $fakeStatus = new JobStatus();

            return response()->json($fakeStatus->toArray());
        }
        $importJobStatus = JobStatusManager::startOrFindJob($identifier);

        return response()->json($importJobStatus->toArray());
    }

}