<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin System Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .dashboard-grid { display: flex; gap: 20px; margin-top: 20px; }
        .card { background: #f8f9fa; border: 1px solid #e3e6f0; border-radius: 6px; padding: 20px; flex: 1; }
        .metric-num { font-size: 2rem; font-weight: bold; color: #007bff; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e3e6f0; }
        th { background-color: #f1f3f9; }
    </style>
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
    <h1>System Dashboard</h1>
    <p>Data metrics rendered directly via client-side asynchronous fetch loops.</p>
</header>

<div class="container">
    <main>
        <h2>Asynchronous API Data Feed</h2>

        <div class="dashboard-grid">
            <div class="card">
                <h3>System Status Mapping</h3>
                <div id="system-status" class="metric-num">Loading...</div>
                <p id="api-meta" style="color: #6c757d; font-size: 0.9rem;"></p>
            </div>

            <div class="card">
                <h3>Total Registered Accounts</h3>
                <div id="total-users-metric" class="metric-num">--</div>
                <p style="color: #6c757d; font-size: 0.9rem;">Live database calculation registry value</p>
            </div>
        </div>

        <div class="card" style="margin-top: 25px;">
            <h3>Recent Active Database Accounts List</h3>
            <table>
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Assigned Security Role</th>
                    <th>Timestamp Registered</th>
                </tr>
                </thead>
                <tbody id="users-table-body">
                <tr>
                    <td colspan="3" style="text-align: center; color: #6c757d;">Fetching real-time records timeline...</td>
                </tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Execute data loading when document window visual assets are structured
    document.addEventListener("DOMContentLoaded", () => {
        loadDashboardStats();
        loadRecentUsers();
    });

    // 1. Client-Side API Consumer: GET Endpoint 1
    function loadDashboardStats() {
        fetch('/api/dashboard-stats')
            .then(response => {
                if (!response.ok) throw new Error("Network metrics connection failure.");
                return response.json();
            })
            .then(data => {
                // Manipulate DOM tree node states dynamically based on incoming JSON properties
                document.getElementById('system-status').textContent = data.status.toUpperCase();
                document.getElementById('total-users-metric').textContent = data.total_accounts;
                document.getElementById('api-meta').textContent = `Engine Vers: ${data.api_version} | Refreshed: ${data.last_updated}`;
            })
            .catch(err => {
                document.getElementById('system-status').textContent = "ERROR";
                console.error("Dashboard stats fetch pipeline error:", err);
            });
    }

    // 2. Client-Side API Consumer: GET Endpoint 2
    function loadRecentUsers() {
        fetch('/api/recent-registrations')
            .then(response => {
                if (!response.ok) throw new Error("Network database list collection failure.");
                return response.json();
            })
            .then(usersArray => {
                const tableBody = document.getElementById('users-table-body');
                tableBody.innerHTML = ""; // Clear existing placeholder layout elements

                if (usersArray.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;">No accounts exist within db context.</td></tr>`;
                    return;
                }

                // Map loop array into structural table cells
                usersArray.forEach(user => {
                    const row = document.createElement('tr');

                    row.innerHTML = `
                        <td><strong>${escapeHtml(user.username)}</strong></td>
                        <td><span class="tag">${escapeHtml(user.role)}</span></td>
                        <td>${escapeHtml(user.created_at)}</td>
                    `;

                    tableBody.appendChild(row);
                });
            })
            .catch(err => {
                document.getElementById('users-table-body').innerHTML =
                    `<tr><td colspan="3" style="text-align:center; color:#dc3545;">Failed to load system dataset.</td></tr>`;
                console.error("User collection fetch pipeline error:", err);
            });
    }

    // XSS mitigation parsing string safety utility helper
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
</body>
</html>