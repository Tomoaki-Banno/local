drop table if exists stock_price_history;
create table stock_price_history
(
    assessment_date date not null,          /* 基準日 */
    item_id int not null,                   /* 品目ID */

    stock_price numeric not null,           /* 在庫評価単価 */
    stock_quantity numeric not null         /* 在庫数 */
);
create unique index stock_price_history_index1 on stock_price_history (assessment_date, item_id);