create table usage_lines
(
    event varchar(15) not null,
    app_uuid varchar(50) not null,
    index_uuid varchar(50) not null,
    time int default 8 not null,
    n int(7) not null
);

create index app_event_index_time
    on usage_lines (app_uuid, event, index_uuid, time);

create index app_event_time
    on usage_lines (app_uuid, event, time);

create index app_time
    on usage_lines (app_uuid, time);

