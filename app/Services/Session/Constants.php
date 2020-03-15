<?php

declare(strict_types=1);
/**
 * Constants.php
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

namespace App\Services\Session;

/**
 * Class Constants.
 */
class Constants
{
    /** @var string */
    public const UPLOAD_CONFIG_FILE = 'config_file_path';
    /** @var string */
    public const CONFIG_COMPLETE_INDICATOR = 'config_complete';
    /** @var string */
    public const CONFIGURATION = 'configuration';
    /** @var string */
    public const DOWNLOAD_JOB_IDENTIFIER = 'download_job_id';
    /** @var string */
    public const SYNC_JOB_IDENTIFIER = 'sync_job_id';
}
