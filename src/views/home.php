<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Portfolio & Showcase</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="site-navigation-bar">
    <div class="nav-left">
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/dashboard">Study Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/chat" class="nav-icon-link" title="Chat Support">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </a>
            <a href="/profile" class="nav-profile-link"><?= htmlspecialchars($_SESSION['username']); ?></a>
            <a href="/logout" class="nav-logout-btn">Logout</a>
        <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        <?php endif; ?>
    </div>
</nav>

<header>
    <h1>Welcome to My Portfolio Website</h1>
    <p>Radovan Vyšňan | HBO-ICT | HZ University of Applied Sciences</p>
</header>

<div class="container">
    <main>
        <section id="biography">
            <h2>About Me</h2>
            <p>I am an ICT student, specializing in data science, back-end development and DevOps.</p>
            <p>I'm currently a 3rd year bachelor student at HZ University of Applied Sciences. At the moment, I'm doing my Study Abroad Minor at the Frankfurt University of Applied Sciences in Germany.</p>
        </section>

        <section id="api-showcase" class="skills-section">
            <h2>My Skills</h2>
            <p>A representation of core engineering domains, programming frameworks, and infrastructural design competencies.</p>

            <div class="skills-grid">
                <span class="skill-pill">PHP (OOP & MVC)</span>
                <span class="skill-pill">JavaScript (ES6+)</span>
                <span class="skill-pill">SQL (MySQL / PostgreSQL)</span>
                <span class="skill-pill">HTML5</span>

                <span class="skill-pill">Docker Containerization</span>
                <span class="skill-pill">Git & GitHub Actions CI/CD</span>
                <span class="skill-pill">RESTful API Engineering</span>
                <span class="skill-pill">Repository Design Pattern</span>

                <span class="skill-pill">Linux Server Administration (Ubuntu/Debian)</span>
                <span class="skill-pill">SSH Key Authentication & Hardening</span>
                <span class="skill-pill">Firewall Configuration (UFW / Iptables)</span>
            </div>
        </section>

        <article id="academic-dashboard">
            <h2>Academic Progress Dashboard</h2>
            <small>
                Current progress in my Study Abroad Minor (SAM) courses
            </small>

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
                    <td colspan="4">
                        Establishing dynamic endpoint pipeline...
                    </td>
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

    // Cross-Site Scripting protection function
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
</script>
</body>
</html>