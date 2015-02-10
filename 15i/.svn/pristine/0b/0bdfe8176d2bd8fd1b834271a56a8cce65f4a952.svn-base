<?php

require_once("Model.class.php");

class Stock_Inout_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->notSelectStrToValue('classification', array('in', 'out', 'payout', 'use'), 'in');
        $converter->nullBlankToValue('Logic_Inout_title', Logic_Inout::classificationToTitle($form['classification'], isset($form['past'])));
        $converter->nullBlankToValue('item_in_out_date', date('Y-m-d'));
        $converter->nullBlankToValue('payout_lot_id', 0);
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'item_in_out_id';
        $this->selectQuery = "
            select
                item_in_out.*
                ,item_master.order_class
                ,coalesce(item_in_out.record_update_date, item_in_out.record_create_date) as gen_last_update
                ,coalesce(item_in_out.record_updater, item_in_out.record_creator) as gen_last_updater
            from
                item_in_out
                left join item_master on item_in_out.item_id = item_master.item_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_dateLockFieldArray"] = array("item_in_out_date");

        //  同じクラスを引数切り替えで複数のページとして扱う場合、ページごとに検索条件や列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        //  （EditBaseでの処理順の関係で、setQueryCondition()で行う必要がある。）
        $form['gen_pageMode'] = $form['classification'];
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Stock_Inout_Model";

        $form['gen_pageTitle'] = $form['Logic_Inout_title'];
        $form['gen_entryAction'] = "Stock_Inout_Entry&classification={$form['classification']}";
        $form['gen_listAction'] = "Stock_Inout_List&classification={$form['classification']}";
        switch ($form['classification']) {
            case "in":
                $form['gen_pageHelp'] = _g("入庫登録");
                break;
            case "out":
                $form['gen_pageHelp'] = _g("出庫登録");
                break;
            case "payout":
                $form['gen_pageHelp'] = _g("支給");
                break;
            default:
                break;
        }

        $form['gen_javascript_noEscape'] = "
            // 品目変更イベント
            function onItemChange() {
                document.getElementById('item_in_out_quantity').focus();
                " . ($form['classification'] == "payout" ? "getPayoutPrice();" : "") . "
            }

            //  Ajaxで標準支給単価と標準ロケを取得
            function getPayoutPrice() {
                var itemId = $('#item_id').val();
                if (!gen.util.isNumeric(itemId)) 
                    return;
                gen.ajax.connect('Stock_Inout_AjaxPayoutPrice', {itemId : itemId}, 
                    function(j) {
                        if (j != null) {
                            $('#item_price').val(j.item_price);
                            if (gen.util.isNumeric(j.default_location_id))
                                $('#location_id').val(j.default_location_id);
                        }
                    });
            }
        ";

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('日付'),
                'type' => 'calendar',
                'name' => 'item_in_out_date',
                'value' => @$form['item_in_out_date'],
                'size' => '10',
                'require' => true,
                'isCalendar' => true
            ),
            array(
                'label' => _g('数量'),
                'type' => 'textbox',
                'name' => 'item_in_out_quantity',
                'value' => @$form['item_in_out_quantity'],
                'require' => true,
                'ime' => 'off',
                'size' => '10'
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'dropdownCategory' => 'item',
                // 修正モードでは品目変更禁止（Entry後に変更前品目の在庫が再計算されないため）
                'readonly' => (isset($form['item_in_out_id']) && !isset($form['gen_record_copy'])),
                'onChange_noEscape' => "onItemChange()",
                'require' => true,
                'size' => '12',
                'subSize' => '20',
            ),
            array(
                'label' => ($form['classification'] == 'payout' ? _g('出庫ロケーション') : _g('ロケーション')),
                'type' => 'select',
                'name' => 'location_id',
                'options' => $option_location_group,
                'selected' => @$form['location_id'],
            ),
            array(
                'label' => _g('入出庫備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '30'
            ),
        );
                
        // 15.1iで、出庫に受注製番を指定できるようにした。原価加算のため。ag.cgi?page=ProjectDocView&ppid=1574&pbid=232676
        if ($form['classification'] == 'out') {
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('製番'),
                'type' => 'dropdown',
                'name' => 'seiban',
                'value' => @$form['seiban'],
                'size' => '10',
                'dropdownCategory' => 'received_seiban',
                'noWrite' => true,
                'tabindex' => -1,
                'helpText_noEscape' => _g('ドロップダウンで任意の受注製番を指定して、出庫金額を原価に加算することができます。') 
                        . _g('ドロップダウンで表示されるのは、製番品目の確定受注だけです。'),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('出庫金額'),
                'type' => 'textbox',
                'name' => 'stock_amount',
                'value' => @$form['stock_amount'],
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g('出庫金額を指定します。') . '<br>'
                        . _g('製番を指定したときのみ入力可能です。') . '<br><br>'
                        . _g('この金額は実績原価に加算されます。') . '<br><br>'
                        . _g('空欄にすると、(出庫日時点の在庫評価単価 × 出庫数) が自動的に登録されます。'),
            );
        }

        if ($form['classification'] != 'in' && $form['classification'] != 'out') {
            // 入庫画面では製番を非表示にした。
            // 入庫画面で製番が表示されるのは「外製登録（支給あり）で製番品目が支給されたときのサプライヤーロケへの入庫」という特殊なレコードのみだった。
            // 製番の項目があるとユーザーが誤った期待をしてしまうことが多いようなので、廃止した。
            // サプライヤーロケ入庫と対になる支給登録には製番表示があるので、その意味では製番表示があってもいいのかもしれないが・・。
            $form['gen_editControlArray'][] = array(
                'label' => _g('製番'),
                'type' => 'textbox',
                'name' => 'seiban',
                'value' => @$form['seiban'],
                'size' => '10',
                'readonly' => 'true',
            );
        }

        if ($form['classification'] == 'use') {
            $form['gen_editControlArray'][] = array(
                'label' => _g('親品目'),
                'type' => 'dropdown',
                'name' => 'parent_item_id',
                'value' => @$form['parent_item_id'],
                'dropdownCategory' => 'item',
                'size' => '12',
                'helpText_noEscape' => _g('親品目（ここで登録する品目を使用して製造した製品）を指定します。')
            );
        }

        if ($form['classification'] == 'payout') {
            $form['gen_editControlArray'][] = array(
                'label' => _g('支給単価'),
                'type' => 'textbox',
                'name' => 'item_price',
                'value' => @$form['item_price'],
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g('支給単価を指定します。')
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('支給先名'),
                'type' => 'dropdown',
                'name' => 'partner_id',
                'value' => @$form['partner_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'partner',
                'require' => true,
                'helpText_noEscape' => _g('支給先を指定します。取引先マスタに「サプライヤー」として登録されている取引先が対象です。'),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('在庫から引き落とさない'),
                'type' => 'checkbox',
                'name' => 'without_stock',
                'onvalue' => '1', // trueのときの値
                'value' => (@$form['without_stock'] == "1" ? "true" : "false"),
                'helpText_noEscape' => _g('このチェックをオンにすると、支給数量が在庫から引き落とされません。')
            );
        }
    }

}
