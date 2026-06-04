<tbody id="course-table-body">
<?php if (isset($courses) && is_array($courses)) : ?>
    <?php foreach ($courses as $course) : ?>
        <tr id="row-<?= (int)$course['id']; ?>">
            </tr>
    <?php endforeach; ?>
<?php else : ?>
    <tr><td colspan="5">No courses found.</td></tr>
<?php endif; ?>
</tbody>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance Tracking Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<header>
    <h1>Admin Dashboard</h1>
</header>

<div class="container">
    <main>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2>Course list</h2>
            <button class="btn" onclick="openNewCourseForm()">➕ Add New Module</button>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
            <thead>
            <tr style="background: var(--light); text-align: left;">
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border);">Course Module</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border); width: 100px;">EC Points</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border); width: 80px;">Grade</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border); width: 140px;">Status</th>
                <th style="padding: 0.75rem; border-bottom: 2px solid var(--border); width: 160px; text-align: center;">Actions</th>
            </tr>
            </thead>
            <tbody id="course-table-body">
            <?php foreach ($courses as $course) : ?>
                <tr id="row-<?= $course['id']; ?>" style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.75rem;" class="cell-name"><?= htmlspecialchars($course['course_name']); ?></td>
                    <td style="padding: 0.75rem;" class="cell-ec"><?= (int)$course['ec_points']; ?> EC</td>
                    <td style="padding: 0.75rem;" class="cell-grade"><?= htmlspecialchars($course['grade']); ?></td>
                    <td style="padding: 0.75rem;" class="cell-status font-weight-500">
                    <span class="<?= $course['status'] === 'Passed' ? 'text-success' : 'text-error'; ?>">
                        <?= htmlspecialchars($course['status']); ?>
                    </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <button class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; margin-right: 0.25rem;"
                                onclick="enableInlineEdit(<?= $course['id']; ?>, '<?= htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', <?= $course['ec_points']; ?>, '<?= htmlspecialchars($course['grade'], ENT_QUOTES); ?>', '<?= htmlspecialchars($course['status'], ENT_QUOTES); ?>')">
                            Edit
                        </button>
                        <button class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; background-color: var(--error);"
                                onclick="deleteCourseEntry(<?= $course['id']; ?>)">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="container">
            <div class="card card-account-list">
                <h3>Active Database Accounts List</h3>
                <table>
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Registered at</th>
                    </tr>
                    </thead>
                    <tbody id="users-table-body">
                    <tr>
                        <td colspan="3" class="table-loading-row">Fetching real-time records timeline...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <aside>
        <sidebar>
            <h2>Credit Summary</h2>
            <p>Total EC:</p>
            <h3 style="font-size: 2.2rem; color: var(--success); margin: 0.5rem 0;">
                <span id="metrics-ec-sum"><?= isset($totalPassedEC) ? (int)$totalPassedEC : 0; ?></span> / 60 EC
            </h3>
        </sidebar>
    </aside>
</div>

<script>
    function enableInlineEdit(id, name, ec, grade, status) {
        const row = document.getElementById(`row-${id}`);

        row.innerHTML = `
        <td style="padding: 0.5rem;"><input type="text" id="edit-name-${id}" value="${name}" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;"><input type="number" id="edit-ec-${id}" value="${ec}" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;"><input type="text" id="edit-grade-${id}" value="${grade}" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;">
            <select id="edit-status-${id}" style="padding:0.4rem; width:100%;">
                <option value="In Progress" ${status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                <option value="Passed" ${status === 'Passed' ? 'selected' : ''}>Passed</option>
                <option value="Failed" ${status === 'Failed' ? 'selected' : ''}>Failed</option>
            </select>
        </td>
        <td style="padding: 0.5rem; text-align: center;">
            <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: var(--success);" onclick="saveInlineEdit(${id})">Save</button>
            <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: #64748b;" onclick="window.location.reload()">Cancel</button>
        </td>
    `;
    }

    function saveInlineEdit(id) {
        const formData = new FormData();
        if (id) formData.append('id', id);
        formData.append('course_name', document.getElementById(`edit-name-${id}`)?.value || document.getElementById('new-name')?.value);
        formData.append('ec_points', document.getElementById(`edit-ec-${id}`)?.value || document.getElementById('new-ec')?.value);
        formData.append('grade', document.getElementById(`edit-grade-${id}`)?.value || document.getElementById('new-grade')?.value);
        formData.append('status', document.getElementById(`edit-status-${id}`)?.value || document.getElementById('new-status')?.value);

        fetch('/api/dashboard/save', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert("Execution error occurred: " + data.error);
                }
            });
    }

    function openNewCourseForm() {
        const tbody = document.getElementById('course-table-body');
        const newRow = document.createElement('tr');
        newRow.style.background = "#f8fafc";
        newRow.style.borderBottom = "2px solid var(--accent)";

        newRow.innerHTML = `
        <td style="padding: 0.5rem;"><input type="text" id="new-name" placeholder="E.g. Computer Networks" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;"><input type="number" id="new-ec" value="5" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;"><input type="text" id="new-grade" value="-" style="padding:0.4rem; width:100%;"></td>
        <td style="padding: 0.5rem;">
            <select id="new-status" style="padding:0.4rem; width:100%;">
                <option value="Active Run">In Progress</option>
                <option value="Passed">Passed</option>
                <option value="Failed">Failed</option>
            </select>
        </td>
        <td style="padding: 0.5rem; text-align: center;">
            <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: var(--accent);" onclick="saveInlineEdit(0)">Create</button>
            <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: #64748b;" onclick="this.closest('tr').remove()">Discard</button>
        </td>
    `;
        tbody.insertBefore(newRow, tbody.firstChild);
    }

    function deleteCourseEntry(id) {
        if (!confirm("Are you sure you want to purge this course entry from the infrastructure database logs?")) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('/api/dashboard/delete', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`row-${id}`).remove();
                    window.location.reload();
                }
            });
    }
    document.addEventListener("DOMContentLoaded", () => {
        loadRecentUsers();
    });

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
