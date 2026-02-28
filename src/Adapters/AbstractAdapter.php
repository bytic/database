<?php

declare(strict_types=1);

namespace Nip\Database\Adapters;

use Nip\Database\Adapters\Profiler\Profiler;

/**
 * Base adapter: wraps the driver-specific query() call with optional profiling.
 *
 * @package Nip\Database\Adapters
 */
abstract class AbstractAdapter
{
    protected ?Profiler $_profiler = null;

    /**
     * Execute a SQL string, optionally recording a profiling entry.
     *
     * Triggers a PHP warning and returns false when the underlying driver
     * reports an error – this preserves the legacy behaviour so that callers
     * that check the return value continue to work.
     */
    public function execute(string $sql): mixed
    {
        $profile = null;

        if ($this->hasProfiler()) {
            $profile = $this->getProfiler()->start();
            if ($profile !== null) {
                $profile->setName($sql);
                $profile->setAdapter($this);
            }
        }

        $result = $this->query($sql);

        if ($this->hasProfiler() && $profile !== null) {
            $this->getProfiler()->end($profile);
        }

        if ($result !== false) {
            return $result;
        }

        trigger_error($this->error() . " [$sql]", E_USER_WARNING);

        return false;
    }

    public function hasProfiler(): bool
    {
        return $this->_profiler instanceof Profiler;
    }

    public function getProfiler(): ?Profiler
    {
        return $this->_profiler;
    }

    public function setProfiler(Profiler $profiler): void
    {
        $this->_profiler = $profiler;
    }

    /** Execute a raw SQL string against the driver. */
    abstract public function query(string $sql): mixed;

    abstract public function error(): string;

    public function newProfiler(): Profiler
    {
        return new Profiler();
    }

    abstract public function quote(mixed $value): int|float|string;

    abstract public function cleanData(mixed $data): mixed;

    abstract public function connect(
        string $host = '',
        string $user = '',
        string $password = '',
        string $database = '',
        bool $newLink = false
    ): mixed;

    abstract public function describeTable(string $table): array|false;

    abstract public function disconnect(): void;

    abstract public function lastInsertID(): int|string;

    abstract public function affectedRows(): int;
}

