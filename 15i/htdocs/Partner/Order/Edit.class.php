<?php

require_once("Model.class.php");

class Partner_Order_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        global $gen_db;

        $converter->nullToValue('order_date', date('Y-m-d'));
        $converter->nullToValue('order_detail_dead_line', date('Y-m-d'));
        $converter->nullBlankToValue('payout_lot_id', 0);

        // List明細モードから来た場合は order_header_id が指定されていない。order_detail_id から order_header_id を取得する。
        if (is_numeric(@$form['order_detail_id'])) {
            $query = "select order_header_id from order_detail where order_detail_id = '{$form['order_detail_id']}'";
            $orderHeaderId = $gen_db->queryOneValue($query);
            $converter->nullBlankToValue('order_header_id', $orderHeaderId);
        }
    }

    function validate($validator, &$form)
    {
        $validator->blankOrNumeric('order_header_id', _g('order_header_idが正しくありません。'));

        return 'action:Partner_Order_List';        // if error
    }

    // データ取得のための設定
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'order_header_id';
        $this->selectQuery = "
            select
                *
                
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                order_header
            [Where]
        ";

        // データロック対象外
        $query = "select unlock_object_3 from company_master";
        $unlock = $gen_db->queryOneValue($query);
        if ($unlock != "1") {
            // データロックの判断基準となるフィールドを指定
            $form["gen_buyDateLockFieldArray"] = array("order_date", "order_detail_dead_line");
        }
    }

    // 表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Partner_Order_Model";

        $form['gen_pageTitle'] = _g("注文登録");
        $form['gen_entryAction'] = "Partner_Order_Entry";
        $form['gen_listAction'] = "Partner_Order_List";
        $form['gen_onLoad_noEscape'] = "onLoad();";
        $form['gen_pageHelp'] = _g("注文書");

        // これを設定すると、「登録して印刷」ボタンと「帳票を印刷」ボタン（編集モードのみ）が表示される。
        $form['gen_reportArray'] = array(
            'action' => "Partner_Order_Report",
            'param' => "check_[id]",
            'seq' => "order_header_order_header_id_seq",
        );

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        $form['gen_javascript_noEscape'] = "
            // 単価・適用数を保存しておくためのページ変数。数量を変更したときに単価が(Ajaxせずに)即時変更されるようにするため。
            // 1要素1行で、品目が変更されたときに取得・保存される。修正モードの場合は、ページロード時にも取得・保存する。
            var default_order_price = new Array(99);
            var default_order_price_2 = new Array(99);
            var default_order_price_3 = new Array(99);
            var order_price_limit_qty_1 = new Array(99);
            var order_price_limit_qty_2 = new Array(99);

            // 明細行の挿入/削除/入れ替えが発生したとき、この名前の関数が存在するとコールバックされる
            function gen_editListMoveLineCallBack(lineNo, newNo, isSwap) {
                var tmp;
                if (isSwap) {
                    tmp = default_order_price[lineNo]; default_order_price[lineNo] = default_order_price[newNo]; default_order_price[newNo] = tmp; 
                    tmp = default_order_price_2[lineNo]; default_order_price_2[lineNo] = default_order_price_2[newNo]; default_order_price_2[newNo] = tmp; 
                    tmp = default_order_price_3[lineNo]; default_order_price_3[lineNo] = default_order_price_3[newNo]; default_order_price_3[newNo] = tmp; 
                    tmp = order_price_limit_qty_1[lineNo]; order_price_limit_qty_1[lineNo] = order_price_limit_qty_1[newNo]; order_price_limit_qty_1[newNo] = tmp; 
                    tmp = order_price_limit_qty_2[lineNo]; order_price_limit_qty_2[lineNo] = order_price_limit_qty_2[newNo]; order_price_limit_qty_2[newNo] = tmp;
                } else {
                    default_order_price[newNo] = default_order_price[lineNo];
                    default_order_price_2[newNo] = default_order_price_2[lineNo];
                    default_order_price_3[newNo] = default_order_price_3[lineNo];
                    order_price_limit_qty_1[newNo] = order_price_limit_qty_1[lineNo]; 
                    order_price_limit_qty_2[newNo] = order_price_limit_qty_2[lineNo];
                } 
            }

            // ページロード
            function onLoad() {
                " . ($isReload ? "
                // リロード（新規登録モードでのバリデーションエラーによる差し戻し、および項目変更・並べ替え・リセット・明細行数変更）のときのみ、
                // 各行の品目名や引当可能数等を再取得する。
                // ちなみに通常時（リロード以外の場合）はSQLによってそれらが取得されるのでこれを実行する必要はない。
                // また、EditListに関しては修正モードのエラー時にもSQL取得される。
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    if ($('#item_id_'+lineNo+'_show').val()!='') {
                        onItemIdChange(lineNo, false);
                    }
                });
                // 修正モード：
                //   各行の単価・適用数を取得してページ変数に保存する。
                //   この場合も上のようにonItemChange()を行ってもいいのだが、そうすると1行ごとにAjaxが実行されるためパフォーマンスが心配。
                " : (is_numeric(@$form['order_header_id']) ? "getPriceList();" : "")) . "

                var elm = $('#item_id_list');
                if (elm.length==0) {
                    $('#partner_id').after('<input type=hidden id=\"item_id_list\" value=\"\">');
                }
                calcTotalAmount();
            }

            // 修正モードでのページロード時に、各行の単価・適用数を取得してページ変数に保存する。
            function getPriceList() {
                ouid = $('#partner_id').val();
                if (!gen.util.isNumeric(ouid)) return;

                ids = '';
                idToLine = new Array();
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    id = $('#item_id_'+lineNo).val();
                    if (id !='') {
                        if (ids!='') ids += ',';
                        ids += id;
                        if (idToLine[id] != '') idToLine[id] += ',';
                        idToLine[id] += lineNo;
                    }
                });
                if (ids == '') 
                    return;

                gen.ajax.connect('Partner_Order_AjaxItemPrice', {ids:ids, orderUserId : ouid}, 
                    function(j) {
                        $.each(j, function(itemId, val) {
                            lineArr = idToLine[itemId].split(',');
                            $.each(lineArr, function(i, line) {
                                // ページ変数の書き換え
                                default_order_price[line] = j[itemId]['default_order_price'];
                                default_order_price_2[line] = j[itemId]['default_order_price_2'];
                                default_order_price_3[line] = j[itemId]['default_order_price_3'];
                                order_price_limit_qty_1[line] = j[itemId]['order_price_limit_qty_1'];
                                order_price_limit_qty_2[line] = j[itemId]['order_price_limit_qty_2'];
                                // マスタ単価チップヘルプの更新
                                showMasterPriceChipHelp(line, j[itemId]['currency_name'], j[itemId]['default_order_price'], j[itemId]['default_order_price_2'], j[itemId]['default_order_price_3'], j[itemId]['order_price_limit_qty_1'], j[itemId]['order_price_limit_qty_2']);
                            });
                        });
                    });
            }

            // 発注先DDクリックイベント（ドロップダウンオープンより先に実行）
            // 現在の品目IDのリストを作ってhiddenとして埋め込む
            function onPartnerIdClick() {
                var str = '';
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    val = $('#item_id_'+lineNo).val();
                    if (val!='') {
                        if (str!='') str += ',';
                        str += val;
                    }
                });

                $('#item_id_list').val(str);
            }

            // 発注先DD変更イベント
            function onPartnerIdChange() {
                // 品目が入力されているすべての行の単価等をアップデート
                isFirst = true;
                priceUpdate = true;
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    val = $('#item_id_'+lineNo).val();
                    if (val!='') {
                        if (isFirst) {
                            priceUpdate = window.confirm('" . _g("発注先を変更すると、品目の単価や注文納期が変更される場合があります。それらを上書きしてもよろしいですか？　（上書きしてもよい場合は[OK]、現在入力されている単価・注文納期を維持する場合は[キャンセル]）") . "');
                            isFirst = false;
                        }
                        onItemIdChange(lineNo, priceUpdate);
                    }
                });
            }

            // 品目変更イベント
            function onItemIdChange(lineNo, priceUpdate) {
                var p = {
                    itemId : $('#item_id_'+lineNo).val(),
                    orderUserId : $('#partner_id').val()
                };
                // 編集モードでは、品目が「なし」に変更されたときに行削除フラグ(hidden)を追加する。
                //  品目「なし」の行は登録処理がスキップされるので、編集モードの場合はそのままだと元の内容が残ってしまう。
                //  上記フラグを追加しておけば、行が削除される。
                " . (is_numeric(@$form['order_header_id']) ? "
                    var id = $('#order_detail_id_'+lineNo).val();
                    if (gen.util.isNumeric(id)) {
                        var delFlagName = 'gen_delete_flag_' + id;
                        if (gen.util.isNumeric(p.itemId)) {
                            $('[name=' + delFlagName + ']').remove();
                        } else {
                            $('#form1').append(\"<input type='hidden' name='\" + delFlagName + \"' value='\"+ id +\"'>\");
                        }
                     }
                " : "") . "
                if (!gen.util.isNumeric(p.itemId)) {
                    gen.edit.editListClearLine('list1',lineNo,true);
                    return;
                }
                gen.edit.submitDisabled();
                gen.ajax.connect('Partner_Order_AjaxItemParam', p, 
                    function(j) {
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                        if (j=='') {
                            alert('" . _g("品目コードがマスタに登録されていないか、この発注先に関連づけられていません。") . "');
                            $('#item_id_'+lineNo+'_show').select();
                            $('#item_name_'+lineNo).html('');   // 前回表示されていた名称を消去
                            return;
                        }
                        // 品目名
                        $('#item_name_'+lineNo).text(j.item_name);

                        // 発注数デフォルト（発注数未入力のときのみ。最低ロットをデフォルトとする）
                        var qtyElm = $('#order_detail_quantity_'+lineNo);
                        if (!gen.util.isNumeric(qtyElm.val())) {
                            if (!gen.util.isNumeric(j.default_lot_unit) || j.default_lot_unit <= 0) {
                                qtyElm.val(1);
                            } else {
                                qtyElm.val(j.default_lot_unit);
                            }
                            // ドロップダウンでの選択時点で次エレメントである数量ボックスにフォーカスがあたるが、
                            // 数値が選択状態になっていないと入力しにくい
                            qtyElm.select();
                        }

                        // 単価デフォルト・適用数をページ変数に保存
                        default_order_price[lineNo] = j.default_order_price;
                        default_order_price_2[lineNo] = j.default_order_price_2 ;
                        default_order_price_3[lineNo] = j.default_order_price_3;
                        order_price_limit_qty_1[lineNo] = j.order_price_limit_qty_1;
                        order_price_limit_qty_2[lineNo] = j.order_price_limit_qty_2;

                        // 単価・注文納期デフォルト
                        if (priceUpdate) {    // バリデーションエラーや行数変更リロードの場合は上書きしない。発注先変更で変更拒否されているときも。
                            // 単位・手配単位倍数
                            $('#order_measure_'+lineNo).val(j.order_measure);
                            $('#multiple_of_order_measure_'+lineNo).val(j.multiple_of_order_measure);
                           
                            // 単価デフォルト
                            setDefaultPrice(lineNo);
                            // 注文納期デフォルト（LT+安全LT 後の日付。休日考慮）
                            var dlElm =  $('#order_detail_dead_line_'+lineNo);
                            if (dlElm.val()=='') {
                                dlElm.val(j.default_dead_line);
                            }
                        }

                        // 通貨
                        $('#currency_name_'+lineNo).text(j.currency_name);

                        // マスタ単価チップヘルプ
                        showMasterPriceChipHelp(lineNo, j.currency_name, j.default_order_price, j.default_order_price_2, j.default_order_price_3, j.order_price_limit_qty_1, j.order_price_limit_qty_2);

                        // 有効在庫数
                        $('#stock_quantity_'+lineNo).text(gen.util.addFigure(j.available_stock_quantity));
                        // 最低/手配ロット
                        var lu1 = gen.util.isNumeric(j.default_lot_unit) ? gen.util.addFigure(j.default_lot_unit) : '-';
                        var lu2 = gen.util.isNumeric(j.default_lot_unit_2) ? gen.util.addFigure(j.default_lot_unit_2) : '-';
                        $('#lot_unit_'+lineNo).text(lu1 + ' / ' + lu2);

                        // 課税区分
                        $('#tax_class_'+lineNo).text(j.tax_class);

                        // MRP/ロット品目なら製番をロック
                        var s1 = $('#seiban_'+lineNo+'_show');
                        var s2 = $('#seiban_'+lineNo+'_dropdown');
                        var s3 = $('#seiban_'+lineNo);
// *** まだ                        
                        if (j.order_class == 0 && s1.attr('lock')!='true') {
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

                        // 再計算
                        calcShowParam(lineNo);
                        calcTotalAmount();
                    });
            }

            // マスタ単価チップヘルプの表示
            function showMasterPriceChipHelp(lineNo, currencyName, price1, price2, price3, limit1, limit2) {
                var limitStr1 = (limit1==null ? '―' : gen.util.escape(limit1) + ' " . _g('以下') . "');
                var limitStr2 = (limit2==null ? '―' : gen.util.escape(limit2) + ' " . _g('以下') . "');
                // チップヘルプの中にtableやdivを書くと正しく表示されない（IEのみ）
                var msg =
                 '" . _g('購入単価') . "1" . _g("：") . "　'+ gen.util.escape(currencyName) + gen.util.escape(price1) + '　（" . _g('適用数') . " '+ limitStr1 +'）<br>' +
                 '" . _g('購入単価') . "2" . _g("：") . "　'+ gen.util.escape(currencyName) + gen.util.escape(price2) + '　（" . _g('適用数') . " '+ limitStr2 +'）<br>' +
                 '" . _g('購入単価') . "3" . _g("：") . "　'+ gen.util.escape(currencyName) + gen.util.escape(price3);
                 $('.chiphelp_master_price_'+lineNo).html(msg);
            }

            // 数量変更イベント
            function onQtyChange(lineNo) {
                setDefaultPrice(lineNo);
                calcShowParam(lineNo);
                calcTotalAmount();
            }

            // デフォルト単価の表示
            // （品目・数量が変わったときに実行）
            function setDefaultPrice(lineNo) {
                // xxx[lineNo] はページ変数。品目が変わったときに保存してある。
                if (gen.util.isNumeric($('#order_detail_quantity_'+lineNo).val())) {
                    var price = default_order_price[lineNo];
                    var qty = $('#order_detail_quantity_'+lineNo).val();
                    if (gen.util.isNumeric(qty)) {
                        // 単価適用数により単価を決定。単価適用数がNullの場合、その単価を使用することに注意
                        if (parseFloat(qty) > parseFloat(nnz(order_price_limit_qty_1[lineNo])) && order_price_limit_qty_1[lineNo] != null) {
                            price = nnz(default_order_price_2[lineNo]);
                            if (parseFloat(qty) > parseFloat(nnz(order_price_limit_qty_2[lineNo])) && order_price_limit_qty_2[lineNo] != null) {
                                price = nnz(default_order_price_3[lineNo]);
                            }
                        }
                    }
                    $('#item_price_'+lineNo+'_show').val(price);
                    $('#item_price_'+lineNo).val(price);
                }
            }

            // 注文書表示数量・表示単価・金額・合計金額の再計算
            // （数量・単価・手配単位倍数が変わったときに実行）
            function calcShowParam(lineNo) {
               var qty = $('#order_detail_quantity_'+lineNo).val();
               var price = $('#item_price_'+lineNo).val();
               var mul = $('#multiple_of_order_measure_'+lineNo).val();
               if (!gen.util.isNumeric(mul) || mul==0) mul = 1;
               var showQtyElm = $('#show_quantity_'+lineNo);
               var showPriceElm = $('#show_price_'+lineNo);
               var amountElm = $('#amount_'+lineNo);
               // 表示数量
               if (gen.util.isNumeric(qty)) {
                   showQtyElm.html(gen.util.addFigure(gen.util.decCalc(qty, mul, '/')));
                   if (gen.util.isNumeric(price)) {
                        // 金額
                        amountElm.html(gen.util.addFigure(gen.util.decCalc(qty, price, '*')));
                   }
               } else {
                   showQtyElm.html('');
               }
               // 表示単価
               if (gen.util.isNumeric(price)) {
                   showPriceElm.html(gen.util.addFigure(gen.util.decCalc(price, mul, '*')));
               } else {
                   showPriceElm.html('');
               }
            }

            // 合計金額の計算
            function calcTotalAmount() {
               var total = 0;
               $('[id^=amount_]').each(function(){
                    var amount = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
               });
               $('#total_amount').val(gen.util.addFigure(total));
            }

            // 従業員を選んだらその所属部門を部門セレクタに設定
            function onWorkerChange() {
                var p = { worker_id : $('#worker_id').val() };
                if (p.worker_id == 'null' || p.worker_id =='') return;
                gen.ajax.connect('Partner_Order_AjaxWorkerParam', p, 
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }

            // Not Num to Zero
            function nnz(val) {
                return (gen.util.isNumeric(val) ? val : 0);
            }
        ";

        // コピーモードでは注文書番号を消す
        if (isset($form['gen_record_copy']))
            unset($form['order_id_for_user']);

        $form['gen_message_noEscape'] = "";

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $existAccepted = false;
        if (isset($form['order_header_id']) && !isset($form['gen_record_copy'])) {
            $existAccepted = Logic_Accepted::hasAcceptedByOrderHeaderId($form['order_header_id']);
            if ($existAccepted) {
                $form['gen_message_noEscape'] = "<font color='blue'>" . _g("このオーダーは受入済みです（もしくは一部受入済みです）。ヘッダ項目、および受入済み行は修正できません。") . "</font>";
            }
        }

        // temp_stock に本日時点の有効在庫数（現在庫リストにおける本日付の有効在庫数と一致）を取得
        //  ・製番品目については、フリー製番在庫のみ。
        //  ・全ロケ・ロット合計。Pロケは排除。
        //  ・引当分は将来分も含めて差し引く。
        if (isset($form['order_header_id'])) {
            $query = "select item_id from order_detail where order_header_id = '{$form['order_header_id']}'";
            $itemArr = $gen_db->getArray($query);
            Logic_Stock::createTempStockTable(date('Y-m-d'), $itemArr[0], '', "sum", "sum", true, false, true);
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('注文書番号'),
                'type' => 'textbox',
                'name' => 'order_id_for_user',
                'value' => @$form['order_id_for_user'],
                'size' => '11',
                'ime' => 'off',
                'readonly' => $existAccepted,
                'hidePin' => true,
                'helpText_noEscape' => _g('自動採番されますので、指定する必要はありません。')
            ),
            array(
                'label' => _g('発注日'),
                'type' => 'calendar',
                'name' => 'order_date',
                'value' => @$form['order_date'],
                'size' => '8',
                'require' => true,
                'readonly' => $existAccepted,
            ),
            array(
                'label' => _g('発注先'),
                'type' => 'dropdown',
                'name' => 'partner_id',
                'value' => @$form['partner_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'partner_for_order',
                // 現在入力されている品目のリスト(hidden)。onClick時（ドロップダウンが開く前）に作成している。
                'dropdownParam' => '[item_id_list]',
                'autoCompleteCategory' => 'customer_partner',
                'onClick' => 'onPartnerIdClick()',
                'onChange_noEscape' => 'onPartnerIdChange()',
                'require' => true,
                'readonly' => $existAccepted,
                'helpText_noEscape' => _g('注文書の発行先を指定します。取引先マスタに「サプライヤー」として登録されている取引先のみ指定できます。'),
            ),
            array(
                'label' => _g('部門(自社)'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'selected' => @$form['section_id'],
                'readonly' => $existAccepted,
            ),
            array(
                'label' => _g('発送先'),
                'type' => 'dropdown',
                'name' => 'delivery_partner_id',
                'value' => @$form['delivery_partner_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'delivery_partner',
                'autoCompleteCategory' => 'customer_partner_shipping',
                'readonly' => $existAccepted,
                'helpText_noEscape' => _g('注文書に記載されます。')
            ),
            array(
                'label' => _g('注文備考'),
                'type' => 'textbox',
                'name' => 'remarks_header',
                'value' => @$form['remarks_header'],
                'size' => '20',
                'readonly' => $existAccepted,
            ),
            array(
                'label' => _g('担当者(自社)'),
                'type' => 'dropdown',
                'dropdownCategory' => 'worker',
                'name' => 'worker_id',
                'value' => @$form['worker_id'],
                'size' => '11',
                'subSize' => '20',
                'onChange_noEscape' => 'onWorkerChange()',
                'readonly' => $existAccepted,
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'textbox',
                'name' => 'total_amount',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('同時に受入を登録'),
                'type' => 'checkbox',
                'name' => 'accepted_regist',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'readonly' => $existAccepted,
                'value' => @$form['accepted_regist'],
                'helpText_noEscape' => _g('このチェックをオンにすると、注文と同時に受入も登録されます。')
            ),

            // ********** List **********
            array(
                'type' => "list",
                'listId' => 'list1', // リストのID。ページ内に複数リストがある場合、ユニークになるようにすること
                'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
                'readonlyCondition' => (isset($form['gen_record_copy']) ? "" : "'[accepted_exist]'=='1'"), // list内だけで使えるプロパティ
                // アラーム色付けはさほど重要な情報ではない割に目立つので、コメントアウトとしてみた。
                //'rowColorCondition'=> array(
                //    "#ffc0cb" => "'[alarm_flag]'=='t'",             // アラーム（間に合わない品目）（ピンク）
                //    ),
                'keyColumn' => 'order_detail_id', // 明細行のキーとなるカラム
                'query' => // Listデータを取得するSQL。 EditBaseで実行され、結果配列が'data'という名前で格納される
                isset($form['order_header_id']) ? "
                        select
                            order_detail.*
                            ,received_number
                            ,order_detail_quantity / cast(coalesce(order_detail.multiple_of_order_measure,1) as numeric) as show_quantity
                            ,(case when foreign_currency_id is null then item_price else foreign_currency_item_price end) as item_price
                            ,(case when foreign_currency_id is null then item_price else foreign_currency_item_price end) * cast(coalesce(order_detail.multiple_of_order_measure,1) as numeric) as show_price
                            ,(case when foreign_currency_id is null then coalesce(order_amount, item_price * order_detail_quantity) else coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) end) as amount
                            ,case when t_acc.odi is null then 0 else 1 end as accepted_exist
                            " . (isset($form['gen_record_copy']) ? ",null as order_no" : "") . "
                            " . (isset($form['gen_record_copy']) ? ",''" : ",case when order_detail_completed then '" . _g("完") . "' else
                               '" . _g("未(残") . " ' || (COALESCE(order_detail_quantity,0) - COALESCE(order_detail.accepted_quantity,0)) || ')' end") . " as completed
                            ,coalesce(cast(default_lot_unit as text),'-') || ' / ' || coalesce(cast(default_lot_unit_2 as text),'-') as lot_unit
                            ,coalesce(available_stock_quantity,0) as stock_quantity
                            ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                            ,order_class
                            ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

                        from
                            order_detail
                            left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2 and order_detail.seiban <> ''
                            left join (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                            left join (select item_id as iid, measure, order_class from item_master) as t_item on order_detail.item_id = t_item.iid
                            left join (select order_header_id as oid, partner_id from order_header) as t_order_header on order_detail.order_header_id = t_order_header.oid
                            left join (select item_id as iid2, order_user_id as oui, default_lot_unit, default_lot_unit_2 from item_order_master) as t_item_order
                                on order_detail.item_id = t_item_order.iid2
                                and t_order_header.partner_id = t_item_order.oui
                            left join (select currency_id as curid, currency_name from currency_master) as t_currency on order_detail.foreign_currency_id = t_currency.curid
                            left join temp_stock on order_detail.item_id = temp_stock.item_id

                        where
                            order_header_id = '{$form['order_header_id']}'
                        order by
                            line_no
                        " : "",
                'controls' => array(
                    array(
                        'label' => _g('ｵｰﾀﾞｰ番号'),
                        'type' => 'div',
                        'name' => 'order_no',
                        'align' => 'center',
                        'size' => '8',
                        'helpText_noEscape' => _g('オーダー1行ごとに設定されるユニークな番号です。登録の際に自動採番されます（任意の番号を指定することはできません）。')
                    ),
                    array(
                        'type' => 'literal',
                    ),
                    array(
                        'label' => _g('品目コード'),
                        'type' => 'dropdown',
                        'name' => 'item_id',
                        'size' => '12',
                        'dropdownCategory' => 'item_order_partner_nosubtext',
                        'autoCompleteCategory' => 'item_order_partner',
                        'dropdownParam' => '[partner_id]',
                        'onChange_noEscape' => 'onItemIdChange([gen_line_no], true)',
                        'require' => true,
                        'placeholder' => _g('品目コード'),
                    ),
                    array(
                        'label' => _g('品目名'),
                        'type' => 'div',
                        'name' => 'item_name',
                        'size' => '15',
                    ),
                    array(
                        'label' => _g('数量'),
                        'type' => 'textbox',
                        'name' => 'order_detail_quantity',
                        'ime' => 'off',
                        'size' => '5',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => "onQtyChange([gen_line_no]);",
                        'helpText_noEscape' => _g('発注単位ではなく、管理単位で指定してください。たとえば発注がキログラム単位、在庫管理単位がグラムの場合、ここの項目はグラム数を指定します。品目を選択した際、この品目の最低ロット数がデフォルト発注数として設定されます（発注数が空欄の場合）。'),
                    ),
                    array(
                        'label' => _g('表示数量'),
                        'type' => 'div',
                        'name' => 'show_quantity',
                        'size' => '5',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('実際に注文書に表示される数量。「数量 ÷ 手配単位倍数」です。'),
                    ),
                    array(
                        'label' => _g('倍数'),
                        'type' => 'textbox',
                        'name' => 'multiple_of_order_measure',
                        'size' => '4',
                        'style' => "text-align:right",
                        'onChange_noEscape' => "calcShowParam([gen_line_no]);calcTotalAmount();",
                        'helpText_noEscape' => _g('手配単位倍数です。在庫管理単位と発注単位が異なる場合、その倍率を指定します。例えばグラム管理している品目をキログラム単位で発注する場合、1000と登録します。省略すると1になります。')
                    ),
                    array(
                        'label' => _g('単位'),
                        'type' => 'textbox',
                        'name' => 'order_measure',
                        'style' => "text-align:right",
                        'size' => '4',
                        'helpText_noEscape' => _g('「個」「kg」「m」など、発注の単位を注文書に記載したい場合、この項目を登録します。'),
                    ),
                    array(// 単価履歴参照機能のため拡張DD化
                        'label' => _g('発注単価'),
                        'type' => 'dropdown',
                        'name' => 'item_price',
                        'size' => '5',
                        'dropdownCategory' => 'order_price',
                        'dropdownParam' => "[partner_id];[item_id_[gen_line_no]]",
                        'dropdownShowCondition_noEscape' => "!isNaN([item_id_[gen_line_no]])",
                        'dropdownShowConditionAlert' => _g("先に品目を指定してください。"),
                        'require' => true,
                        'style' => "text-align:right",
                        'onChange_noEscape' => "calcShowParam([gen_line_no]);calcTotalAmount();",
                        'helpText_noEscape' => _g('発注単位あたりの単価ではなく、管理単位あたりの単価で指定してください。'),
                    ),
                    array(
                        'label' => _g('表示単価'),
                        'type' => 'div',
                        'name' => 'show_price',
                        'size' => '7.7',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('実際に注文書に表示される単価。「発注単価 × 手配単位倍数」です。'),
                    ),
                    array(
                        'label' => _g('取引通貨'),
                        'type' => 'div',
                        'name' => 'currency_name',
                        'size' => '3',
                        'style' => "text-align:center",
                    ),
                    array(// マスタ単価表示アイコン
                        'label' => _g('ﾏｽﾀ単価'),
                        'type' => 'div',
                        'name' => 'master_price',
                        'style' => "text-align:center",
                        'literal_noEscape' => "<a class='gen_chiphelp' id='chiphelp_master_price_[gen_line_no]' href='#' rel='p.chiphelp_master_price_[gen_line_no]' title='" . _g('品目マスタ単価') . "'><img src='img/currency-yen.png' border='0'></a>" .
                        "<p class='chiphelp_master_price_[gen_line_no]'>" . _g("品目を選択（変更）してからこのアイコンをマウスオーバーすると、品目マスタの単価情報が表示されます。") . "</p>",
                        'size' => '3',
                    ),
                    array(
                        'label' => _g('金額'),
                        'type' => 'div',
                        'name' => 'amount',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('数量 × 発注単価 で計算されます。'),
                    ),
                    array(
                        'label' => _g('課税区分'),
                        'type' => 'div',
                        'name' => 'tax_class',
                        'size' => '6',
                        'style' => "text-align:center",
                    ),
                    array(
                        'label' => _g('注文納期'),
                        'type' => 'calendar',
                        'name' => 'order_detail_dead_line',
                        'size' => '7',
                        'require' => true,
                        'isCalendar' => true,
                        'hideSubButton' => true,
                        'helpText_noEscape' => _g("注文納期を指定します。") . "<br>"
                            . _g("品目を選択した際、この品目のリードタイム（安全リードタイムを含む）とカレンダーマスタを考慮した注文納期が自動的に設定されます。（注文納期が空欄の場合のみ）"),
                    ),
                    array(
                        'label' => _g('受入状況'),
                        'type' => 'div',
                        'name' => 'completed',
                        'style' => 'text-align:center',
                        'size' => '8',
                    ),
                    array(
                        'label' => _g('有効在庫数'), // 本日時点の有効在庫。取得条件はAjaxItemParam参照
                        'type' => 'div',
                        'name' => 'stock_quantity',
                        'size' => '7',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('本日時点の有効在庫数です。サプライヤーロケ分は含みません。'),
                    ),
                    array(
                        'label' => _g('最低/手配ﾛｯﾄ'),
                        'type' => 'div',
                        'name' => 'lot_unit',
                        'size' => '7',
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g("品目マスタの「最低ロット数」「手配ロット数」が表示されます。「0」はまるめないことを表します。") . "<br>"
                            . _g("ここでは参考用に表示されるだけで、この値に基づいて自動的に発注数が調整されることはありません。"),
                    ),
                    array(
                        'label' => _g('注文明細備考'),
                        'type' => 'text',
                        'name' => 'remarks',
                        'size' => '17',
                        'width' => '120',
                        // 以前は横方向を400にしていたが、金額欄が見えないという苦情があったため短くした
                        // ag.cgi?page=ProjectDocView&pid=1574&did=224141
                        'focusZoom' => array('left', 355, 25),     // フォーカス時のサイズ拡張(方向,width,height)
                    ),
                    // 10iから、オーダーに手動で製番を付与したり、振り替えたりできるようになった（製番品目のみ）。
                    // 構成の一時的な変更（代替品の使用など）があった場合、09iでは、構成を変更して再度所要量計算
                    // するか、製番なしでオーダーして受入後に製番引当するしかなかった。これらはいずれも不便で面倒だった。
                    // 10iでこの機能が実装されたことにより、受注とオーダーのひもつけを維持しながら柔軟に構成の入れ替えが
                    // 行えるようになった。
                    // 製番なしのオーダーに製番をつけるだけでなく、製番オーダーを別製番に振り替えることもできて
                    // しまうことに注意が必要。後者は禁止すべきかとも思ったが、特急オーダーのための振り替えなどに便利な
                    // 場合もありそうなので、そのままにした。気をつけて使ってもらう必要がある。
                    // 15iでは、完了済の製番も選択できるようになった。ag.cgi?page=ProjectDocView&pid=1574&did=217672
                    array(
                        'label' => _g('製番'),
                        'type' => 'dropdown',
                        'name' => 'seiban',
                        'size' => '9',
                        'dropdownCategory' => 'received_seiban',
                        'dropdownShowCondition_noEscape' => "!isNaN([item_id_[gen_line_no]]) && !$('#seiban_[gen_line_no]_show').attr('disabled')",
                        'dropdownShowConditionAlert' => _g("製番を指定できるのは、製番品目が指定されている場合のみです。"),
                        'readonlyCondition' => "'[order_class]'=='1' || '[order_class]'=='2'",
                        'noWrite' => true,
                        'tabindex' => -1,
                        'helpText_noEscape' => '<b>' . _g('MRP/ロット品目') . '：</b>' . _g('製番はつきません。')
                            . '<br><br><b>' . _g('製番品目') . '：</b>' . _g('このオーダーが所要量計算の結果として発行されたものであれば、もとになった受注と同じ製番が自動的につきます。') . '<br>'
                            . _g('ドロップダウンで任意の受注製番を指定して、注文と受注を結びつけることもできます。ドロップダウンで表示されるのは、製番品目の確定受注だけです。'),
                    ),
                ),
            ),
        );
    }

}
