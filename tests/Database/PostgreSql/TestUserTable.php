<?php

namespace Libertyphp\Datamapper\Tests\Database\PostgreSql;

use Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable;

final class TestUserTable extends PostgreSqlDatabaseTable
{
    public static function getTableName(): string
    {
        return 'test_users';
    }

    public static function getPrimaryKeyName(): string
    {
        return 'id';
    }
}