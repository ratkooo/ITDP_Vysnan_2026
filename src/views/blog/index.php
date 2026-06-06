<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retain session structural context checks for rendering UI shells
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
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
    <h1>Radovan's blog posts</h1>
    <p>Academic insights and technical perspectives</p>
</header>

<div class="container">
    <main>
        <h2>Latest Publications</h2>
        <p style="color: #64748b; margin-bottom: 1.5rem;">Explore past blog posts posted by me.</p>

        <div id="message-container"></div>

        <?php if ($isAdmin) : ?>
            <?php if (isset($_GET['new']) && $_GET['new'] == '1') : ?>
                <article>
                    <h3>New Blog Article</h3>
                    <form id="create-post-form">
                        <div>
                            <label for="new-title">Title</label>
                            <input type="text" id="new-title" name="title" required>
                        </div>

                        <div>
                            <label for="new-summary">Summary</label>
                            <input type="text" id="new-summary" name="summary" required>
                        </div>

                        <div>
                            <label for="new-content">Description</label>
                            <textarea id="new-content" name="content" required></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn">Publish Article</button>
                            <a href="/blog" class="btn">Cancel</a>
                        </div>
                    </form>
                </article>
            <?php else : ?>
                <p style="margin-bottom: 1.5rem;">
                    <a href="/blog?new=1" class="btn">➕ Add New Publication</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <div id="blog-posts-container">
            <p style="color: #64748b;">Loading dynamic publication records...</p>
        </div>
    </main>

    <aside>
        <sidebar>
            <h2>Administrative Rights</h2>
            <?php if ($isAdmin) : ?>
                <p class="text-success">✔ Signed in with admin privileges.</p>
            <?php else : ?>
                <p>Sign in as an admin account to activate content creation features across this node matrix.</p>
            <?php endif; ?>
        </sidebar>
    </aside>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const postsContainer = document.getElementById('blog-posts-container');
    const messageContainer = document.getElementById('message-container');
    const createForm = document.getElementById('create-post-form');

    // 1. Fetch Archive Dataset Loop
    if (postsContainer) {
        fetch('/api/blog/posts')
            .then(response => {
                if (!response.ok) throw new Error("Infrastructure collection error.");
                return response.json();
            })
            .then(posts => {
                postsContainer.innerHTML = ''; // Reset container visibility

                if (!posts || posts.length === 0) {
                    postsContainer.innerHTML = `
                        <article>
                            <h3>No Publications Found</h3>
                            <small>Archive Empty</small>
                            <p>Check back later for newly recorded field insights.</p>
                        </article>
                    `;
                    return;
                }

                // Append matching elements into current DOM workspace template loops
                posts.forEach(post => {
                    const article = document.createElement('article');
                    // Format system date cleanly for users
                    const pubDate = new Date(post.created_at).toLocaleDateString('en-US', {
                        month: 'long', day: 'numeric', year: 'numeric'
                    });

                    article.innerHTML = `
                        <h3>${escapeHtml(post.title)}</h3>
                        <small>Published on ${pubDate} by ${escapeHtml(post.author || 'Admin')}</small>
                        <p style="color: var(--text); margin-bottom: 1.25rem;">${escapeHtml(post.summary)}</p>
                        <div>
                            <a href="/blogpost?id=${post.id}" class="btn" style="background-color: #2563eb;">Read Entire Article →</a>
                        </div>
                    `;
                    postsContainer.appendChild(article);
                });
            })
            .catch(error => {
                postsContainer.innerHTML = `<p class="text-error">Unable to process dynamic content loop metrics query.</p>`;
            });
    }

    // 2. Intercept and handle Create execution loops via AJAX POST
    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('title', document.getElementById('new-title').value);
            formData.append('summary', document.getElementById('new-summary').value);
            formData.append('content', document.getElementById('new-content').value);

            fetch('/api/blog/save', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/blog'; // Force routing redirection state reset
                    } else {
                        messageContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">${escapeHtml(data.error || 'Failed to persist new blog article instance.')}</p>`;
                    }
                })
                .catch(err => {
                    messageContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">Network error encountered while handling transaction logs.</p>`;
                });
        });
    }
});

// Helper function to mitigate XSS vulnerabilities within structural dynamic scripts
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
</body>
</html>