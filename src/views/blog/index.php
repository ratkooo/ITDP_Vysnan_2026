<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Article View</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

    <nav>
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <a href="/dashboard">Study Dashboard</a>
        <a href="/login">Login</a>
    </nav>

    <header>
        <h1>Object-Oriented Design in PHP 8.2</h1>
        <p>Published on June 1, 2026 by Project Owner</p>
    </header>

    <div class="container">
        <main>
            <article>
                <p>Dependency injection and structural isolation ensure your domain logic never interacts with underlying data layer drivers directly. By building distinct repository files, switching connection layers remains completely invisible to upstream application logic.</p>

                <p>Adhering strictly to compliance baselines like PSR-12 guarantees team tracking reads run smoothly across cross-functional code pipelines.</p>
            </article>

            <a href="/blog" class="btn">&larr; Back to Blog Overview</a>
        </main>

        <aside>
            <sidebar>
                <h2>Article Metadata</h2>
                <p><strong>Status:</strong> <span class="text-success">Live (Published)</span></p>
                <p><strong>Target Audience:</strong> Software Engineers, Evaluators</p>
                <p><strong>Reading Time:</strong> 3 mins</p>
            </sidebar>
        </aside>
    </div>

    <footer>
        <p>&copy; <?= date('Y'); ?> Portfolio App. Built using PSR-12 standards.</p>
    </footer>

</body>
</html>