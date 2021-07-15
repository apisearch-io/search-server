create table logs
(
    app_uuid varchar(50) null,
    index_uuid varchar(50) null,
    time int null,
    n int null,
    type varchar(255) null,
    params text null,
    constraint logs_pk
        unique (app_uuid, index_uuid, time)
);

