<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Session\Constants;
use Illuminate\Http\Request;

/**
 * Class NavController
 */
class NavController extends Controller
{
    //
    /**
     * Return back to index. Needs no session updates.
     */
    public function toStart()
    {
        return redirect(route('index'));
    }

    /**
     * Return back to upload.
     */
    public function toUpload()
    {
        session()->forget(Constants::CONFIG_COMPLETE_INDICATOR);
        session()->forget(Constants::CONFIGURATION);

        return redirect(route('import.start'));
    }

    /**
     * Return back to config
     */
    public function toConfig()
    {
        session()->forget(Constants::CONFIG_COMPLETE_INDICATOR);
        session()->forget(Constants::DOWNLOAD_JOB_IDENTIFIER);

        return redirect(route('import.configure.index') . '?overruleskip=true');
    }
}
