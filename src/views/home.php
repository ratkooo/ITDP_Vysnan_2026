<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Portfolio & Showcase</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (session_status() == PHP_SESSION_NONE) session_start();  ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
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
    <h1>Welcome to My Professional Showcase</h1>
    <p>Software Engineer | Academic Progress Portfolio</p>
</header>

<div class="container">
    <main>
        <section id="biography">
            <h2>About Me</h2>
            <p>Hello! Welcome to my personal showcase application. I am an aspiring IT professional dedicated to clean code architecture, robust backend lifecycles, and elegant user interfaces.</p>
            <p>This application is constructed using a decoupled PHP backend architecture running securely inside isolated container systems, ensuring high system stability and compliance with global development paradigms.</p>
        </section>

        <section id="api-showcase" style="margin-top: 2.5rem;">
            <h2>Dynamic API Integration</h2>
            <p>The panel below actively pulls live JSON data from my custom backend RESTful endpoints utilizing client-side asynchronous <code>fetch()</code> requests:</p>

            <div id="api-data-container">
                <p><em>Loading live stream data via asynchronous fetch...</em></p>
            </div>
        </section>
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

<footer>
    <p>&copy; <?= date('Y'); ?> Portfolio App. Built using PSR-12 and Docker Engine.</p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiEndpoint = '/api/projects';

        fetch(apiEndpoint)
            .then(response => {
                if (!response.ok) throw new Error('Network interface error');
                return response.json();
            })
            .then(data => {
                const container = document.getElementById('api-data-container');
                container.innerHTML = `<strong>Endpoint Response:</strong> ${data.message || 'Data stream connected successfully.'}`;
            })
            .catch(error => {
                console.error('API Fetch Error:', error);
                const container = document.getElementById('api-data-container');
                container.innerHTML = `<span class="text-error">Failed to asynchronously stream API resources dynamically.</span>`;
            });
    });
</script>
</body>
</html>