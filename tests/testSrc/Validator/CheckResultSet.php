<?php

namespace StubTests\Sources\Validator;

class CheckResultSet
{
    /** @var array<string, string> */
    private array $failures = [];

    /** @var array<string> */
    private array $successes = [];

    public function addFailure(string $name, string $message): void
    {
        $this->failures[$name] = $message;
    }

    public function addSuccess(string $name): void
    {
        $this->successes[] = $name;
    }

    /**
     * @return array<string, string>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * @return array<string>
     */
    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function hasFailures(): bool
    {
        return count($this->failures) > 0;
    }

    public function getFailureCount(): int
    {
        return count($this->failures);
    }

    public function getSuccessCount(): int
    {
        return count($this->successes);
    }
}
