<?php

namespace Libertyphp\Datamapper\Tests\Database\PostgreSql;

use Libertyphp\Datamapper\Database\PostgreSql\PostgreSqlDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

final class PostgreSqlDatabaseTest extends TestCase
{
    private PostgreSqlDatabase $db;

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db->execute('DROP TABLE test_users');
        $this->db->execute('DROP SEQUENCE test_users_id_seq');
    }

    final public function testSimpleInsertAndSelectQuery(): void
    {
        $this->db->execute("INSERT INTO test_users (email, password) VALUES ('ivanov_test@test.local', '123456')");
        $rows = $this->db->select("SELECT * FROM test_users WHERE email = :email", ['email' => 'ivanov_test@test.local']);

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertCount(6, $row);

        $this->assertSame(1, $row['id']);
        $this->assertSame('ivanov_test@test.local', $row['email']);
        $this->assertSame('123456', $row['password']);
        $this->assertNull($row['first_name']);
        $this->assertNull($row['last_name']);
        $this->assertNull($row['group_id']);
    }

    final public function testSimpleInsertAndSelectRowQuery(): void
    {
        $this->db->execute("INSERT INTO test_users (email, password) VALUES ('ivanov_test@test.local', '123456'), ('petrov_test@test.local', '123456')");
        $row = $this->db->selectRow("SELECT * FROM test_users WHERE password = :password ORDER BY id DESC", ['password' => '123456']);

        $this->assertCount(6, $row);

        $this->assertSame(2, $row['id']);
        $this->assertSame('petrov_test@test.local', $row['email']);
        $this->assertSame('123456', $row['password']);
        $this->assertNull($row['first_name']);
        $this->assertNull($row['last_name']);
        $this->assertNull($row['group_id']);
    }

    final public function testBatchInsert(): void
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

        $rows = $this->db->select("SELECT * FROM test_users ORDER BY id ASC");

        foreach ($rows as $index => $row) {
            $this->assertSame($usersData[$index]['email'], $row['email']);
            $this->assertSame($usersData[$index]['password'], $row['password']);
            $this->assertNull($row['first_name']);
            $this->assertNull($row['last_name']);
            $this->assertSame($usersData[$index]['group_id'], $row['group_id']);
        }
    }

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
        $rows = $this->db->select('SELECT * FROM test_users WHERE group_id IN (:group_ids) ORDER BY id ASC', ['group_ids' => [1, 3]]);

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

    final public function testSimpleSqlInjection(): void
    {
        $this->db->execute("INSERT INTO test_users (email, password) VALUES ('ivanov_test@test.local', '123456')");

        $inputEmail = '"" OR email in (SELECT email FROM test_users)';

        $row = $this->db->selectRow('SELECT * FROM test_users WHERE email = :email', ['email' => $inputEmail]);
        $this->assertNull($row);
    }
}