<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Portfolio & Showcase</title>
    <style>
        :root {
            --primary: #1e293b;
            --accent: #2563eb;
            --text: #334155;
            --light: #f8fafc;
        }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            margin: 0;
            padding: 0;
            background-color: var(--light);
        }
        header {
            background: var(--primary);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        nav {
            background: #0f172a;
            padding: 0.5rem;
            text-align: center;
        }
        nav a {
            color: white;
            margin: 0 1rem;
            text-decoration: none;
            font-weight: 500;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        main, sidebar {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h2 {
            color: var(--primary);
            border-bottom: 2px solid var(--light);
            padding-bottom: 0.5rem;
        }
        .btn {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        #api-data-container {
            background: var(--light);
            padding: 1rem;
            border-left: 4px solid var(--accent);
            margin-top: 1rem;
            border-radius: 0 4px 4px 0;
        }
        footer {
            text-align: center;
            padding: 2rem;
            background: var(--primary);
            color: white;
            margin-top: 3rem;
        }
    </style>
</head>
<body>

    <nav>
        <a href="/">Home</a>
        <a href="/blog">Blog</a>
        <a href="/dashboard">Study Dashboard</a>
        <a href="/login">Login</a>
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

                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e2e8f0;">

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
            // Point this URL to one of your custom RESTful GET endpoints
            const apiEndpoint = '/api/projects';

            fetch(apiEndpoint)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network interface error');
                    }
                    return response.json();
                })
                .then(data => {
                    const container = document.getElementById('api-data-container');
                    // Render your JSON structure dynamically here
                    container.innerHTML = `
                        <strong>Endpoint Response:</strong> ${data.message || 'Data stream connected successfully.'}
                    `;
                })
                .catch(error => {
                    console.error('API Fetch Error:', error);
                    const container = document.getElementById('api-data-container');
                    container.innerHTML = `<span style="color: #dc2626;">Failed to asynchronously stream API resources dynamically.</span>`;
                });
        });
    </script>
</body>
</html>