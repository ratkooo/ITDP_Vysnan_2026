SETTING UP DOCKER CONTAINER

1. Create docker file
2. run docker build: docker build -t itdp .
3. Run the container within a volume: docker run -d --name itdp -p 8080:80 -v ${PWD}:/var/www/html itdp

SETTING UP MIGRATIONS

1. Create an .sqlite file in src/database/
2. Create a migrate.php in root directory
3. Run migrations: docker exec -it itdp php migrate.php
4. Give Apache permissions to write on the database file:
   docker exec -it itdp chown -R www-data:www-data /var/www/html/database
   docker exec -it itdp chmod -R 775 /var/www/html/database
5. With docker compose up and running, run migrations using: docker compose exec webserver php migrate.php
6. To reseed the database, use: docker compose exec webserver php migrate.php --refresh

SETTING UP DOCKER COMPOSE

1. Create a docker-compose.yml file in the root directory
2. Run: docker stop itdp 
        docker rm itdp
    to remove the old container 
3. Run: docker compose up -d
    to run the docker compose and create a new container, -d is used to run detached from the terminal.
4. To stop the environment from running: docker compose down
5. To stop the environment and delete data, run: docker compose down -v

SETTING UP SEPARATE CONTAINERS FOR MYSQL AND WEBSERVER

1. Edit docker-compose.yml and dockerfile
2. Run: docker compose up -d --build
    to rebuild the environment
