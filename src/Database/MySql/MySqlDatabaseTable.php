<?php

namespace Libertyphp\Datamapper\Database\MySql;

use Libertyphp\Datamapper\Database\SqlDatabaseInterface;
use Libertyphp\Datamapper\Database\SqlQueryException;
use Libertyphp\Datamapper\Database\WhereSqlBuilder;

abstract class MySqlDatabaseTable
{
    protected SqlDatabaseInterface $db;

    public function __construct(SqlDatabaseInterface $db)
    {
        $this->db = $db;
    }

    abstract public static function getTableName(): string;

    abstract public static function getPrimaryKeyName(): string;

    public function getDb(): SqlDatabaseInterface
    {
        return $this->db;
    }

    /**
     * @throws SqlQueryException
     */
    public function getRowById(int $id): ?array
    {
        $tableName = static::getTableName();
        $pk = static::getPrimaryKeyName();
        return $this->db->selectRow("SELECT * FROM {$tableName} WHERE {$pk} = :pk", ['pk' => $id]);
    }

    /**
     * @throws SqlQueryException
     */
    public function getRowsByIds(array $ids): array
    {
        $ids = array_unique($ids);
        if (!$ids) {
            return [];
        }

        $tableName = static::getTableName();
        $pk = static::getPrimaryKeyName();

        return $this->db->select("SELECT * FROM {$tableName} WHERE {$pk} IN (:ids)", ['ids' => $ids]);
    }

    /**
     * @throws SqlQueryException
     */
    protected function insertRow(array $columnValues): void
    {
        $sqlColumns = [];
        $sqlValues = [];
        foreach ($columnValues as $column => $value) {
            $sqlColumns[] = "{$column}";
            $sqlValues[] = ":{$column}";
        }

        $columnsSqlString = join(', ', $sqlColumns);
        $valuesSqlString = join(', ', $sqlValues);

        $tableName = static::getTableName();

        $this->db->execute(
            "INSERT INTO {$tableName} ({$columnsSqlString}) VALUES ({$valuesSqlString})",
            $columnValues
        );
    }

    /**
     * @throws SqlQueryException
     */
    protected function updateRow(int $id, array $columnValues): void
    {
        $pk = static::getPrimaryKeyName();

        $columnsSetSql = [];
        foreach ($columnValues as $column => $value) {
            $columnsSetSql[] = "{$column} = :{$column}";
        }

        $columnsSqlString = join(', ', $columnsSetSql);
        $columnValues[$pk] = $id;

        $tableName = static::getTableName();

        $this->db->execute(
            "UPDATE {$tableName} SET {$columnsSqlString} WHERE {$pk} = :id",
            $columnValues
        );
    }

    /**
     * @throws SqlQueryException
     */
    public function saveRow(array $row): array
    {
        $pk = static::getPrimaryKeyName();

        if (empty($row[$pk])) {
            if (isset($row[$pk])) {
                unset($row[$pk]);
            }

            $this->insertRow($row);

            $lastInsertedId = (int) $this->db->getLastInsertedId();
            $row[$pk] = $lastInsertedId;
        } else {
            $pkValue = $row[$pk];
            unset($row[$pk]);

            $this->updateRow($pkValue, $row);
            $row[$pk] = $pkValue;
        }

        return $row;
    }

    /**
     * @throws SqlQueryException
     */
    public function getRows(array $where, ?array $order = null, ?int $limit = null, ?int $offset = null): array
    {
        $tableName = static::getTableName();
        $pk = static::getPrimaryKeyName();

        $whereSqlBuilder = new WhereSqlBuilder($where);

        $whereSql = $whereSqlBuilder->getSql();
        $binds    = $whereSqlBuilder->getBinds();

        $orderSql = $order ? join(', ', $order) : "{$pk} ASC";

        $sql = "SELECT * FROM {$tableName} WHERE {$whereSql} ORDER BY {$orderSql}";

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset) {
            $sql .= " OFFSET {$offset}";
        }

        return $this->db->select($sql, $binds);
    }

    /**
     * @throws SqlQueryException
     */
    public function getRow(array $where, ?array $order = null): ?array
    {
        $rows = $this->getRows($where, $order, 1);
        return $rows[0] ?? null;
    }

    /**
     * @throws SqlQueryException
     */
    public function getCount(array $where): int
    {
        $tableName = static::getTableName();
        $binds = [];

        if (!$where) {
            $sql = "SELECT COUNT(*) cnt FROM {$tableName}";
        } else {
            $whereSqlBuilder = new WhereSqlBuilder($where);

            $whereSql = $whereSqlBuilder->getSql();
            $binds    = $whereSqlBuilder->getBinds();

            $sql = "SELECT COUNT(*) cnt FROM {$tableName} WHERE {$whereSql}";
        }

        return $this->db->selectRow($sql, $binds)['cnt'];
    }

    /**
     * @throws SqlQueryException
     */
    public function deleteRow(int $id): void
    {
        $tableName = static::getTableName();
        $pk = static::getPrimaryKeyName();

        $this->db->execute("DELETE FROM {$tableName} WHERE {$pk} = ?", [$id]);
    }
}
