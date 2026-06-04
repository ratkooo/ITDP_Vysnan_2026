<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=portfolio_db;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        die("System ledger registry connection failed.");
    }
}

$errorMessage = "";

// Handle Admin Lifecycle Mutator: Creating a new article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($summary) || empty($content)) {
            $errorMessage = "All data management form input metrics are mandatory.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO blog_posts (title, summary, content) VALUES (:title, :summary, :content)");
                $stmt->execute([
                        'title' => $title,
                        'summary' => $summary,
                        'content' => $content
                ]);
                header("Location: /blog");
                exit;
            } catch (Exception $e) {
                $errorMessage = "Failed to persist new blog article instance.";
            }
        }
    }
}

// Fetch archive loop dataset
try {
    $stmt = $pdo->query("SELECT id, title, summary, author, created_at FROM blog_posts ORDER BY id DESC");
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    $posts = [];
    $errorMessage = "Unable to process dynamic content loop metrics query.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Insights & Blog Archive</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/../navbar.php'; ?>

<header>
    <h1>The Engineering Publishing Space</h1>
    <p>Architectural Thoughts, Case Studies, and Academic Logs</p>
</header>

<div class="container">
    <main>
        <h2>Latest Publications</h2>
        <p style="color: #64748b; margin-bottom: 1.5rem;">Explore short excerpts of architectural case studies and design logs.</p>

        <?php if (!empty($errorMessage)): ?>
            <p class="text-error" style="margin-bottom: 1rem;"><?= htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <?php if (isset($_GET['new']) && $_GET['new'] == '1'): ?>
                <article>
                    <h3>Compose New Platform Publication</h3>
                    <form method="POST" action="/blog">
                        <input type="hidden" name="action" value="create">

                        <div>
                            <label for="new-title">Article Title String</label>
                            <input type="text" id="new-title" name="title" required>
                        </div>

                        <div>
                            <label for="new-summary">Brief Excerpt Summary Paragraph</label>
                            <input type="text" id="new-summary" name="summary" required>
                        </div>

                        <div>
                            <label for="new-content">Core Mark Content Markdown</label>
                            <textarea id="new-content" name="content" required></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn">Publish Article</button>
                            <a href="/blog" class="btn">Cancel</a>
                        </div>
                    </form>
                </article>
            <?php else: ?>
                <p style="margin-bottom: 1.5rem;">
                    <a href="/blog?new=1" class="btn">➕ Add New Publication</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <article>
                <h3>No Publications Found</h3>
                <small>Archive Empty</small>
                <p>Check back later for newly recorded field insights.</p>
            </article>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article>
                    <h3><?= htmlspecialchars($post['title']); ?></h3>
                    <small>
                        Published on <?= date('F j, Y', strtotime($post['created_at'])); ?> by <?= htmlspecialchars($post['author'] ?? 'Admin'); ?>
                    </small>
                    <p style="color: var(--text); margin-bottom: 1.25rem;">
                        <?= htmlspecialchars($post['summary']); ?>
                    </p>
                    <div>
                        <a href="/blogpost?id=<?= $post['id']; ?>" class="btn" style="background-color: #2563eb;">Read Entire Article →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <aside>
        <sidebar>
            <h2>Navigation Node</h2>
            <p>Return to the main dashboard workspace portal at any time to verify real-time tracking metrics.</p>
            <a href="/" class="btn" style="width: 100%;">Return to Home Overview</a>
            <hr>
            <h2>Administrative Rights</h2>
            <?php if ($isAdmin): ?>
                <p class="text-success">✔ Signed in with full platform creation authorities.</p>
            <?php else: ?>
                <p>Sign in as an admin account to activate content creation features across this node matrix.</p>
            <?php endif; ?>
        </sidebar>
    </aside>
</div>
</body>
</html>