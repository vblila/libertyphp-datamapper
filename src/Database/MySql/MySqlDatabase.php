<?php

namespace Libertyphp\Datamapper\Database\MySql;

use Libertyphp\Datamapper\Database\RawSqlProcessor;
use Libertyphp\Datamapper\Database\SqlDatabaseInterface;
use Libertyphp\Datamapper\Database\SqlQueryException;
use Libertyphp\Datamapper\Database\SqlQueryProfiler;
use Libertyphp\Datamapper\Database\SqlQueryProfilerResult;
use PDO;

class MySqlDatabase implements SqlDatabaseInterface
{
    private PDO $pdoConnection;

    private ?SqlQueryProfiler $sqlQueryProfiler;

    public function __construct(PDO $pdoConnection, ?SqlQueryProfiler $sqlQueryProfiler = null)
    {
        $this->pdoConnection    = $pdoConnection;
        $this->sqlQueryProfiler = $sqlQueryProfiler;
    }

    public function getLastInsertedId(): string
    {
        return $this->pdoConnection->lastInsertId();
    }

    public function select(string $sql, array $binds = []): array
    {
        $rawSqlProcessor = new RawSqlProcessor($sql, $binds);

        $processedSql   = $rawSqlProcessor->getProcessedSql();
        $processedBinds = $rawSqlProcessor->getProcessedBinds();

        $sqlQueryProfilerResult = new SqlQueryProfilerResult($processedSql, $processedBinds);

        $pdoStatement = $this->pdoConnection->prepare($processedSql);
        if (!$pdoStatement->execute($processedBinds)) {
            throw new SqlQueryException(join("\n", $pdoStatement->errorInfo()));
        }

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $sqlQueryProfilerResult->setFinishMicroTimestamp(microtime(true));
        if ($this->sqlQueryProfiler) {
            $this->sqlQueryProfiler->addSqlQueryProfilerResult($sqlQueryProfilerResult);
        }

        return $rows;
    }

    public function selectRow(string $sql, array $binds = []): ?array
    {
        $result = $this->select($sql, $binds);
        return $result ? $result[0] : null;
    }

    public function execute(string $sql, array $binds = []): void
    {
        $rawSqlProcessor = new RawSqlProcessor($sql, $binds);

        $processedSql   = $rawSqlProcessor->getProcessedSql();
        $processedBinds = $rawSqlProcessor->getProcessedBinds();

        $sqlQueryProfilerResult = new SqlQueryProfilerResult($processedSql, $processedBinds);

        $pdoStatement = $this->pdoConnection->prepare($processedSql);
        $result = $pdoStatement->execute($processedBinds);

        $sqlQueryProfilerResult->setFinishMicroTimestamp(microtime(true));
        if ($this->sqlQueryProfiler) {
            $this->sqlQueryProfiler->addSqlQueryProfilerResult($sqlQueryProfilerResult);
        }

        if (!$result) {
            throw new SqlQueryException(join("\n", $pdoStatement->errorInfo()));
        }
    }

    public function batchInsert(string $tableName, array $batchColumnValues): void
    {
        $columns = array_keys($batchColumnValues[0]);
        $columnsString = join(', ', $columns);

        $manyValues = [];
        $binds = [];

        foreach ($batchColumnValues as $index => $columnValues) {
            $values = [];
            foreach ($columnValues as $column => $value) {
                $values[] = ":{$column}_{$index}";
                $binds["{$column}_{$index}"] = $value;
            }

            $manyValues[] = '(' . join(',', $values) . ')';
        }

        $valuesString = join(',', $manyValues);

        $this->execute("INSERT INTO {$tableName} ({$columnsString}) VALUES {$valuesString}", $binds);
    }

    public function beginTransaction(): void
    {
        if (!$this->pdoConnection->inTransaction()) {
            $this->pdoConnection->beginTransaction();
        }
    }

    public function commitTransaction(): void
    {
        if ($this->pdoConnection->inTransaction()) {
            $this->pdoConnection->commit();
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->pdoConnection->inTransaction()) {
            $this->pdoConnection->rollBack();
        }
    }
}
