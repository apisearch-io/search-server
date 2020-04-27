create table tokens
(
    token_uuid varchar(255) not null
        primary key,
    app_uuid varchar(255) not null,
    content varchar(255) not null
)
    collate=utf8_unicode_ci;

