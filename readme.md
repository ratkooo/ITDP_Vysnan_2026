DEVOPS INSTRUCTIONS

First we initialize composer by creating a composer.json file in the root directory:
```json
{
  "name": "student/showcase-app",
  "description": "ITDP Showcase Application",
  "type": "project",
  "require": {
    "php": ">=8.2"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "phpstan/phpstan": "^1.11",
    "squizlabs/php_codesniffer": "*",
    "ext-pdo": "*"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "App\\Tests\\": "tests/"
    }
  }
}
```
After the file is created, we run:
```powershell
composer install
```
in a powershell terminal to install the dependencies.

1. SINGLE CONTAINER CONFIGURATION

First, we need to create a dockerfile in the root directory of the app:
```dockerfile
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

WORKDIR /var/www/html

EXPOSE 80
```
Then, we create a database.sqlite file in the root directory to store the database data in

Afterwards, we need to create a migration script, for example, migrate.php in the root folder:
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$dbPath = __DIR__ . 'database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting database migrations...\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS course_progress (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_name TEXT NOT NULL,
        ec_points INTEGER NOT NULL,
        grade REAL,
        status TEXT DEFAULT 'not_started'
    );");
    echo "✔ Created 'course_progress' table successfully.\n";

    echo "All migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
```
Then to run the application using the Docker container, we execute these commands in a Powershell terminal:
```powershell
docker build -t itdp

# Instantiate and execute the container running background processes daemonized (-d)
docker run -d \
  -p 8080:80 \
  --name itdp \
  -v "$(pwd)":/var/www/html \
  -v local_app_persistence:/var/www/html/storage \
```
We need to allow the Apache web server to write data to your database file:
```powershell
docker exec -it itdp chown -R www-data:www-data /var/www/html/database
docker exec -it itdp chmod -R 775 /var/www/html/database
```
To run the migrations, we execute a Powershell command:
```powershell
docker compose exec webserver php migrate.php
```
To stop the container and purge the container:
```powershell
docker stop itdp && docker rm itdp
```
2. ADDING DOCKER COMPOSE

First, we need to create a docker-compose.yml file in the root directory:
```yml
version: '3.8'

services:
  webserver:
    container_name: itdp2
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
```
To shut down and clean the old container, we will use:
```powershell
docker stop itdp
docker rm itdp
```
Then to launch the new container using Docker Compose, we use the command:
```powershell
docker compose up -d
```
Then, we run the migrations:
```powershell
docker compose exec webserver php migrate.php
```
3. SEPARATING THE CONTAINERS

First we edit the docker-compose.yml file to create 2 containers, one for the webserver and the other for the database:
```yml
version: '3.8'

services:
  webserver:
    container_name: itdp3
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
      - DB_CONNECTION=mysql
      - DB_HOST=db_server
      - DB_DATABASE=portfolio_db
      - DB_USERNAME=portfolio_user
      - DB_PASSWORD=portfolio_password
    depends_on:
      - db_server
    networks:
      - portfolio-network

  db_server:
    image: mysql:8.0
    container_name: showcase_database
    restart: always
    environment:
      MYSQL_DATABASE: portfolio_db
      MYSQL_USER: portfolio_user
      MYSQL_PASSWORD: portfolio_password
      MYSQL_ROOT_PASSWORD: root_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - portfolio-network

networks:
  portfolio-network:
    driver: bridge

volumes:
  mysql_data:
```
Then, edit the dockerfile so that it installs MySQL drivers instead of SQLite ones:
```dockerfile
FROM php:8.2-apache

# Install MySQL drivers instead of SQLite
RUN docker-php-ext-install pdo pdo_mysql

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite
WORKDIR /var/www/html
EXPOSE 80
```
Rebuild the container using:
```powershell
docker compose up -d --build
```
GITHUB CI ACTIONS WORKFLOW

To have our app checked at every push to the Main branch, we need to create a ci-checks.yml file in the .github/workflows folder:
```yml
name: Continuous Integration Quality Checks

on:
  push:
    branches: [ "main" ]
  pull_request:
    branches: [ "main" ]

jobs:
  qa_checks:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Code Base
        uses: actions/checkout@v4

      - name: Setup PHP Environment
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - name: Cache Composer Dependencies
        uses: actions/cache@v3
        with:
          path: ~/.composer/cache/files
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.json') }}

      - name: Install Project Dependencies
        run: composer install --no-progress --prefer-dist

      - name: Run PHP CodeSniffer (PSR-12)
        run: php ./vendor/bin/phpcs --standard=PSR12 --exclude="Generic.Files.LineLength" src/

      - name: Run PHPStan Static Analysis
        run: php ./vendor/bin/phpstan analyse src/ --level=8

      - name: Install Deptrac Tool
        run: curl -LSs https://github.com/qossmic/deptrac/releases/latest/download/deptrac.phar -o deptrac.phar && chmod +x deptrac.phar

      - name: Run Deptrac Analysis
        run: php ./vendor/bin/deptrac analyse --config-file=deptrac.yaml
```

Then, we need to create a configuration file for Deptrac called deptrac.yml in the root directory:
```yml
deptrac:
  layers:
    - name: Controllers
      collectors:
        - type: directory
          value: src/Controllers/.*

    - name: Repositories
      collectors:
        - type: directory
          value: src/Repositories/.*

    - name: Models
      collectors:
        - type: directory
          value: src/Models/.*

  ruleset:
    Controllers:
      - Repositories
      - Models

    Repositories:
      - Models

    Models: []
```
APP DEPLOYMENT

For the webapp deployment, I will be using my own private server running Ubuntu Server, so these steps apply directly to my configuration. Replicability can be guaranteed only if the developer wants to deploy the app on this server.

1.` we need to access the server using SSH from a Windows Powershell terminal (Authentication requiered):
```powershell
ssh ratko@100.83.224.114
```
2. we run these commands to install and update all dependencies including Docker:
```wsl
sudo apt update && sudo apt upgrade -y

sudo apt install -y curl git certbot python3-certbot-nginx nginx docker.io docker-compose

sudo systemctl enable --now docker

sudo usermod -aG docker $USER

sudo apt install nginx -o Dpkg::Options::="--force-confold" -y

newgrp docker
```
3. create the website's configuration file:
```wsl
sudo nano /etc/nginx/sites-available/itdp
```
4. in the Nano editor:
```
# 1. Public HTTP Redirection Block (IPv4 & IPv6)
server {
    listen 192.168.178.69:80;
    listen [2001:16b8:c549:d300:24e:1ff:feae:c9df]:80;
    server_name server100bomby.pp.ua; # USER NEEDS THEIR OWN DOMAIN, MINE IS REGISTERED THROUGH NIC.UA FOR FREE

    return 301 https://$host$request_uri;
}

# 2. Public Secure HTTPS Block (IPv4 & IPv6)
server {
    listen 192.168.178.69:443 ssl;
    listen [2001:16b8:c549:d300:24e:1ff:feae:c9df]:443 ssl;
    server_name server100bomby.pp.ua;

    ssl_certificate /etc/letsencrypt/live/server100bomby.pp.ua/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/server100bomby.pp.ua/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
5. Check config file and restart Nginx:
```wsl
sudo ln -s /etc/nginx/sites-available/itdp /etc/nginx/sites-enabled/

sudo nginx -t

sudo systemctl restart nginx
```
```
7. When this is done, we need to configure 


Once this is done, we create a directory for our Project files and pull the GitHub repository:
```wsl
cd ~

mkdir /itdp

git clone https://github.com/HZ-ICT1-2526/itdp-ratkooo
```
Once it's cloned, navigate to the folder:
```wsl
cd /itdp-ratkooo
```
Then, create an .env file using:
```wsl
