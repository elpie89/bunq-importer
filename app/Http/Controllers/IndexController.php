<?php
/**
 * IndexController.php

 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Class IndexController.
 */
class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Index');
    }

    /**
     * @return RedirectResponse|Redirector
     */
    public function flush()
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        session()->flush();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');

        return redirect(route('index'));
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        app('log')->debug('If you see this, debug logging is configured correctly.');

        return view('index');
    }
}
