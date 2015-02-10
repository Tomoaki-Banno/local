<?php

class Logic_SystemUtility
{

    //  トランクリア専用にした。func名と引数を変更。
    //  削除は delete from ではなく truncate で行うようにした（より高速かつきれいに消去できる）
    //  また、トランザクションをかけるのをやめた。truncateはトランザクション内では使用できないため。
    //      truncateで途中失敗する可能性は小さいし、失敗しても大きな問題にはならないと判断）
    static function clearTranData()
    {
        global $gen_db;
        $query = "truncate table accepted";
        $gen_db->query($query);
        $query = "truncate table achievement";
        $gen_db->query($query);
        $query = "truncate table bill_detail";
        $gen_db->query($query);
        $query = "truncate table bill_header";
        $gen_db->query($query);
        $query = "truncate table chat_detail";
        $gen_db->query($query);
        $query = "truncate table chat_header";
        $gen_db->query($query);
        $query = "truncate table chat_user";
        $gen_db->query($query);
        $query = "truncate table data_access_log";
        $gen_db->query($query);
        $query = "truncate table delivery_detail";
        $gen_db->query($query);
        $query = "truncate table delivery_header";
        $gen_db->query($query);
        $query = "truncate table estimate_header CASCADE";
        $gen_db->query($query); // foreign key制約により、tranを先に削除する必要がある
        $query = "truncate table estimate_detail";
        $gen_db->query($query);
        $query = "truncate table inventory";
        $gen_db->query($query);
        $query = "truncate table item_in_out";
        $gen_db->query($query);
        $query = "truncate table location_move";
        $gen_db->query($query);
        $query = "truncate table mrp";
        $gen_db->query($query);
        $query = "truncate table mrp_process";
        $gen_db->query($query);
        $query = "truncate table order_process";
        $gen_db->query($query);
        $query = "truncate table order_header CASCADE";
        $gen_db->query($query); // foreign key制約により、tranを先に削除する必要がある
        $query = "truncate table order_detail";
        $gen_db->query($query);
        $query = "truncate table order_child_item";
        $gen_db->query($query);
        $query = "truncate table paying_in";
        $gen_db->query($query);
        $query = "truncate table payment";
        $gen_db->query($query);
        $query = "truncate table plan";
        $gen_db->query($query);
        $query = "truncate table received_dummy_child_item";
        $gen_db->query($query);
        $query = "truncate table received_header";
        $gen_db->query($query);
        $query = "truncate table received_detail";
        $gen_db->query($query);
        $query = "truncate table seiban_change";
        $gen_db->query($query);
        $query = "truncate table staff_schedule";
        $gen_db->query($query);
        $query = "truncate table staff_schedule_user";
        $gen_db->query($query);
        $query = "truncate table stock_price_history";
        $gen_db->query($query);
        $query = "truncate table upload_file_info"; // バックアップ復元時を考慮し、ファイル自体は消さない
        $gen_db->query($query);
        $query = "truncate table use_plan";
        $gen_db->query($query);
        $query = "truncate table waster_detail";
        $gen_db->query($query);
        $query = "truncate table number_table";
        $gen_db->query($query);
    }

    static function clearLog()
    {
        global $gen_db;
        $query = "truncate table access_log";
        $gen_db->query($query);
        $query = "truncate table error_log";
        $gen_db->query($query);
    }

    static function clearItemMaster()
    {
        global $gen_db;
        $query = "truncate table bom_master";
        $gen_db->query($query);
        $query = "truncate table item_master CASCADE";
        $gen_db->query($query);
        $query = "truncate table item_order_master";
        $gen_db->query($query);
        $query = "truncate table item_process_master";
        $gen_db->query($query);
        $query = "truncate table customer_price_master";
        $gen_db->query($query);
    }

    static function clearBomMaster()
    {
        global $gen_db;
        $query = "truncate table bom_master";
        $gen_db->query($query);
    }

}