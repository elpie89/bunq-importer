<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ImportException;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Log;
use Artisan;
/**
 *
 * Class IndexController
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
     * @return Factory|View
     */
    public function index()
    {
        return view('index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function flush()
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        session()->flush();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');

        return redirect(route('index'));
    }
}
