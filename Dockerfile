FROM driftphp/base
WORKDIR /var/www
COPY . .

RUN apk add git
RUN composer install -n --prefer-dist --no-dev --no-suggest && \
    composer dump-autoload -n --no-dev --optimize

FROM driftphp/base
RUN apk add openssh
RUN ssh-keygen -t rsa -b 4096 -f /etc/ssh/ssh_host_rsa_key -N “”
WORKDIR /var/www
COPY . .
RUN rm -Rf vendor/*
COPY --from=0 /var/www/vendor vendor/
COPY docker/* /

EXPOSE 8000
EXPOSE 22
CMD ["sh", "/server-entrypoint.sh"]
