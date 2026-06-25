# Testing Documentation

## 1. Introduction

This document describes the test plan for the ITDP Showcase Application. It covers the testing strategy, the mapping of automated tests to user stories, and instructions for running the test suite. Automated testing is implemented using **PHPUnit 10**, with a target of at least **45% code coverage** of app-level code, as required by the ITDP 2026 specification (Section 3.4).

---

## 2. User Stories and Test Scope

The following functional user stories drive the test plan:

| ID  | User Story |
|-----|------------|
| US1 | As the owner, I want to register and log in so that I can access protected features. |
| US2 | As the owner, I want to manage the professional biographical information so that visitors can easily find more information about me. |
| US3 | As the owner, I want to send and receive messages with visitors via the chat system. |
| US4 | As a visitor, I want to see my messages and communicate with the owner. |

---

## 3. Testing Strategy

The test suite is divided into two layers:

### 3.1 Unit Tests (`tests/unit/`)

Unit tests verify individual classes in complete isolation — no database, no HTTP context, no session state. Dependencies are either absent or replaced with simple stubs.

| Test File | Class Under Test | What is Verified |
|-----------|-----------------|------------------|
| `UserTest.php` | `App\Models\User` | Correct property assignment via readonly constructor; registration validation rules (username length, RFC email format, password length, password confirmation match) via data provider. |
| `MessageTest.php` | `App\Models\Message` | Full property assignment on construction; nullable `id` and `createdAt` fields accept `null`; mutable properties can be updated after construction. |

### 3.2 Integration Tests (`tests/integration/`)

Integration tests wire real controller and repository classes together with mocked PDO/interfaces, verifying that the application layers collaborate correctly. Session state (`$_SESSION`) and GET parameters (`$_GET`) are set up per-test and reset in `setUp()`.

| Test File | Class Under Test | What is Verified |
|-----------|-----------------|------------------|
| `AuthControllerTest.php` | `App\Controllers\AuthController` | Successful registration flow with mocked repository returning no duplicate; duplicate username rejection; valid credentials establish correct `$_SESSION` keys (`user_id`, `username`, `role`); wrong password leaves session empty. |
| `BiographyControllerTest.php` | `App\Controllers\BiographyController` | `getBiographyData()` returns correct JSON structure with bio text and skills array; handles missing data gracefully; returns 500 error JSON on `PDOException`. `updateBio()`, `createSkill()`, `updateSkill()`, `deleteSkill()` each return 403 Unauthorized for non-admin sessions and succeed for admin sessions. |
| `ChatControllerTest.php` | `App\Controllers\ChatController` | `getThreads()` returns 403 for non-admin role and correct thread array for admin. `getMessages()` returns 401 for unauthenticated requests, message array for regular user, empty array when admin provides no target. `sendMessage()` returns 401 unauthenticated and rejects empty payload. |
| `MySQLUserRepositoryTest.php` | `App\Repositories\MySQLUserRepository` | `findByUsername()` returns populated `User` object on match, `null` on no match, and maps `admin` role correctly. `findByEmail()` returns `User` on match, `null` on no match. `insert()` returns `true` on successful execute, `false` on failure. |
| `MySQLMessageRepositoryTest.php` | `App\Repositories\MySQLMessageRepository` | `getActiveThreads()` returns thread array, empty array on false query, empty array when no rows exist. `getMessagesByUserId()` returns message array and empty array when none exist. `save()` returns `true` on success, `false` on failure. |

---

## 4. Test Cases by User Story

### US1 — Authentication (Register & Login)

| Test Case | Type | Test Method | Expected Result |
|-----------|------|-------------|-----------------|
| Valid registration data passes all rules | Unit | `UserTest::testRegistrationValidationRules` (data provider: `Valid Account Registration Data`) | `true` |
| Username shorter than 5 characters is rejected | Unit | `UserTest::testRegistrationValidationRules` (data provider: `Username Too Short Edge Case`) | `false` |
| Non-RFC email format is rejected | Unit | `UserTest::testRegistrationValidationRules` (data provider: `Invalid RFC Email Format`) | `false` |
| Password under 8 characters is rejected | Unit | `UserTest::testRegistrationValidationRules` (data provider: `Password Under Minimum Length`) | `false` |
| Mismatched password confirmation is rejected | Unit | `UserTest::testRegistrationValidationRules` (data provider: `Mismatched Password Fields`) | `false` |
| Successful registration with no duplicates | Integration | `AuthControllerTest::testRegisterActionHandlesSuccessfulFlow` | Completes without error |
| Duplicate username is rejected | Integration | `AuthControllerTest::testRegisterActionRejectsDuplicateEntities` | Registration rejected |
| Valid credentials set session variables | Integration | `AuthControllerTest::testLoginActionEstablishesValidAuthenticatedSession` | `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']` set correctly |
| Wrong password leaves session empty | Integration | `AuthControllerTest::testLoginActionRejectsInvalidCredentials` | `user_id` not present in `$_SESSION` |

### US2 — Biography Management

| Test Case | Type | Test Method | Expected Result |
|-----------|------|-------------|-----------------|
| Biography data returns correct JSON | Integration | `BiographyControllerTest::testGetBiographyDataReturnsJsonWithBioAndSkills` | JSON with `bio` string and `skills` array |
| Missing bio/skills returns empty values | Integration | `BiographyControllerTest::testGetBiographyDataHandlesMissingData` | `bio: ""`, `skills: []` |
| Database error returns 500 error JSON | Integration | `BiographyControllerTest::testGetBiographyDataHandlesDatabaseException` | JSON with `error` key |
| Non-admin cannot update bio | Integration | `BiographyControllerTest::testUpdateBioRejectsNonAdminRole` | `{"success": false, "error": "Unauthorized"}` |
| No session role cannot update bio | Integration | `BiographyControllerTest::testUpdateBioRejectsWhenNoSessionRole` | `{"success": false}` |
| Admin can update bio | Integration | `BiographyControllerTest::testUpdateBioSucceedsForAdminWithEmptyInput` | `{"success": true}` |
| Non-admin cannot create skill | Integration | `BiographyControllerTest::testCreateSkillRejectsNonAdminRole` | `{"success": false, "error": "Unauthorized"}` |
| Empty skill name is rejected | Integration | `BiographyControllerTest::testCreateSkillRejectsEmptySkillName` | `{"success": false, "error": "Skill name cannot be empty"}` |
| Non-admin cannot update skill | Integration | `BiographyControllerTest::testUpdateSkillRejectsNonAdminRole` | `{"success": false, "error": "Unauthorized"}` |
| Invalid update parameters are rejected | Integration | `BiographyControllerTest::testUpdateSkillRejectsInvalidParameters` | `{"success": false, "error": "Invalid parameters"}` |
| Non-admin cannot delete skill | Integration | `BiographyControllerTest::testDeleteSkillRejectsNonAdminRole` | `{"success": false, "error": "Unauthorized"}` |
| Admin can delete skill | Integration | `BiographyControllerTest::testDeleteSkillSucceedsForAdmin` | `{"success": true}` |

### US3 & US4 — Chat (Messages)

| Test Case | Type | Test Method | Expected Result |
|-----------|------|-------------|-----------------|
| Message entity stores all properties correctly | Unit | `MessageTest::testMessageConstructorSetsAllProperties` | All properties match constructor arguments |
| Nullable fields accept null | Unit | `MessageTest::testMessageAcceptsNullableFields` | `id` and `createdAt` are `null` |
| Message properties are mutable | Unit | `MessageTest::testMessagePropertiesAreMutable` | Updated values reflect after assignment |
| Non-admin cannot list threads | Integration | `ChatControllerTest::testGetThreadsRejectsNonAdminRole` | JSON with `error` key |
| Admin receives thread list | Integration | `ChatControllerTest::testGetThreadsReturnsDataForAdmin` | Array with thread data |
| Empty session cannot list threads | Integration | `ChatControllerTest::testGetThreadsRejectsWhenSessionEmpty` | JSON with `error` key |
| Unauthenticated user cannot get messages | Integration | `ChatControllerTest::testGetMessagesRejectsUnauthenticatedRequest` | `{"error": "Authentication required"}` |
| Regular user receives their messages | Integration | `ChatControllerTest::testGetMessagesReturnsMessagesForRegularUser` | Message array |
| Admin with no target gets empty array | Integration | `ChatControllerTest::testGetMessagesReturnsEmptyArrayForAdminWithNoTarget` | `[]` |
| Admin with target user gets messages | Integration | `ChatControllerTest::testGetMessagesReturnsMessagesForAdminWithTargetUserId` | Message array for target user |
| Unauthenticated user cannot send messages | Integration | `ChatControllerTest::testSendMessageRejectsUnauthenticatedRequest` | `{"success": false, "error": "Authentication required"}` |
| Empty message payload is rejected | Integration | `ChatControllerTest::testSendMessageRejectsEmptyMessagePayload` | `{"success": false, "error": "...Empty..."}` |

### Repository Layer (Supporting All Stories)

| Test Case | Type | Test Method | Expected Result |
|-----------|------|-------------|-----------------|
| `findByUsername` returns User when found | Integration | `MySQLUserRepositoryTest::testFindByUsernameReturnsUserWhenFound` | `User` instance with correct properties |
| `findByUsername` returns null when not found | Integration | `MySQLUserRepositoryTest::testFindByUsernameReturnsNullWhenNotFound` | `null` |
| `findByUsername` maps admin role correctly | Integration | `MySQLUserRepositoryTest::testFindByUsernameReturnsAdminRoleCorrectly` | `role === 'admin'` |
| `findByEmail` returns User when found | Integration | `MySQLUserRepositoryTest::testFindByEmailReturnsUserWhenFound` | `User` instance |
| `findByEmail` returns null when not found | Integration | `MySQLUserRepositoryTest::testFindByEmailReturnsNullWhenNotFound` | `null` |
| `insert` returns true on success | Integration | `MySQLUserRepositoryTest::testInsertReturnsTrueOnSuccess` | `true` |
| `insert` returns false on failure | Integration | `MySQLUserRepositoryTest::testInsertReturnsFalseOnFailure` | `false` |
| `getActiveThreads` returns thread array | Integration | `MySQLMessageRepositoryTest::testGetActiveThreadsReturnsThreadArray` | Array of 2 threads |
| `getActiveThreads` returns `[]` on false query | Integration | `MySQLMessageRepositoryTest::testGetActiveThreadsReturnsEmptyArrayOnFalseQuery` | `[]` |
| `getActiveThreads` returns `[]` when no rows | Integration | `MySQLMessageRepositoryTest::testGetActiveThreadsReturnsEmptyWhenNoThreadsExist` | `[]` |
| `getMessagesByUserId` returns messages | Integration | `MySQLMessageRepositoryTest::testGetMessagesByUserIdReturnsMessages` | Array of messages |
| `getMessagesByUserId` returns `[]` when none | Integration | `MySQLMessageRepositoryTest::testGetMessagesByUserIdReturnsEmptyArrayForNoMessages` | `[]` |
| `save` returns true on success | Integration | `MySQLMessageRepositoryTest::testSaveMessageReturnsTrueOnSuccess` | `true` |
| `save` returns false on failure | Integration | `MySQLMessageRepositoryTest::testSaveMessageReturnsFalseOnFailure` | `false` |

---

## 5. Test Environment Setup

### Prerequisites

- PHP 8.2+
- Composer dependencies installed

```bash
composer install
```

### Running the Tests

Run the full test suite:

```bash
php vendor/bin/phpunit
```

Run with code coverage (requires Xdebug or PCOV):

```bash
php vendor/bin/phpunit --coverage-text
```

Run only unit tests:

```bash
php vendor/bin/phpunit --testsuite "ITDP Test Suite" --filter "Tests\\Unit"
```

Run only integration tests:

```bash
php vendor/bin/phpunit --testsuite "ITDP Test Suite" --filter "Tests\\Integration"
```

### PHPUnit Configuration

The test suite is configured in `phpunit.xml`:

- **Bootstrap**: `tests/bootstrap.php` — loads the Composer autoloader and defines `PHPUNIT_RUNNING` to suppress header redirect side effects during controller tests.
- **Source for coverage**: `src/` directory (excluding `src/Views/`).
- **Coverage target**: ≥ 45% of app-level code.

---

## 6. Mocking Strategy

To keep tests fast and self-contained, external dependencies are replaced:

- **PDO / PDOStatement**: Stubbed using `$this->createStub(PDO::class)` and `$this->createStub(PDOStatement::class)` in repository and controller tests. This avoids a live database connection.
- **UserRepositoryInterface**: Mocked using `$this->createMock(UserRepositoryInterface::class)` in `AuthControllerTest`, allowing precise control over `findByUsername`, `findByEmail`, and `insert` return values.
- **HTTP side effects**: Controller methods that call `header()` or `exit` are wrapped in `ob_start()`/`ob_end_clean()` output buffers, and the `PHPUNIT_RUNNING` constant suppresses redirect exits.
- **Session state**: `$_SESSION`, `$_GET` are reset in each test's `setUp()` to ensure full test isolation.

---

## 7. Test Summary

| Category | Count |
|----------|-------|
| Unit Tests | 9 (across 2 files) |
| Integration Tests | 39 (across 5 files) |
| **Total** | **48** |

> Note: PHPUnit counts each data provider row as a separate test. `testRegistrationValidationRules` in `UserTest` has 5 data provider entries, so it contributes 5 test runs rather than 1.

The combination of unit tests on the model layer and integration tests on the controller and repository layers ensures that core business logic, data validation, access control, and database interactions are all verified automatically.
