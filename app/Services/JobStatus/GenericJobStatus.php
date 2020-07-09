<?php
declare(strict_types=1);

namespace App\Services\JobStatus;

/**
 * Class GenericJobStatus
 */
class GenericJobStatus
{
    /** @var string */
    public const JOB_WAITING = 'waiting_to_start';
    /** @var string */
    public const JOB_RUNNING = 'job_running';
    /** @var string */
    public const JOB_ERRORED = 'job_errored';
    /** @var string */
    public const JOB_DONE = 'job_done';
    public array $errors;
    public array $messages;
    public array $warnings;
    public string $status;

    /**
     * ImportJobStatus constructor.
     */
    public function __construct()
    {
        $this->status   = self::JOB_WAITING;
        $this->errors   = [];
        $this->warnings = [];
        $this->messages = [];
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $config           = new self;
        $config->status   = $array['status'];
        $config->errors   = $array['errors'] ?? [];
        $config->warnings = $array['warnings'] ?? [];
        $config->messages = $array['messages'] ?? [];

        return $config;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status'   => $this->status,
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
            'messages' => $this->messages,
        ];
    }
}
