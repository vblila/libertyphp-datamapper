<?php

namespace Libertyphp\Datamapper\Tests\Database\PostgreSql;

use Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabase;
use Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable;
use PDO;
use PHPUnit\Framework\TestCase;

final class PostgreSqlDatabaseTableTest extends TestCase
{
    private PostgreSqlDatabase $db;

    private PostgreSqlDatabaseTable $testUserTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PostgreSqlDatabase(new PDO(getenv('postgresql_dsn'), getenv('postgresql_user'), getenv('postgresql_password')));
        $this->db->execute("CREATE SEQUENCE test_users_id_seq START 1");

        $this->db->execute("
            CREATE TABLE test_users (
                id INTEGER NOT NULL DEFAULT NEXTVAL('test_users_id_seq'::REGCLASS),
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) DEFAULT NULL,
                last_name VARCHAR(255) DEFAULT NULL,
                group_id INT DEFAULT NULL,
                CONSTRAINT test_users_pk PRIMARY KEY (id)
            )
        ");

        $this->testUserTable = new TestUserTable($this->db);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db->execute('DROP TABLE test_users');
        $this->db->execute('DROP SEQUENCE test_users_id_seq');
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::saveRow
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRows
     */
    final public function testSimpleInsertAndSelect(): void
    {
        $row = $this->testUserTable->saveRow([
            'email'    => 'ivanov_test@test.local',
            'password' => '123456',
        ]);

        $this->assertSame(1, $row['id']);

        $row = $this->testUserTable->getRow(['email = ?' => 'ivanov_test@test.local']);

        $this->assertCount(6, $row);

        $this->assertSame(1, $row['id']);
        $this->assertSame('ivanov_test@test.local', $row['email']);
        $this->assertSame('123456', $row['password']);
        $this->assertNull($row['first_name']);
        $this->assertNull($row['last_name']);
        $this->assertNull($row['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::saveRow
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::deleteRow
     */
    final public function testSimpleInsertAndDelete(): void
    {
        $row = $this->testUserTable->saveRow([
            'email'    => 'ivanov_test@test.local',
            'password' => '123456',
        ]);

        $this->assertSame(1, $row['id']);

        $this->testUserTable->deleteRow($row['id']);

        $rows = $this->testUserTable->getRows([]);
        $this->assertCount(0, $rows);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::saveRow
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::updateRow
     */
    final public function testSimpleInsertAndUpdate(): void
    {
        $row = $this->testUserTable->saveRow([
            'email'    => 'ivanov_test@test.local',
            'password' => '123456',
        ]);

        $this->assertSame(1, $row['id']);

        $row = $this->testUserTable->getRow(['email = ?' => 'ivanov_test@test.local']);
        $row['group_id'] = 12;

        $this->testUserTable->saveRow($row);

        $row = $this->testUserTable->getRow(['email = ?' => 'ivanov_test@test.local']);

        $this->assertSame(1, $row['id']);
        $this->assertSame('ivanov_test@test.local', $row['email']);
        $this->assertSame('123456', $row['password']);
        $this->assertNull($row['first_name']);
        $this->assertNull($row['last_name']);
        $this->assertSame(12, $row['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRowById
     */
    final public function testGetRowById(): void
    {
        $this->testUserTable->saveRow([
            'email'      => 'ivanov_test@test.local',
            'password'   => '123456',
            'first_name' => 'Ivan',
            'last_name'  => 'Ivanov',
            'group_id'   => 1,
        ]);

        $row = $this->testUserTable->getRowById(1);

        $this->assertCount(6, $row);

        $this->assertSame(1, $row['id']);
        $this->assertSame('ivanov_test@test.local', $row['email']);
        $this->assertSame('123456', $row['password']);
        $this->assertSame('Ivan', $row['first_name']);
        $this->assertSame('Ivanov', $row['last_name']);
        $this->assertSame(1, $row['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRowsByIds
     */
    final public function testGetRowsByIds(): void
    {
        $usersData = [
            [
                'email'      => 'ivanov_test@test.local',
                'password'   => '123456',
                'first_name' => 'Ivan',
                'last_name'  => 'Ivanov',
                'group_id'   => 1,
            ],
            [
                'email'      => 'petrov_test@test.local',
                'password'   => 'qwerty',
                'first_name' => 'Petr',
                'last_name'  => 'Petrov',
                'group_id'   => 2,
            ],
            [
                'email'    => 'sidorov_test@test.local',
                'password' => 'qazqaz',
                'first_name' => 'Sidr',
                'last_name'  => 'Sidorov',
                'group_id' => 3,
            ],
        ];

        $this->db->batchInsert('test_users', $usersData);

        $rows = $this->testUserTable->getRowsByIds([1, 3]);

        $this->assertCount(2, $rows);

        $this->assertSame(1, $rows[0]['id']);
        $this->assertSame('ivanov_test@test.local', $rows[0]['email']);
        $this->assertSame('123456', $rows[0]['password']);
        $this->assertSame('Ivan', $rows[0]['first_name']);
        $this->assertSame('Ivanov', $rows[0]['last_name']);
        $this->assertSame(1, $rows[0]['group_id']);

        $this->assertSame(3, $rows[1]['id']);
        $this->assertSame('sidorov_test@test.local', $rows[1]['email']);
        $this->assertSame('qazqaz', $rows[1]['password']);
        $this->assertSame('Sidr', $rows[1]['first_name']);
        $this->assertSame('Sidorov', $rows[1]['last_name']);
        $this->assertSame(3, $rows[1]['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::saveRow
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRows
     */
    final public function testWhereIn(): void
    {
        $usersData = [
            [
                'email'    => 'ivanov_test@test.local',
                'password' => '123456',
                'group_id' => 1,
            ],
            [
                'email'    => 'petrov_test@test.local',
                'password' => 'qwerty',
                'group_id' => 2,
            ],
            [
                'email'    => 'sidorov_test@test.local',
                'password' => 'qazqaz',
                'group_id' => 3,
            ],
        ];

        $this->db->batchInsert('test_users', $usersData);

        // PDO doesn't support "where in" binds. Our database class transforms query before executing.
        $rows = $this->testUserTable->getRows(['group_id IN (?)' => [1, 3]]);

        $this->assertCount(2, $rows);

        $this->assertSame(1, $rows[0]['id']);
        $this->assertSame('ivanov_test@test.local', $rows[0]['email']);
        $this->assertSame('123456', $rows[0]['password']);
        $this->assertNull($rows[0]['first_name']);
        $this->assertNull($rows[0]['last_name']);
        $this->assertSame(1, $rows[0]['group_id']);

        $this->assertSame(3, $rows[1]['id']);
        $this->assertSame('sidorov_test@test.local', $rows[1]['email']);
        $this->assertSame('qazqaz', $rows[1]['password']);
        $this->assertNull($rows[1]['first_name']);
        $this->assertNull($rows[1]['last_name']);
        $this->assertSame(3, $rows[1]['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::saveRow
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRows
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getCount
     */
    final public function testComplexSelectAndCount(): void
    {
        $usersData = [
            [
                'email'    => 'ivanov_test@test.local',
                'password' => '123456',
                'group_id' => 1,
            ],
            [
                'email'    => 'petrov_test@test.local',
                'password' => 'qwerty',
                'group_id' => 2,
            ],
            [
                'email'    => 'sidorov_test@test.local',
                'password' => 'qazqaz',
                'group_id' => 3,
            ],
        ];

        $this->db->batchInsert('test_users', $usersData);

        $where = [
            'group_id IN (?)' => [1, 3],
            'group_id >= ? OR password IN (?)' => [5, ['123456', 'qwerty']],
            'email LIKE ?' => 'ivanov%',
        ];

        $rows = $this->testUserTable->getRows($where);
        $this->assertCount(1, $rows);

        $rowsCount = $this->testUserTable->getCount($where);
        $this->assertSame(1, $rowsCount);

        $this->assertSame(1, $rows[0]['id']);
        $this->assertSame('ivanov_test@test.local', $rows[0]['email']);
        $this->assertSame('123456', $rows[0]['password']);
        $this->assertNull($rows[0]['first_name']);
        $this->assertNull($rows[0]['last_name']);
        $this->assertSame(1, $rows[0]['group_id']);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRows
     */
    final public function testQuotesInSql(): void
    {
        $this->testUserTable->saveRow([
            'email'      => 'ivanov_test@test.local',
            'password'   => '123456',
            'first_name' => '"Quoted name"',
        ]);

        // Query hasn't problem with quotes
        $row = $this->testUserTable->getRow(['first_name = ?' => "'"]);
        $this->assertNull($row);

        $rowsCount = $this->testUserTable->getCount(['first_name = ?' => "'"]);
        $this->assertSame(0, $rowsCount);

        // Query hasn't problem with escaped quotes
        $row = $this->testUserTable->getRow(['first_name = ?' => "\'"]);
        $this->assertNull($row);

        $rowsCount = $this->testUserTable->getCount(['first_name = ?' => "\'"]);
        $this->assertSame(0, $rowsCount);

        // Query hasn't problem with double quotes
        $row = $this->testUserTable->getRow(['first_name = ?' => '"']);
        $this->assertNull($row);

        $rowsCount = $this->testUserTable->getCount(['first_name = ?' => '"']);
        $this->assertSame(0, $rowsCount);

        // Query hasn't problem with using double quotes by "like" filter
        $row = $this->testUserTable->getRow(['first_name LIKE ?' => '"%name%"']);
        $this->assertNotNull($row);

        $rowsCount = $this->testUserTable->getCount(['first_name LIKE ?' => '"%name%"']);
        $this->assertSame(1, $rowsCount);

        // Query hasn't problem with using double quotes in strict comparison
        $row = $this->testUserTable->getRow(['first_name = ?' => '"Quoted name"']);
        $this->assertNotNull($row);

        $rowsCount = $this->testUserTable->getCount(['first_name = ?' => '"Quoted name"']);
        $this->assertSame(1, $rowsCount);
    }

    /**
     * @covers \Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabaseTable::getRows
     */
    final public function testSimpleSqlInjection(): void
    {
        $this->testUserTable->saveRow([
            'email'    => 'ivanov_test@test.local',
            'password' => '123456',
        ]);

        $inputEmail = '"" OR email in (SELECT email FROM test_users)';

        $row = $this->testUserTable->getRow(['email = ?' => $inputEmail]);
        $this->assertNull($row);

        $rowsCount = $this->testUserTable->getCount(['email = ?' => $inputEmail]);
        $this->assertSame(0, $rowsCount);
    }
}