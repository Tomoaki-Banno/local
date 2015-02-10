<?php

require_once("Model.class.php");

class Manufacturing_Order_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullToValue('order_date', date('Y-m-d'));
        $converter->nullToValue('order_detail_dead_line', date('Y-m-d'));
        $converter->nullBlankToValue('payout_lot_id', 0);
    }

    function validate($validator, &$form)
    {
        $validator->blankOrNumeric('order_header_id', _g('order_header_idが正しくありません。'));

        return 'action:Manufacturing_Order_List';        // if error
    }

    // データ取得のための設定
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'order_header_id';

        $this->selectQuery = "
            select
                order_header.*
                ,t_detail.*
                ,item_master.*
                ,coalesce(order_header.record_update_date, order_header.record_create_date) as gen_last_update
                ,coalesce(order_header.record_updater, order_header.record_creator) as gen_last_updater
            from
                order_header
                inner join (
                    select
                        order_header_id as ohid
                        ,order_detail_id
                        ,line_no
                        ,order_no
                        ,seiban
                        ,item_id
                        ,item_code
                        ,item_name
                        ,item_price
                        ,item_sub_code
                        ,order_detail_quantity
                        ,order_detail_dead_line
                        ,accepted_quantity
                        ,order_detail_completed
                        ,alarm_flag
                        ,payout_location_id
                        ,payout_lot_id
                        ,plan_date
                        ,plan_qty
                        ,order_measure
                        ,multiple_of_order_measure
                        ,tax_class
                        ,remarks
                    from
                        order_detail
                    ) as t_detail on order_header.order_header_id = t_detail.ohid
                inner join item_master on t_detail.item_id = item_master.item_id
            [Where]
        ";

        // データロック対象外
        $query = "select unlock_object_2 from company_master";
        $unlock = $gen_db->queryOneValue($query);
        if ($unlock != "1") {
            // データロックの判断基準となるフィールドを指定
            $form["gen_dateLockFieldArray"] = array("order_date", "order_detail_dead_line");
        }
    }

    // 表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Manufacturing_Order_Model";

        $form['gen_pageTitle'] = _g("製造指示登録");
        $form['gen_entryAction'] = "Manufacturing_Order_Entry";
        $form['gen_listAction'] = "Manufacturing_Order_List";
        $form['gen_pageHelp'] = _g("製造指示");

        // これを設定すると、「登録して印刷」ボタンと「帳票を印刷」ボタン（編集モードのみ）が表示される。
        $form['gen_reportArray'] = array(
            'action' => "Manufacturing_Order_Report",
            'param' => "check_[id]",
            'seq' => "order_header_order_header_id_seq",
        );

        $form['gen_javascript_noEscape'] = "
            function onItemIdChange() {
                var p = {
                    itemId : $('#item_id').val()
                };
                if (!gen.util.isNumeric(p.itemId)) {
                    return;
                }

                gen.ajax.connect('Manufacturing_Order_AjaxItemParam', p, 
                    function(j) {
                        if (j=='') return;

                        // MRP/ロット品目なら製番をロック
                        var s1 = $('#seiban_show');
                        var s2 = $('#seiban_dropdown');
                        var s3 = $('#seiban');
                        if (j.order_class == 0) {
                            s1.css('background-color','#ffffff');
                            gen.ui.enabled(s1);
                            s1.attr('readonly');    // 手入力は禁止
                            s2.removeAttr('disabled');
                        } else {
                            s1.css('background-color','#cccccc');
                            gen.ui.disabled(s1);    // readonlyだとフォーカス喪失後の背景色の問題あり
                            s1.val('');
                            s2.attr('disabled');
                            s3.val('');
                        }
                    });
            }
        ";

        $form['gen_message_noEscape'] = "";

        // すでに実績が登録されている場合は、変更不可とする
        // ここでは全編集禁止としているが、一部変更許可するように変更するとしても、
        // 少なくとも手配先は変更不可とする必要がある（支給が変わる可能性があるので）
        // もし実績登録後も変更可能にすると、以下の問題が発生する：
        // ・子品目を持つ品目に対する製造指示について、指示数より少ない製造数で製造完了扱いとし、
        //   さらにその後に製造指示数を変更した場合に、余分な予約数が登録されてしまう
        // ・子品目を持つ品目に対する製造指示について、実績登録後に製造指示を更新した場合に、予約数が不正になる
        // これらは Logic_Order::entryUsePlan() で使用予約の更新時にいったんUSE_PLANのレコードを
        // 削除した後で、納品済み数の差し引きおよび完了調整の再登録が行われていないため。
        // カスタマイズするならその点を考慮する必要がある。
        if (is_numeric(@$form['order_header_id'])) {
            if (Logic_Achievement::existAchievement($form['order_header_id'])) {
                // 実績登録済み
                $form['gen_readonly'] = "true";
                $form['gen_message_noEscape'] .= "<font color=red>" . _g("この製造指示に対する製造実績が登録されているため、<br>内容を変更することはできません。") . "</font><br><br>";
            } else {
                // 外製工程受入登録済み
                $query = "select *
                    from order_detail
                    inner join order_detail as t_sub_order on order_detail.order_no = t_sub_order.subcontract_parent_order_no
                    inner join accepted on t_sub_order.order_detail_id = accepted.order_detail_id
                    where order_detail.order_header_id = '{$form['order_header_id']}'
                    ";
                if ($gen_db->existRecord($query)) {
                    $form['gen_readonly'] = "true";
                    $form['gen_message_noEscape'] .= "<font color=red>" . _g("この製造指示に対する外製工程の受入が登録されているため、<br>内容を変更することはできません。") . "</font><br><br>";
                }
            }
        }

        // 前回登録時から構成表マスタが変更されている場合は警告を出す
        if (is_numeric(@$form['order_header_id'])) {
            if (Logic_Order::isModifiedBom($form['order_header_id'])) {
                $form['gen_message_noEscape'] .= "<font color=blue>" . _g("前回この製造指示を登録した後で、関連する構成表マスタが変更されています。") . "<br>" .
                        _g("ここで「登録」ボタンを押すと、使用予約数や支給数、実績登録時の引落数に構成表マスタの変更が反映されます。") . "<br>" .
                        _g("「閉じる」をクリックすれば、前回登録時の構成が維持されます。") . "</font><br><br>";
            }
        }
        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'type' => 'textbox',
                'name' => 'order_no',
                'value' => @$form['order_no'],
                'size' => '12',
                'readonly' => (isset($form['order_header_id'])),
                'ime' => 'off',
                'hidePin' => true,
                'helpText_noEscape' => _g("自動的に採番されますので、指定する必要はありません。") . "<br>" .
                _g("修正モードではオーダー番号を変更することはできません。"),
            ),
            // 10iから、オーダーに手動で製番を付与したり、振り替えたりできるようになった（製番品目のみ）。
            // 詳細は Partner_Order_Edit の カラムリストの製番の箇所のコメントを参照。
            // 15iでは、完了済の製番も選択できるようになった。ag.cgi?page=ProjectDocView&pid=1574&did=217672
            array(
                'label' => _g('製番'),
                'type' => 'dropdown',
                'name' => 'seiban',
                'value' => @$form['seiban'],
                'size' => '10',
                'dropdownCategory' => 'received_seiban',
                'dropdownShowCondition_noEscape' => "!isNaN([item_id]) && !$('#seiban_show').attr('disabled')",
                'dropdownShowConditionAlert' => _g("製番を指定できるのは、製番品目が指定されている場合のみです。"),
                'readonly' => @$form['order_class'] != '0',
                'noWrite' => true,
                'tabindex' => -1,
                'helpText_noEscape' => '<b>' . _g('MRP品目') . '：</b>' . _g('製番はつきません。') .
                        '<br><br><b>' . _g('製番品目') . '：</b>' . _g('このオーダーが所要量計算の結果として発行されたものであれば、' .
                        'もとになった受注と同じ製番が自動的につきます。') .
                        '<br>' . _g('ドロップダウンで任意の受注製番を指定して、製造指示と受注を結びつけることもできます。' .
                        'ドロップダウンで表示されるのは、製番品目の確定受注だけです。'),
            ),
            array(
                'label' => _g('製造開始日'),
                'type' => 'calendar',
                'name' => 'order_date',
                'value' => @$form['order_date'],
                'size' => '8',
                'isCalendar' => true,
                'require' => true,
            ),
            array(
                'label' => _g('製造納期'),
                'type' => 'calendar',
                'name' => 'order_detail_dead_line',
                'value' => @$form['order_detail_dead_line'],
                'size' => '8',
                'isCalendar' => true,
                'require' => true,
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'dropdownCategory' => 'item_order_manufacturing',   // 詳細は Logic_Dropdown の該当箇所を参照
                'autoCompleteCategory' => 'item_order_manufacturing',
                'onChange_noEscape' => 'onItemIdChange()',
                'require' => true,
                'size' => '12',
                'subSize' => '20',
                'helpText_noEscape' => _g("選択できる品目は手配先によって変わります。品目マスタで手配先が「内製」として登録されている品目が対象です。ダミー品目は登録できません。"),
            ),
            array(
                'label' => _g('数量'),
                'type' => 'textbox',
                'name' => 'order_detail_quantity',
                'value' => @$form['order_detail_quantity'],
                'size' => '8',
                'ime' => 'off',
                'require' => true,
            ),
            array(
                'label' => _g('製造指示備考'),
                'type' => 'textbox',
                'name' => 'remarks_header',
                'value' => @$form['remarks_header'],
                'ime' => 'on',
                'size' => '30'
            ),
        );
    }

}