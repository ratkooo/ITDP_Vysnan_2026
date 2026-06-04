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

    (function() {
        const badge = document.getElementById('msgExclamationBadge');
        if (!badge) return;

        const isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'true' : 'false'; ?>;

        // FIXED: Regex checks for /chat or /chat.php anywhere in path structures to prevent broken evaluation rules
        const isChatPage = /\/chat(\.php)?\/?$/.test(window.location.pathname);

        // Immediate Guard: Completely suppress the exclamation badge if standard user lands inside the chat page
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

                        if (isChatPage && activeTargetUserId == uid) {
                            seenThreads[uid] = count;
                        }

                        const lastSeenCount = seenThreads[uid] || 0;
                        if (count > lastSeenCount && lastSender !== 'admin') {
                            // If currently looking at this active thread right now, don't flash an alert
                            if (isChatPage && activeTargetUserId == uid) {
                                // Keep reading
                            } else {
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

        if (isChatPage) {
            const clearBadge = () => {
                badge.style.display = 'none';
                checkGlobalMessages();
            };
            window.addEventListener('focus', clearBadge);
            document.addEventListener('click', clearBadge);
        }
    })();
</script>
</body>
</html>