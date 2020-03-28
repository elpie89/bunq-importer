<?php
/**
 * Configuration.php
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

namespace App\Services\Configuration;

use Carbon\Carbon;
use RuntimeException;

/**
 * Class Configuration.
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    /** @var array */
    private $accountTypes;
    /** @var array */
    private $accounts;
    /** @var string */
    private $dateNotAfter;
    /** @var string */
    private $dateNotBefore;
    /** @var string */
    private $dateRange;
    /** @var int */
    private $dateRangeNumber;
    /** @var string */
    private $dateRangeUnit;
    /** @var bool */
    private $doMapping;
    /** @var array */
    private $mapping;
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
        $this->rules           = true;
        $this->skipForm        = false;
        $this->doMapping       = false;
        $this->accounts        = [];
        $this->version         = self::VERSION;
        $this->mapping         = [];
        $this->accountTypes    = [];
        $this->dateRange       = 'all';
        $this->dateRangeNumber = 30;
        $this->dateRangeUnit   = 'd';
        $this->dateNotBefore   = '';
        $this->dateNotAfter    = '';
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
        $object->accountTypes    = $array['account_types'] ?? [];
        $object->dateRange       = $array['date_range'] ?? 'all';
        $object->dateRangeNumber = $array['date_range_number'] ?? 30;
        $object->dateRangeUnit   = $array['date_range_unit'] ?? 'd';
        $object->dateNotBefore   = $array['date_not_before'] ?? '';
        $object->dateNotAfter    = $array['date_not_after'] ?? '';
        $object->doMapping       = $array['do_mapping'] ?? false;
        $object->version         = $version;

        return $object;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public static function fromFile(array $data): self
    {
        app('log')->debug('Now in Configuration::fromClassic', $data);
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

        $object->mapping         = $array['mapping'];
        $object->accountTypes    = $array['account_types'] ?? [];
        $object->dateRange       = $array['date_range'];
        $object->dateRangeNumber = $array['date_range_number'];
        $object->dateRangeUnit   = $array['date_range_unit'];
        $object->dateNotBefore   = $array['date_not_before'];
        $object->dateNotAfter    = $array['date_not_after'];
        $object->doMapping       = $array['do_mapping'];

        $doImport = $array['do_import'] ?? [];
        $accounts = [];
        foreach ($doImport as $bunqId => $selected) {
            $selected = (int) $selected;
            if (1 === $selected) {
                $accounts[(int) $bunqId] = (int) ($array['accounts'][$bunqId] ?? 0);
            }
        }
        $object->accounts = $accounts;

        switch ($object->dateRange) {
            case 'all':
                $object->dateRangeUnit   = null;
                $object->dateRangeNumber = null;
                $object->dateNotBefore   = null;
                $object->dateNotAfter    = null;
                break;
            case 'partial':
                $object->dateNotAfter  = null;
                $object->dateNotBefore = self::calcDateNotBefore($object->dateRangeUnit, $object->dateRangeNumber);
                break;
            case 'range':
                $before = $object->dateNotBefore;
                $after  = $object->dateNotAfter;

                if (null !== $before && null !== $after && $object->dateNotBefore > $object->dateNotAfter) {
                    [$before, $after] = [$after, $before];
                }

                $object->dateNotBefore = null === $before ? null : $before->format('Y-m-d');
                $object->dateNotAfter  = null === $after ? null : $after->format('Y-m-d');
        }

        return $object;
    }

    /**
     * @param string $unit
     * @param int    $number
     *
     * @return string|null
     */
    private static function calcDateNotBefore(string $unit, int $number): ?string
    {
        $functions = [
            'd' => 'subDays',
            'w' => 'subWeeks',
            'm' => 'subMonths',
            'y' => 'subYears',
        ];
        if (isset($functions[$unit])) {
            $today    = Carbon::now();
            $function = $functions[$unit];
            $today->$function($number);

            return $today->format('Y-m-d');
        }
        app('log')->error(sprintf('Could not parse date setting. Unknown key "%s"', $unit));

        return null;
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
        $object->dateNotBefore   = $data['date_not_before'] ?? '';
        $object->accountTypes    = $array['account_types'] ?? [];
        $object->dateNotAfter    = $data['date_not_after'] ?? '';
        $object->doMapping       = $data['do_mapping'] ?? false;
        $object->mapping         = $data['mapping'] ?? [];
        $object->accounts        = $data['accounts'] ?? [];

        // TODO recalculate the date if 'partial'
        if ('partial' === $data['date_range']) {
            $object->dateNotBefore = self::calcDateNotBefore($object->dateRangeUnit, $object->dateRangeNumber);
        }
        // set version to "1" and return.
        $object->version = 1;

        return $object;
    }

    /**
     * @return array
     */
    public function getAccountTypes(): array
    {
        return $this->accountTypes;
    }

    /**
     * @param array $accountTypes
     */
    public function setAccountTypes(array $accountTypes): void
    {
        $this->accountTypes = $accountTypes;
    }

    /**
     * @return array
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * @param array $accounts
     */
    public function setAccounts(array $accounts): void
    {
        $this->accounts = $accounts;
    }

    /**
     * @return string
     */
    public function getDateNotAfter(): ?string
    {
        if (null === $this->dateNotAfter) {
            return null;
        }
        if ('' === $this->dateNotAfter) {
            return null;
        }

        return $this->dateNotAfter;
    }

    /**
     * @param string $dateNotAfter
     */
    public function setDateNotAfter(string $dateNotAfter): void
    {
        $this->dateNotAfter = $dateNotAfter;
    }

    /**
     * @return string|null
     */
    public function getDateNotBefore(): ?string
    {
        if (null === $this->dateNotBefore) {
            return null;
        }
        if ('' === $this->dateNotBefore) {
            return null;
        }

        return $this->dateNotBefore;
    }

    /**
     * @param string $dateNotBefore
     */
    public function setDateNotBefore(string $dateNotBefore): void
    {
        $this->dateNotBefore = $dateNotBefore;
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
     * @return bool
     */
    public function isDoMapping(): bool
    {
        return $this->doMapping;
    }

    /**
     * @return bool
     */
    public function isRules(): bool
    {
        return $this->rules;
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
            'account_types'     => $this->accountTypes,
            'date_range'        => $this->dateRange,
            'date_range_number' => $this->dateRangeNumber,
            'date_range_unit'   => $this->dateRangeUnit,
            'date_not_before'   => $this->dateNotBefore,
            'date_not_after'    => $this->dateNotAfter,
            'do_mapping'        => $this->doMapping,
        ];
    }
}
