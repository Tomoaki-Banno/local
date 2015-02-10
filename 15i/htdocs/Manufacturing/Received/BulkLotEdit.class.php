<?php

class Manufacturing_Received_BulkLotEdit extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label'=>_g('受注番号'),
                'field'=>'received_number',
            ),
            array(
                'label'=>_g('客先注番'),
                'field'=>'customer_received_number',
                'hide'=>true,
            ),
            array(
                'label'=>_g('見積番号'),
                'field'=>'estimate_number',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注製番'),
                'field'=>'seiban',
                'hide'=>true,
            ),
            array(
                'label'=>_g('得意先コード/名'),
                'field'=>'customer_master___customer_no',
                'field2'=>'customer_master___customer_name',
            ),
            array(
                'label'=>_g('品目コード/名'),
                'field'=>'item_code',
                'field2'=>'item_name',
            ),
            array(
                'label'=>_g('品目グループ'),
                'field'=>'item_group_id',
                'type'=>'select',
                'options'=>$option_item_group,
                'hide'=>true,
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
            ),
            array(
                'label'=>_g('発送先コード/名'),
                'field'=>'t_delivery_customer___customer_no',
                'field2'=>'t_delivery_customer___customer_name',
                'hide'=>true,
            ),
            array(
                'label'=>_g('担当者コード/名'),
                'field'=>'worker_code',
                'field2'=>'worker_name',
                'hide'=>true,
            ),
            array(
                'label'=>_g('部門コード/名'),
                'field'=>'section_code',
                'field2'=>'section_name',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注備考1'),
                'field'=>'remarks_header',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注備考2'),
                'field'=>'remarks_header_2',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注備考3'),
                'field'=>'remarks_header_3',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注明細備考1'),
                'field'=>'received_detail___remarks',
                'ime'=>'on',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注日'),
                'type'=>'dateFromTo',
                'field'=>'received_date',
                'size'=>'80',
                'hide'=>true,
            ),
            array(
                'label'=>_g('受注納期'),
                'type'=>'dateFromTo',
                'field'=>'dead_line',
                'size'=>'80',
                'hide'=>true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('change_date', date('Y-m-d'));
        $converter->nullBlankToValue('use_by_limit', date('Y-m-d'));
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
             select
                received_header.received_header_id
                ,received_number
                ,customer_received_number
                ,t_estimate.estimate_number
                ,line_no
                ,seiban
                ,customer_master.customer_no
                ,t_delivery_customer.customer_no as delivery_customer_no
                ,customer_master.customer_name
                ,t_delivery_customer.customer_name as delivery_customer_name
                ,received_date
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name
                ,remarks_header
                ,remarks_header_2
                ,remarks_header_3

                ,received_detail.received_detail_id
                ,item_code
                ,item_name
                ,maker_name
                ,spec
                ,rack_no
                ,order_class
                ,received_quantity
                ,measure
                ,dead_line
                ,received_detail.remarks

                ,item_master.comment as item_remarks_1
                ,item_master.comment_2 as item_remarks_2
                ,item_master.comment_3 as item_remarks_3
                ,item_master.comment_4 as item_remarks_4
                ,item_master.comment_5 as item_remarks_5
                ,customer_master.remarks as customer_remarks_1
                ,customer_master.remarks_2 as customer_remarks_2
                ,customer_master.remarks_3 as customer_remarks_3
                ,customer_master.remarks_4 as customer_remarks_4
                ,customer_master.remarks_5 as customer_remarks_5
            from
                item_master
                inner join received_detail on item_master.item_id = received_detail.item_id
                inner join received_header on received_header.received_header_id = received_detail.received_header_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as t_delivery_customer on received_header.delivery_customer_id = t_delivery_customer.customer_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join section_master on received_header.section_id = section_master.section_id
                left join (select estimate_header_id as ehid,estimate_number
                    from estimate_header) as t_estimate on received_header.estimate_header_id = t_estimate.ehid

                /* 出庫ロット（製番引当） */
                LEFT JOIN
                (
                    SELECT
                        seiban_change.item_id
                        ,seiban_change.dist_seiban
                        ,sum(seiban_change.quantity) as seiban_change_qty
                    FROM
                        seiban_change
                        inner join item_master on seiban_change.item_id = item_master.item_id
                        left JOIN (select lot_no, stock_seiban from achievement
                            union select lot_no, stock_seiban from accepted) as t_ach_acc 
                            on seiban_change.source_seiban = t_ach_acc.stock_seiban and seiban_change.source_seiban <> ''
                    WHERE
                        item_master.order_class = 2
                        /* ロット番号のある（実績とひもついた）製番在庫か、製番フリー在庫を出す。
                           逆に言えば、実績とひもつかない製番在庫（受注製番・計画製番）は出さない。 */
                        and (t_ach_acc.lot_no is not null or seiban_change.source_seiban = '')
                    GROUP BY
                        seiban_change.item_id, dist_seiban
                ) AS T_lot ON received_detail.seiban = T_lot.dist_seiban and received_detail.item_id = T_lot.item_id

            [Where]
                and item_master.order_class = 2 -- ロット品目のみ
                and received_detail.received_quantity > coalesce(seiban_change_qty,0) -- 未引当のみ
                and guarantee_grade = 0 -- 確定のみ
            [Orderby]
        ";
        $this->orderbyDefault = "received_number desc, line_no";
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("一括ロット引当");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Received_BulkLotEdit";
        $form['gen_idField'] = "received_detail_id";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_pageHelp'] = _g("一括ロット引当");

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ
        
        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Received_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('受注登録へ戻る');
        
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<br><font color=red>" . _g("一括引当登録を行う権限がありません。") . "</font>";
        } else {
            // 再表示したときに復元
//            $defaultDateStr = @$form['change_year'] . "-" . @$form['change_month'] . "-" . @$form['change_day'];
//            if (Gen_String::isDateString($defaultDateStr)) {
//                $defaultDate = strtotime($defaultDateStr);
//            } else {
//                $defaultDate = time();
//            }
            $defaultLocationId = "";
            if (is_numeric(@$form['location_id'])) $defaultLocationId = $form['location_id'];

            // 画面中央のメッセージ
            $html_change_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("在庫引当日") . " : ",
                    'name' => "change_date",
                    'value' => @$form['change_date'],
                    'size' => '85',
                )
            );
            $html_use_by_limit = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("対象消費期限（以降）") . " : ",
                    'name' => "use_by_limit",
                    'value' => @$form['use_by_limit'],
                    'size' => '85',
                )
            );
            // サプライヤーロケは選択肢に含めない。
            //  サプライヤーロケに存在する在庫にはロット引当できないため。（このクラス内の Logic_Stock::createTempStockTable() 呼び出しの
            //  引数指定でサプライヤーロケを排除していることに注目）
            $locOpt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master where customer_id is null order by location_code", false ,array("0"=>GEN_DEFAULT_LOCATION_NAME));
            $locHtml = Gen_String::makeSelectHtml("location_id", $locOpt, $defaultLocationId);
            $form['gen_message_noEscape'] = _g("ロット品目の受注に対し、ロットの自動引当処理を行います。ロットは消費期限の古いものから順に引き当てられます。") . "<br>
                <table><tr>
                    <td>{$html_change_date}</td>
                    <td width='30px'></td>
                    <td>{$html_use_by_limit}</td>
                    <td width='30px'></td>
                    <td>"._g("出庫ロケーション") . "：" . $locHtml . "</td>
                </tr></table>
                <div id=\"doButton\">
                <input type=button value=\"&nbsp;&nbsp;" . _g("一括引当登録を実行") . "&nbsp;&nbsp;\" onClick=\"bulkSeibanChange()\">
                </div>
            ";
        }

        // イベント処理
        $form['gen_javascript_noEscape'] = "
            function bulkSeibanChange() {
                var frm = gen.list.table.getCheckedPostSubmit('sc_check');
                if (frm.count == 0) {
                    alert('". _g("引当するデータを選択してください。") ."');
                    return;
                }
                var msg = '';
                msg += '". _g("一括引当処理を実行してもよろしいですか？") . "';
                if (!window.confirm(msg)) return;

                var postUrl = 'index.php?action=Manufacturing_Received_BulkLotEntry';
                postUrl += '&change_date=' + $('#change_date').val();
                postUrl += '&use_by_limit=' + $('#use_by_limit').val();
                postUrl += '&location_id=' + $('#location_id').val();
                frm.submit(postUrl);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("登録"),
                'type' => 'checkbox',
                'name' => 'sc_check'
            ),
        );
        
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('見積番号'),
                'field' => 'estimate_number',
                'width' => '90',
                'align' => 'center',
                'parentColumn' => 'received_number',
                'sameCellJoin' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '45',
                'align' => 'center',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'width' => '110',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
            ),
            array(
                'label' => _g('発送先コード'),
                'field' => 'delivery_customer_no',
                'width' => '110',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('発送先名'),
                'field' => 'delivery_customer_name',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
            ),
            array(
                'label' => _g('受注日'),
                'field' => 'received_date',
                'type' => 'date',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
            ),
            array(
                'label' => _g('受注納期'),
                'field' => 'dead_line',
                'type' => 'date',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'received_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
                'width' => '110',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'worker_name',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
                'width' => '110',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注備考1'),
                'field' => 'remarks_header',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注備考2'),
                'field' => 'remarks_header_2',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注備考3'),
                'field' => 'remarks_header_3',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
                'hide' => true,
            ),
            array(
                'label' => _g('受注明細備考1'),
                'field' => 'remarks',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考1'),
                'field' => 'customer_remarks_1',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考2'),
                'field' => 'customer_remarks_2',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考3'),
                'field' => 'customer_remarks_3',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考4'),
                'field' => 'customer_remarks_4',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考5'),
                'field' => 'customer_remarks_5',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'item_remarks_1',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'item_remarks_2',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'item_remarks_3',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'item_remarks_4',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'item_remarks_5',
                'hide' => true,
            ),
        );
    }

}
