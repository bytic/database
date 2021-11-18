<?php

namespace Nip\Database\Connections;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Result;

/**
 * Trait ConnectionLegacyMethods
 * @package Nip\Database\Connections
 */
trait ConnectionLegacyMethods
{
    protected $_queries = [];

    /**
     * @inheritDoc
     */
    public function executeQuery(
        string $sql,
        array $params = [],
        $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        $result = parent::executeQuery($sql, $params, $types, $qcp);

        return \Nip\Database\Result::fromDBAL($result);
    }

    /**
     * @inheritDoc
     */
    public function executeCacheQuery($sql, $params, $types, QueryCacheProfile $qcp): Result
    {
        $result = parent::executeQuery($sql, $params, $types, $qcp);

        return \Nip\Database\Result::fromDBAL($result);
    }

    /**
     * @inheritDoc
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $result = parent::executeQuery($sql, $params, $types, $qcp);

        return \Nip\Database\Result::fromDBAL($result);
    }

    /**
     * @param string $database
     * @deprecated Databases should be selected in config
     */
    public function setDatabase($database)
    {
    }

    /**
     * Disconnects from server
     * @deprecated use $connection->close()
     */
    public function disconnect()
    {
        $this->triggerDeprecation(__METHOD__, 'close');
        $this->close();
    }

    /**
     * Adds backticks to input
     *
     * @param string $input
     * @return string
     */
    public function protect($input)
    {
        $this->triggerDeprecation(__METHOD__, 'quoteIdentifier');

        return $this->quoteIdentifier($input);
//        return str_replace("`*`", "*", '`'.str_replace('.', '`.`', $input).'`');
    }


    /**
     * @return array
     */
    public function getQueries(): ?array
    {
        $this->triggerDeprecation(__METHOD__, '_config->getSQLLogger()');

        /** @var DebugStack $logger */
        $logger = $this->_config->getSQLLogger();

        if (!is_object($logger) && !property_exists($logger, 'queries')) {
            return null;
        }

        return $logger->queries;
    }

    /**
     * @param string $method
     */
    protected function triggerDeprecation($method, $alternative = null)
    {
        @trigger_error(
            sprintf(
                "Method %s is no longer available for %s. Moving to Doctrine Connection. %s",
                $method, __CLASS__,
                ($alternative ? 'Use alternative ->'.$alternative.' instead.' : 'No alternatives provided.')
            ),
            E_USER_DEPRECATED
        );
    }
}