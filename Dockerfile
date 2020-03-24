FROM driftphp/base
WORKDIR /var/www
COPY . .

RUN apk add git
RUN composer install -n --prefer-dist --no-dev --no-suggest && \
    composer dump-autoload -n --no-dev --optimize

FROM driftphp/base
WORKDIR /var/www
COPY . .
RUN rm -Rf vendor/*
COPY --from=0 /var/www/vendor vendor/
COPY docker/* /

EXPOSE 8000
CMD ["sh", "/server-entrypoint.sh"]
