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
        <article id="academic-dashboard">
            <h2>Academic Progress Dashboard</h2>
            <small>
                Tracking current educational milestones and European Credit Transfer (EC) metrics via read-only backend API client loops.
            </small>

            <table>
                <thead>
                <tr>
                    <th>Course Curriculum Title</th>
                    <th>EC Weight</th>
                    <th>Assigned Grade</th>
                    <th>Execution Status</th>
                </tr>
                </thead>
                <tbody id="home-course-table-body">
                <tr>
                    <td colspan="4">
                        Establishing dynamic endpoint pipeline...
                    </td>
                </tr>
                </tbody>
            </table>
        </article>

        <section id="biography">
            <h2>About Me</h2>
            <p>Hello! Welcome to my personal showcase application. I am an aspiring IT professional dedicated to clean code architecture, robust backend lifecycles, and elegant user interfaces.</p>
            <p>This application is constructed using a decoupled PHP backend architecture running securely inside isolated container systems, ensuring high system stability and compliance with global development paradigms.</p>
        </section>

        <section id="api-showcase">
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
    document.addEventListener("DOMContentLoaded", () => {
        // Execute dynamic API consumer request
        fetch('/api/student-courses')
            .then(response => {
                if (!response.ok) throw new Error("API stream collection failure.");
                return response.json();
            })
            .then(coursesArray => {
                const tbody = document.getElementById('home-course-table-body');
                tbody.innerHTML = ""; // Clear out the loading row

                if (coursesArray.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4">No academic milestones registered.</td></tr>`;
                    return;
                }

                // Append the courses row elements dynamically
                coursesArray.forEach(item => {
                    const row = document.createElement('tr');

                    // Maps system status flags strictly to stylesheet class tokens
                    let stateBadge = '';
                    if (item.status.toLowerCase() === 'passed') {
                        stateBadge = `<span class="text-success">✔ PASSED</span>`;
                    } else {
                        stateBadge = `<span>⏳ IN PROGRESS</span>`;
                    }

                    row.innerHTML = `
                        <td><strong>${escapeHtml(item.course_name)}</strong></td>
                        <td>${escapeHtml(item.ec_points)} EC</td>
                        <td>${escapeHtml(item.grade)}</td>
                        <td>${stateBadge}</td>
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

    // Cross-Site Scripting protection function
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
</script>
</body>
</html>