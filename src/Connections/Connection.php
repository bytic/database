<?php

declare(strict_types=1);

namespace Nip\Database\Connections;

use Nip\Database\Adapters\HasAdapterTrait;
use Nip\Database\Exception;
use Nip\Database\Metadata\HasMetadata;
use Nip\Database\Query\AbstractQuery as AbstractQuery;
use Nip\Database\Query\Delete as DeleteQuery;
use Nip\Database\Query\Insert as InsertQuery;
use Nip\Database\Query\Select as SelectQuery;
use Nip\Database\Query\Update as UpdateQuery;
use Nip\Database\Result;
use PDO;

/**
 * Represents a single database connection.
 *
 * Wraps an adapter (e.g. MySQLi) and provides a fluent query-builder factory,
 * result execution, and connection lifecycle management.
 *
 * @package Nip\Database\Connections
 */
class Connection implements ConnectionInterface
{
    use HasAdapterTrait;
    use HasMetadata;

    /**
     * The active PDO connection (kept for PDO-based future migration).
     */
    protected mixed $pdo;

    protected string $database;

    protected string $tablePrefix = '';

    /** @var array<string, mixed> */
    protected array $config = [];

    /** @var AbstractQuery|string|null */
    protected mixed $_query = null;

    /** @var list<AbstractQuery|string> */
    protected array $_queries = [];

    /**
     * @param  \PDO|\Closure|false|null $pdo
     * @param  string $database
     * @param  string $tablePrefix
     * @param  array<string, mixed> $config
     */
    public function __construct(mixed $pdo, string $database = '', string $tablePrefix = '', array $config = [])
    {
        $this->pdo         = $pdo;
        $this->database    = $database;
        $this->tablePrefix = $tablePrefix;
        $this->config      = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(string $host, string $user, string $password, string $database, bool $newLink = false): static
    {
        if (!$this->pdo) {
            try {
                $this->pdo = $this->getAdapter()->connect($host, $user, $password, $database, $newLink);

                if (isset($this->config['charset'])) {
                    $charset = $this->config['charset'];
                    $this->getAdapter()->query('SET CHARACTER SET ' . $charset);
                    $this->getAdapter()->query('SET NAMES ' . $charset);
                }
                if (isset($this->config['modes'])) {
                    $this->getAdapter()->query("set session sql_mode='{$this->config['modes']}'");
                }
                $this->setDatabase($database);
            } catch (Exception $e) {
                $e->log();
            }
        }

        return $this;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    /**
     * Optionally prefix a table name (no-op by default; override in subclasses).
     */
    public function tableName(string $table): string
    {
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function newSelect(): SelectQuery
    {
        /** @var SelectQuery $query */
        $query = $this->newQuery('select');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function newQuery(string $type = 'select'): AbstractQuery
    {
        $className = '\\Nip\\Database\\Query\\' . inflector()->camelize($type);
        /** @var AbstractQuery $query */
        $query = new $className();
        $query->setManager($this);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function newInsert(): InsertQuery
    {
        /** @var InsertQuery $query */
        $query = $this->newQuery('insert');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function newUpdate(): UpdateQuery
    {
        /** @var UpdateQuery $query */
        $query = $this->newQuery('update');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function newDelete(): DeleteQuery
    {
        /** @var DeleteQuery $query */
        $query = $this->newQuery('delete');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AbstractQuery|string $query): Result
    {
        $this->_queries[] = $query;

        $sql       = is_string($query) ? $query : $query->getString();
        $resultSQL = $this->getAdapter()->execute($sql);
        $result    = new Result($resultSQL, $this->getAdapter());
        $result->setQuery($query);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertID(): int|string
    {
        return $this->getAdapter()->lastInsertID();
    }

    /**
     * {@inheritdoc}
     */
    public function affectedRows(): int
    {
        return $this->getAdapter()->affectedRows();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->pdo) {
            try {
                $this->getAdapter()->disconnect();
            } catch (Exception $e) {
                $e->log();
            }
        }
    }

    /**
     * @param null|string $table
     * @return array{fields: array<string, mixed>, indexes: array<string, mixed>}|false
     */
    public function describeTable(?string $table): array|false
    {
        return $this->getAdapter()->describeTable($this->protect($table ?? ''));
    }

    /**
     * {@inheritdoc}
     */
    public function protect(string $input): string
    {
        return str_replace('`*`', '*', '`' . str_replace('.', '`.`', $input) . '`');
    }

    /**
     * @return list<AbstractQuery|string>
     */
    public function getQueries(): array
    {
        return $this->_queries;
    }

    public function getPdo(): mixed
    {
        return $this->pdo;
    }
}

