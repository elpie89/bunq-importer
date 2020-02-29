<?php
declare(strict_types=1);
/**
 * Configuration.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii/csv-importer).
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

namespace App\Services\Configuration;

use Log;
use RuntimeException;

/**
 * Class Configuration
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    /** @var array */
    private $accounts;
    /** @var bool */
    private $rules;
    /** @var bool */
    private $skipForm;
    /** @var int */
    private $version;

    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->rules    = true;
        $this->skipForm = false;
        $this->accounts = [];
        $this->version  = self::VERSION;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $version = $array['version'] ?? 1;

        // TODO now have room to do version based array parsing.

        $object           = new self;
        $object->rules    = $array['rules'];
        $object->skipForm = $array['skip_form'];
        $object->accounts = $array['accounts'];
        $object->version  = $version;

        return $object;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public static function fromFile(array $data): self
    {
        Log::debug('Now in Configuration::fromClassic', $data);
        $version = $data['version'] ?? 1;
        if (1 === $version) {
            return self::fromDefaultFile($data);
        }
        throw new RuntimeException(sprintf('Configuration file version "%s" cannot be parsed.', $version));
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public static function fromRequest(array $array): self
    {
        $object           = new self;
        $object->version  = self::VERSION;
        $object->rules    = $array['rules'];
        $object->skipForm = $array['skip_form'];
        $object->accounts = $array['accounts'];

        return $object;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    private static function fromDefaultFile(array $data): self
    {
        $object           = new self;
        $object->rules    = $data['rules'] ?? true;
        $object->skipForm = $data['skip_form'] ?? true;

        // array values
        $object->accounts = [];

        // set version to "1" and return.
        $object->version = 1;

        return $object;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    private static function fromVersionTwo(array $data): self
    {
        return self::fromArray($data);
    }

    /**
     * @return bool
     */
    public function isSkipForm(): bool
    {
        return $this->skipForm;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'rules'     => $this->rules,
            'skip_form' => $this->skipForm,
            'accounts'  => $this->accounts,
            'version'   => $this->version,
        ];
    }


}
