<?php

namespace Libertyphp\Datamapper\Database;

interface SqlDatabaseInterface
{
    public function getLastInsertedId(): string;

    /**
     * @throws SqlQueryException
     */
    public function select(string $sql, array $binds = []): array;

    /**
     * @throws SqlQueryException
     */
    public function selectRow(string $sql, array $binds = []): ?array;

    /**
     * @throws SqlQueryException
     */
    public function execute(string $sql, array $binds = []): void;

    /**
     * @throws SqlQueryException
     */
    public function batchInsert(string $tableName, array $batchColumnValues): void;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;
}
