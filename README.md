# user-crud-app
A simple API for user CRUD actions.

Steps to run the app:
- composer install
- ./vendor/bin/sail up - Ups all docker containers, in case any of the ports on your machine are taken, you may modify this by overriding the .env variables as listed in docker-compose.yml
- ./vendor/bin/sail test - Run the test suite
