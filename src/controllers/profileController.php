<?php

namespace App\Controllers;

use PDO;
use PDOException;

class ProfileController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Endpoint: GET /api/profile-data
     * Publicly fetches the current bio and skills list
     */
    public function getProfileData() {
        header('Content-Type: application/json');
        try {
            // Get Biography Text
            $bioStmt = $this->pdo->query("SELECT bio_text FROM bio_settings WHERE id = 1");
            $bio = $bioStmt->fetch(PDO::FETCH_ASSOC);
            $bioText = $bio ? $bio['bio_text'] : '';

            // Get Skills List
            $skillsStmt = $this->pdo->query("SELECT id, skill_name FROM skills ORDER BY id DESC");
            $skills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

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
    public function updateBio() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $bioText = isset($input['bio_text']) ? trim($input['bio_text']) : '';

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
    public function createSkill() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $skillName = isset($input['skill_name']) ? trim($input['skill_name']) : '';

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
    public function updateSkill() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $skillName = isset($input['skill_name']) ? trim($input['skill_name']) : '';

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
    public function deleteSkill() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? intval($input['id']) : 0;

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