<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=db_server;dbname=portfolio_db;charset=utf8mb4", "portfolio_user", "portfolio_password", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        die("System ledger registry connection failed.");
    }
}

$errorMessage = "";

// ==========================================
// DIRECT ADMIN LIFECYCLE FORM MUTATORS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['action'])) {
        // 1. Update Biography Text File Entry
        if ($_POST['action'] === 'update_bio' && isset($_POST['bio'])) {
            try {
                $stmt = $pdo->prepare("UPDATE profile_settings SET bio = :bio WHERE id = 1");
                $stmt->execute(['bio' => $_POST['bio']]);
                header("Location: /");
                exit;
            } catch (Exception $e) {
                $errorMessage = "Failed to update target persistent bio tracks.";
            }
        }

        // 2. Create New Engineering Credential Record Token
        if ($_POST['action'] === 'create_skill' && isset($_POST['skill_name'])) {
            $skillName = trim($_POST['skill_name']);
            if (!empty($skillName)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO skills (skill_name) VALUES (:skill_name)");
                    $stmt->execute(['skill_name' => $skillName]);
                    header("Location: /");
                    exit;
                } catch (Exception $e) {
                    $errorMessage = "Failed to persist new credential entry module.";
                }
            } else {
                $errorMessage = "Skill identity naming parameter can't be blank.";
            }
        }

        // 3. Update Existing Technical Competency Token
        if ($_POST['action'] === 'update_skill' && isset($_POST['id'], $_POST['skill_name'])) {
            $skillName = trim($_POST['skill_name']);
            if (!empty($skillName)) {
                try {
                    $stmt = $pdo->prepare("UPDATE skills SET skill_name = :skill_name WHERE id = :id");
                    $stmt->execute([
                            'skill_name' => $skillName,
                            'id' => $_POST['id']
                    ]);
                    header("Location: /");
                    exit;
                } catch (Exception $e) {
                    $errorMessage = "Failed to apply modifications to target record details.";
                }
            }
        }

        // 4. Purge/Delete Selected Professional Skill Registry Link
        if ($_POST['action'] === 'delete_skill' && isset($_POST['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM skills WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
                header("Location: /");
                exit;
            } catch (Exception $e) {
                $errorMessage = "Failed to purge administrative skill entity log.";
            }
        }
    }
}

// Fetch synchronous content records for standard execution render loops
try {
    // Dynamic Bio lookup
    $bioStmt = $pdo->query("SELECT bio FROM profile_settings WHERE id = 1 LIMIT 1");
    $profileData = $bioStmt->fetch();
    $currentBio = $profileData ? $profileData['bio'] : '';

    // Dynamic Skills catalog collection array mapping
    $skillsStmt = $pdo->query("SELECT id, skill_name FROM skills ORDER BY id ASC");
    $skillsList = $skillsStmt->fetchAll();
} catch (Exception $e) {
    $currentBio = "Loading bio footprint details...";
    $skillsList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Portfolio & Showcase</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<header>
    <h1>Welcome to My Portfolio Website</h1>
    <p>Radovan Vyšňan | HBO-ICT | HZ University of Applied Sciences</p>
</header>

<div class="container">
    <main>
        <?php if (!empty($errorMessage)) : ?>
            <p class="text-error" style="margin-bottom: 1rem;"><?= htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <section id="biography">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2>About Me</h2>
                <?php if ($isAdmin && !isset($_GET['edit_bio'])) : ?>
                    <a href="/?edit_bio=1" class="btn" style="padding: 4px 10px; font-size: 0.85em; text-decoration: none;">✏️ Edit Bio</a>
                <?php endif; ?>
            </div>

            <?php if ($isAdmin && isset($_GET['edit_bio'])) : ?>
                <form method="POST" action="/" style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 6px;">
                    <input type="hidden" name="action" value="update_bio">
                    <label for="bio-textarea" style="display:block; font-weight:bold; margin-bottom:5px;">Update Biography Text:</label>
                    <textarea id="bio-textarea" name="bio" rows="6" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; margin-bottom: 10px; resize: vertical;"><?= htmlspecialchars($currentBio); ?></textarea>
                    <div>
                        <button type="submit" class="btn" style="padding: 5px 15px; background-color: #22c55e;">Save Changes</button>
                        <a href="/" class="btn" style="padding: 5px 15px; background-color: #64748b; text-decoration: none; margin-left: 5px;">Cancel</a>
                    </div>
                </form>
            <?php else : ?>
                <div id="bio-content">
                    <?php
                    if (!empty($currentBio)) {
                        foreach (explode("\n\n", $currentBio) as $paragraph) {
                            echo "<p>" . htmlspecialchars($paragraph) . "</p>";
                        }
                    } else {
                        echo '<p class="text-muted">No profile biography registered yet.</p>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="api-showcase" class="skills-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2>My Skills</h2>
                <?php if ($isAdmin && !isset($_GET['add_skill']) && !isset($_GET['edit_skill'])) : ?>
                    <a href="/?add_skill=1" class="btn" style="padding: 4px 10px; font-size: 0.85em; background-color: #3b82f6; text-decoration: none;">➕ Add New Skill</a>
                <?php endif; ?>
            </div>

            <?php if ($isAdmin && isset($_GET['add_skill'])) : ?>
                <form method="POST" action="/" style="margin-bottom: 1.5rem; background: rgba(0,0,0,0.02); padding: 12px; border-radius: 6px; display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="action" value="create_skill">
                    <input type="text" name="skill_name" placeholder="Enter professional skill label..." required style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1;">
                    <button type="submit" class="btn" style="background-color: #22c55e; padding: 6px 12px;">Add</button>
                    <a href="/" class="btn" style="background-color: #64748b; padding: 6px 12px; text-decoration: none;">Cancel</a>
                </form>
            <?php endif; ?>

            <?php if ($isAdmin && isset($_GET['edit_skill'], $_GET['skill_id'])) : ?>
                <form method="POST" action="/" style="margin-bottom: 1.5rem; background: rgba(0,0,0,0.02); padding: 12px; border-radius: 6px; display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="action" value="update_skill">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['skill_id']); ?>">
                    <input type="text" name="skill_name" value="<?= htmlspecialchars($_GET['current_name'] ?? ''); ?>" required style="padding: 6px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1;">
                    <button type="submit" class="btn" style="background-color: #3b82f6; padding: 6px 12px;">Update</button>
                    <a href="/" class="btn" style="background-color: #64748b; padding: 6px 12px; text-decoration: none;">Cancel</a>
                </form>
            <?php endif; ?>

            <p>A representation of core engineering domains, programming frameworks, and infrastructural design competencies.</p>

            <div class="skills-grid" id="skills-grid">
                <?php if (empty($skillsList)) : ?>
                    <p style="color: #64748b; font-style: italic;">No skills registered.</p>
                <?php else : ?>
                    <?php foreach ($skillsList as $skill) : ?>
                        <span class="skill-pill" style="<?= $isAdmin ? 'display: inline-flex; align-items: center; gap: 8px;' : '' ?>">
                            <span><?= htmlspecialchars($skill['skill_name']); ?></span>
                            <?php if ($isAdmin) : ?>
                                <span style="display:inline-flex; gap: 4px; margin-left:4px; font-size:0.9em; border-left:1px solid rgba(255,255,255,0.2); padding-left:6px;">
                                    <a href="/?edit_skill=1&skill_id=<?= $skill['id']; ?>&current_name=<?= urlencode($skill['skill_name']); ?>" title="Edit Skill" style="text-decoration:none;">✏️</a>

                                    <form method="POST" action="/" style="display: inline; margin: 0; padding: 0;" onsubmit="return confirm('Confirm complete removal of this professional registration token?');">
                                        <input type="hidden" name="action" value="delete_skill">
                                        <input type="hidden" name="id" value="<?= $skill['id']; ?>">
                                        <button type="submit" style="background: none; border: none; padding: 0; margin: 0; cursor: pointer; font-size: inherit;">❌</button>
                                    </form>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <article id="academic-dashboard">
            <h2>Academic Progress Dashboard</h2>
            <small>Current progress in my Study Abroad Minor (SAM) courses</small>

            <table>
                <thead>
                <tr>
                    <th>Course Title</th>
                    <th>EC</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody id="home-course-table-body">
                <tr>
                    <td colspan="4">Establishing dynamic endpoint pipeline...</td>
                </tr>
                </tbody>
            </table>
        </article>
    </main>

    <aside>
        <sidebar>
            <h2>Programme Tracking</h2>
            <p>Monitor my active higher academic roadmap progress and EC accumulations live.</p>
            <a href="/dashboard" class="btn">View Study Dashboard</a>
            <hr>
            <h2>Latest Insights</h2>
            <p>Explore ideas on programming architecture inside my publishing space.</p>
            <a href="/blog" class="btn">Read Blog Posts</a>
        </sidebar>
    </aside>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // ==========================================
        // PIPELINE: ACADEMIC PROGRESS GRID DATA
        // ==========================================
        fetch('/api/student-courses')
            .then(response => {
                if (!response.ok) throw new Error("API stream collection failure.");
                return response.json();
            })
            .then(coursesArray => {
                const tbody = document.getElementById('home-course-table-body');
                tbody.innerHTML = "";

                if (coursesArray.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4">No academic milestones registered.</td></tr>`;
                    return;
                }

                coursesArray.forEach(item => {
                    const row = document.createElement('tr');
                    let stateBadge = item.status.toLowerCase() === 'passed'
                        ? `<span class="text-success">✔ PASSED</span>`
                        : `<span>⏳ IN PROGRESS</span>`;

                    row.innerHTML = `
                        <td><strong>${escapeHtml(item.course_name)}</strong></td>
                        <td style="text-align: center; font-family: monospace;">${escapeHtml(item.ec_points)} EC</td>
                        <td style="text-align: center; font-weight: bold; font-family: monospace;">${escapeHtml(item.grade)}</td>
                        <td style="text-align: center;">${stateBadge}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(err => {
                console.error("Home table population failure:", err);
                document.getElementById('home-course-table-body').innerHTML =
                    `<tr><td colspan="4" class="text-error">⚠️ Error compiling dynamic progress grid.</td></tr>`;
            });
    });

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // ==========================================
    // PIPELINE: RECURSIVE CHAT ALERTS NOTIFICATIONS
    // ==========================================
    (function() {
        const badge = document.getElementById('msgExclamationBadge');
        if (!badge) return;

        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const isChatPage = /\/chat(\.php)?\/?$/.test(window.location.pathname);

        if (isChatPage && !isAdmin) {
            badge.style.display = 'none';
        }

        async function checkGlobalMessages() {
            try {
                if (isAdmin) {
                    const response = await fetch('/api/chat-threads');
                    if (!response.ok) return;
                    const threads = await response.json();

                    let showBadge = false;
                    let seenThreads = JSON.parse(localStorage.getItem('chat_seen_threads') || '{}');

                    threads.forEach(thread => {
                        const uid = thread.user_id;
                        const count = parseInt(thread.message_count || 0);
                        const lastSender = thread.last_sender;

                        if (isChatPage && typeof activeTargetUserId !== 'undefined' && activeTargetUserId == uid) {
                            seenThreads[uid] = count;
                        }

                        const lastSeenCount = seenThreads[uid] || 0;
                        if (count > lastSeenCount && lastSender !== 'admin') {
                            if (!(isChatPage && typeof activeTargetUserId !== 'undefined' && activeTargetUserId == uid)) {
                                showBadge = true;
                            }
                        }
                    });

                    localStorage.setItem('chat_seen_threads', JSON.stringify(seenThreads));
                    badge.style.display = showBadge ? 'flex' : 'none';

                } else {
                    const response = await fetch('/api/chat-messages');
                    if (!response.ok) return;
                    const messages = await response.json();

                    const count = messages.length;
                    const lastMsg = messages[count - 1];

                    if (isChatPage) {
                        localStorage.setItem('chat_user_seen_count', count);
                        badge.style.display = 'none';
                    } else {
                        const lastSeenCount = parseInt(localStorage.getItem('chat_user_seen_count') || '0');
                        if (count > lastSeenCount && lastMsg && lastMsg.sender_username === 'admin') {
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Error polling notifications:', error);
            }
        }

        checkGlobalMessages();
        setInterval(checkGlobalMessages, 3000);
    })();
</script>
</body>
</html>
