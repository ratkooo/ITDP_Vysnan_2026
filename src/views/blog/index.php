<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Establish administrative clearance credentials flag
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. Establish Database connection context if not pre-injected by core router
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
$successMessage = "";

// 3. Handle Admin Core Data Operations (POST Lifecycle Mutators)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['action'])) {

        // Operation A: Create / Insert New Blog Article Record
        if ($_POST['action'] === 'create' && isset($_POST['title'], $_POST['summary'], $_POST['content'])) {
            $title = trim($_POST['title']);
            $summary = trim($_POST['summary']);
            $content = trim($_POST['content']);

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
                    header("Location: /blog"); // Terminate redirection resubmission bugs
                    exit;
                } catch (Exception $e) {
                    $errorMessage = "Failed to persist new blog article instance.";
                }
            }
        }

        // Operation B: Delete Existing Blog Article Record
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
                header("Location: /blog"); // Clear POST token stream data buffer
                exit;
            } catch (Exception $e) {
                $errorMessage = "Failed to purge administrative article log.";
            }
        }

        // Operation C: Update / Save Changes to Existing Blog Record
        if ($_POST['action'] === 'update' && isset($_POST['id'], $_POST['title'], $_POST['summary'], $_POST['content'])) {
            $title = trim($_POST['title']);
            $summary = trim($_POST['summary']);
            $content = trim($_POST['content']);

            if (empty($title) || empty($summary) || empty($content)) {
                $errorMessage = "All data management form input metrics are mandatory.";
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE blog_posts SET title = :title, summary = :summary, content = :content WHERE id = :id");
                    $stmt->execute([
                            'title' => $title,
                            'summary' => $summary,
                            'content' => $content,
                            'id' => $_POST['id']
                    ]);
                    header("Location: /blog");
                    exit;
                } catch (Exception $e) {
                    $errorMessage = "Failed to update target persistent engine rows.";
                }
            }
        }
    }
}

// 4. Fetch Active Article Ledger Collection Datasets
try {
    $stmt = $pdo->query("SELECT id, title, summary, content, author, created_at FROM blog_posts ORDER BY id DESC");
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

<nav>
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/profile"><?= htmlspecialchars($_SESSION['username']); ?></a>
            <a href="/logout">Logout</a>
        <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </div>
</nav>

<header>
    <h1>The Engineering Publishing Space</h1>
    <p>Architectural Thoughts, Case Studies, and Academic Logs</p>
</header>

<div class="container">
    <main>
        <h2>Latest Publications</h2>

        <?php if (!empty($errorMessage)): ?>
            <p class="text-error"><?= htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <?php if (isset($_GET['new']) && $_GET['new'] == '1'): ?>
                <article>
                    <h3>Compose New Platform Publication</h3>
                    <form method="POST" action="/blog">
                        <input type="hidden" name="action" value="create">

                        <label for="new-title">Article Title String</label>
                        <input type="text" id="new-title" name="title" required>

                        <label for="new-summary">Brief Excerpt Summary Paragraph</label>
                        <input type="text" id="new-summary" name="summary" required>

                        <label for="new-content">Core Mark Content Markdown</label>
                        <textarea id="new-content" name="content" rows="8" required></textarea>

                        <div>
                            <button type="submit" class="btn">Publish Article</button>
                            <a href="/blog" class="btn">Cancel</a>
                        </div>
                    </form>
                </article>
            <?php else: ?>
                <p>
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

                    <?php if ($isAdmin && isset($_GET['edit']) && (int)$_GET['edit'] === (int)$post['id']): ?>
                        <h3>Modify System Article Instance</h3>
                        <form method="POST" action="/blog">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $post['id']; ?>">

                            <label for="title-<?= $post['id']; ?>">Article Title String</label>
                            <input type="text" id="title-<?= $post['id']; ?>" name="title" value="<?= htmlspecialchars($post['title']); ?>" required>

                            <label for="summary-<?= $post['id']; ?>">Brief Excerpt Summary Paragraph</label>
                            <input type="text" id="summary-<?= $post['id']; ?>" name="summary" value="<?= htmlspecialchars($post['summary']); ?>" required>

                            <label for="content-<?= $post['id']; ?>">Core Mark Content Markdown</label>
                            <textarea id="content-<?= $post['id']; ?>" name="content" rows="8" required><?= htmlspecialchars($post['content']); ?></textarea>

                            <div>
                                <button type="submit" class="btn">Commit Changes</button>
                                <a href="/blog" class="btn">Discard Changes</a>
                            </div>
                        </form>

                    <?php else: ?>
                        <h3><?= htmlspecialchars($post['title']); ?></h3>
                        <small>
                            Published on <?= date('F j, Y', strtotime($post['created_at'])); ?> by <?= htmlspecialchars($post['author']); ?>
                        </small>
                        <p><strong>Summary:</strong> <em><?= htmlspecialchars($post['summary']); ?></em></p>
                        <hr>
                        <p><?= nl2br(htmlspecialchars($post['content'])); ?></p>

                        <?php if ($isAdmin): ?>
                            <hr>
                            <div>
                                <a href="/blog?edit=<?= $post['id']; ?>" class="btn">Modify Post Content</a>

                                <form method="POST" action="/blog" onsubmit="return confirm('Confirm complete removal of this permanent publication?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $post['id']; ?>">
                                    <button type="submit" class="btn">Purge/Delete Article</button>
                                </form>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <aside>
        <sidebar>
            <h2>Navigation Node</h2>
            <p>Return to the main dashboard workspace portal at any time to verify real-time tracking metrics.</p>
            <a href="/" class="btn">Return to Home Overview</a>
            <hr>
            <h2>Administrative Rights</h2>
            <?php if ($isAdmin): ?>
                <p class="text-success">✔ Signed in with full platform creation, revision, and deletion authorities.</p>
            <?php else: ?>
                <p>Sign in as an admin account to activate on-the-fly content editing features across this node matrix.</p>
            <?php endif; ?>
        </sidebar>
    </aside>
</div>

<footer>
    <p>&copy; <?= date('Y'); ?> Portfolio App. Built using PSR-1