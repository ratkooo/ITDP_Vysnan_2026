CREATE TABLE IF NOT EXISTS blog_posts (
                                          id INT AUTO_INCREMENT PRIMARY KEY,
                                          title VARCHAR(255) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(100) DEFAULT 'Radovan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed initial professional articles safely without creating duplicates
INSERT INTO blog_posts (title, summary, content)
SELECT
    'Passing my ITDP this year',
    'I really need to pass my ITDP this year',
    'Passing my ITDP is my life-long dream, hopefully being realised this year. I cannot wait to see my effort being finally recognized and I finally pass this shit.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE title = 'Embracing Decoupled Architecture in Modern PHP');

INSERT INTO blog_posts (title, summary, content)
SELECT
    'Why Semantic Markup and Strict CSS Separation Matter',
    'An analysis of code maintainability when discarding inline styling in favor of unified styles.',
    'Inline styles create structural noise and technical debt. Utilizing global design systems ensures that changes to a central stylesheet propagate seamlessly across entire layouts. This simplifies accessibility audits and conforms tightly to clean UI guidelines.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE title = 'Why Semantic Markup and Strict CSS Separation Matter');