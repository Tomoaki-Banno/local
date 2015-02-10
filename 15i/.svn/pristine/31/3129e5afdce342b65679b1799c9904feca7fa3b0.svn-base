<?php

class Mobile_ItemMaster_List extends Base_MobileListBase
{
    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null=>_g("(すべて)")));

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('品目コード'),
                    'field'=>'item_code',
                ),
                array(
                    'label'=>_g('品目名'),
                    'field'=>'item_name',
                ),
                array(
                    'label'=>_g('品目グループ'),
                    'type'=>'select',
                    'field'=>'item_group_id',
                    'options'=>$option_item_group,
                ),

                array(
                    'label'=>_g('管理区分'),
                    'type'=>'select',
                    'field'=>'order_class',
                    'options'=>array(''=>'(' . _g("すべて") . ')', 0=>_g('製番'), 1=>_g('MRP')),
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('手配区分'),
                    'type'=>'select',
                    'field'=>'partner_class',
                    'options'=>array(''=>'(' . _g("すべて") . ')',3=>_g('内製'), 0=>_g('発注'), 1=>_g('外注(支給なし)'), 2=>_g('外注(支給あり)')),
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('棚番'),
                    'field'=>'rack_no',
                    'hide'=>true,
                ),

                array(
                    'label'=>_g('品目備考1'),
                    'field'=>'comment',
                    'ime'=>'on',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考2'),
                    'field'=>'comment_2',
                    'ime'=>'on',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考3'),
                    'field'=>'comment_3',
                    'ime'=>'on',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考4'),
                    'field'=>'comment_4',
                    'ime'=>'on',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('品目備考5'),
                    'field'=>'comment_5',
                    'ime'=>'on',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('非表示品目の表示'),
                    'type'=>'select',
                    'field'=>'end_item',
                    'options'=>array("false"=>_g("しない"), "true"=>_g("する")),   // 「しない」時は end_item = false のレコードに限定
                    'nosql'=>'true',
                    'default'=>'false',
                    'hide'=>true,
                ),
                array(
                    'label'=>_g('登録方法'),
                    'type'=>'select',
                    'field'=>'item_master___dropdown_flag',
                    //'nosql'=>true,
                    'options'=>array(''=>'(' . _g("すべて") . ')', true=>_g('マスタ以外から登録された品目')),
                    'helpText_noEscape'=>_g("「マスタ以外から登録された品目」とは、各画面の品目選択の拡張ドロップダウンから登録された品目のことです。") . "<br><br>"
                        . _g("たとえば受注登録画面や注文登録画面などで、品目選択のドロップダウンに目的の品目がない場合、その画面からジャンプしてマスタに新規登録することができます。そのようにして登録された品目が「マスタ以外から登録された品目」になります。") . "<br><br>"
                        . _g("受注登録や注文登録の時点では仮に品目登録しておき、あとから品目マスタで項目を編集する、という場合にこの項目での絞り込みを利用すると便利です。"),
                    'hide'=>true,
                ),
            );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;
        
        $this->selectQuery = "
             SELECT
                *
 ----- file upload -----
                --,case when image_file_oid is null then '' else '" . _g("有") . "' end as is_image_exist
                ----- lot ver ----- change 1 line
                ,case item_master.order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end as order_class_show
                ,case partner_class when 0 then '" . _g("発注") . "' when 1 then '" . _g("外注(支給なし)") . "' when 2 then '" . _g("外注(支給あり)") . "' else '" . _g("内製") . "' end as partner_class_show
                ,case without_mrp when 0 then '" . _g("含める") . "' else '" . _g("含めない") . "' end as without_mrp_show
                ,case received_object when 0 then '" . _g("受注対象") . "' else '" . _g("対象外") . "' end as received_object_show
                ,case item_master.tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as show_tax_class
                ,case when end_item then '" . _g("非表示") . "' else '' end as show_end_item
                ,case when dummy_item then '" . _g("ダミー品目") . "' else '' end as show_dummy_item
                ,case when end_item then 1 else null end as end_item_csv
                ,case when dummy_item then 1 else null end as dummy_item_csv

                ,item_master.record_create_date as gen_record_create_date
                ,item_master.record_creator as gen_record_creater
                ,coalesce(item_master.record_update_date, item_master.record_create_date) as gen_record_update_date
                ,coalesce(item_master.record_updater, item_master.record_creator) as gen_record_updater

             FROM
                item_master
                left join (select item_group_id as gid, item_group_code as item_group_code, item_group_name as item_group_name from item_group_master) as t1 on item_master.item_group_id = t1.gid
                left join (select item_group_id as gid, item_group_code as item_group_code_2, item_group_name as item_group_name_2 from item_group_master) as t2 on item_master.item_group_id_2 = t2.gid
                left join (select item_group_id as gid, item_group_code as item_group_code_3, item_group_name as item_group_name_3 from item_group_master) as t3 on item_master.item_group_id_3 = t3.gid
                left join (select location_id as lid, location_code as default_location_code, location_name as default_location_name from location_master) as t4 on item_master.default_location_id = t4.lid
                left join (select location_id as lid, location_code as default_location_code_2, location_name as default_location_name_2 from location_master) as t5 on item_master.default_location_id_2 = t5.lid
                left join (select location_id as lid, location_code as default_location_code_3, location_name as default_location_name_3 from location_master) as t6 on item_master.default_location_id_3 = t6.lid
                left join (select item_id as iid, order_user_id, partner_class from item_order_master where line_number=0) as t7 on item_master.item_id = t7.iid
                left join (select item_id as iid, sum(default_work_minute) as default_work_minute, sum(default_work_minute * charge_price) as default_work_price, sum(overhead_cost) as overhead_cost from item_process_master group by item_id) as t8 on item_master.item_id = t8.iid
                left join customer_master on t7.order_user_id = customer_master.customer_id
                -- 以下の2行は最終入出庫日の取得用。1行でも書けるが、なるべくインデックスを使わせるためにこのような書き方にした。
                left join (select item_id as iid, max(item_in_out_date) as last_in_date from item_in_out where classification in ('in','manufacturing') group by item_id) as t10 on item_master.item_id = t10.iid
                left join (select item_id as iid, max(item_in_out_date) as last_out_date from item_in_out where classification in ('out','payout','use','delivery') group by item_id) as t11 on item_master.item_id = t11.iid
                ";

        // 「and item_id >= 0」はインデックスを確実に使わせるために入れた。劇的に速度向上。
        $this->selectQuery .= "
            [Where] and item_id >= 0
                " . (@$form['gen_search_end_item'] == "false" ? " and (end_item is null or end_item = false)" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code';
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $this->tpl = "mobile/list.tpl";
        
        $form['gen_pageTitle'] = _g("品目マスタ");
        $form['gen_listAction'] = "Mobile_ItemMaster_List";
        $form['gen_linkAction'] = "Mobile_ItemMaster_Detail";
        $form['gen_idField'] = "item_id";
        
        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>_g('品目コード'),
                    'label'=>"",
                    'field'=>'item_code',
                    'fontSize'=>13,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('品目名'),
                    'label'=>"",
                    'field'=>'item_name',
                    'fontSize'=>15,
                ),
            );
    }
}