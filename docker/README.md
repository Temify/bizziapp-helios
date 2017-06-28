# HeliosAPI Docker

Files for building docker image with working Apache 2, PHP 7.0 and phpunit@latest.

## Installation

1. Pull this sources and go to folder './docker'
2. Install Docker ([https://docs.docker.com/engine/installation/](https://docs.docker.com/engine/installation/))
3. Install Docker Compose ([https://docs.docker.com/compose/install/](https://docs.docker.com/compose/install/))
4. Build image `sudo docker-compose build`
5. Run container `sudo docker-compose up`

## Usage

Every run must fill up environment variables for database connection:

DB_DRIVER - pdo driver (default "pdo_sqlsqv")

DB_HOST - hostname or ip address (default "")

DB_PORT - opened database port (default "1433")

DB_NAME - database name (default "")

DB_USER - username (default "")

DB_PASSWORD - password (default "")

### Running web server:

`sudo docker run -d --name heliosapi-web -v $PWD:/myApp:rw -v $PWD/apache2.conf:/etc/apache2/sites-enabled/000-default.conf -v $PWD:/var/www/html -p 8080:80 -e DB_DRIVER=<db pdo driver> -e DB_HOST=<db host> -e DB_PORT=<db port> -e DB_NAME=<db name> -e DB_USER=<db login> -e DB_PASSWORD=<db password> michal/php/heliosapi:php7.0 /usr/sbin/apache2ctl -D FOREGROUND`

Server is accessible on http://localhost:8080

### Running phpunit tests:

`sudo docker run -ti -v $PWD:/myApp:rw -e DB_DRIVER=<db pdo driver> -e DB_HOST=<db host> -e DB_PORT=<db port> -e DB_NAME=<db name> -e DB_USER=<db login> -e DB_PASSWORD=<db password> michal/php/heliosapi:php7.0 phpunit tests/.`

### Running composer install

`sudo docker run -ti -v $PWD:/myApp:rw -e DB_DRIVER=<db pdo driver> -e DB_HOST=<db host> -e DB_PORT=<db port> -e DB_NAME=<db name> -e DB_USER=<db login> -e DB_PASSWORD=<db password> michal/php/heliosapi:php7.0 composer install`

## Credits

Michal Šindler 19.6.2017