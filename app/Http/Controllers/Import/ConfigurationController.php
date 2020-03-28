<?php
/**
 * ConfigurationController.php
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

namespace App\Http\Controllers\Import;

use App\Bunq\ApiContext\ApiContextManager;
use App\Bunq\Requests\MonetaryAccountList;
use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ConfigComplete;
use App\Http\Middleware\ConfigurationPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Storage\StorageService;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Account;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Class ConfigurationController.
 */
class ConfigurationController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Import configuration');
        $this->middleware(ConfigComplete::class)->except('download');
    }

    /**
     * @return ResponseFactory|Response
     */
    public function download()
    {
        // do something
        $config = Configuration::fromArray(session()->get(Constants::CONFIGURATION))->toArray();
        $result = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, 512);

        $response = response($result);
        $name     = sprintf('bunq_import_config_%s.json', date('Y-m-d'));
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * @throws ApiHttpException
     * @throws ImportException
     * @return Factory|RedirectResponse|View
     */
    public function index()
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Import from bunq';
        $subTitle  = 'Configure your bunq import';

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && true === $configuration->isSkipForm()) {
            // skipForm
            return redirect()->route('import.download.index');
        }
        // get list of asset accounts in Firefly III
        $uri     = (string) config('bunq.uri');
        $token   = (string) config('bunq.access_token');
        $request = new GetAccountsRequest($uri, $token);
        $request->setType(GetAccountsRequest::ASSET);
        $ff3Accounts = $request->get();

        // get the user's bunq accounts.
        ApiContextManager::getApiContext();

        /** @var MonetaryAccountList $lister */
        $lister           = app(MonetaryAccountList::class);
        $bunqAccounts     = $lister->listing();
        $combinedAccounts = [];
        foreach ($bunqAccounts as $bunqAccount) {
            $bunqAccount['ff3_id']       = null;
            $bunqAccount['ff3_name']     = null;
            $bunqAccount['ff3_type']     = null;
            $bunqAccount['ff3_iban']     = null;
            $bunqAccount['ff3_currency'] = null;
            /** @var Account $ff3Account */
            foreach ($ff3Accounts as $ff3Account) {
                if ($bunqAccount['currency'] === $ff3Account->currencyCode && $bunqAccount['iban'] === $ff3Account->iban
                    && 'CANCELLED' !== $bunqAccount['status']
                ) {
                    $bunqAccount['ff3_id']       = $ff3Account->id;
                    $bunqAccount['ff3_name']     = $ff3Account->name;
                    $bunqAccount['ff3_type']     = $ff3Account->type;
                    $bunqAccount['ff3_iban']     = $ff3Account->iban;
                    $bunqAccount['ff3_currency'] = $ff3Account->currencyCode;
                    $bunqAccount['ff3_uri']      = sprintf('%saccounts/show/%d', $uri, $ff3Account->id);
                }
            }
            $combinedAccounts[] = $bunqAccount;
        }

        $mapping = '{}';
        if (null !== $configuration) {
            $mapping = base64_encode(json_encode($configuration->getMapping(), JSON_THROW_ON_ERROR, 512));
        }

        return view(
            'import.configuration.index', compact('mainTitle', 'subTitle', 'ff3Accounts', 'combinedAccounts', 'configuration', 'bunqAccounts', 'mapping')
        );
    }

    /**
     * @param ConfigurationPostRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(ConfigurationPostRequest $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        // store config on drive.

        $fromRequest   = $request->getAll();
        $configuration = Configuration::fromRequest($fromRequest);
        $config        = StorageService::storeContent(json_encode($configuration, JSON_THROW_ON_ERROR, 512));

        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set config as complete.
        session()->put(Constants::CONFIG_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.download.index');
    }
}
