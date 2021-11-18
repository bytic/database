<?php

namespace Nip\Database;

use Doctrine\DBAL\FetchMode;
use Traversable;

/**
 * Class Result
 * @package Nip\Database
 * @internal use Result from doctrine
 */
class Result extends \Doctrine\DBAL\Result
{
    /**
     * @var \Doctrine\DBAL\Result
     */
    protected $resultDbal;

    public static function fromDBAL($resultDbal)
    {
        return new static($resultDbal);
    }

    /**
     * Result constructor.
     */
    public function __construct($resultDbal)
    {
        $this->resultDbal = $resultDbal;
    }

    /**
     * @inheritDoc
     */
    public function fetchNumeric()
    {
        return $this->resultDbal->fetchNumeric();
    }

    /**
     * @inheritDoc
     */
    public function fetchAssociative()
    {
        return $this->resultDbal->fetchAssociative();
    }

    /**
     * @inheritDoc
     */
    public function fetchOne()
    {
        return $this->resultDbal->fetchOne();
    }

    /**
     * @inheritDoc
     */
    public function fetchAllNumeric(): array
    {
        return $this->resultDbal->fetchAllNumeric();
    }

    /**
     * @inheritDoc
     */
    public function fetchAllAssociative(): array
    {
        return $this->resultDbal->fetchAllAssociative();
    }

    /**
     * @inheritDoc
     */
    public function fetchAllKeyValue(): array
    {
        return $this->resultDbal->fetchAllKeyValue();
    }

    /**
     * @inheritDoc
     */
    public function fetchAllAssociativeIndexed(): array
    {
        return $this->resultDbal->fetchAllAssociativeIndexed();
    }

    /**
     * @inheritDoc
     */
    public function fetchFirstColumn(): array
    {
        return $this->resultDbal->fetchFirstColumn();
    }

    /**
     * @inheritDoc
     */
    public function iterateNumeric(): Traversable
    {
        return $this->resultDbal->iterateNumeric();
    }

    /**
     * @inheritDoc
     */
    public function iterateAssociative(): Traversable
    {
        return $this->resultDbal->iterateAssociative();
    }

    /**
     * @inheritDoc
     */
    public function iterateKeyValue(): Traversable
    {
        return $this->resultDbal->iterateKeyValue();
    }

    /**
     * @inheritDoc
     */
    public function iterateAssociativeIndexed(): Traversable
    {
        return $this->resultDbal->iterateAssociativeIndexed();
    }

    /**
     * @inheritDoc
     */
    public function iterateColumn(): Traversable
    {
        return $this->resultDbal->iterateColumn();
    }

    /**
     * @inheritDoc
     */
    public function rowCount(): int
    {
        return $this->resultDbal->rowCount();
    }

    /**
     * @inheritDoc
     */
    public function columnCount(): int
    {
        return $this->resultDbal->columnCount();
    }

    /**
     * @inheritDoc
     */
    public function free(): void
    {
        $this->resultDbal->free();
    }

    /**
     * @inheritDoc
     */
    public function fetch(int $mode = FetchMode::ASSOCIATIVE)
    {
        return $this->resultDbal->fetch($mode);
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(int $mode = FetchMode::ASSOCIATIVE): array
    {
        return $this->resultDbal->fetchAll($mode);
    }

    /**
     * @deprecated No alternative present
     */
    public function getAdapter()
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * @deprecated No alternative present
     */
    public function setAdapter($adapter)
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * Fetches all rows from current result set.
     *
     * @return list<array<string,mixed>>
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetchResults(): array
    {
        return $this->resultDbal->fetchAllAssociative();
    }

    /**
     * Fetches row from current result set.
     *
     * @return array<string,mixed>|false
     */
    public function fetchResult()
    {
        return $this->resultDbal->fetchAssociative();
    }

    /**
     * @deprecated
     */
    public function checkValid()
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * @deprecated
     */
    public function isValid()
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * @deprecated
     */
    public function getQuery()
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * @deprecated
     */
    public function setQuery($query)
    {
        $this->triggerDeprecation(__METHOD__);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @deprecated use rowCount()
     */
    public function numRows(): int
    {
        $this->triggerDeprecation(__METHOD__, 'rowCount');

        return $this->resultDbal->rowCount();
    }

    /**
     * @param string $method
     */
    protected function triggerDeprecation($method, $alternative = null)
    {
        @trigger_error(
            sprintf(
                "Method %s is no longer available for %s. Moving to Doctrine Result. %s",
                $method, __CLASS__, ($alternative ? 'Use ->'.$alternative.' instead.' : 'No alternatives provided.')
            ),
            E_USER_DEPRECATED
        );
    }
}
