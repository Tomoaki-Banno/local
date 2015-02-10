<?php

class Manufacturing_Received_LotEdit extends Base_ListBase
{
    function convert($converter, &$form)
    {
    }

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // この画面のqueryはカスタム項目に対応していない（メインテーブルをグループ化している）
        $this->denyCustomColumn = true;

        // サプライヤーロケは選択肢に含めない。
        //  サプライヤーロケに存在する在庫にはロット引当できないため。（このクラス内の Logic_Stock::createTempStockTable() 呼び出しの
        //  引数指定でサプライヤーロケを排除していることに注目）
        $query = "select location_id, location_name from location_master where customer_id is null order by location_id";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0"=>GEN_DEFAULT_LOCATION_NAME));

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('受注製番'),
                    'field'=>'seiban',
                    'nosql'=>true,
                    'notShowMatchBox'=>true,
                    'hidePin'=>true,
                ),
                array(
                    'label'=>_g('引当日'),
                    'type'=>'calendar',
                    'field'=>'change_date',
                    'nosql'=>true,
                    'default'=> date('Y-m-d'),
                ),
                 array(
                    'label'=>_g('出庫ロケーション'),
                    'type'=>'select',
                    'field'=>'location_id',
                    'options'=>$option_location_group,
                    'nosql'=>true,
                    'helpText_noEscape'=>_g("サプライヤーロケーションは選択できません。（サプライヤーロケーションの在庫にはロット引き当てできません。）")
                ),
           );
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $changeDate = @$form['gen_search_change_date'];
        if (!Gen_String::isDateString($changeDate)) {
            $changeDate = date('Y-m-d');
        }
        $locationId = @$form['gen_search_location_id'];
        if (!Gen_String::isNumeric($locationId)) {
            $locationId = 0;
        }

        // 指定ロケの品目・製番別在庫データを取得
        Logic_Stock::createTempStockTable(
            $changeDate,
            null,   // item_id
            null,   // seiban
            $locationId,
            "sum",  // lot
            false,  // 有効在庫を取得するか
            false,  // サプライヤー在庫を含めるかどうか
            false   //  use_plan の全期間分差し引くかどうか。有効在庫を取得しないので無関係
            );

        $this->selectQuery = "
             SELECT
                t_ach_acc.stock_seiban
                ,t_ach_acc.lot_no
                ,t_ach_acc.use_by
                ,temp_stock.logical_stock_quantity
                -- 基本的には消費期限順だが、消費期限なし（製番フリー在庫）は一番上に出すようにする
                ,coalesce(use_by,'1970-1-1') as forOrder

             FROM
                received_detail
                INNER JOIN temp_stock on received_detail.item_id = temp_stock.item_id
                LEFT JOIN
                    (select stock_seiban, lot_no, use_by from achievement
                     union select stock_seiban, lot_no, use_by from accepted) as t_ach_acc
                     on temp_stock.seiban = t_ach_acc.stock_seiban

             [Where]
                and ".(@$form['gen_search_seiban']=='' ? '1=0' : "received_detail.seiban='{$form['gen_search_seiban']}'")."
                and logical_stock_quantity > 0
                /* ロット番号のある（実績とひもついた）製番在庫か、製番フリー在庫を出す。
                   逆に言えば、実績とひもつかない製番在庫（受注製番・計画製番）は出さない。 */
                and (lot_no is not null or temp_stock.seiban = '')
             [Orderby]
        ";
        $this->orderbyDefault = "forOrder";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("ロット指定");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Received_LotEdit";
        $form['gen_idField'] = 'stock_seiban';

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Received_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('受注登録へ戻る');

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("一括引当登録を行う権限がありません。") . "</Font>";
        } else if (@$form['gen_search_seiban']=='') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("受注製番を指定してください。") . "</Font>";
        } else {
            $query = "
                 SELECT
                    received_header.received_number
                    ,received_detail.seiban
                    ,item_master.item_code
                    ,item_master.item_name
                    ,received_detail.received_quantity
                    ,seiban_change_qty

                 FROM
                    received_detail
                    INNER JOIN received_header ON received_header.received_header_id=received_detail.received_header_id
                    LEFT JOIN item_master ON received_detail.item_id = item_master.item_id

                    -- 合計引当済数
                    LEFT JOIN
                    (
                        SELECT
                            dist_seiban
                            ,string_agg(achievement.lot_no, ',') as lot_no
                            ,sum(quantity) as seiban_change_qty
                        FROM
                            seiban_change
                            LEFT JOIN achievement on seiban_change.source_seiban = achievement.stock_seiban
                        GROUP BY
                            dist_seiban
                    ) AS T_lot ON received_detail.seiban = T_lot.dist_seiban

                 WHERE
                    received_detail.seiban = '{$form['gen_search_seiban']}'
            ";
            if (!($obj = $gen_db->queryOneRowObject($query))) {
                $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("指定された受注製番は存在しません。") . "</Font>";
            } else {
                // 画面中央のメッセージ（各種セレクタ）
                $form['gen_message_noEscape'] = "
                    <table>
                    <tr>
                        <td>受注番号</td>
                        <td><input type='text' id='received_number' value='" . h($obj->received_number) . "' style='width:100px; background-color:#cccccc' readonly></td>
                        <td width='20'></td>
                        <td>品目コード</td>
                        <td><input type='text' id='item_code' value='" . h($obj->item_code) . "' style='width:100px; background-color:#cccccc' readonly></td>
                        <td width='20'></td>
                        <td>品目名</td>
                        <td><input type='text' id='item_name' value='" . h($obj->item_name) . "' style='width:200px; background-color:#cccccc' readonly></td>
                    </tr>
                    <tr>
                        <td>受注数</td>
                        <td><input type='text' id='received_quantity' value='" . h($obj->received_quantity) . "' style='width:100px; background-color:#cccccc' readonly></td>
                        <td width='20'></td>
                        <td>引当済数</td>
                        <td><input type='text' id='seiban_change_qty' value='" . h($obj->seiban_change_qty) . "' style='width:100px; background-color:#cccccc' readonly></td>
                        <td width='20'></td>
                        <td>在庫引当合計数</td>
                        <td><input type='text' id='sum_quantity' value='' style='width:100px; background-color:#cccccc' readonly></td>
                    </td>
                    </table>

                    <div id=\"doButton\">
                    <input type=button value=\"&nbsp;&nbsp;" . _g("一括引当登録を実行") . "&nbsp;&nbsp;\" onClick=\"bulkSeibanChange()\">
                    </div>
                ";
            }
        }

        $form['gen_javascript_noEscape'] = "
            function bulkSeibanChange() {
                var frm = gen.list.table.getCheckedPostSubmit('stock_seiban', new Array('change_quantity'));
                if (frm.count == 0) {
                   alert('". _g("データが選択されていません。") . "');
                   return;
                }
                var msg = '';
                msg += '". _g("一括引当を実行しますか？") . "';
                if (!window.confirm(msg)) return;

                document.body.style.cursor = 'wait';
                $('#doButton').html(\"<table><tr><td bgcolor='#ffcc33'>". _g("実行中") ."...</td></tr></table>\");
                gen.ui.disabled($('#gen_searchButton'));

                var postUrl = 'index.php?action=Manufacturing_Received_LotEntry';
                postUrl += '&dist_seiban=' + $('#gen_search_seiban').val();
                postUrl += '&change_date=' + $('#gen_search_change_date').val();
                postUrl += '&location_id=' + $('#gen_search_location_id').val();
                frm.submit(postUrl, null);
            }

            function check(id) {
               // 在庫数<引当数の入力はエラーにする
               var logical = $('#logical_stock_quantity_' + id).html();
               var qty = $('#change_quantity_' + id).val();
               if (parseFloat(logical) < parseFloat(qty)) {
                   $('#change_quantity_' + id).val('');
                   alert('" . _g("在庫数が不足しているため、引当ができません。") . "');
                   $('#stock_seiban_'+id).attr('checked',false);
               } else {
                   $('#stock_seiban_'+id).attr('checked',true);
               }
               
               var total = 0;
               $('[id^=change_quantity_]').each(function(){
                    if (gen.util.isNumeric(this.value)) {
                        total = gen.util.decCalc(total, this.value, '+');
                    }
               });
               $('#sum_quantity').val(gen.util.addFigure(total));
            }
        ";
        
        $form['gen_rowColorCondition'] = array(
            "#f9bdbd" => "('[use_by]'!=''&&'[use_by]'<'" . date('Y-m-d') . "')",
        );
        $form['gen_colorSample'] = array(
            "f9bdbd" => array(_g("ピンク"), _g("消費期限切れ")),
        );

        $form['gen_fixColumnArray'] =
            array(
                array(
                    'label'=>_g("引当"),
                    'width'=>'40',
                    'type'=>'checkbox',
                    'name'=>'stock_seiban',
                    'align'=>'center',
                ),
            );

        $form['gen_columnArray'] =
            array(
                array(
                    'label'=>_g('ロット番号'),
                    'field'=>'lot_no',
                    'width'=>'200',
                    'align'=>'center',
                ),
                array(
                    'label'=>_g('製番'),
                    'field'=>'stock_seiban',
                    'width'=>'100',
                    'align'=>'center',
                ),
                array(
                    'label'=>_g('消費期限'),
                    'field'=>'use_by',
                    'type'=>'date',
                    'width'=>'100',
                    'align'=>'center',
                ),
                array(
                    'label'=>_g('理論在庫'),
                    'field'=>'logical_stock_quantity',
                    'type'=>'div',
                    'width'=>'100',
                    'align'=>'center',
                ),
                array(
                    'label'=>_g('在庫引当数'),
                    'width'=>'80',
                    'type'=>'textbox',
                    'align'=>'center',
                    'field'=>'change_quantity',
                    'colorCondition' => array("#ffffcc" => "true"),
                    'style'=>'text-align:right; background-color:#ffffcc',
                    'onChange_noEscape' =>"check('[id]')"
                ),
          );
    }
}