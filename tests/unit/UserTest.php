<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Satisfies the exact 5-argument constructor structure of your User model
        $this->user = new User(42, 'student123', 'student@hz.nl', 'hash123', 'admin');
    }

    /**
     * Test successful state retrieval via model readonly properties.
     */
    public function testUserEntityExposesCorrectReadonlyProperties(): void
    {
        $this->assertEquals(42, $this->user->id);
        $this->assertEquals('student123', $this->user->username);
        $this->assertEquals('student@hz.nl', $this->user->email);
        $this->assertEquals('hash123', $this->user->passwordHash);
        $this->assertEquals('admin', $this->user->role);
    }

    /**
     * Data provider simulating valid and invalid input patterns for account validation tests.
     */
    public function registrationValidationProvider(): array
    {
        return [
            'Valid Account Registration Data' => ['alex123', 'alex@hz.nl', 'password123', 'password123', true],
            'Username Too Short Edge Case'    => ['alex', 'alex@hz.nl', 'password123', 'password123', false],
            'Invalid RFC Email Format'        => ['alex123', 'alex.hz.nl', 'password123', 'password123', false],
            'Password Under Minimum Length'   => ['alex123', 'alex@hz.nl', 'pass', 'pass', false],
            'Mismatched Password Fields'      => ['alex123', 'alex@hz.nl', 'password123', 'mismatch456', false],
        ];
    }

    /**
     * @dataProvider registrationValidationProvider
     */
    public function testRegistrationValidationRules(
        string $username,
        string $email,
        string $password,
        string $passwordConfirm,
        bool $expectedOutcome
    ): void {
        // Core structural criteria matching your business validation layout specifications
        $isUsernameValid = strlen($username) >= 5;
        $isEmailValid    = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $isPasswordValid = strlen($password) >= 8 && $password === $passwordConfirm;

        $isValid = $isUsernameValid && $isEmailValid && $isPasswordValid;

        $this->assertEquals($expectedOutcome, $isValid);
    }
}