<?php

class Logic_DataDelete
{

    static function deleteData($delete_date)
    {
        global $gen_db;

        $gen_db->begin();

        // ●データ削除は、在庫が動く日を基準とする
        
        // 不適合明細
        $query = "delete from waster_detail where achievement_id in (select achievement_id from achievement where achievement_date < '{$delete_date}'::date)";
        $gen_db->query($query);

        // 実績テーブル
        $query = "delete from achievement where achievement_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 納品テーブル
        $query = "delete from delivery_detail where delivery_header_id in (select delivery_header_id from delivery_header where delivery_date < '{$delete_date}'::date)";
        $gen_db->query($query);
        $query = "delete from delivery_header where delivery_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 受注テーブル
        // 基準日以前であっても、納品レコードが残っている場合は削除しない
        $query = "delete from received_detail where dead_line < '{$delete_date}'::date and received_detail_id not in (select received_detail_id from delivery_detail)";
        $gen_db->query($query);
        $query = "delete from received_header where received_header_id not in (select received_header_id from received_detail)";
        $gen_db->query($query);
        $query = "delete from received_dummy_child_item where received_detail_id_for_dummy not in (select received_detail_id from received_detail)";
        $gen_db->query($query);
        
        // 見積テーブル
        $query = "delete from estimate_detail where estimate_header_id in (select estimate_header_id from estimate_header where estimate_date < '{$delete_date}'::date)";
        $gen_db->query($query);
        $query = "delete from estimate_header where estimate_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 受入テーブル
        $query = "delete from accepted where accepted_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 注文書データ
        $query = "delete from order_detail where order_detail_dead_line < '{$delete_date}'::date
            and order_detail_id not in (select order_detail_id from accepted)
            and order_detail_id not in (select order_detail_id from achievement)
        ";
        $gen_db->query($query);
        $query = "delete from order_header where order_header_id not in (select order_header_id from order_detail)";
        $gen_db->query($query);

        // 入金テーブル
        $query = "delete from paying_in where paying_in_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 支払テーブル
        $query = "delete from payment where payment_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 使用予定
        $query = "delete from use_plan where order_header_id NOT in (select order_header_id from order_header) and order_detail_id NOT in (select order_detail_id from order_detail)";
        $gen_db->query($query);

        // 入出庫テーブル
        $query = "delete from item_in_out where item_in_out_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // ロケ間移動
        $query = "delete from location_move where move_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // 製番引当
        $query = "delete from seiban_change where change_date < '{$delete_date}'::date";
        $gen_db->query($query);

        // データアクセスログ
        $query = "delete from data_access_log where access_time < '{$delete_date}'::date";
        $gen_db->query($query);

        // スケジュール
        $query = "
            delete from staff_schedule_user where schedule_id in (select schedule_id from staff_schedule where end_date < '{$delete_date}'::date);
            delete from staff_schedule where end_date < '{$delete_date}'::date;
        ";
        $gen_db->query($query);

        // 請求書（bill_header, detail）は繰越す必要があるため消さない
        
        // 棚卸
        //  品目/製番/ロケ/ロット につき最低1レコード(最終棚卸)は残るようにする
        $query = "
        delete from inventory
        where inventory_id in (
            select
                inventory_id
            from
                inventory
                left join (
                    select
                        item_id,
                        seiban,
                        location_id,
                        lot_id,
                        max(inventory_date) as inventory_date
                    from
                        inventory
                    group by
                        item_id,
                        seiban,
                        location_id,
                        lot_id
                ) as t1
                on inventory.item_id = t1.item_id
                and inventory.seiban = t1.seiban
                and inventory.location_id = t1.location_id
                and inventory.lot_id = t1.lot_id
                and inventory.inventory_date = t1.inventory_date
            where
                t1.item_id is null
                and inventory.inventory_date < '{$delete_date}'::date
        );
        ";
        $gen_db->query($query);

        //------------------------------------------------
        // 不要トランデータの掃除（以前は月次処理（Logic_Monthly）内で行われていた処理）
        //------------------------------------------------
        //  引当テーブルの掃除（以前はLogic_Reserve::monthly にあった処理）
        //  引当合計が0になった引当・使用予約はテーブルから削除する
        $query = "delete from use_plan where received_detail_id in (select received_detail_id from use_plan group by received_detail_id having SUM(quantity) = 0)";
        $gen_db->query($query);
        $query = "delete from use_plan where order_header_id in (select order_header_id from use_plan group by order_header_id having SUM(quantity) = 0)";
        $gen_db->query($query);

        // order_child_itemテーブルの掃除
        $query = "delete from order_child_item where order_detail_id not in (select order_detail_id from order_detail)";
        $gen_db->query($query);
        
        // received_dummy_child_itemテーブルの掃除
        $query = "delete from received_dummy_child_item where received_detail_id_for_dummy not in (select received_detail_id from received_detail)";
        $gen_db->query($query);

        // order_processテーブルの掃除
        $query = "delete from order_process where order_detail_id not in (select order_detail_id from order_detail)";
        $gen_db->query($query);

        //------------------------------------------------
        // コミット
        //------------------------------------------------

        $gen_db->commit();

        //------------------------------------------------
        // vacuum
        //------------------------------------------------

        $gen_db->query("vacuum analyze");
    }

}