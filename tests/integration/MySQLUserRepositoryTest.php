<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repositories\MySQLUserRepository;
use App\Models\User;
use PDO;
use PDOStatement;

class MySQLUserRepositoryTest extends TestCase
{
    private PDO $pdoMock;
    private MySQLUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdoMock    = $this->createStub(PDO::class);
        $this->repository = new MySQLUserRepository($this->pdoMock);
    }

    // ─── findByUsername ──────────────────────────────────────────────────────

    /**
     * findByUsername() returns a populated User object when a matching row exists.
     */
    public function testFindByUsernameReturnsUserWhenFound(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'id'       => 1,
            'username' => 'testUser',
            'email'    => 'test@hz.nl',
            'password' => 'hashedPassword',
            'role'     => 'user',
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->findByUsername('testUser');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('testUser', $result->username);
        $this->assertEquals('test@hz.nl', $result->email);
        $this->assertEquals('hashedPassword', $result->passwordHash);
        $this->assertEquals('user', $result->role);
    }

    /**
     * findByUsername() returns null when no row matches.
     */
    public function testFindByUsernameReturnsNullWhenNotFound(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->findByUsername('nonexistent');

        $this->assertNull($result);
    }

    /**
     * findByUsername() maps the 'admin' role correctly.
     */
    public function testFindByUsernameReturnsAdminRoleCorrectly(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'id'       => 99,
            'username' => 'adminUser',
            'email'    => 'admin@hz.nl',
            'password' => 'adminHash',
            'role'     => 'admin',
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->findByUsername('adminUser');

        $this->assertNotNull($result);
        $this->assertEquals('admin', $result->role);
    }

    // ─── findByEmail ─────────────────────────────────────────────────────────

    /**
     * findByEmail() returns a populated User object when a matching row exists.
     */
    public function testFindByEmailReturnsUserWhenFound(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn([
            'id'       => 2,
            'username' => 'emailUser',
            'email'    => 'user@hz.nl',
            'password' => 'emailHash',
            'role'     => 'user',
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->findByEmail('user@hz.nl');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('emailUser', $result->username);
        $this->assertEquals('user@hz.nl', $result->email);
    }

    /**
     * findByEmail() returns null when no row matches.
     */
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->findByEmail('missing@hz.nl');

        $this->assertNull($result);
    }

    // ─── insert ──────────────────────────────────────────────────────────────

    /**
     * insert() returns true when the prepared statement executes successfully.
     */
    public function testInsertReturnsTrueOnSuccess(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->insert('newUser', 'new@hz.nl', 'hashedPw', 'user');

        $this->assertTrue($result);
    }

    /**
     * insert() returns false when statement execution fails.
     */
    public function testInsertReturnsFalseOnFailure(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->insert('failUser', 'fail@hz.nl', 'hash', 'user');

        $this->assertFalse($result);
    }
}
