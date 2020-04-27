create table index_configs
(
    repository_reference_uuid varchar(255) not null
        primary key,
    content varchar(255) not null
)
    collate=utf8_unicode_ci;

