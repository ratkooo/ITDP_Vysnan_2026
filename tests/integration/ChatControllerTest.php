<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\ChatController;
use PDO;
use PDOStatement;

class ChatControllerTest extends TestCase
{
    private PDO $pdoMock;
    private ChatController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $_SESSION = [];
        $_GET     = [];

        $this->pdoMock  = $this->createStub(PDO::class);
        $this->controller = new ChatController($this->pdoMock);
    }

    // ─── getThreads ──────────────────────────────────────────────────────────

    /**
     * getThreads() returns 403 JSON when session role is not admin.
     */
    public function testGetThreadsRejectsNonAdminRole(): void
    {
        $_SESSION['user_id'] = 5;
        $_SESSION['role']    = 'user';

        ob_start();
        $this->controller->getThreads();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertArrayHasKey('error', $decoded);
    }

    /**
     * getThreads() returns JSON array of user threads for admin.
     */
    public function testGetThreadsReturnsDataForAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role']    = 'admin';

        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([
            [
                'user_id'       => 2,
                'username'      => 'alice',
                'thread_owner'  => 'alice',
                'message_count' => 3,
                'last_sender'   => 'admin',
            ],
        ]);

        $this->pdoMock->method('query')->willReturn($stmtMock);

        ob_start();
        $this->controller->getThreads();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('alice', $decoded[0]['username']);
    }

    /**
     * getThreads() returns 403 when no role key exists in session.
     */
    public function testGetThreadsRejectsWhenSessionEmpty(): void
    {
        // Empty session

        ob_start();
        $this->controller->getThreads();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertArrayHasKey('error', $decoded);
    }

    // ─── getMessages ─────────────────────────────────────────────────────────

    /**
     * getMessages() returns 401 JSON when user is not authenticated.
     */
    public function testGetMessagesRejectsUnauthenticatedRequest(): void
    {
        // No user_id in session

        ob_start();
        $this->controller->getMessages();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('Authentication required', $decoded['error']);
    }

    /**
     * getMessages() returns message array for an authenticated regular user.
     */
    public function testGetMessagesReturnsMessagesForRegularUser(): void
    {
        $_SESSION['user_id'] = 5;
        $_SESSION['role']    = 'user';

        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            [
                'sender_username' => 'admin',
                'message_text'    => 'Welcome!',
                'created_at'      => '2024-01-01 10:00:00',
            ],
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        ob_start();
        $this->controller->getMessages();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Welcome!', $decoded[0]['message_text']);
    }

    /**
     * getMessages() returns empty array when admin has no target user_id in GET params.
     */
    public function testGetMessagesReturnsEmptyArrayForAdminWithNoTarget(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role']    = 'admin';
        // $_GET['user_id'] intentionally not set

        ob_start();
        $this->controller->getMessages();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertEquals([], $decoded);
    }

    /**
     * getMessages() returns messages when admin specifies a target user via GET.
     */
    public function testGetMessagesReturnsMessagesForAdminWithTargetUserId(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role']    = 'admin';
        $_GET['user_id']     = '3';

        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            ['sender_username' => 'user3', 'message_text' => 'Hi admin', 'created_at' => '2024-01-02 09:00:00'],
        ]);

        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        ob_start();
        $this->controller->getMessages();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('Hi admin', $decoded[0]['message_text']);
    }

    // ─── sendMessage ─────────────────────────────────────────────────────────

    /**
     * sendMessage() returns 401 JSON when user is not authenticated.
     */
    public function testSendMessageRejectsUnauthenticatedRequest(): void
    {
        // No session

        ob_start();
        $this->controller->sendMessage();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Authentication required', $decoded['error']);
    }

    /**
     * sendMessage() rejects empty message payload (php://input empty in CLI).
     */
    public function testSendMessageRejectsEmptyMessagePayload(): void
    {
        $_SESSION['user_id']  = 5;
        $_SESSION['role']     = 'user';
        $_SESSION['username'] = 'testUser';

        ob_start();
        $this->controller->sendMessage();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertStringContainsString('Empty', $decoded['error']);
    }
}
