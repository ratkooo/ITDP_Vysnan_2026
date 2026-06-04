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
$singlePost = null;

// Handle Admin Lifecycle Mutators: Update and Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['action'])) {
        // Operation A: Delete Row
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
                $stmt->execute(['id' => $_POST['id']]);
                header("Location: /blog");
                exit;
            } catch (Exception $e) {
                $errorMessage = "Failed to purge administrative article log.";
            }
        }

        // Operation B: Update Row
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
                    header("Location: /blogpost?id=" . (int)$_POST['id']);
                    exit;
                } catch (Exception $e) {
                    $errorMessage = "Failed to update target persistent engine rows.";
                }
            }
        }
    }
}

// Fetch specific post data mapping context
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, summary, content, author, created_at FROM blog_posts WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $singlePost = $stmt->fetch();

        if (!$singlePost) {
            $errorMessage = "The requested publication context could not be located.";
        }
    } catch (Exception $e) {
        $errorMessage = "Error retrieving specific publication ledger.";
    }
} else {
    header("Location: /blog");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $singlePost ? htmlspecialchars($singlePost['title']) : 'Error'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/../navbar.php'; ?>

<header>
    <h1>The Engineering Publishing Space</h1>
    <p>Detailed Article Presentation Node</p>
</header>

<div class="container">
    <main>
        <?php if (!empty($errorMessage)) : ?>
            <article>
                <p class="text-error" style="margin-bottom: 1rem;"><?= htmlspecialchars($errorMessage); ?></p>
                <a href="/blog" class="btn">← Return to Blog Feed</a>
            </article>
        <?php endif; ?>

        <?php if ($singlePost) : ?>
            <?php if ($isAdmin && isset($_GET['edit'])) : ?>
                <article>
                    <h3>Modify System Article Instance</h3>
                    <form method="POST" action="/blogpost?id=<?= $singlePost['id']; ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $singlePost['id']; ?>">

                        <div>
                            <label for="title">Article Title String</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($singlePost['title']); ?>" required>
                        </div>

                        <div>
                            <label for="summary">Brief Excerpt Summary Paragraph</label>
                            <input type="text" id="summary" name="summary" value="<?= htmlspecialchars($singlePost['summary']); ?>" required>
                        </div>

                        <div>
                            <label for="content">Core Mark Content Markdown</label>
                            <textarea id="content" name="content" required><?= htmlspecialchars($singlePost['content']); ?></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn">Commit Changes</button>
                            <a href="/blogpost?id=<?= $singlePost['id']; ?>" class="btn">Discard Changes</a>
                        </div>
                    </form>
                </article>
            <?php else : ?>
                <article>
                    <a href="/blog" style="text-decoration: none; font-weight: 600; color: #2563eb; display: inline-block; margin-bottom: 1rem;">← Back to Archive Feed</a>

                    <h2 style="color: var(--primary); font-size: 2rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($singlePost['title']); ?></h2>
                    <small style="display: block; color: #64748b; margin-bottom: 1.5rem;">
                        Published on <?= date('F j, Y', strtotime($singlePost['created_at'])); ?> by <?= htmlspecialchars($singlePost['author'] ?? 'Admin'); ?>
                    </small>

                    <p style="font-size: 1.15rem; color: var(--primary); line-height: 1.7; background-color: var(--light); padding: 1rem; border-left: 4px solid var(--accent); border-radius: 4px; margin-bottom: 2rem;">
                        <strong>Summary:</strong> <em><?= htmlspecialchars($singlePost['summary']); ?></em>
                    </p>

                    <div style="font-size: 1.05rem; line-height: 1.8; color: #1e293b; margin-bottom: 2rem;">
                        <?= nl2br(htmlspecialchars($singlePost['content'])); ?>
                    </div>

                    <?php if ($isAdmin) : ?>
                        <hr style="margin: 2rem 0;">
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="/blogpost?id=<?= $singlePost['id']; ?>&edit=1" class="btn">Modify Post Content</a>

                            <form method="POST" action="/blogpost?id=<?= $singlePost['id']; ?>" onsubmit="return confirm('Confirm complete removal of this permanent publication?');" style="margin: 0;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $singlePost['id']; ?>">
                                <button type="submit" class="btn" style="background-color: var(--error);">Purge/Delete Article</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <aside>
        <sidebar>
            <h2>Navigation Node</h2>
            <p>Return to the archive overview or core tracking nodes.</p>
            <a href="/blog" class="btn" style="width: 100%; margin-bottom: 0.75rem; background-color: var(--secondary);">Back to Snippets</a>
            <a href="/" class="btn" style="width: 100%;">Return to Home Overview</a>
            <hr>
            <h2>Administrative Rights</h2>
            <?php if ($isAdmin) : ?>
                <p class="text-success">✔ Signed in with full alteration and content revision authorities.</p>
            <?php else : ?>
                <p>Sign in as an admin account to activate modification tools across this viewpoint row.</p>
            <?php endif; ?>
        </sidebar>
    </aside>
</div>
</body>
</html>
