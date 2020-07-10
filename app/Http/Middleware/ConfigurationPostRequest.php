<?php
/**
 * ConfigurationPostRequest.php

 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Validation\Validator;

/**
 * Class ConfigurationPostRequest.
 */
class ConfigurationPostRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        // parse entire config file.
        $mapping  = $this->get('mapping') ? json_decode(base64_decode($this->get('mapping')), true, 512, JSON_THROW_ON_ERROR) : null;
        $doImport = $this->get('do_import') ?? [];

        return [
            'do_import'         => $doImport,
            'rules'             => $this->convertBoolean($this->get('rules')),
            'skip_form'         => $this->convertBoolean($this->get('skip_form')),
            'date_range'        => $this->string('date_range'),
            'date_range_number' => $this->integer('date_range_number'),
            'date_range_unit'   => $this->string('date_range_unit'),
            'date_not_before'   => $this->date('date_not_before'),
            'date_not_after'    => $this->date('date_not_after'),
            'do_mapping'        => $this->convertBoolean($this->get('do_mapping')),
            'mapping'           => $mapping,
            'accounts'          => $this->get('accounts'),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            //'some_weird_field' => 'required',
            'rules'             => 'numeric|between:0,1',
            'do_mapping'        => 'numeric|between:0,1',
            'date_range'        => 'required|in:all,partial,range',
            'date_range_number' => 'numeric|between:1,365',
            'date_range_unit'   => 'required|in:d,w,m,y',
            'date_not_before'   => 'date|nullable',
            'date_not_after'    => 'date|nullable',
            'accounts.*'        => 'numeric',
            'do_import.*'       => 'numeric',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            static function (Validator $validator) {
                $data = $validator->getData();
                if (!isset($data['do_import'])) {
                    $validator->errors()->add(
                        'accounts', 'Select at least one bunq account to import from.'
                    );
                }
            }
        );
    }
}
