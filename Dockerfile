FROM php:latest

RUN apt-get update && docker-php-ext-install sockets && docker-php-ext-install pcntl

EXPOSE 10001

ADD vendor /opt/bchecker/vendor
ADD bchecker-srv /opt/bchecker/bchecker-srv
ADD config.yaml /opt/bchecker/config.yaml

WORKDIR /opt/bchecker

CMD ["./bchecker-srv", "./config.yaml"]
