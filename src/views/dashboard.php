<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance Tracking Dashboard</title>
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
    <h1>Study Programme Progress Dashboard</h1>
    <p>Live higher education milestones tracking data</p>
</header>

<div class="container">
    <main>
        <h2>Completed Course Roadmap</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <thead>
            <tr style="background: var(--light); text-align: left;">
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border);">Course Module</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border);">EC Points</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border);">Grade</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border);">Status</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">Introduction to Development</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">5 EC</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">8.5</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);" class="text-success">Passed</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">Object-Oriented Programming</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">5 EC</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">9.0</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);" class="text-success">Passed</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">IT Development Portfolio</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">10 EC</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border);">-</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border); color: var(--accent);">Active Run</td>
            </tr>
            </tbody>
        </table>
    </main>

    <aside>
        <sidebar>
            <h2>Credit Summary</h2>
            <p>Total academic tracking metrics:</p>
            <h3 style="font-size: 2rem; color: var(--accent); margin: 0.5rem 0;">10 / 60 EC</h3>
            <p>Minimum threshold validation requirements checked dynamically.</p>
        </sidebar>
    </aside>
</div>

<footer>
    <p>&copy; <?= date('Y'); ?> Portfolio App. Built using PSR-12 standards.</p>
</footer>

</body>
</html>