<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

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
}
