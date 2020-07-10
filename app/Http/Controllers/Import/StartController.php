<?php
/**
 * StartController.php

 */

declare(strict_types=1);

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Class StartController.
 */
class StartController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Import from bunq.');
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        $mainTitle = 'Import from bunq';
        $subTitle  = 'Start page and instructions';

        return view('import.start.index', compact('mainTitle', 'subTitle'));
    }
}
