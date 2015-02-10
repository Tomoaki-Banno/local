drop table if exists number_table;
create table number_table
(
    number_name text unique not null,   /* 採番名 */
    curr_number text not null           /* 大きな値を扱えるようにするためtextとする */
);