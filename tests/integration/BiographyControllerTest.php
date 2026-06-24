<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\BiographyController;
use PDO;
use PDOStatement;
use PDOException;

class BiographyControllerTest extends TestCase
{
    private PDO $pdoMock;
    private BiographyController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $_SESSION = [];
        $_GET     = [];

        $this->pdoMock  = $this->createStub(PDO::class);
        $this->controller = new BiographyController($this->pdoMock);
    }

    // ─── getBiographyData ────────────────────────────────────────────────────

    /**
     * getBiographyData() returns JSON with bio text and skills array.
     */
    public function testGetBiographyDataReturnsJsonWithBioAndSkills(): void
    {
        $bioStmt = $this->createStub(PDOStatement::class);
        $bioStmt->method('fetch')->willReturn(['bio_text' => 'I am a PHP developer']);

        $skillsStmt = $this->createStub(PDOStatement::class);
        $skillsStmt->method('fetchAll')->willReturn([
            ['id' => 1, 'skill_name' => 'PHP'],
            ['id' => 2, 'skill_name' => 'MySQL'],
        ]);

        $this->pdoMock->method('query')
            ->willReturnOnConsecutiveCalls($bioStmt, $skillsStmt);

        ob_start();
        $this->controller->getBiographyData();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('I am a PHP developer', $decoded['bio']);
        $this->assertCount(2, $decoded['skills']);
        $this->assertEquals('PHP', $decoded['skills'][0]['skill_name']);
    }

    /**
     * getBiographyData() returns empty bio and empty skills when queries return no data.
     */
    public function testGetBiographyDataHandlesMissingData(): void
    {
        $bioStmt = $this->createStub(PDOStatement::class);
        $bioStmt->method('fetch')->willReturn(false);

        $skillsStmt = $this->createStub(PDOStatement::class);
        $skillsStmt->method('fetchAll')->willReturn([]);

        $this->pdoMock->method('query')
            ->willReturnOnConsecutiveCalls($bioStmt, $skillsStmt);

        ob_start();
        $this->controller->getBiographyData();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertEquals('', $decoded['bio']);
        $this->assertEquals([], $decoded['skills']);
    }

    /**
     * getBiographyData() returns 500 JSON error on PDOException.
     */
    public function testGetBiographyDataHandlesDatabaseException(): void
    {
        $this->pdoMock->method('query')
            ->willThrowException(new PDOException('DB connection error'));

        ob_start();
        $this->controller->getBiographyData();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertEquals('Database query failed', $decoded['error']);
    }

    // ─── updateBio ───────────────────────────────────────────────────────────

    /**
     * updateBio() returns 403 JSON when caller is not admin.
     */
    public function testUpdateBioRejectsNonAdminRole(): void
    {
        $_SESSION['role'] = 'user';

        ob_start();
        $this->controller->updateBio();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Unauthorized', $decoded['error']);
    }

    /**
     * updateBio() returns 403 JSON when session has no role key at all.
     */
    public function testUpdateBioRejectsWhenNoSessionRole(): void
    {
        // $_SESSION is empty — no role key

        ob_start();
        $this->controller->updateBio();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
    }

    /**
     * updateBio() persists the update and returns success JSON for admin.
     * php://input is empty in CLI, so bio_text will be ''.
     */
    public function testUpdateBioSucceedsForAdminWithEmptyInput(): void
    {
        $_SESSION['role'] = 'admin';

        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        ob_start();
        $this->controller->updateBio();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertTrue($decoded['success']);
    }

    // ─── createSkill ─────────────────────────────────────────────────────────

    /**
     * createSkill() returns 403 JSON when caller is not admin.
     */
    public function testCreateSkillRejectsNonAdminRole(): void
    {
        $_SESSION['role'] = 'user';

        ob_start();
        $this->controller->createSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Unauthorized', $decoded['error']);
    }

    /**
     * createSkill() returns error JSON when skill_name is empty
     * (php://input is empty in CLI context).
     */
    public function testCreateSkillRejectsEmptySkillName(): void
    {
        $_SESSION['role'] = 'admin';

        ob_start();
        $this->controller->createSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Skill name cannot be empty', $decoded['error']);
    }

    // ─── updateSkill ─────────────────────────────────────────────────────────

    /**
     * updateSkill() returns 403 JSON when caller is not admin.
     */
    public function testUpdateSkillRejectsNonAdminRole(): void
    {
        $_SESSION['role'] = 'user';

        ob_start();
        $this->controller->updateSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Unauthorized', $decoded['error']);
    }

    /**
     * updateSkill() rejects when id=0 or skill_name is empty
     * (php://input empty → id defaults to 0).
     */
    public function testUpdateSkillRejectsInvalidParameters(): void
    {
        $_SESSION['role'] = 'admin';

        ob_start();
        $this->controller->updateSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Invalid parameters', $decoded['error']);
    }

    // ─── deleteSkill ─────────────────────────────────────────────────────────

    /**
     * deleteSkill() returns 403 JSON when caller is not admin.
     */
    public function testDeleteSkillRejectsNonAdminRole(): void
    {
        $_SESSION['role'] = 'user';

        ob_start();
        $this->controller->deleteSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Unauthorized', $decoded['error']);
    }

    /**
     * deleteSkill() executes DELETE and returns success JSON for admin.
     */
    public function testDeleteSkillSucceedsForAdmin(): void
    {
        $_SESSION['role'] = 'admin';

        $stmtMock = $this->createStub(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmtMock);

        ob_start();
        $this->controller->deleteSkill();
        $output = ob_get_clean();

        $decoded = json_decode((string)$output, true);
        $this->assertTrue($decoded['success']);
    }
}
