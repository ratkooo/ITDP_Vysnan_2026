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
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">Loading academic performance indices...</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 3rem;">
            <div class="card card-account-list">
                <h3>Active Database Accounts List</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                    <tr style="text-align: left; border-bottom: 2px solid var(--border);">
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
                <span id="metrics-ec-sum">0</span> / 30 EC
            </h3>
        </sidebar>
    </aside>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadCourses();
        loadRecentUsers();
    });

    // Asynchronously fetch and render the academic courses matrix
    function loadCourses() {
        fetch('/api/student-courses')
            .then(res => {
                if (!res.ok) throw new Error("Could not populate academic summary catalog.");
                return res.json();
            })
            .then(coursesArray => {
                const tbody = document.getElementById('course-table-body');
                tbody.innerHTML = "";

                if (coursesArray.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:1.5rem;">No courses managed within database logs.</td></tr>`;
                    document.getElementById('metrics-ec-sum').textContent = "0";
                    return;
                }

                let totalEcSum = 0;

                coursesArray.forEach(course => {
                    const row = document.createElement('tr');
                    row.id = `row-${course.id}`;
                    row.style.borderBottom = "1px solid var(--border)";

                    const isPassed = course.status === 'Passed';
                    if (isPassed) {
                        totalEcSum += parseInt(course.ec_points) || 0;
                    }

                    row.innerHTML = `
                        <td style="padding: 0.75rem;" class="cell-name">${escapeHtml(course.course_name)}</td>
                        <td style="padding: 0.75rem;" class="cell-ec">${parseInt(course.ec_points)} EC</td>
                        <td style="padding: 0.75rem;" class="cell-grade">${escapeHtml(course.grade)}</td>
                        <td style="padding: 0.75rem;" class="cell-status font-weight-500">
                            <span class="${isPassed ? 'text-success' : 'text-error'}">
                                ${escapeHtml(course.status)}
                            </span>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; margin-right: 0.25rem;"
                                    onclick="enableInlineEdit(${course.id}, '${escapeJsParam(course.course_name)}', ${course.ec_points}, '${escapeJsParam(course.grade)}', '${escapeJsParam(course.status)}')">
                                Edit
                            </button>
                            <button class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; background-color: var(--error);"
                                    onclick="deleteCourseEntry(${course.id})">
                                Delete
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Update aggregate credit count component dynamically
                document.getElementById('metrics-ec-sum').textContent = totalEcSum;
            })
            .catch(err => {
                document.getElementById('course-table-body').innerHTML = 
                    `<tr><td colspan="5" style="text-align:center; color:var(--error); padding:1.5rem;">Failed to fetch dynamic performance tracking parameters.</td></tr>`;
                console.error(err);
            });
    }

    function enableInlineEdit(id, name, ec, grade, status) {
        const row = document.getElementById(`row-${id}`);

        row.innerHTML = `
            <td style="padding: 0.5rem;"><input type="text" id="edit-name-${id}" value="${escapeHtml(name)}" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;"><input type="number" id="edit-ec-${id}" value="${ec}" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;"><input type="text" id="edit-grade-${id}" value="${escapeHtml(grade)}" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;">
                <select id="edit-status-${id}" style="padding:0.4rem; width:100%;">
                    <option value="In Progress" ${status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                    <option value="Passed" ${status === 'Passed' ? 'selected' : ''}>Passed</option>
                    <option value="Failed" ${status === 'Failed' ? 'selected' : ''}>Failed</option>
                </select>
            </td>
            <td style="padding: 0.5rem; text-align: center;">
                <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: var(--success);" onclick="saveInlineEdit(${id})">Save</button>
                <button class="btn" style="padding: 0.35rem 0.65rem; font-size:0.85rem; background-color: #64748b;" onclick="loadCourses()">Cancel</button>
            </td>
        `;
    }

    function saveInlineEdit(id) {
        const formData = new FormData();
        
        let nameField, ecField, gradeField, statusField;

        if (id === 0) {
            nameField = document.getElementById('new-name')?.value;
            ecField = document.getElementById('new-ec')?.value;
            gradeField = document.getElementById('new-grade')?.value;
            statusField = document.getElementById('new-status')?.value;
        } else {
            formData.append('id', id);
            nameField = document.getElementById(`edit-name-${id}`)?.value;
            ecField = document.getElementById(`edit-ec-${id}`)?.value;
            gradeField = document.getElementById(`edit-grade-${id}`)?.value;
            statusField = document.getElementById(`edit-status-${id}`)?.value;
        }

        formData.append('course_name', nameField || '');
        formData.append('ec_points', ecField || '0');
        formData.append('grade', gradeField || '-');
        formData.append('status', statusField || 'In Progress');

        fetch('/api/dashboard/save', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadCourses(); // Refresh decoupled data row grid
                } else {
                    alert("Execution error occurred: " + data.error);
                }
            })
            .catch(err => console.error("Mutation communication failure:", err));
    }

    function openNewCourseForm() {
        const tbody = document.getElementById('course-table-body');
        
        // Prevent stacking multiple empty instantiation loops
        if (document.getElementById('new-name')) return;

        const newRow = document.createElement('tr');
        newRow.style.background = "#f8fafc";
        newRow.style.borderBottom = "2px solid var(--accent)";

        newRow.innerHTML = `
            <td style="padding: 0.5rem;"><input type="text" id="new-name" placeholder="E.g. Computer Networks" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;"><input type="number" id="new-ec" value="5" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;"><input type="text" id="new-grade" value="-" style="padding:0.4rem; width:100%;"></td>
            <td style="padding: 0.5rem;">
                <select id="new-status" style="padding:0.4rem; width:100%;">
                    <option value="In Progress">In Progress</option>
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
        // Nielsen Heuristic 5: Error Prevention
        if (!confirm("Are you sure you want to purge this course entry from the infrastructure database logs?")) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('/api/dashboard/delete', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadCourses();
                } else {
                    alert("Failed to delete the specified record.");
                }
            })
            .catch(err => console.error("Deletion routing error:", err));
    }

    function loadRecentUsers() {
        fetch('/api/recent-registrations')
            .then(response => {
                if (!response.ok) throw new Error("Network database list collection failure.");
                return response.json();
            })
            .then(usersArray => {
                const tableBody = document.getElementById('users-table-body');
                tableBody.innerHTML = "";

                if (usersArray.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding: 1rem;">No accounts exist within db context.</td></tr>`;
                    return;
                }

                usersArray.forEach(user => {
                    const row = document.createElement('tr');
                    row.style.borderBottom = "1px solid var(--border)";
                    row.innerHTML = `
                        <td style="padding:0.5rem;"><strong>${escapeHtml(user.username)}</strong></td>
                        <td style="padding:0.5rem;"><span class="tag">${escapeHtml(user.role)}</span></td>
                        <td style="padding:0.5rem;">${escapeHtml(user.created_at)}</td>
                    `;
                    tableBody.appendChild(row);
                });
            })
            .catch(err => {
                document.getElementById('users-table-body').innerHTML =
                    `<tr><td colspan="3" style="text-align:center; color:var(--error); padding: 1rem;">Failed to load system dataset.</td></tr>`;
                console.error(err);
            });
    }

    // XSS Mitigation Utility Engine
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Protects inline parameters passed back into dynamic onclick event handlers
    function escapeJsParam(str) {
        if (!str) return '';
        return str.toString()
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/"/g, '\\"');
    }
</script>
</body>
</html>
