<?php

class Manufacturing_SeibanExpand_Edit extends Base_ListBase
{

    var $idSerial;

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('オーダー手配済'),
                'type' => 'select',
                'field' => 'order_finish',
                'options' => array('0' => _g('表示する'), '1' => _g('表示しない')),
                'nosql' => 'true',
            ),
            array(
                'type' => 'holder', // 保持用
                'field' => 'idSerial',
                'nosql' => 'true', // sqlに含めない
                'hide' => 'true', // 検索条件に表示させない
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        if (!isset($form["gen_search_idSerial"])) {
            // 展開対象データidを配列に列挙する
            $idArr = array();
            foreach ($form as $name => $value) {
                if (substr($name, 0, 11) == "chk_expand_") {
                    $idArr[] = substr($name, 11, strlen($name) - 11);
                }
            }
            $form["gen_search_idSerial"] = join(",", $idArr);
        }
        $this->idSerial = $form["gen_search_idSerial"];
        // 初回表示時の保持のためにセット
        $_SESSION['Manufacturing_SeibanExpand_Edit_idSerial'] = $this->idSerial;

        // 製番受注の展開データを作成する
        Logic_Seiban::expandReceivedSeiban($this->idSerial);
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                t01.received_detail_id || '_' || t01.item_id as expand_id
                ,t01.seiban
                ,t01.item_id
                ,t02.item_code
                ,t02.item_name
                ,t01.lc
                ,t01.quantity
                ,(coalesce(t03.order_total,0)-coalesce(t06.change_out_quantity,0)+coalesce(t07.change_in_quantity,0)) as order_total
                ,case when (coalesce(t01.quantity,0) - (coalesce(t03.order_total,0)-coalesce(t06.change_out_quantity,0)+coalesce(t07.change_in_quantity,0))) < 0 then 0
                    else (coalesce(t01.quantity,0) - (coalesce(t03.order_total,0)-coalesce(t06.change_out_quantity,0)+coalesce(t07.change_in_quantity,0))) end as order_quantity
                ,order_date
                ,dead_line
                ,t04.order_user_id
                ,t04.partner_class
                ,case when (coalesce(t01.quantity,0) - (coalesce(t03.order_total,0)-coalesce(t06.change_out_quantity,0)+coalesce(t07.change_in_quantity,0))) < 0 then 0
                    else (coalesce(t01.quantity,0) - (coalesce(t03.order_total,0)-coalesce(t06.change_out_quantity,0)+coalesce(t07.change_in_quantity,0))) * coalesce(default_order_price,0) end as order_amount
                ,t01.alarm_flag
                ,coalesce(t05.order_flag,0) as order_flag
                ,'' as remarks
                ,''::text as currency_name /* 集計行が「データの数」の場合のエラー回避 */
            from
                temp_seiban_expand as t01
                inner join item_master as t02 on t01.item_id = t02.item_id
                left join (
                    select
                        item_id, seiban, sum(order_detail_quantity) as order_total
                    from
                        order_detail
                    where
                        coalesce(subcontract_order_process_no,'') = ''
                        and coalesce(subcontract_parent_order_no,'') = ''
                    group by
                        item_id, seiban
                    ) as t03 on t01.item_id = t03.item_id and t01.seiban = t03.seiban
                left join (
                    select
                        item_id, order_user_id, partner_class, default_order_price
                    from
                        item_order_master
                    where
                        line_number = 0
                    ) as t04 on t01.item_id = t04.item_id
                left join (
                    select
                        received_detail_id,
                        sum(order_flag) as order_flag
                    from (
                        select
                            t051.received_detail_id,
                            t051.item_id,
                            case when (coalesce(t051.quantity,0) - coalesce(t052.order_total,0)) > 0 then 1 else 0 end as order_flag
                        from
                            temp_seiban_expand as t051
                            left join (
                                select
                                    item_id, seiban, sum(order_detail_quantity) as order_total
                                from
                                    order_detail
                                group by
                                    item_id, seiban
                                ) as t052 on t051.item_id = t052.item_id and t051.seiban = t052.seiban
                        ) as t_order_flag
                    group by
                        received_detail_id
                    ) as t05 on t01.received_detail_id = t05.received_detail_id
                left join (
                    select
                        item_id, source_seiban, sum(quantity) as change_out_quantity
                    from
                        seiban_change
                    group by
                        item_id, source_seiban
                    ) as t06 on t01.item_id = t06.item_id and t01.seiban = t06.source_seiban
                left join (
                    select
                        item_id, dist_seiban, sum(quantity) as change_in_quantity
                    from
                        seiban_change
                    group by
                        item_id, dist_seiban
                    ) as t07 on t01.item_id = t07.item_id and t01.seiban = t07.dist_seiban
            [Where]
                " . (@$form['gen_search_order_finish'] == "1" ? " and (coalesce(t01.quantity,0) - coalesce(t03.order_total,0)) > 0" : "") . "
            [Orderby]
        ";
        $this->orderbyDefault = "seiban, lc, item_code";
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("製番展開");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_SeibanExpand_Edit";
        $form['gen_idField'] = 'expand_id';
        $form['gen_onLoad_noEscape'] = "onLoad();allPartnerClassChange()";
        $form['gen_afterListUpdateScript_noEscape'] = "allPartnerClassChange();";
        $form['gen_pageHelp'] = _g("製番展開");

        $form['gen_titleRowHeight'] = 40;       // 見出し部の1行の高さ
        $form['gen_dataRowHeight'] = 30;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Received_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('受注登録へ戻る');

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("一括オーダー登録を行う権限がありません。") . "</Font>";
        } else {
            $form['gen_message_noEscape'] = "
                <table border=\"0\">
                <tr><td align=\"left\">
                    <input type='checkbox' value='true' id='seiban_expand_today'" . ((@$form['seiban_expand_today'] == 'true') ? " checked" : "") . ">" . _g("手配日を当日にする") . "
                    <img src=\"img/pin02.png\" id=\"gen_pin_off_seiban_expand_today\" style=\"vertical-align: text-top; cursor:pointer;" . (in_array('seiban_expand_today', $form['gen_pins']) ? "display:none;" : "") . "\" onclick=\"gen.pin.turnOn('Manufacturing_SeibanExpand_Edit', 'seiban_expand_today', '');\">
                    <img src=\"img/pin01.png\" id=\"gen_pin_on_seiban_expand_today\" style=\"vertical-align: text-top; cursor:pointer;" . (!in_array('seiban_expand_today', $form['gen_pins']) ? "display:none;" : "") . "\" onclick=\"gen.pin.turnOff('Manufacturing_SeibanExpand_Edit', 'seiban_expand_today', '');\">
                </td></tr>
                <tr><td align=\"center\">
                    <div id=\"doButton\">
                    <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp;" . _g("一括オーダー確定") . "&nbsp;&nbsp;\" onClick=\"bulkOrder()\">
                    </div>
                </td></tr>
                </table>
            ";
        }

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("製番展開を行う権限がありません。") . "</Font>";
        }

        $form['gen_javascript_noEscape'] = "
            function onLoad() {
                // 非表示id値をセット
                $('#gen_search_idSerial').val('{$this->idSerial}');
            }

            // 全手配区分変更イベント
            var timeoutId;
            function allPartnerClassChange() {
                // LazyLoad終了まで待つ ag.cgi?page=ProjectDocView&pid=1139&did=223706
                if (typeof gen_domArr_D !== 'undefined' && gen_domArr_D != null) {
                    if (gen.waitDialog.count == 0) {
                        gen.waitDialog.show('" . _g("お待ちください..") . "');
                    }
                    timeoutId = setTimeout('allPartnerClassChange()',500);
                    return;
                } else {
                    clearTimeout(timeoutId);
                    gen.waitDialog.hide(true);
                }

                // DD設定
                $('[name^=\"partner_class_\"]').each(function() {
                    var id = this.name.replace('partner_class_', '');
                    var partnerClass = $('#'+this.name).val();
                    var qty = $('#order_quantity_'+id).val();
                    if (gen.util.isNumeric(qty)) $('#order_quantity_'+id).val(gen.util.round(qty,-1));
                    if (partnerClass=='3') onPartnerClassChange(this.name);
                    if (partnerClass!='3') onOrderUserIdChange('order_user_id_'+id);
                });
            }

            // 手配区分変更イベント
            function onPartnerClassChange(field) {
                var id = field.replace('partner_class_', '');
                var partnerClass = $('#'+field).val();
                if (partnerClass=='0' || partnerClass=='1' || partnerClass=='2') {
                    $('#order_user_id_'+id+'_show').removeAttr('disabled');
                    $('#order_user_id_'+id+'_dropdown').removeAttr('disabled');
                    $('#order_user_id_'+id+'_show').css('background-color','#ffffcc');
                    $('#order_price_'+id).removeAttr('disabled');
                    $('#order_price_'+id).css('background-color','#ffffcc');
                } else {
                    $('#order_user_id_'+id+'_show').attr('disabled', 'disabled');
                    $('#order_user_id_'+id+'_dropdown').attr('disabled', 'disabled');
                    $('#order_user_id_'+id+'_show').css('background-color','#cccccc');
                    $('#order_price_'+id).attr('disabled', 'disabled');
                    $('#order_price_'+id).css('background-color','#cccccc');
                    $('#order_user_id_'+id).val('');
                    $('#order_user_id_'+id+'_show').val('');
                    $('#order_user_id_'+id+'_sub').val('');
                    $('#order_price_'+id).val('');
                    $('#order_amount_'+id).html('0');
                    $('#currency_name_'+id).html('');
                }
            }

            // オーダー数変更イベント
            function onOrderQuantityChange(field) {
                var id = field.replace('order_quantity_', '');
                setOrderData(id);
            }

            // 単価変更イベント
            function onOrderPriceChange(field) {
                var id = field.replace('order_price_', '');
                setAmountData(id);
            }

            // 手配先変更イベント
            function onOrderUserIdChange(field) {
                var id = field.replace('order_user_id_', '');
                setOrderData(id);
            }

            // 手配先単価設定
            function setOrderData(id) {
                var orderUserId = $('#order_user_id_'+id).val();
                var p = {orderUserId : orderUserId, id : id};
                gen.ajax.connect('Manufacturing_SeibanExpand_AjaxPriceParam', p, 
                    function(j) {
                        if (j.currency_status == 'success') {
                            $('#currency_name_'+id).html(j.currency_name);
                        } else {
                            $('#currency_name_'+id).html('');
                        }
                        if (j.status == 'success') {
                            var pri = j.default_order_price;
                            var qty = $('#order_quantity_'+id).val();
                            if (gen.util.isNumeric(qty)) {
                                // 単価適用数により単価を決定。単価適用数がNullの場合、その単価を使用することに注意
                                if (parseFloat(qty) > parseFloat(nnz(j.order_price_limit_qty_1)) && j.order_price_limit_qty_1 != null) {
                                    pri = nnz(j.default_order_price_2);
                                    if (parseFloat(qty) > parseFloat(nnz(j.order_price_limit_qty_2)) && j.order_price_limit_qty_2 != null) {
                                        pri = nnz(j.default_order_price_3);
                                    }
                                }
                            }
                            $('#order_price_'+id).val(pri);
                            setAmountData(id);
                        }
                    });
            }

            // 金額設定
            function setAmountData(id) {
                var qty = $('#order_quantity_'+id).val();
                var pri = $('#order_price_'+id).val();
                if (!gen.util.isNumeric(qty)) return;
                if (!gen.util.isNumeric(pri)) return;
                $('#order_amount_'+id).html(gen.util.addFigure(gen.util.decCalc(qty,pri,'*')));   // valではなくhtml
            }

            function nnz(val) {
                return (gen.util.isNumeric(val) ? val : 0);
            }

            // 一括オーダー確定処理
            function bulkOrder() {
                var frm = new gen.postSubmit();
                var count = 0;
                var err = false;
                var elmName;
                var id;
                var quantity;
                var partnerClass;
                var orderUserId;
                var price;
                var orderDate;
                var deadLine;
                var isToday = $('#seiban_expand_today')[0].checked;
                if (isToday) {
                    frm.add('isToday', 'true');
                } else {
                    frm.add('isToday', 'false');
                }
                $('[name^=expand_id_]').each(function() {
                    if (this.checked) {
                        frm.add(this.name, this.value);
                        id = this.name.substr('expand_id'.length+1);

                        // オーダー数チェック
                        elmName = 'order_quantity_'+id;
                        quantity = $('#'+elmName).val();
                        if (!gen.util.isNumeric(quantity) || quantity=='0') {
                            alert('" . _g("オーダー数が正しくありません。") . "');
                            $('#'+elmName).focus().select();
                            err = true;
                            return false;
                        }
                        frm.add(elmName, quantity);

                        // 手配区分チェック
                        elmName = 'partner_class_'+id;
                        partnerClass = $('#'+elmName).val();
                        if (partnerClass == undefined) {
                            alert('" . _g("画面右端の「表示項目選択」アイコンで、「手配区分」列を表示してください。") . "');
                            err = true;
                            return false;
                        }
                        frm.add(elmName, partnerClass);
                        if (partnerClass!='3') {
                            // 手配先チェック
                            elmName = 'order_user_id_'+id;
                            orderUserId = $('#'+elmName).val();
                            if (!gen.util.isNumeric(orderUserId)) {
                                alert('" . _g("手配先が正しくありません。") . "');
                                $('#'+elmName + '_show').focus();
                                err = true;
                                return false;
                            }
                            frm.add(elmName, orderUserId);

                            // 単価チェック
                            elmName = 'order_price_'+id;
                            price = $('#'+elmName).val();
                            if (!gen.util.isNumeric(price)) {
                                alert('" . _g("オーダー単価が正しくありません。") . "');
                                $('#'+elmName).focus().select();
                                err = true;
                                return false;
                            }
                            frm.add(elmName, price);
                        }

                        // 手配日チェック（手配日を本日で確定チェックがOff時）
                        if (!isToday) {
                            elmName = 'order_date_'+id;
                            orderDate = $('#'+elmName).val();
                            if (!gen.date.isDate(orderDate)) {
                                alert('" . _g("手配日が正しくありません。") . "');
                                $('#'+elmName).focus().select();
                                err = true;
                                return false;
                            }
                            frm.add(elmName, orderDate);
                        }

                        // 納期チェック
                        elmName = 'dead_line_'+id;
                        deadLine = $('#'+elmName).val();
                        if (!gen.date.isDate(deadLine)) {
                            alert('" . _g("オーダー納期が正しくありません。") . "');
                            $('#'+elmName).focus().select();
                            err = true;
                            return false;
                        }
                        frm.add(elmName, deadLine);

                        elmName = 'remarks_'+id;
                        var remElm = $('#'+elmName);
                        if (remElm.length > 0) {    // 備考列非表示対策
                            frm.add(elmName, remElm.val());
                        }

                        count++;
                    }
                });

                if (err) return;
                if (count == 0) {
                    alert('" . _g("一括オーダー確定するデータを選択してください。") . "');
                } else {
                    if (!window.confirm('" . _g("処理を実行してもよろしいですか？") . "')) {
                        " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#doButton'));") . "
                        return;
                    }

                    gen.waitDialog.show('" . _g("お待ちください..") . "');

                    var postUrl = 'index.php?action=Manufacturing_SeibanExpand_BatchOrder&gen_doOrder=true';
                    frm.submit(postUrl, null);
                    // 画面更新とwaitダイアログ表示。listUpdateによるAjax更新はBulkInspectionクラスの処理が終わるまで
                    // session_start()で足止めになるので、結果として処理が終わるまでダイアログが出たままとなる。
                    listUpdate(null, false);
                }
            }
        ";

        if (@$form['gen_doOrder']) {        // 一括データ確定時
            $form['gen_javascript_noEscape'] .= "alert('" . _g("一括オーダー確定処理しました。") . "');\n";
        }

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[order_quantity]'<=0", // オーダー済
            "#f9bdbd" => "'[alarm_flag]'==1", // オーダー納期調整
        );
        
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("手配済み")),
            "f9bdbd" => array(_g("ピンク"), _g("オーダー納期調整")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => 'true',
                'colorCondition' => array("#ffffff" => "'[order_flag]'>='1'"),
            ),
            array(
                'label' => _g('階層'),
                'field' => 'lc',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g("確定"),
                'width' => '40',
                'type' => 'checkbox',
                'name' => 'expand_id',
                'align' => 'center',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'cellId' => 'item_code_[id]',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('必要数'),
                'field' => 'quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('手配済数'),
                'field' => 'order_total',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('オーダー数'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'order_quantity',
                'style' => 'text-align:right; background-color:#ffffcc; ime-mode:disabled;',
                'onChange_noEscape' => "onOrderQuantityChange('[id]')"
            ),
            array(
                'label' => _g("手配区分"),
                'width' => '113',
                'type' => 'select',
                'field' => "partner_class",
                'options' => Gen_Option::getPartnerClass('options'),
                '//colorCondition' => array("#ffffff" => "true"),
                'style' => 'background-color:#ffffcc',
                'onChange_noEscape' => "onPartnerClassChange('[id]')",
            ),
            array(
                'label' => _g("手配先"),
                'width' => '260',
                'type' => 'dropdown',
                'align' => 'center',
                'field' => "order_user_id",
                'divField' => "item_id",
                'size' => '10',
                'subSize' => '15',
                'dropdownCategory' => 'partner_for_order',
                'dropdownParam' => '[div_order_user_id_[id]]',
                'style' => 'background-color:#ffffcc',
                'onChange_noEscape' => "onOrderUserIdChange('[id]')",
            ),
            array(
                'label' => _g('オーダー単価'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'order_price',
                'style' => 'text-align:right; background-color:#ffffcc; ime-mode:disabled;',
                'onChange_noEscape' => "onOrderPriceChange('[id]')",
            ),
            array(
                'label' => _g('金額'),
                'field' => 'order_amount',
                'type' => 'numeric',
                'cellId' => 'order_amount_[id]',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '80',
                'align' => 'center',
                'cellId' => 'currency_name_[id]',
            ),
            array(
                'label' => _g('手配日'),
                'width' => '100',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'order_date',
                'style' => 'text-align:center; background-color:#ffffcc; ime-mode:disabled;',
                'onChange_noEscape' => "gen.dateBox.dateFormat('order_date_[id]')",
            ),
            array(
                'label' => _g('オーダー納期'),
                'width' => '100',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'dead_line',
                'style' => 'text-align:center; background-color:#ffffcc; ime-mode:disabled;',
                'onChange_noEscape' => "gen.dateBox.dateFormat('dead_line_[id]')",
            ),
            array(
                'label' => _g('オーダー備考'),
                'width' => '200',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'remarks',
                'style' => 'text-align:left; background-color:#ffffff',
            ),
        );
    }

}
