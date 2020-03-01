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
    /** @var array */
    private $mapping;

    /** @var bool */
    private $doMapping;

    /** @var string */
    private $dateRange;

    /** @var int */
    private $dateRangeNumber;

    /** @var string */
    private $dateRangeUnit;

    /** @var string */
    private $dateRangeStart;

    /** @var string */
    private $dateRangeEnd;

    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->rules           = true;
        $this->skipForm        = false;
        $this->doMapping       = false;
        $this->accounts        = [];
        $this->version         = self::VERSION;
        $this->mapping         = [];
        $this->dateRange       = 'all';
        $this->dateRangeNumber = 30;
        $this->dateRangeUnit   = 'd';
        $this->dateRangeStart  = '';
        $this->dateRangeEnd    = '';
    }

    /**
     * @return string
     */
    public function getDateRangeStart(): string
    {
        return $this->dateRangeStart;
    }

    /**
     * @param string $dateRangeStart
     */
    public function setDateRangeStart(string $dateRangeStart): void
    {
        $this->dateRangeStart = $dateRangeStart;
    }

    /**
     * @return string
     */
    public function getDateRangeEnd(): string
    {
        return $this->dateRangeEnd;
    }

    /**
     * @param string $dateRangeEnd
     */
    public function setDateRangeEnd(string $dateRangeEnd): void
    {
        $this->dateRangeEnd = $dateRangeEnd;
    }

    /**
     * @return string
     */
    public function getDateRangeUnit(): string
    {
        return $this->dateRangeUnit;
    }

    /**
     * @param string $dateRangeUnit
     */
    public function setDateRangeUnit(string $dateRangeUnit): void
    {
        $this->dateRangeUnit = $dateRangeUnit;
    }

    /**
     * @return int
     */
    public function getDateRangeNumber(): int
    {
        return $this->dateRangeNumber;
    }

    /**
     * @param int $dateRangeNumber
     */
    public function setDateRangeNumber(int $dateRangeNumber): void
    {
        $this->dateRangeNumber = $dateRangeNumber;
    }

    /**
     * @return string
     */
    public function getDateRange(): string
    {
        return $this->dateRange;
    }

    /**
     * @param string $dateRange
     */
    public function setDateRange(string $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    /**
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping): void
    {
        $this->mapping = $mapping;
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

        $object                  = new self;
        $object->rules           = $array['rules'] ?? false;
        $object->skipForm        = $array['skip_form'] ?? false;
        $object->accounts        = $array['accounts'] ?? [];
        $object->mapping         = $array['mapping'] ?? [];
        $object->dateRange       = $array['date_range'] ?? 'all';
        $object->dateRangeNumber = $array['date_range_number'] ?? 30;
        $object->dateRangeUnit   = $array['date_range_unit'] ?? 'd';
        $object->dateRangeStart  = $array['date_range_start'] ?? '';
        $object->dateRangeEnd    = $array['date_range_end'] ?? '';
        $object->doMapping       = $array['do_mapping'] ?? false;
        $object->version         = $version;

        return $object;
    }

    /**
     * @return bool
     */
    public function isDoMapping(): bool
    {
        return $this->doMapping;
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
        $object                  = new self;
        $object->version         = self::VERSION;
        $object->rules           = $array['rules'];
        $object->skipForm        = $array['skip_form'];
        $object->accounts        = $array['accounts'];
        $object->mapping         = $array['mapping'];
        $object->dateRange       = $array['date_range'];
        $object->dateRangeNumber = $array['date_range_number'];
        $object->dateRangeUnit   = $array['date_range_unit'];
        $object->dateRangeStart  = $array['date_range_start'];
        $object->dateRangeEnd    = $array['date_range_end'];
        $object->doMapping       = $array['do_mapping'];

        return $object;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    private static function fromDefaultFile(array $data): self
    {
        $object                  = new self;
        $object->rules           = $data['rules'] ?? true;
        $object->skipForm        = $data['skip_form'] ?? false;
        $object->dateRange       = $data['date_range'] ?? 'all';
        $object->dateRangeNumber = $data['date_range_number'] ?? 30;
        $object->dateRangeUnit   = $data['date_range_unit'] ?? 'd';
        $object->dateRangeStart  = $data['date_range_start'] ?? '';
        $object->dateRangeEnd    = $data['date_range_end'] ?? '';
        $object->doMapping       = $data['do_mapping'] ?? false;
        $object->mapping         = $data['mapping'] ?? [];
        $object->accounts        = $data['accounts'] ?? [];

        // set version to "1" and return.
        $object->version = 1;

        return $object;
    }

    /**
     * @return bool
     */
    public function isRules(): bool
    {
        return $this->rules;
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
            'rules'             => $this->rules,
            'skip_form'         => $this->skipForm,
            'accounts'          => $this->accounts,
            'version'           => $this->version,
            'mapping'           => $this->mapping,
            'date_range'        => $this->dateRange,
            'date_range_number' => $this->dateRangeNumber,
            'date_range_unit'   => $this->dateRangeUnit,
            'date_range_start'  => $this->dateRangeStart,
            'date_range_end'    => $this->dateRangeEnd,
            'do_mapping'        => $this->doMapping,
        ];
    }
}
