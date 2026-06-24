<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retain session structural context checks for rendering administrative controls
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article Overview</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/../navbar.php'; ?>

<header>
    <h1>Article overview</h1>
</header>

<div class="container">
    <main id="main-content-node">
        <div id="error-feedback-container"></div>

        <div id="blog-content-container">
            <p style="color: #64748b;">Retrieving specific publication ledger context metrics...</p>
        </div>
    </main>

    <aside>
        <sidebar>
            <h2>Navigation</h2>
            <p>Return back to the home overview.</p>
            <a href="/" class="btn" style="width: 100%;">Return to Home</a>
            <hr>
            <h2>Administrative Rights</h2>
            <?php if ($isAdmin) : ?>
                <p class="text-success">✔ Signed in with full alteration and content revision authorities.</p>
            <?php else : ?>
                <p>Sign in as an admin account to enable modification tools for this blog post.</p>
            <?php endif; ?>
        </sidebar>
    </aside>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Determine context identity flags from current location tracking states
    const urlParams = new URLSearchParams(window.location.search);
    const postId = urlParams.get('id');
    const isEditMode = urlParams.has('edit') && <?= $isAdmin ? 'true' : 'false' ?>;

    const contentContainer = document.getElementById('blog-content-container');
    const feedbackContainer = document.getElementById('error-feedback-container');

    if (!postId) {
        window.location.href = '/blog';
        return;
    }

    // 1. Execution Path: Load core post parameters asynchronously
    fetch(`/api/blog/post-detail?id=${postId}`)
        .then(response => {
            if (!response.ok) throw new Error("Target instance unavailable.");
            return response.json();
        })
        .then(post => {
            document.title = escapeHtml(post.title);
            contentContainer.innerHTML = ''; // Flush placeholder messages

            if (isEditMode) {
                // Render Application Interface State: Update Form
                contentContainer.innerHTML = `
                    <article>
                        <h3>Modify Article</h3>
                        <form id="update-post-form">
                            <input type="hidden" id="edit-id" value="${post.id}">

                            <div>
                                <label for="title">Title</label>
                                <input type="text" id="edit-title" value="${escapeHtml(post.title)}" required>
                            </div>

                            <div>
                                <label for="summary">Summary</label>
                                <input type="text" id="edit-summary" value="${escapeHtml(post.summary)}" required>
                            </div>

                            <div>
                                <label for="content">Description</label>
                                <textarea id="edit-content" style="height: 300px;" required>${escapeHtml(post.content)}</textarea>
                            </div>

                            <div>
                                <button type="submit" class="btn">Save Changes</button>
                                <a href="/blogpost?id=${post.id}" class="btn" style="background-color: var(--error);">Discard Changes</a>
                            </div>
                        </form>
                    </article>
                `;
                bindUpdateEvent();
            } else {
                // Render Application Interface State: Read Mode View
                const pubDate = new Date(post.created_at).toLocaleDateString('en-US', {
                    month: 'long', day: 'numeric', year: 'numeric'
                });

                // Convert line endings cleanly to replicate standard paragraph formatting layout configurations
                const optimizedBodyContent = escapeHtml(post.content).replace(/\n/g, "<br>");

                contentContainer.innerHTML = `
                    <article>
                        <a href="/blog" style="text-decoration: none; font-weight: 600; color: #2563eb; display: inline-block; margin-bottom: 1rem;">← Back to Blog Feed</a>

                        <h2 style="color: var(--primary); font-size: 2rem; margin-bottom: 0.5rem;">${escapeHtml(post.title)}</h2>
                        <small style="display: block; color: #64748b; margin-bottom: 1.5rem;">
                            Published on ${pubDate} by ${escapeHtml(post.author || 'Admin')}
                        </small>

                        <p style="font-size: 1.15rem; color: var(--primary); line-height: 1.7; background-color: var(--light); padding: 1rem; border-left: 4px solid var(--accent); border-radius: 4px; margin-bottom: 2rem;">
                            <strong>Summary:</strong> <em>${escapeHtml(post.summary)}</em>
                        </p>

                        <div style="font-size: 1.05rem; line-height: 1.8; color: #1e293b; margin-bottom: 2rem;">
                            ${optimizedBodyContent}
                        </div>

                        <?php if ($isAdmin) : ?>
                            <hr style="margin: 2rem 0;">
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="/blogpost?id=${post.id}&edit=1" class="btn">Modify Article</a>
                                <button id="delete-action-trigger" class="btn" style="background-color: var(--error);">Delete Article</button>
                            </div>
                        <?php endif; ?>
                    </article>
                `;
                bindDeleteEvent(post.id);
            }
        })
        .catch(err => {
            feedbackContainer.innerHTML = `
                <article>
                    <p class="text-error" style="margin-bottom: 1rem;">The requested publication context could not be located or tracked.</p>
                    <a href="/blog" class="btn">← Return to Blog Feed</a>
                </article>
            `;
            contentContainer.innerHTML = '';
        });

    // 2. Event Binding Interface Configuration: Handle Administrative Modification Commits
    function bindUpdateEvent() {
        const updateForm = document.getElementById('update-post-form');
        if (!updateForm) return;

        updateForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('id', document.getElementById('edit-id').value);
            formData.append('title', document.getElementById('edit-title').value);
            formData.append('summary', document.getElementById('edit-summary').value);
            formData.append('content', document.getElementById('edit-content').value);

            fetch('/api/blog/save', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/blogpost?id=${postId}`; // Revert execution state track to view layout
                    } else {
                        feedbackContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">${escapeHtml(data.error || 'Failed to update target persistent engine rows.')}</p>`;
                    }
                })
                .catch(err => {
                    feedbackContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">Network failure intercepting application mutation commands.</p>`;
                });
        });
    }

    // 3. Event Binding Interface Configuration: Handle Administrative Deletion Requests (Heuristic 5 Error Prevention)
    function bindDeleteEvent(id) {
        const deleteBtn = document.getElementById('delete-action-trigger');
        if (!deleteBtn) return;

        deleteBtn.addEventListener('click', () => {
            if (confirm("Do you want to delete this article? This operation cannot be undone.")) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('/api/blog/delete', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '/blog'; // Clean fall-through routing back to index layout
                        } else {
                            feedbackContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">Failed to purge administrative article log.</p>`;
                        }
                    })
                    .catch(err => {
                        feedbackContainer.innerHTML = `<p class="text-error" style="margin-bottom: 1rem;">Network communication anomaly encountered during table deletion commands.</p>`;
                    });
            }
        });
    }
});

// Helper validation abstraction mitigating database insertion XSS injection traces
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
</body>
</html>