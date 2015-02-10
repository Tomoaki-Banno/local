<?php

require_once("Model.class.php");

class Manufacturing_Received_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        global $gen_db;

        $converter->nullBlankToValue('received_date', date("Y-m-d"));

         // List明細モードから来た時のための処理
         if (!isset($form['received_header_id']) && isset($form['received_detail_id'])) {
            if (isset($form['gen_multi_edit'])) {
                $keyArr = explode(',', $form['received_detail_id']);
                $hidArr = array();
                $hids = "";
                foreach ($keyArr as $id) {
                    $hid = $gen_db->queryOneValue("select received_header_id from received_detail where received_detail_id = '{$id}'");
                    if (!in_array($hid, $hidArr)) {
                        if ($hids != "") $hids .= ",";
                        $hids .= $hid;
                        $hidArr[] = $hid;
                     }
                }
                $form['received_header_id'] = $hids;
            } else {
                $form['received_header_id'] = $gen_db->queryOneValue("select received_header_id from received_detail where received_detail_id = '" . $form['received_detail_id'] . "'");
            }
         }
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'received_header_id';

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                received_header.*
                ,customer_master.price_percent
                ,customer_master.remarks as customer_remarks
                ,customer_master.remarks_2 as customer_remarks_2
                ,customer_master.remarks_3 as customer_remarks_3
                ,customer_master.remarks_4 as customer_remarks_4
                ,customer_master.remarks_5 as customer_remarks_5
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

                ,coalesce(received_header.record_update_date, received_header.record_create_date) as gen_last_update
                ,coalesce(received_header.record_updater, received_header.record_creator) as gen_last_updater
            from
                received_header
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            [Where]
        ";

        // データロック対象外
        $query = "select unlock_object_1 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        if ($unlock != "1") {
            // データロックの判断基準となるフィールドを指定
            $form["gen_salesDateLockFieldArray"] = array("received_date");
        }
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Manufacturing_Received_Model";

        $form['gen_pageTitle'] = _g("受注登録");
        $form['gen_entryAction'] = "Manufacturing_Received_Entry";
        $form['gen_listAction'] = "Manufacturing_Received_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntry()";
        $form['gen_pageHelp'] = _g("受注登録");

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        $form['gen_javascript_noEscape'] = "
            var isPageDataModified = false;	// データ（品目）が変更されたかどうかのフラグ

            // ページロード
            function onLoad() {
                " . ($isReload ? "
                // リロード（新規登録モードでのバリデーションエラーによる差し戻し、および項目変更・並べ替え・リセット・明細行数変更）のときのみ、
                // 各行の品目名や引当可能数等を再取得する。
                // ちなみに通常時はSQLによってそれらが取得されるのでこれを実行する必要はない。
                // また、EditListに関しては修正モードのエラー時にもSQL取得される。
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    onItemIdChange(lineNo, true);
                });
                " : "") . "
                " . (isset($form['gen_estimateCopy']) ? "copyFromEstimate(true);" : "") . "

                calcTotalAmount();
                onCustomerIdChange(false);
            }

            // 担当者を選んだらその所属部門を部門のデフォルトとして設定
            function onWorkerChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Manufacturing_Received_AjaxWorkerParam', {worker_id : wid},
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }

            // 得意先が変わったらAjaxで掛率と取引通貨、売掛残高、備考を取得する。
            // また、priceUpdateがtrueの場合（ページロード以外の場合）は受注単価を更新する。
            // 品目が選択されているすべての明細行に対してAjax処理がおこなわれるので重い処理だが、
            // 通常は先に得意先を選択してから各品目を入力していくので、それほど問題は大きくないと思う。
            function onCustomerIdChange(priceUpdate) {
                if (!gen.util.isNumeric(customerId = $('#customer_id').val())) {
                    $('#currency_name').val('');
                    return;
                }

                gen.ajax.connect('Manufacturing_Received_AjaxCustomerParam', {customerId : customerId" . (isset($form['received_header_id']) ? ",receivedHeaderId : " . h($form['received_header_id']) : "") . "},
                    function(j) {
                        if (gen.util.isNumeric(j.price_percent)) $('#price_percent').val(j.price_percent);
                        $('#currency_name').val(j.currency_name);
                        $('#receivable_balance').val(gen.util.addFigure(j.receivable_balance));
                        $('#credit_line').val(gen.util.addFigure(j.credit_line));
                        $('#customer_remarks').val(j.remarks);
                        $('#customer_remarks_2').val(j.remarks_2);
                        $('#customer_remarks_3').val(j.remarks_3);
                        $('#customer_remarks_4').val(j.remarks_4);
                        $('#customer_remarks_5').val(j.remarks_5);
                    });

                // 単価の再計算
                // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                if (priceUpdate) {
                    isFirst = true;
                    $('[id^=line_no]').each(function(){
                        lineNo = this.innerHTML;
                        if (isFirst && gen.util.isNumeric($('#item_id_'+lineNo).val())) {
                            if (!window.confirm('" . _g("得意先を変更すると、受注単価が変更される場合があります。受注単価を上書きしてもよろしいですか？　（上書きしてもよい場合は[OK]、現在入力されている受注単価を維持する場合は[キャンセル]）") . "')) {
                                return false;   // break
                            }
                            isFirst = false;
                        }
                        updatePrice(lineNo,false);
                    });
                }
            }

            // 品目が変わったらAjaxで品目情報や各種デフォルト値を取得して表示する。
            function onItemIdChange(lineNo, isReloadInit) {
                var id = $('#received_detail_id_'+lineNo).val();
                var itemId = $('#item_id_'+lineNo).val();

                // 編集モードでは、品目が「なし」に変更されたときに行削除フラグ(hidden)を追加する。
                //  品目「なし」の行は登録処理がスキップされるので、編集モードの場合はそのままだと元の内容が残ってしまう。
                //  上記フラグを追加しておけば、行が削除される。
                " . (is_numeric(@$form['received_header_id']) ? "
                    if (gen.util.isNumeric(id)) {
                        var delFlagName = 'gen_delete_flag_' + id;
                        if (gen.util.isNumeric(itemId) || itemId == '[multi]') {
                            $('[name=' + delFlagName + ']').remove();
                        } else {
                            $('#form1').append(\"<input type='hidden' name='\" + delFlagName + \"' value='\"+ id +\"'>\");
                        }
                     }
                " : "") . "

                if (!gen.util.isNumeric(itemId)) {
                    if (itemId != '[multi]')
                        gen.edit.editListClearLine('list1',lineNo,true);
                    return;
                }

                if (!isReloadInit) isPageDataModified = true;

                var p = {itemId : itemId, customerId : $('#customer_id').val(), qty : gen.util.trim($('#received_quantity_'+lineNo).val())};
                if (id != undefined && id != '') {
                    p.received_detail_id = id;
                }

                gen.ajax.connect('Manufacturing_Received_AjaxItemParam', p,
                    function(j) {
                        $('#item_name_'+lineNo).text(j.item_name);
                        var price = gen_round(j.product_price);
                        var elm = $('#product_price_'+lineNo+'_show');
                        if (!isReloadInit || elm.val() == '') {   // エラー差し戻しや行数変更の際、入力値が上書きされてしまわないように
                            elm.val(price);
                            $('#product_price_'+lineNo).val(price);
                        }
                        $('#default_selling_price_'+lineNo).text(gen.util.addFigure(j.default_selling_price));
                        $('#tax_class_'+lineNo).text(j.tax_class);
                        elm = $('#sales_base_cost_'+lineNo);
                        if (!isReloadInit || elm.val() == '') {   // エラー差し戻しや行数変更の際、入力値が上書きされてしまわないように
                            elm.val(j.sales_base_cost);
                        }
                        // 引当数は廃止。詳細はeditControlArrayの引当数の箇所のコメントを参照
                        //if (j.order_class=='0' || j.order_class=='2') {	// 製番/ロット品目のときは在庫引当数を無効に
                        //    gen.ui.disabled($('#reserve_quantity_'+lineNo).val(0).css('background-color','#cccccc'));
                        //} else {
                        //    gen.ui.enabled($('#reserve_quantity_'+lineNo).css('background-color','#ffffff'));
                        //}
                        //$('#reserve_quantity_'+lineNo).val('0');
                        $('#reservable_quantity_'+lineNo).text(gen.util.addFigure(gen_round(j.reservable_quantity)));
                        $('#measure_'+lineNo).text(j.measure);
                        onQtyChange(lineNo, false);     // 単価は設定済みなので更新しない
                    });

                // 受注納期デフォルト（1行目は明日、2行目以降は上の行の受注納期をコピー）
                if ($('#dead_line_'+lineNo).val()=='') {
                    var dl = gen.date.getDateStr(gen.date.calcDate(new Date(),1));  // 明日
                    if (lineNo>1) {
                        dl = $('#dead_line_'+(lineNo-1)).val();
                    }
                    $('#dead_line_'+lineNo).val(dl);
                }
            }

            // 受注数変更イベント
            function onQtyChange(lineNo, priceUpdate) {

                // 引当数は廃止。詳細はeditControllArrayの引当数の箇所のコメントを参照
                //var received_detail = $('#received_quantity_'+lineNo).val();
                //if (!gen.util.isNumeric(received_detail)) return;
                //var reserve = $('#reserve_quantity_'+lineNo).val();
                //var reservable = gen.util.delFigure($('#reservable_quantity_'+lineNo).html());
                // 13iにて自動引き当てはコメントアウト
                //if (reserve == '') {    // 引当数がセットされているときは書き換えない。修正モード表示時に上書きされてしまうのを避けるため
                //    if (parseFloat(received_detail) < parseFloat(reservable)) {
                //         $('#reserve_quantity_'+lineNo).val(gen_round(received_detail));
                //    } else {
                //         $('#reserve_quantity_'+lineNo).val(gen_round(reservable));
                //    }
                //}

                if (priceUpdate) {
                    updatePrice(lineNo,true);
                } else {
                    // 合計計算
                    calcAmount(lineNo);
                    calcTotalAmount();
                }
            }

            // 単価を更新
            function updatePrice(lineNo, needConfirm) {
                if (!gen.util.isNumeric(itemId = $('#item_id_'+lineNo).val())) {
                    calcAmount(lineNo);
                    calcTotalAmount();
                    return;
                }
                var p = {itemId : itemId, customerId : $('#customer_id').val(), qty : gen.util.trim($('#received_quantity_'+lineNo).val())};

                gen.ajax.connect('Manufacturing_Received_AjaxItemParam', p,
                    function(j) {
                        var price = gen_round(j.product_price);
                        var elm = $('#product_price_'+lineNo+'_show');
                        if (needConfirm && elm.val() != '' && price != elm.val()) {
                            if (window.confirm('" . _g("受注単価をマスタ単価によって上書きしてもよろしいですか？（上書きしてもよい場合は[OK]、現在入力されている単価を維持する場合は[キャンセル]）") . "')) {
                                $('#product_price_'+lineNo+'_show').val(price);
                                $('#product_price_'+lineNo).val(price);
                            }
                        } else {
                            $('#product_price_'+lineNo+'_show').val(price);
                            $('#product_price_'+lineNo).val(price);
                        }
                        calcAmount(lineNo);
                        calcTotalAmount();
                        $('#product_price_'+lineNo+'_show').focus();    // フォーカスセット
                        $('#product_price_'+lineNo+'_show').select();   // 全選択
                    });
            }

            // 単価変更イベント
            function onPriceChange(lineNo) {
                calcAmount(lineNo);
                calcTotalAmount();
            }

            // 販売原単価変更イベント
            function onBaseCostChange(lineNo) {
                calcAmount(lineNo);
                calcTotalAmount();
            }

            // 受注金額・販売粗利の計算（各明細行）
            function calcAmount(lineNo) {
                var qty = gen.util.trim($('#received_quantity_'+lineNo).val());
                var price = gen.util.trim($('#product_price_'+lineNo).val());
                if (gen.util.isNumeric(qty) && gen.util.isNumeric(price)) {
                    $('#amount_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(qty,price,'*')));
                    var baseCost = gen.util.delFigure($('#sales_base_cost_'+lineNo).val());
                    if (!gen.util.isNumeric(baseCost)) baseCost = 0;
                    $('#sales_gross_margin_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(gen.util.decCalc(price, baseCost, '-'),qty,'*')));
                } else {
                    $('#amount_'+lineNo).html('');
                    $('#sales_gross_margin_'+lineNo).html();
                }
            }

            // 受注合計金額・合計粗利の計算
            function calcTotalAmount() {
               var total = 0;
               $('[id^=amount_]').each(function(){
                    var amount = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
               });
               $('#total_amount').val(gen.util.addFigure(total));

               total = 0;
               $('[id^=sales_gross_margin_]').each(function(){
                    var gm = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(gm)) {
                        total = gen.util.decCalc(total, gm, '+');
                    }
               });
               $('#total_gross_margin').val(gen.util.addFigure(total));
            }

            // 見積コピー
            function copyFromEstimate(isAfterCopy) {
                var id = $('#estimate_header_id').val();
                if (id == '') return;
                if (id == 'null') {
                    $('#estimate_header_id').val('');
                    alert('" . _g("見積番号が正しくありません。") . "');
                    $('#estimate_header_id_show').focus().select();
                    return;
                }
                if (!isAfterCopy && isPageDataModified && !confirm('" . _g("見積の内容をこの伝票にコピーします。現在の入力内容が上書きされます。実行してもよろしいですか？") . "')) return;

                gen.ajax.connect('Manufacturing_Received_AjaxEstimateDetail', {estimate_header_id : id},
                    function(j) {
                      if (j.status == 'success') {
                        // 明細行の数が足りるかどうか確認
                        var count = 0;
                        for(var i in j){
                            if (!isNaN(i)) count++;
                        }
                        if ($('#gen_select_list1').val() < count) {
                            // 行が足りないときは増やす
                            gen.edit.changeNumberOfList('" . $form['gen_editActionWithKey'] . "&gen_estimateCopy','list1',count);
                            return;
                        }

                        // いったんすべての行を消す
                        // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                        $('[id^=line_no]').each(function(){
                            lineNo = this.innerHTML;
                            gen.edit.editListClearLine('list1', lineNo);
                        });

                        // ヘッダを書き込む
                    	$('#customer_id').val(j.customer_id);
                        $('#customer_id_show').val(j.customer_no).focus();    // focus() はプレースホルダを消すため
                        $('#customer_id_sub').val(j.customer_name);
                        onCustomerIdChange(false);
                        $('#remarks_header').val(j.remarks_header);
                        $('#remarks_header_2').val(j.remarks_header_2);
                        $('#remarks_header_3').val(j.remarks_header_3);

                        // 明細を書き込む
                        $.each(j, function(key, value){
                            if (gen.util.isNumeric(key)) {
                                $('#item_id_'+key).val(j['item_id_'+key]);
                                $('#item_id_'+key+'_show').val(j['item_code_'+key]).focus();    // focus() はプレースホルダを消すため
                                $('#item_name_'+key).html(j['item_name_'+key]);
                                $('#received_quantity_'+key).val(j['quantity_'+key]);
                                $('#product_price_'+key).val(j['sale_price_'+key]);
                                $('#product_price_'+key+'_show').val(j['sale_price_'+key]);
                                $('#sales_base_cost_'+key).val(j['base_cost_'+key]);
                                $('#remarks_'+key).val(j['remarks_'+key]);
                                onItemIdChange(key, true);  // 単価が上書きされないよう、第2引数をtrueにする
                            }
                        });
                      } else {
                        alert('" . _g("見積データの取得に失敗しました。") . "');
                      }
                    }
                );
            }

            // 登録前処理
            function beforeEntry() {
                if (".(isset($form['gen_multi_edit']) ? "true" : "false").") {
                    document.forms[0].submit();
                    return;
                }

                // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                var str = '';
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;

                    var item_id = $('#item_id_'+lineNo).val();
                    var deadline = $('#dead_line_'+lineNo).val();
                    if (item_id != '' || deadline != '') {
                        if (item_id == '') {     // 本当はほかの項目もチェックすべき
                            alert(lineNo+'" . _g("行目： 品目を指定してください。") . "'); str = ''; return false;
                        }
                        if (deadline == '') {
                            alert(lineNo+'" . _g("行目： 受注納期を指定してください。") . "'); str = ''; return false;
                        }
                        if (str!='') str += ',';
                        str += lineNo + ':' + deadline;
                    }
                });

                if (str == '') {
                    alert('" . _g("登録するデータがありません。") . "');
                    return;
                }

                // 与信限度額チェック
                var cl = gen.util.delFigure($('#credit_line').val());
                if (gen.util.isNumeric(cl)) {
                    var rb = gen.util.nz(gen.util.delFigure($('#receivable_balance').val()));
                    var ta = gen.util.nz(gen.util.delFigure($('#total_amount').val()));
                    if (gen.util.decCalc(rb, ta, '+') > cl) {
                       if (!window.confirm('" . _g("売掛残高と今回受注額の合計が与信限度額をオーバーしていますが、このまま登録してもよろしいですか？") . "')) {
                            return;
                       }
                    }
                }

                // 休業日チェック
                gen.ajax.connect('Manufacturing_Received_AjaxDeadlineCheck', {deadline : str},
                    function(j) {
                       if (j.result == 'incorrect') {
                           alert(j.lineNo + '" . _g("行目： 受注納期が正しくありません。") . "'); return;
                       } else if (j.result == 'holiday') {
                           if (!window.confirm('" . _g("指定された受注納期に休業日が含まれていますが、このまま登録してもよろしいですか？") . "')) {
                                return;
                           }
                       }
                       document.forms[0].submit();
                    });
            }
        ";

        // コピーモードでは受注番号と見積番号を消す
        if (isset($form['gen_record_copy'])) {
            unset($form['received_number']);
            unset($form['estimate_header_id']);
        }

        // 納品済みの行は内容を変更できないようにする（コピーモードのときは内容を変更できるようにする）
        // => 一部行が納品済みでも他の行を修正できるよう、ここはコメントアウトした。
        //    納品済み行のロックはコントロールレベルで行われる。
//        if (isset($form['received_header_id']) && !isset($form['gen_record_copy'])) {
//            if (Logic_Delivery::hasDeliveryByReceivedHeaderId($form['received_header_id'])) {
//                $form['gen_readonly'] = "true";
//                $form['gen_message_noEscape'] = "<font color='blue'>" . _g("この受注は納品済みであるため、内容を修正できません。") . "</font>";
//            }
//        }
        $existDelivery = false;
        $form['gen_message_noEscape'] = "";
        if (isset($form['received_header_id']) && !isset($form['gen_record_copy'])) {
            $arr = explode(",", $form['received_header_id']);
            $existDelivery = false;
            foreach($arr as $hid) {
                if (Logic_Delivery::hasDeliveryByReceivedHeaderId($hid)) {
                    $existDelivery = true;
                    break;
                }
            }
            if ($existDelivery) {
                $form['gen_message_noEscape'] = "<font color='blue'>" . _g("この受注は納品済みです（もしくは一部納品済みです）。ヘッダ項目、および納品済み行は修正できません。") . "</font>";
            }
        }

        // 前回登録時から構成表マスタが変更されている場合は警告を出す
        if (is_numeric(@$form['received_header_id'])) {
            if (Logic_Received::isModifiedBomForDummy($form['received_header_id'])) {
                if ($form['gen_message_noEscape'] != "") {
                    $form['gen_message_noEscape'] .= "<br><br>";
                }
                $form['gen_message_noEscape'] .= "<font color=blue>" . _g("前回この受注を登録した後で、関連する構成表マスタが変更されています。") . "<br>" .
                        _g("ここで「登録」ボタンを押すと、使用予約数に構成表マスタの変更が反映されます。") . "<br>" .
                        _g("「閉じる」をクリックすれば、前回登録時の構成が維持されます。") . "</font><br><br>";
            }
        }

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        // 修正・コピーモードでの引当数・引当可能数テーブルの取得 （EditListのSQLで使用）
        if (isset($form['received_header_id']) && !isset($form['gen_multi_edit'])) {
            $query = "create temp table temp_reserve (received_detail_id int, reserve_qty numeric, reservable_qty numeric)";
            $gen_db->query($query);
            $query = "
            select
                received_detail_id
                ,item_id
                ,received_quantity
            from
                received_detail
            where
                received_header_id = '{$form['received_header_id']}'
            ";
            $arr = $gen_db->getArray($query);
            foreach ($arr as $row) {
                if (isset($form['gen_record_copy'])) {
                    // コピーモード
                    $reservableQty = Logic_Reserve::calcReservableQuantity($row['item_id'], "", "");
                    //$reserveQty = $row['received_quantity'];
                    //if ($reserveQty > $reservableQty)
                    //    $reserveQty = $reservableQty;
                } else {
                    // 修正モード
                    $reservableQty = Logic_Reserve::calcReservableQuantity($row['item_id'], $row['received_detail_id'], "");
                    //$reserveQty = Logic_Reserve::getReserveQuantity($row['received_detail_id']);
                }
                $query = "insert into temp_reserve (received_detail_id, reserve_qty, reservable_qty) values ('{$row['received_detail_id']}','0','{$reservableQty}')";
                //$query = "insert into temp_reserve (received_detail_id, reserve_qty, reservable_qty) values ('{$row['received_detail_id']}','{$reserveQty}','{$reservableQty}')";
                $gen_db->query($query);
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('受注番号'),
                'type' => 'textbox',
                'name' => 'received_number',
                'value' => @$form['received_number'],
                'size' => '11',
                'readonly' => $existDelivery,
                'hidePin' => true,
                'helpText_noEscape' => _g('自動採番されますので、指定する必要はありません。')
            ),
            array(
                'label' => _g('見積参照'),
                'type' => 'dropdown',
                'name' => 'estimate_header_id',
                'value' => @$form['estimate_header_id'],
                'size' => '11',
                'dropdownCategory' => 'estimate_header',
                'onChange_noEscape' => 'copyFromEstimate()',
                'readonly' => isset($form['received_header_id']) && $form['received_header_id'] != '',
                'hidePin' => true,
                'helpText_noEscape' => _g('見積を選択すると、その内容がこの受注にコピーされます。') . "<br><br>" . _g('修正モードでは使用できません。'),
            ),
            array(
                'label' => _g('客先注番'),
                'type' => 'textbox',
                'name' => 'customer_received_number',
                'value' => @$form['customer_received_number'],
                'size' => '11',
                'readonly' => $existDelivery,
            ),
            array(
                'label' => _g('確定度'),
                'type' => 'select',
                'name' => 'guarantee_grade',
                'options' => array('0' => _g('確定'), '1' => _g('予約')),
                'tabindex' => '-1',
                'readonly' => $existDelivery,
                'selected' => @$form['guarantee_grade'],
                'helpText_noEscape' => _g('正式受注の場合は「確定」、内示段階の場合は「予約」にしてください。予約の場合は所要量計算において無視されます（内示モードにすれば含まれます）。') . '<br><br>'
                    ._g('受注品目にダミー品目が含まれる場合、「予約」を選択することはできません。'),    // この仕様の理由は、Modelの guarantee_grade の箇所のコメントを参照。
            ),
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'customer',
                'autoCompleteCategory' => 'customer_received',
                'onChange_noEscape' => 'onCustomerIdChange(true)',
                'require' => true,
                'readonly' => $existDelivery,
                'placeholder' => _g('得意先コード'),
                'helpText_noEscape' => _g('発注元を指定します。指定できるのは取引先マスタで区分を「得意先」に指定した取引先のみです。')
            ),
            array(
                'label' => _g('受注日'),
                'type' => 'calendar',
                'name' => 'received_date',
                'value' => @$form['received_date'],
                'size' => '8',
                'readonly' => $existDelivery,
                'require' => true,
            ),
            array(
                'label' => _g('発送先'),
                'type' => 'dropdown',
                'name' => 'delivery_customer_id',
                'value' => @$form['delivery_customer_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'delivery_customer',
                'autoCompleteCategory' => 'customer_received_shipping',
                'readonly' => $existDelivery,
                'helpText_noEscape' => _g('出荷指示書に記載されます。得意先と発送先が異なる場合のみ指定してください。'),
            ),
            array(
                'label' => _g('掛率（％）'),
                'type' => 'textbox',
                'name' => 'price_percent',
                'value' => @$form['price_percent'],
                'style' => 'text-align:right',
                'readonly' => true,
                'size' => '10',
                'helpText_noEscape' => _g('取引先マスタの「掛率」、もしくは掛率グループマスタの「掛率」の値が表示されます。（取引先マスタ「掛率」が優先されます。）') . '<br>'
                . _g('品目マスタ「標準販売単価」にこの掛率をかけた単価が、デフォルトの受注単価になります。'),
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
                'readonly' => $existDelivery,
            ),
            array(
                'label' => _g('部門(自社)'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'readonly' => $existDelivery,
                'selected' => @$form['section_id'],
            ),
            array(
                'label' => _g("取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name",
                'value' => '',
                'size' => '8',
                'readonly' => 'true',
                'helpText_noEscape' => _g("請求先の取引通貨（取引先マスタで設定）が表示されます。「受注単価」はこの取引通貨で設定してください。") . "<br><br>" . _g("なお、レートは受注日時点のレート（「為替レートマスタ」）が適用されます。"),
            ),
            array(
                'label' => _g('売掛残高'),
                'type' => 'textbox',
                'name' => 'receivable_balance',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先に対する売掛残高（受注ベース）が表示されます。この受注の金額は含みません。") . '<br><br>'
                . _g("この得意先に請求先が設定されているときは、請求先の売掛残高が表示されます。") . '<br><br>'
                . _g("「売掛残高表」を受注ベースで発行したときの売掛残高と同じです（詳しくは売掛残高表画面のページヒントをご覧ください）。") . '<br>'
                . _g("ただし、修正モードでは、売掛残高表の金額から今回受注額を引いた金額になります。"),
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'textbox',
                'name' => 'total_amount',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('与信限度額'),
                'type' => 'textbox',
                'name' => 'credit_line',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先の与信限度額が表示されます。") . '<br>'
                . _g("与信限度額は、取引先マスタで設定することができます。") . '<br>'
                . _g("この得意先に請求先が設定されているときは、請求先の与信限度額が表示されます。"),
            ),
            array(
                'label' => _g('合計粗利'),
                'type' => 'textbox',
                'name' => 'total_gross_margin',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('受注備考1'),
                'type' => 'textbox',
                'name' => 'remarks_header',
                'value' => @$form['remarks_header'],
                'size' => '22',
                'readonly' => $existDelivery,
                'ime' => 'on',
                'helpText_noEscape' => _g('納品書にも転記されます。') . '<br><br>'
                . _g('見積書から受注に転記した場合、この欄には見積書の「見積備考」が入ります。'),
            ),
            array(
                'label' => _g('同時に納品を登録'),
                'type' => 'checkbox',
                'name' => 'delivery_regist',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'readonly' => $existDelivery,
                'value' => @$form['delivery_regist'],
                'helpText_noEscape' => _g('このチェックをオンにすると、受注と同時に納品も登録されます。') . '<br><br>'
                . _g('納品数 = 受注数、また 納品日 = 受注日 となります。分納には対応できません。') . '<br><br>'
                . _g('「同時に納品書印刷」をオンにすることにより、納品書も印刷できます。') . '<br><br>'
                . _g('受注と納品(出荷・売上)の両方を管理する必要がなく、売上だけ登録できればいいという場合に、この機能を使用すると便利です。'),
            ),
            array(
                'label' => _g('受注備考2'),
                'type' => 'textbox',
                'name' => 'remarks_header_2',
                'value' => @$form['remarks_header_2'],
                'size' => '22',
                'readonly' => $existDelivery,
                'ime' => 'on',
                'helpText_noEscape' => _g('納品書にも転記されます。') . '<br><br>'
                . _g('見積書から受注に転記した場合、この欄には見積書の「件名」が入ります。'),
            ),
            array(
                'label' => _g('同時に納品書印刷'),
                'type' => 'checkbox',
                'name' => 'delivery_regist_print',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'readonly' => $existDelivery,
                'value' => @$form['delivery_regist_print'],
                'helpText_noEscape' => _g('このチェックおよび「同時に納品を登録」をオンにすると、登録時に納品書が印刷されます。') . '<br><br>'
                . _g('「同時に納品を登録」がオフになっている場合、この項目は無意味です。'),
            ),
            array(
                'label' => _g('受注備考3'),
                'type' => 'textbox',
                'name' => 'remarks_header_3',
                'value' => @$form['remarks_header_3'],
                'size' => '22',
                'readonly' => $existDelivery,
                'ime' => 'on',
                'helpText_noEscape' => _g('納品書にも転記されます。') . '<br><br>'
                . _g('見積書から受注に転記した場合、この欄には見積書の「受渡場所」が入ります。'),
            ),
            // 以下の項目 ag.cgi?page=ProjectDocView&pid=1516&did=205554
            array(
                'label' => _g("取引先備考1"),
                'type' => 'textbox',
                'name' => "customer_remarks",
                'value' => @$form['customer_remarks'],
                'size' => '22',
                'hide' => true,
                'readonly' => 'true',
            ),
            array(
                'label' => _g("取引先備考2"),
                'type' => 'textbox',
                'name' => "customer_remarks_2",
                'value' => @$form['customer_remarks_2'],
                'size' => '22',
                'hide' => true,
                'readonly' => 'true',
            ),
            array(
                'label' => _g("取引先備考3"),
                'type' => 'textbox',
                'name' => "customer_remarks_3",
                'value' => @$form['customer_remarks_3'],
                'size' => '22',
                'hide' => true,
                'readonly' => 'true',
            ),
            array(
                'label' => _g("取引先備考4"),
                'type' => 'textbox',
                'name' => "customer_remarks_4",
                'value' => @$form['customer_remarks_4'],
                'size' => '22',
                'hide' => true,
                'readonly' => 'true',
            ),
            array(
                'label' => _g("取引先備考5"),
                'type' => 'textbox',
                'name' => "customer_remarks_5",
                'value' => @$form['customer_remarks_5'],
                'size' => '22',
                'hide' => true,
                'readonly' => 'true',
            ),
            // ********** List **********
            array(
                'type' => "list",
                'listId' => 'list1', // リストのID。ページ内に複数リストがある場合、ユニークになるようにすること
                'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
                'keyColumn' => 'received_detail_id', // 明細行のキーとなるカラム
                'readonlyCondition' => (isset($form['gen_record_copy']) ? "" : "('[delivery_quantity]'>0 || ('[delivery_quantity]'==0 && '[delivery_completed]'=='t'))"), // list内だけで使えるプロパティ
                'query' => // Listデータを取得するSQL。 EditBaseで実行され、結果配列が'data'という名前で格納される
                isset($form['received_header_id']) ? "
                        select
                            received_detail.*
                            ,item_master.item_name as item_name
                            ,item_master.default_selling_price
                            ,item_master.order_class
                            ,item_master.measure
                            " . (isset($form['gen_record_copy']) ? ",null as seiban" : "") . "
                            " . (isset($form['gen_record_copy']) ? ",''" : ",delivery_quantity") . " as delivery_quantity
                            " . (isset($form['gen_record_copy']) ? ",''" : ",case when delivery_completed then '" . _g("完") . "' else
                                '" . _g("未(残") . " ' || (COALESCE(received_quantity,0) - COALESCE(delivery_quantity,0)) || ')' end") . " as delivery
                            ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price
                            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end * received_quantity as amount
                            ,case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end as sales_base_cost
                            ,(case when foreign_currency_id is null then product_price else foreign_currency_product_price end
                                - coalesce(case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end,0))
                                * received_quantity as sales_gross_margin
                             /* 在庫数（旧：引当可能数） */
                            ,".(isset($form['gen_multi_edit']) ? "''" : "temp_reserve.reservable_qty")." as reservable_quantity


                             /* 以下は15iで廃止。理由は editControlArrayの引当数の項目を参照 */
                             /* 在庫引当数 */
                             /* ,".(isset($form['gen_record_copy']) ? "0" : (isset($form['gen_multi_edit']) ? "''" : "temp_reserve.reserve_qty"))." as reserve_quantity */
                             /* 製番引当済数 */
                             /* ,case when item_master.order_class = 1 then null else coalesce(seiban_change_in_qty,0) - coalesce(seiban_change_out_qty,0) end as seiban_change_quantity */
                    from
                        received_detail
                        left join (select received_detail_id, sum(quantity) as use_plan_qty from use_plan group by received_detail_id) as t_use on received_detail.received_detail_id = t_use.received_detail_id
                        left join item_master on received_detail.item_id = item_master.item_id
                        /* 納品済み数 */
                        left join (
                            select
                                received_detail_id
                                ,SUM(delivery_quantity) as delivery_quantity
                            from
                                delivery_detail
                                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                            group by
                                received_detail_id
                        ) as t_delivery on received_detail.received_detail_id = t_delivery.received_detail_id
                        ".(isset($form['gen_multi_edit']) ? "" : "LEFT JOIN temp_reserve on received_detail.received_detail_id = temp_reserve.received_detail_id")."
                           -- 製番引当数（製番品目用）
                           left join (
                               select
                                   item_id
                                   ,source_seiban
                                   ,sum(quantity) as seiban_change_out_qty
                               from
                                   seiban_change
                               where
                                   source_seiban <> ''
                               group by
                                   item_id
                                   ,source_seiban
                               ) as t_seiban_change_out
                               on received_detail.item_id = t_seiban_change_out.item_id
                               and received_detail.seiban = t_seiban_change_out.source_seiban
                           left join (
                               select
                                   item_id
                                   ,dist_seiban
                                   ,sum(quantity) as seiban_change_in_qty
                               from
                                   seiban_change
                               where
                                   dist_seiban <> ''
                               group by
                                   item_id
                                   ,dist_seiban
                               ) as t_seiban_change_in
                               on received_detail.item_id = t_seiban_change_in.item_id
                               and received_detail.seiban = t_seiban_change_in.dist_seiban

                        where
                            received_header_id = '{$form['received_header_id']}'
                        order by
                            line_no
                        " : "",
                'controls' => array(
                    array(
                        'label' => _g('製番'),
                        'type' => 'div',
                        'name' => 'seiban',
                        'align' => 'center',
                        'size' => '10',
                        'helpText_noEscape' => _g('受注および計画1件ごとに設定されるユニークな番号です。MRP品目・製番品目の別にかかわらず設定されます。登録の際に自動採番されます（任意の番号を指定することはできません）。'),
                    ),
                    array(
                        'label' => _g('納品状況'),
                        'type' => 'div',
                        'name' => 'delivery',
                        'style' => 'text-align:center',
                        'size' => '9',
                    ),
                    array(
                        'label' => _g('品目コード'),
                        'type' => 'dropdown',
                        'name' => 'item_id',
                        'size' => '15',
                        'dropdownCategory' => 'item_received_nosubtext',
                        'autoCompleteCategory' => 'item_received',
                        'onChange_noEscape' => "onItemIdChange([gen_line_no]);",
                        'placeholder' => _g('品目コード'),
                        'require' => true,
                    ),
                    array(
                        'label' => _g('品目名'),
                        'type' => 'div',
                        'name' => 'item_name',
                        'size' => '14',
                    ),
                    array(
                        'label' => _g('受注数'),
                        'type' => 'textbox',
                        'name' => 'received_quantity',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => 'onQtyChange([gen_line_no],true)',
                    ),
                    array(
                        'label' => _g('単位'),
                        'type' => 'div',
                        'name' => 'measure',
                        'style' => "text-align:right",
                        'size' => '6',
                        'helpText_noEscape' => _g('品目マスタ「管理単位」です。'),
                    ),
                    array(// 単価履歴参照機能のため拡張DD化
                        'label' => _g('受注単価'),
                        'type' => 'dropdown',
                        'name' => 'product_price',
                        'size' => '5',
                        'dropdownCategory' => 'received_price',
                        'dropdownParam' => "[customer_id];[item_id_[gen_line_no]]",
                        'dropdownShowCondition_noEscape' => "!isNaN([item_id_[gen_line_no]])",
                        'dropdownShowConditionAlert' => _g("先に品目を指定してください。"),
                        'require' => true,
                        'style' => "text-align:right",
                        'onChange_noEscape' => 'onPriceChange([gen_line_no])',
                    ),
                    array(// このコントロールを非表示にする際は、JavaScriptも変更すること。
                        'label' => _g('標準単価'),
                        'type' => 'div',
                        'name' => 'default_selling_price',
                        'size' => '7',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('品目マスタ「標準販売単価」です。'),
                    ),
                    array(
                        'label' => _g('金額'),
                        'type' => 'div',
                        'name' => 'amount',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「受注数 × 受注単価」で計算されます。'),
                    ),
                    array(
                        'label' => _g('課税区分'),
                        'type' => 'div',
                        'name' => 'tax_class',
                        'size' => '6',
                        'style' => "text-align:center;vertical-align:bottom",
                        'helpText_noEscape' => sprintf(_g('品目マスタ「課税区分」（課税/非課税）です。なお、取引通貨が%s以外の場合は常に非課税扱いとなります。'), $keyCurrency),
                    ),
                    array(
                        'label' => _g('販売原単価'),
                        'type' => 'textbox',
                        'name' => 'sales_base_cost',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'onChange_noEscape' => 'onBaseCostChange([gen_line_no])',
                        'helpText_noEscape' => _g("この品目の販売原価（単価）を指定します。") . '<br><br>' .
                        _g("ジェネシスでは、「標準原価」（非製造業および原価管理をさほど重視しない製造業向け）と「実績原価」（原価管理を重視する製造業向け）の2つの原価管理方法があります。") .
                        _g("この項目は基本的に、「標準原価」を使用する場合のためのものです。「実績原価」を使用する場合は、原価リストの画面を参照してください。") . '<br><br>' .
                        _g("ここで登録した販売原価は、受注画面（リスト）に表示されます。") .
                        _g("また、納品登録画面における販売原価のデフォルト値になります。") .
                        _g("（納品登録において販売原価を変更することもできます。受注画面と納品画面の販売原価が異なる場合、販売レポートでは納品画面の値が表示されます。）") . '<br><br>' .
                        _g("品目を選択するとデフォルトの販売原価が自動的に入力されます。デフォルト値は以下のように計算されます。") . '<br><br>' .
                        "●<b>" . _g("製造品（標準手配先が「内製」の品目）") . "：</b>" . _g("品目マスタ「標準加工時間(分)」 * 品目マスタ「工賃(\/分)」") . '<br><br>' .
                        "●<b>" . _g("購入品（標準手配先が「内製」以外の品目）") . "：</b>" . _g("品目マスタの在庫評価単価") . '<br><br>' .
                        _g("標準手配先が「内製」「外注(支給あり)」で、なおかつ構成表マスタで子品目が登録されている場合、見積品目を構成展開し、子品目の原価が合計されます。") . '<br><br>' .
                        _g("取引通貨が外貨の場合、デフォルト原価も外貨建てで表示されます（本日付けのレートで計算されます）。"),
                    ),
                    array(
                        'label' => _g('販売粗利'),
                        'type' => 'div',
                        'name' => 'sales_gross_margin',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「(受注単価 - 販売原単価) × 数量)」で計算されます。'),
                    ),
                    array(
                        'label' => _g('受注納期'),
                        'type' => 'calendar',
                        'name' => 'dead_line',
                        'size' => '8',
                        'require' => true,
                        'isCalendar' => true,
                        'hideSubButton' => true,
                    ),
                    array(
                        // 15iで名称変更（「引当可能数」⇒「在庫数」）
                        'label' => _g('在庫数'),
                        'type' => 'div',
                        'name' => 'reservable_quantity',
                        'size' => '8',
                        'style' => "text-align:right",
                        // 製番品目が常に0なのは、もともとこの項目は「引当可能数」だったため）
                        'helpText_noEscape' => "<b>" . _g('製番品目') . ":</b> " . _g('常に0です。') . '<br><br><b>' . _g('MRP品目') . '</b>：' . _g(' [資材管理]メニューの[現在庫リスト]における本日付の「有効在庫数」です。ただし現在庫リストの有効在庫は指定日までの引当しか考慮しないのに対し、ここの値は将来分の引当も使用できないものとして差し引いていますので、その分の差異がでることがあります。') . '<br>' . _g('全ロケーションの合計です（ただしサプライヤーロケーションは含みません）。')
                    ),
                    // 15iで在庫引当数を廃止。ag.cgi?page=ProjectDocView&pid=1574&did=225207
                    //  ・引当しても、他の受注が横取りで納品できてしまうので意味が無い。
                    //  ・引当しなくても「出荷予定数」という形で有効在庫数が減少する。
                    //  ・引当を行うと、所要量計算を行った時に結果が意図どおりにならず、混乱の元となる。
                    //  ※所要量計算を使うユーザーであれば基本的に引当機能は不要。
                    //  　所要量計算を行わず、厳密な引当の運用をしたいユーザーにとっては引当機能が必要だろうが、
                    //　　引当機能が意味を持つようにするには、有効在庫数を超えた納品ができないよう制限する必要がある。
                    //  　しかし、そうしてしまうとユーザーによっては縛りがきつすぎると考えられる。
                    //  　また、入出庫等も合わせて制限しないと無意味。
                    //  上記の理由で、13iの時点でデフォルトの在庫引当数は0とし、ユーザーには引当機能を使用しない
                    //  ことを推奨していた。
                    //array(
                    //    'label' => _g('在庫引当数'),
                    //    'type' => 'textbox',
                    //    'name' => 'reserve_quantity',
                    //    'size' => '6',
                    //    'ime' => 'off',
                    //    'style' => "text-align:right",
                    //    'readonlyCondition' => "'[order_class]'=='0' || '[order_class]'=='2'",
                    //    // 製番品目は在庫引当可能数が常に0になるようにしたため、受注引当できない。理由は Logic_Reserve::calcReservableQuantity() 冒頭コメントを参照。
                    //    'helpText_noEscape' => "<b>" . _g('製番品目') . ":</b><br>"
                    //        . _g("常に0となり、変更できません。製番品目の引当は[資材管理]-[製番引当]画面で行ってください。その画面で引当登録した数は、右の[製番引当済数]欄に表示されています。") . "<br><br>"
                    //        . "<b>" . _g("MRP品目") . ":</b><br>"
                    //        . _g("在庫から引き当てる数量を指定します。下の「在庫引当可能数」以下の値を指定してください。") . "<br><br>"
                    //        . "<b>" . _g('ロット品目') . ":</b><br>"
                    //        . _g("常に0となり、変更できません。ロット品目の引当は受注リスト画面で行ってください。") . "<br><br>"
                    //),
                    // 15iで廃止。ag.cgi?page=ProjectDocView&pid=1574&did=225207
                    // ほとんど活用されていないと思われるため。
                    //array(
                    //    'label' => _g('製番引当済数'),
                    //    'type' => 'div',
                    //    'name' => 'seiban_change_quantity',
                    //    'style' => 'text-align:right',
                    //    'size' => '8',
                    //    'helpText_noEscape' => _g('[資材管理]-[製番引当登録]画面で登録した引当数です。MRP品目は表示されません。'),
                    //),
                    array(
                        'label' => _g('受注明細備考1'),
                        'type' => 'textbox',
                        'name' => 'remarks',
                        'ime' => 'on',
                        'size' => '18',
                        'focusZoom' => array('left', 400, 25),     // フォーカス時のサイズ拡張(方向,width,height)
                    ),
                    array(
                        'label' => _g('受注明細備考2'),
                        'type' => 'textbox',
                        'name' => 'remarks_2',
                        'ime' => 'on',
                        'size' => '18',
                        'focusZoom' => array('left', 400, 25),
                    ),
                ),
            ),
        );
    }

}
