<?php
/**
 * MappingController.php
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


use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use Illuminate\Http\Request;
use Storage;

/**
 * Class MappingController
 */
class MappingController extends Controller
{
    /**
     *
     * @throws \GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException
     */
    public function index()
    {
        $mainTitle = 'Map date';
        $subTitle  = 'Link to Firefly III data.';

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && false === $configuration->isDoMapping()) {
            // skipForm
            return redirect()->route('import.sync.index');
        }

        $mapping = $configuration->getMapping();

        // parse all opposing accounts from the download
        $bunqAccounts = $this->getOpposingAccounts();

        // get accounts from Firefly III
        $ff3Accounts = $this->getFireflyIIIAccounts();

        return view('import.mapping.index', compact('mainTitle', 'subTitle', 'configuration', 'bunqAccounts', 'ff3Accounts', 'mapping'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex(Request $request)
    {
        // post mapping is not particularly complex.
        $result  = $request->all();
        $mapping = $result['mapping'] ?? [];

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && false === $configuration->isDoMapping()) {
            // skipForm
            return redirect()->route('import.sync.index');
        }
        // save mapping in config.
        $configuration->setMapping($mapping);

        // save mapping in config, save config.
        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        return redirect(route('import.sync.index'));
    }

    /**
     * @return array
     * @throws \GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException
     */
    private function getFireflyIIIAccounts(): array
    {
        $token   = config('bunq.access_token');
        $uri     = config('bunq.uri');
        $request = new GetAccountsRequest($uri, $token);
        /** @var GetAccountsResponse $result */
        $result = $request->get();
        $return = [];
        foreach ($result as $entry) {
            $type = $entry->type;
            if ('reconciliation' === $type || 'initial-balance' === $type) {
                continue;
            }
            $id                 = (int)$entry->id;
            $return[$type][$id] = $entry->name;
            if ('' !== (string)$entry->iban) {
                $return[$type][$id] = sprintf('%s (%s)', $entry->name, $entry->iban);
            }
        }
        foreach ($return as $type => $entries) {
            asort($return[$type]);
        }

        return $return;
    }

    /**
     * @return array
     */
    private function getOpposingAccounts(): array
    {
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        $disk       = Storage::disk('downloads');
        $json       = $disk->get($downloadIdentifier);
        $array      = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $opposing   = [];
        /** @var array $account */
        foreach ($array as $account) {
            foreach ($account as $entry) {
                if ('' === trim($entry['counter_party']['iban'])) {
                    $opposing[] = trim($entry['counter_party']['display_name']);
                }
                if ('' !== trim($entry['counter_party']['iban'])) {
                    $opposing[] = sprintf('%s (%s)', trim($entry['counter_party']['display_name']), trim($entry['counter_party']['iban']));
                }
            }
        }
        $filtered = array_filter(
            $opposing, static function (string $value) {
            return '' !== $value;
        }
        );

        return array_unique($filtered);
    }

}