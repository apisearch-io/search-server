create table purchases
(
    id int auto_increment
        primary key,
    app_uuid varchar(80) null,
    index_uuid varchar(80) null,
    user_uuid varchar(255) null,
    time datetime null,
    constraint purchases_pk
        unique (app_uuid, index_uuid, time),
    constraint purchases_pk_2
        unique (app_uuid, index_uuid, time, user_uuid),
    constraint purchases_purchase_items_purchase_id_fk
        foreign key (id) references purchase_items (purchase_id)
            on delete cascade
);

create table purchase_items
(
    purchase_id int null,
    item_uuid varchar(255) null
);

create index purchase_items_purchase_id_index
	on purchase_items (purchase_id);

