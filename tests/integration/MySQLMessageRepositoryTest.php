<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repositories\MySQLMessageRepository;
use App\Models\Message;
use PDO;
use PDOStatement;

class MySQLMessageRepositoryTest extends TestCase
{
    private PDO $pdoMock;
    private MySQLMessageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdoMock    = $this->createStub(PDO::class);
        $this->repository = new MySQLMessageRepository($this->pdoMock);
    }

    // ─── getActiveThreads ────────────────────────────────────────────────────

    /**
     * getActiveThreads() returns populated array of threads from a valid query.
     */
    public function testGetActiveThreadsReturnsThreadArray(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([
            ['user_id' => 1, 'thread_owner' => 'alice'],
            ['user_id' => 2, 'thread_owner' => 'bob'],
        ]);

        $this->pdoMock->method('query')->willReturn($stmtMock);

        $result = $this->repository->getActiveThreads();

        $this->assertCount(2, $result);
        $this->assertEquals('alice', $result[0]['thread_owner']);
        $this->assertEquals('bob', $result[1]['thread_owner']);
    }

    /**
     * getActiveThreads() returns empty array when PDO::query() returns false.
     */
    public function testGetActiveThreadsReturnsEmptyArrayOnFalseQuery(): void
    {
        $this->pdoMock->method('query')->willReturn(false);

        $result = $this->repository->getActiveThreads();

        $this->assertEquals([], $result);
    }

    /**
     * getActiveThreads() returns empty array when there are no rows.
     */
    public function testGetActiveThreadsReturnsEmptyWhenNoThreadsExist(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);

        $this->pdoMock->method('query')->willReturn($stmtMock);

        $result = $this->repository->getActiveThreads();

        $this->assertEquals([], $result);
    }

    // ─── getMessagesByUserId ─────────────────────────────────────────────────

    /**
     * getMessagesByUserId() returns messages sorted for the given user ID.
     */
    public function testGetMessagesByUserIdReturnsMessages(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            [
                'sender_username' => 'admin',
                'message_text'    => 'Hello student',
                'created_at'      => '2024-01-01 10:00:00',
            ],
            [
                'sender_username' => 'student123',
                'message_text'    => 'Hi admin',
                'created_at'      => '2024-01-01 10:05:00',
            ],
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->getMessagesByUserId(5);

        $this->assertCount(2, $result);
        $this->assertEquals('Hello student', $result[0]['message_text']);
    }

    /**
     * getMessagesByUserId() returns empty array when no messages exist.
     */
    public function testGetMessagesByUserIdReturnsEmptyArrayForNoMessages(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $result = $this->repository->getMessagesByUserId(999);

        $this->assertEquals([], $result);
    }

    // ─── save ────────────────────────────────────────────────────────────────

    /**
     * save() returns true when the INSERT executes successfully.
     */
    public function testSaveMessageReturnsTrueOnSuccess(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $message = new Message(null, 5, 1, 'admin', 'Test message');
        $result  = $this->repository->save($message);

        $this->assertTrue($result);
    }

    /**
     * save() returns false when statement execution fails.
     */
    public function testSaveMessageReturnsFalseOnFailure(): void
    {
        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        $message = new Message(null, 5, 1, 'admin', 'Failing message');
        $result  = $this->repository->save($message);

        $this->assertFalse($result);
    }
}
