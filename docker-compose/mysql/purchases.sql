create table purchases
(
    id int auto_increment
        primary key,
    app_uuid varchar(80) null,
    index_uuid varchar(80) null,
    user_uuid varchar(255) null,
    time int null
);

create table purchase_items
(
    purchase_id int null,
    item_uuid varchar(255) null,
    constraint purchase_items_purchases_id_fk
        foreign key (purchase_id) references purchases (id)
);

create index purchase_items_purchase_id_index
    on purchase_items (purchase_id);

create index purchases_pk
    on purchases (app_uuid, index_uuid, time);

create index purchases_pk_2
    on purchases (app_uuid, index_uuid, time, user_uuid);
