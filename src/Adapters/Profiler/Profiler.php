<?php

declare(strict_types=1);

namespace Nip\Database\Adapters\Profiler;

/**
 * Database query profiler.
 *
 * Extends the generic Nip_Profiler with SQL-specific filtering by query type
 * and optional per-query timing filters.
 *
 * @package Nip\Database\Adapters\Profiler
 */
class Profiler extends \Nip_Profiler
{
    /** @var array<int, string>|null */
    public ?array $filterTypes = null;

    public function newProfile(mixed $id): QueryProfile
    {
        return new QueryProfile($id);
    }

    protected function applyFilters(mixed $profile): bool
    {
        if (parent::applyFilters($profile)) {
            return $this->secondsFilter($profile);
        }
        return false;
    }

    public function typeFilter(mixed $profile): bool
    {
        if (is_array($this->filterTypes) && in_array($profile->type, $this->filterTypes, true)) {
            $this->deleteProfile($profile);

            return false;
        }

        return true;
    }

    public function setFilterQueryType(?array $queryTypes = null): static
    {
        $this->filterTypes = $queryTypes;

        return $this;
    }
}
