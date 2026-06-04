<?php

namespace App\Controllers;

use PDO;
use PDOException;

class ProfileController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Endpoint: GET /api/profile-data
     */
    public function getProfileData(): void
    {
        header('Content-Type: application/json');
        try {
            $bioStmt = $this->pdo->query("SELECT bio_text FROM bio_settings WHERE id = 1");
            $bio = $bioStmt ? $bioStmt->fetch(PDO::FETCH_ASSOC) : false;
            $bioText = $bio ? $bio['bio_text'] : '';

            $skillsStmt = $this->pdo->query("SELECT id, skill_name FROM skills ORDER BY id DESC");
            $skills = $skillsStmt ? $skillsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            echo json_encode([
                'bio' => $bioText,
                'skills' => $skills
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed']);
        }
    }

    /**
     * Endpoint: POST /api/admin/update-bio
     */
    public function updateBio(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput !== false ? $rawInput : '', true);
        $bioText = isset($input['bio_text']) ? trim((string)$input['bio_text']) : '';

        try {
            $stmt = $this->pdo->prepare("UPDATE bio_settings SET bio_text = :bio_text WHERE id = 1");
            $stmt->execute(['bio_text' => $bioText]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Update error']);
        }
    }

    /**
     * Endpoint: POST /api/admin/skills/create
     */
    public function createSkill(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput !== false ? $rawInput : '', true);
        $skillName = isset($input['skill_name']) ? trim((string)$input['skill_name']) : '';

        if (empty($skillName)) {
            echo json_encode(['success' => false, 'error' => 'Skill name cannot be empty']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO skills (skill_name) VALUES (:skill_name)");
            $stmt->execute(['skill_name' => $skillName]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database write error']);
        }
    }

    /**
     * Endpoint: POST /api/admin/skills/update
     */
    public function updateSkill(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput !== false ? $rawInput : '', true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $skillName = isset($input['skill_name']) ? trim((string)$input['skill_name']) : '';

        if (!$id || empty($skillName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE skills SET skill_name = :skill_name WHERE id = :id");
            $stmt->execute(['skill_name' => $skillName, 'id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Update failure']);
        }
    }

    /**
     * Endpoint: POST /api/admin/skills/delete
     */
    public function deleteSkill(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput !== false ? $rawInput : '', true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;

        try {
            $stmt = $this->pdo->prepare("DELETE FROM skills WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Deletion failure']);
        }
    }
}
