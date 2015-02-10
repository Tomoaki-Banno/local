<?php

class Logic_Dropdown
{

    // 拡張ドロップダウンのカテゴリを新規作成・変更するときは、このfunctionを変更
    //  （ここ以外を修正する必要はない）
    static function _getDropdownParam($category, $id, $param, $search, $matchBox, $selecterSearch, $selecterSearch2, $code, $isCodeToId)
    {
        global $gen_db;

        $hasSelecter = false;
        $selecterTitle = "";
        $selectOptionTags_noEscape = "";
        $hasSelecter2 = false;
        $selecterTitle2 = "";
        $selectOptionTags_noEscape2 = "";
        $masterEditAction = "";
        $masterCode = "";
        $masterName = "";
        $masterCheckQuery = "";
        $orderArray_noEscape = "";
        $idConvert = false;
        $isMatchBox = true;
        
        // クオートされているはずだが念のため
        $id = $gen_db->quoteParam($id);
        $param = $gen_db->quoteParam($param);
        $search = $gen_db->quoteParam($search);
        $selecterSearch = $gen_db->quoteParam($selecterSearch);
        $selecterSearch2 = $gen_db->quoteParam($selecterSearch2);
        $code = $gen_db->quoteParam($code);

        // idの数値検知
        if ($category != 'stock' && $category != 'seiban_stock' && $category != 'seiban_stock_dist' && $category != 'order_id_for_user' && $category != 'received_seiban' && $category != 'textHistory') {
            $id = (isset($id) && is_numeric($id) ? $id : 'null');
        }

        // SQL中には必ず「id」列と「show」列が必要。
        // id列は非表示で、POSTされる値になる(select の OPTION VALUE と同じ）
        // show列は親コントロールに表示される(select の OPTIONタグにはさまれた文字列と同じ）

        switch ($category) {
            case 'item':                        // 品目（item_id -> item_code, item_name）
            case 'item_received':               // 品目（受注用）
            case 'item_received_nosubtext':     // 品目（受注用。サブテキスト無し）
            case 'item_plan':                   // 品目（計画用）
            case 'item_order_manufacturing':    // 品目（製造指示書用）
            case 'item_order_subcontract':      // 品目（外製指示書用）
            case 'item_order_partner_nosubtext':// 品目（注文書用。サブテキスト無し）
                // item_order_master を JOINする必要があるかどうか（Where条件に item_order_master の内容を使用するかどうか）。
                // JOINしないほうが圧倒的に速い。
                $isItemOrder = ($category == "item_order_manufacturing" || $category == "item_order_subcontract" || $category == "item_order_partner_nosubtext");

                // 計画用
                if ($category == "item_plan") {
                    $params = explode(";", $param);
                    // 計画登録済みの品目は表示しない。また製番品目は登録不可となったため、表示しない
                    $planWhere = " and order_class=1";
                    if (is_numeric($params[0]) && is_numeric($params[1])) {
                        $planWhere .= " and item_id not in (select item_id from plan where plan_year={$params[0]} and plan_month={$params[1]} and classification=0)";
                    }
                }
                
                // 製造指示書用
                if ($category == "item_order_manufacturing") {
                    // 品目マスタにおいて標準手配先が内製と関連付けされているもの。
                    // また、ダミー品目の登録は禁止。（理由は Manufacturing_Order_Model の item_id の validate部分のコメントを参照）
                    // また、標準手配先が内製以外で、代替手配先が内製になっている品目は含まれないことに注意。
                    //  　「line_number=0」を削除すればそういう品目も出てくるようになる。
                    //  　おそらくそうしても大丈夫だとは思うが、標準手配先が内製でなくても問題ないかどうか、検証が必要。
                    //  　ag.cgi?page=ProjectDocView&pid=1389&did=123681
                    // 13i rev. 20130402 外製品が製造指示書発行できると困るという報告があり、外製品を除外することにした。
                    $manufacturingWhere = " and partner_class=3 and not coalesce(item_master.dummy_item,false) and line_number=0";
                }
                
                // 外製指示書/注文書用
                $isPartner = ($category == 'item_order_subcontract' || $category == 'item_order_partner_nosubtext');
                if ($isPartner) {
                    // 表示される品目は、現在選択されている取引先によって決まる。
                    // （ドロップダウンボタンを押したときのpartner_id(paramで取得)の値で判断）
                    // 取引先が品目マスタにおいてその品目と関連付けされており、
                    // なおかつ手配種別が「注文」「外注」として登録されているもの。
                    // また、外製についてはダミー品目の登録は禁止。（理由は Manufacturing_Order_Model の item_id の validate部分のコメントを参照）
                    // 13i rev. 20130527 内製品が外製指示登録できると困るという報告があり、内製品を除外することにした。
                    $partnerWhere = " and partner_class " . ($category == 'item_order_subcontract' ? "in (1,2)" : "= 0");
                    if ($category == 'item_order_subcontract') {
                        $partnerWhere .= " and not coalesce(item_master.dummy_item,false)";
                    }
                    if (!is_numeric($selecterSearch2)) {
                        $selecterSearch2 = "0";
                    }
                    // $isCodeToId は、テキストボックス直接入力のときには絞り込みが無効になるようにするため
                    // /ag.cgi?page=ProjectDocView&pid=1574&did=228791
                    if ($selecterSearch2 == "0" && $param != "" && !$isCodeToId) {
                        $partnerWhere .= " and item_order_master.order_user_id in ({$param})";
                    }
                }
                

                $query = "
                select
                    item_master.item_id as id
                    ," . ($isItemOrder ? "max" : "") . "(item_code) as show
                    ," . ($isItemOrder ? "max" : "") . "(item_name) as subtext
                    ," . ($isItemOrder ? "max" : "") . "(CASE order_class WHEN 0 THEN '" . _g("製番") . "' WHEN 2 THEN '" . _g("ロット") . "' ELSE '" . _g("MRP") . "' END) as c2
                    ," . ($isItemOrder ? "max" : "") . "(spec) as c3
                from
                    item_master
                    " . ($isItemOrder ? "inner join item_order_master on item_master.item_id=item_order_master.item_id" : "") . "
                [Where]
                    not coalesce(item_master.end_item, false) -- 非表示品目は表示しない
                    " . ($search == "" ? "" : self::_getSearchCondition(array('item_code', 'item_name', 'spec'), $matchBox, $search)) . "
                    " . (is_numeric($selecterSearch) ? " and (item_group_id = {$selecterSearch} or item_group_id_2 = {$selecterSearch} or item_group_id_3 = {$selecterSearch})" : "") . "
                    " . ($category == "item_received" || $category == "item_received_nosubtext" ? " and received_object=0 " : "") . "
                    " . ($category == "item_order_manufacturing" ? $manufacturingWhere : "") . "
                    " . ($category == "item_plan" ? $planWhere : "") . "
                    " . ($param == "not_dummy" ? " and not coalesce(item_master.dummy_item,false)" : "") . "
                    " . ($isPartner ? $partnerWhere : "") . "
                    " . ($isItemOrder ? " group by item_master.item_id " : "") . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("品目コード"), _g("品目名"), _g("手配"), _g("仕様"));
                $orderArray_noEscape = array(array("label" => _g("品目コード"), "field" => "show", "desc" => false),
                    array("label" => _g("品目名"), "field" => "subtext", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "center", "left");
                $columnWidthArray = array(0, 0, 120, 221, 70, 220);
                // 「(なし)」行を設けるかどうか
                $hasNothingRow = true;
                // サブテキストを設けるかどうか
                $hasSubtext = !($category == "item_received_nosubtext" || $category == "item_order_partner_nosubtext");
                // 絞り込みセレクタを設けるかどうか。trueのときは$selecterTitle と $selectOptionTags_noEscape が必要
                $hasSelecter = true;
                $selecterTitle = _g("品目グループ");
                $selectOptionTags_noEscape = self::_getSelectOptionTags_noEscape_ItemGroup($selecterSearch);
                // 絞り込みセレクタ2
                if ($isPartner) {
                    $hasSelecter2 = true;
                    $selecterTitle2 = "";
                    $selectOptionTags_noEscape2 = "<option value=0" . ($selecterSearch2 == '0' ? " selected" : "") . ">" . _g("取引先に関連付けられた品目のみ") . "</option><option value=1" . ($selecterSearch2 != "0" ? " selected" : "") . ">" . _g("すべての品目") . "</option>";
                }
                // id を code と subtext に変換するSQL
                $idToCodeQuery = "select item_code as showtext, item_name as subtext from item_master where item_id = {$id}";
                $codeColumn = "item_code";
                // 新規登録ボタン、およびダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Item_Edit";
                $masterCode = "item_code";
                $masterName = "item_name";
                $masterCheckQuery = "select item_id from item_master where item_code ='{$code}'";
                if ($category == "item_order_manufacturing") {
                    $alertForMaster = _g("指定された品目は品目マスタの手配先が「内製」ではないか、ダミー品目なので、使用できません。");
                }
                if ($category == "item_order_partner_nosubtext") {
                    $alertForMaster = _g("指定された品目は、品目マスタの手配区分が「発注」ではないため、使用できません。");
                }
                if ($category == "item_order_subcontract") {
                    $alertForMaster = _g("指定された品目は、品目マスタの手配区分が「外製」ではないか、ダミー品目であるため、使用できません。");
                }
                break;

            case 'item_customer_user':      // 品目（item_id -> item_code, item_name）
                $query = "
                select
                    item_master.item_id as id
                    ,item_code as show
                    ,item_name as subtext
                    ,selling_price as c1
                from
                    item_master
                    inner join customer_price_master on item_master.item_id = customer_price_master.item_id
                [Where]
                    customer_id = {$_SESSION["user_customer_id"]}
                    and not coalesce(item_master.end_item, false) -- 非表示品目は表示しない
                    " . ($search == "" ? "" : self::_getSearchCondition(array('item_code', 'item_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("品目コード"), _g("品目名"), _g("単価"));
                $orderArray_noEscape = array(array("label" => _g("品目コード"), "field" => "show", "desc" => false),
                    array("label" => _g("品目名"), "field" => "subtext", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "right");
                $columnWidthArray = array(0, 0, 100, 200, 100);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select item_code as showtext, item_name as subtext from item_master where item_id = {$id}";
                $codeColumn = "item_code";
                break;

            case 'item_forbom':             // 品目（BOM用。子品目の有無を表示）
            case 'item_forbom_copy':        // 品目（BOMの近似品目用）
                $query = "
                select
                    item_master.item_id as id
                    ,item_code as show
                    ,item_name as subtext
                    ,case when bom_item_id is null then '' else '" . _g("あり") . "' end as c1
                    ,CASE order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end as c2
                    ,case when dummy_item then '" . _g("ダミー") . "' else '' end as c3
                from
                    item_master
                    left join (select item_id as bom_item_id from bom_master group by item_id) as t_bom on item_master.item_id = t_bom.bom_item_id
                [Where]
                    not coalesce(item_master.end_item, false) -- 非表示品目は表示しない
                    " . ($search == "" ? "" : self::_getSearchCondition(array('item_code', 'item_name'), $matchBox, $search)) . "
                    " . (is_numeric($selecterSearch) ? " and (item_group_id = {$selecterSearch} or item_group_id_2 = {$selecterSearch} or item_group_id_3 = {$selecterSearch})" : "") . "
                    " . ($category == "item_forbom_copy" && $param != "" ? " and order_class = (select order_class from item_master where item_id = '{$param}')" : "") . "
                order by
                    show
                ";

                $titleArray = array("ID", _g("品目コード"), _g("品目名"), _g("子品目"), _g("手配"), _g("ダミー品目"));
                $alignArray = array("left", "left", "left", "left", "center", "center", "center");
                $columnWidthArray = array(0, 0, 120, 221, 70, 70, 80);
                // 「(なし)」行を設けるかどうか
                $hasNothingRow = true;
                // サブテキストを設けるかどうか
                $hasSubtext = true;
                // 絞り込みセレクタを設けるかどうか。trueのときは$selecterTitle と $selectOptionTags_noEscape が必要
                $hasSelecter = true;
                $selecterTitle = _g("品目グループ");
                $selectOptionTags_noEscape = self::_getSelectOptionTags_noEscape_ItemGroup($selecterSearch);
                // id を code と subtext に変換するSQL
                $idToCodeQuery = "select item_code as showtext, item_name as subtext from item_master where item_id = {$id}";
                $codeColumn = "item_code";
                // 新規登録ボタン、およびダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Item_Edit";
                $masterCode = "item_code";
                $masterName = "item_name";
                $masterCheckQuery = "select item_id from item_master where item_code ='{$code}'";

                break;

            case 'customer':                // 得意先（customer_id ⇒ customer_no, customer_name）
            case 'customer_nosubtext':      // 得意先（サブテキスト無し）
            case 'customer_bill_close':     // 得意先（締め請求のみ）
            case 'customer_or_suppler':     // 得意先もしくはサプライヤー
                $query = "
                select
                    customer_id as id
                    ,customer_no as show
                    ,customer_name as subtext
                    ,CASE classification WHEN 0 THEN '" . _g("得意先") . "' WHEN 1 THEN '" . _g("サプライヤー") . "' ELSE '" . _g("発送先") . "' END as classification
                    ,address1
                from
                    customer_master
                [Where]
                    not coalesce(customer_master.end_customer, false) -- 非表示取引先は表示しない
                    and classification " . ($category == "customer_or_suppler" ? "in (0,1)" : "= 0") . "
                    " . ($category == "customer_bill_close" ? " and bill_pattern <> 2" . ($param != "" ? " and customer_id not in ({$param})" : "") : "") . "
                    " . ($search == "" ? "" : self::_getSearchCondition(array('customer_no', 'customer_name', 'address1'), $matchBox, $search)) . "
                    " . (is_numeric($selecterSearch) ? " and (customer_group_id_1 = {$selecterSearch} or customer_group_id_2 = {$selecterSearch} or customer_group_id_3 = {$selecterSearch})" : "") . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("取引先コード"), _g("取引先名"), _g("種類"), _g("住所1"));
                $orderArray_noEscape = array(array("label" => _g("取引先コード"), "field" => "show", "desc" => false),
                    array("label" => _g("取引先名"), "field" => "subtext", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "center", "left");
                $columnWidthArray = array(0, 0, 100, 200, 80, 200);
                $hasNothingRow = true;
                $hasSubtext = ($category == "customer" || $category == "customer_or_suppler");
                // 絞り込みセレクタを設けるかどうか。trueのときは$selecterTitle と $selectOptionTags_noEscape が必要
                $hasSelecter = true;
                $selecterTitle = _g("取引先グループ");
                $selectOptionTags_noEscape = self::_getSelectOptionTags_noEscape_CustomerGroup($selecterSearch);
                $idToCodeQuery = "select customer_no as showtext, customer_name as subtext from customer_master where customer_id = {$id}";
                $codeColumn = "customer_no";
                // 新規登録ボタン、ダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Customer_Edit";
                $masterCode = "customer_no";
                $masterName = "customer_name";
                $masterCheckQuery = "select customer_id from customer_master where customer_no = '{$code}'";
                if ($category == "customer" || $category == "customer_nosubtext") {
                    $alertForMaster = _g("指定された取引先は「得意先」ではないか、非表示なので使用できません。");
                }
                if ($category == "customer_bill_close") {
                    $alertForMaster = _g("指定された取引先は「得意先」ではないか、請求パターンが「締め請求」であるか、非表示なので使用できません。");
                }
                if ($category == "customer_or_suppler") {
                    $alertForMaster = _g("指定された取引先は「得意先」または「サプライヤー」ではないか、非表示なので使用できません。");
                }
                break;

            case 'delivery_customer':       // 発送先（受注用。customer_id ⇒ customer_no, customer_name）
            case 'delivery_partner':        // 発送先（発注用。customer_id ⇒ customer_no, customer_name）
                $query = "
                select
                    customer_id as id
                    ,customer_no as show
                    ,customer_name as subtext
                    ,address1
                    ,address2
                from
                    customer_master
                [Where]
                    /* delivery_partnerのとき、サプライヤーだけでなく得意先も含めることに注意。注文品を得意先に直送することがあるため。ag.cgi?page=ProjectDocView&pid=1574&did=221611 */
                    classification in (" . ($category == "delivery_partner" ? "1," : "") . "0,2)
                    and not coalesce(customer_master.end_customer, false) -- 非表示取引先は表示しない
                    " . ($search == "" ? "" : self::_getSearchCondition(array('customer_no', 'customer_name', 'address1', 'address2'), $matchBox, $search)) . "
                    " . (is_numeric($selecterSearch) ? " and (customer_group_id_1 = {$selecterSearch} or customer_group_id_2 = {$selecterSearch} or customer_group_id_3 = {$selecterSearch})" : "") . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("取引先コード"), _g("取引先名"), _g("住所1"), _g("住所2"));
                $orderArray_noEscape = array(array("label" => _g("取引先コード"), "field" => "show", "desc" => false),
                    array("label" => _g("取引先名"), "field" => "subtext", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 100, 200, 200, 200);
                $hasNothingRow = true;
                $hasSubtext = true;
                // 絞り込みセレクタを設けるかどうか。trueのときは$selecterTitle と $selectOptionTags_noEscape が必要
                $hasSelecter = true;
                $selecterTitle = _g("取引先グループ");
                $selectOptionTags_noEscape = self::_getSelectOptionTags_noEscape_CustomerGroup($selecterSearch);
                $idToCodeQuery = "select customer_no as showtext, customer_name as subtext from customer_master where customer_id = {$id}";
                $codeColumn = "customer_no";
                // 新規登録ボタン、ダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Customer_Edit";
                $masterCode = "customer_no";
                $masterName = "customer_name";
                $masterCheckQuery = "select customer_id from customer_master where customer_no = '{$code}'";
                break;

            case 'partner':             // サプライヤー（customer_id ⇒ customer_no, customer_name）
            case 'partner_for_order':   // サプライヤー（「品目に関連付けられた取引先のみ」セレクタつき）
            case 'partner_for_location':// サプライヤー（ロケ用）
                if ($category == 'partner_for_order' && !is_numeric($selecterSearch2))
                    $selecterSearch2 = "0";

                // テキストボックス直接入力のときにはselecter2が無効になるようにする
                // ag.cgi?page=ProjectDocView&pid=1574&did=229483
                if ($isCodeToId) {
                    $selecterSearch2 = "1";
                }

                $query = "
                select
                    customer_id as id
                    ,max(customer_no) as show
                    ,max(customer_name) as subtext
                from
                    customer_master
                    " . ($selecterSearch2 == "0" && $param != "" ? " inner join item_order_master on customer_master.customer_id = item_order_master.order_user_id" : "") . "
                [Where]
                    classification = 1 
                    and not coalesce(customer_master.end_customer, false) -- 非表示取引先は表示しない
                    " . ($category == "partner_for_location" ? " and customer_id not in (select customer_id from location_master where customer_id is not null" . (is_numeric($param) ? " and customer_id <> {$param}" : "") . ")" : "") . "
                    " . ($search == "" ? "" : self::_getSearchCondition(array('customer_no', 'customer_name'), $matchBox, $search)) . "
                    " . (is_numeric($selecterSearch) ? " and (customer_group_id_1 = {$selecterSearch} or customer_group_id_2 = {$selecterSearch} or customer_group_id_3 = {$selecterSearch})" : "") . "
                    " . ($selecterSearch2 == "0" && $param != "" ? " and item_order_master.item_id in ({$param})" : "") . "
                group by
                    customer_id
                [Orderby]
                ";

                $titleArray = array("ID", _g("取引先コード"), _g("取引先名"));
                $orderArray_noEscape = array(array("label" => _g("取引先コード"), "field" => "show", "desc" => false),
                    array("label" => _g("取引先名"), "field" => "subtext", "desc" => false));
                $alignArray = array("left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 160, 260);
                $hasNothingRow = true;
                $hasSubtext = true;
                // 絞り込みセレクタを設けるかどうか。trueのときは$selecterTitle と $selectOptionTags_noEscape が必要
                $hasSelecter = true;
                $selecterTitle = _g("取引先グループ");
                $selectOptionTags_noEscape = self::_getSelectOptionTags_noEscape_CustomerGroup($selecterSearch);
                if ($category == 'partner_for_order') {
                    $hasSelecter2 = true;
                    $selecterTitle2 = "";
                    $selectOptionTags_noEscape2 = "<option value=0" . ($selecterSearch2 == '0' ? " selected" : "") . ">" . _g("品目に関連付けられた取引先のみ") . "</option><option value=1" . ($selecterSearch2 != "0" ? " selected" : "") . ">" . _g("すべての取引先") . "</option>";
                }
                $idToCodeQuery = "select customer_no as showtext, customer_name as subtext from customer_master where customer_id = {$id}";
                $codeColumn = "customer_no";
                // 新規登録ボタン、ダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Customer_Edit&classification=1";
                $masterCode = "customer_no";
                $masterName = "customer_name";
                $masterCheckQuery = "select customer_id from customer_master where customer_no ='{$code}'";
                $alertForMaster = _g("指定された取引先は「サプライヤー」ではないか、非表示なので使用できません。");
                break;

            case 'worker': // 従業員
                $query = "
                select
                    worker_id as id
                    ,worker_code as show
                    ,worker_name as subtext
                    ,section_name
                from
                    worker_master
                    left join section_master on worker_master.section_id = section_master.section_id
                [Where]
                    not coalesce(worker_master.end_worker, false) -- 退職は表示しない
                    " . ($search == "" ? "" : self::_getSearchCondition(array('worker_code', 'worker_name', 'section_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("従業員コード"), _g("従業員名"), _g("部門"));
                $orderArray_noEscape = array(array("label" => _g("従業員コード"), "field" => "show", "desc" => false),
                    array("label" => _g("従業員名"), "field" => "subtext", "desc" => false),
                    array("label" => _g("部門"), "field" => "section_name", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 100, 160, 160);
                $hasNothingRow = true;
                $hasSubtext = true;
                $hasSelecter = false;
                $idToCodeQuery = "select worker_code as showtext, worker_name as subtext from worker_master where worker_id = {$id}";
                $codeColumn = "worker_code";
                // 新規登録ボタン、およびダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ）を使用するカテゴリは以下の4行が必要。
                //  ※他の拡張DDにこのコードをコピーして使用する場合、該当マスタのeditクラスのコード項目にいくつかの処理が必要（Master_Item_Editのarrayの品目コード部を参照）
                $masterEditAction = "Master_Worker_Edit";
                $masterCode = "worker_code";
                $masterName = "worker_name";
                $masterCheckQuery = "select worker_id from worker_master where worker_code ='{$code}'";
                break;

            case 'manufacturing':    // 製造指示書（未完了のみ。order_detail_id ⇒ order_no）
                $query = "
                select
                    order_detail_id as id
                    ,order_no as show
                    ,item_code
                    ,item_name
                    ,round(order_detail_quantity," . GEN_DECIMAL_POINT_DROPDOWN . ")
                    ,seiban
                from
                    order_detail
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                [Where]
                    classification = 0
                    and (order_detail_completed = false or order_detail_completed is null)
                    " . ($search == "" ? "" : self::_getSearchCondition(array('order_no', 'item_code', 'item_name', 'seiban'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("オーダー番号"), _g("品目コード"), _g("品目名"), _g("数量"), _g("製番"));
                $orderArray_noEscape = array(array("label" => _g("オーダー番号"), "field" => "show", "desc" => false),
                    array("label" => _g("品目コード"), "field" => "item_code", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("製番"), "field" => "seiban", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "right", "center");
                $columnWidthArray = array(0, 0, 100, 130, 130, 50, 100);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select order_no as showtext from order_detail where order_detail_id = {$id}";
                $codeColumn = "order_no";
                break;

            case 'order_no':                // オーダー番号（注文書）（order_detail_id ⇒ order_no）
            case 'order_no_subcontract':    // オーダー番号（外製指示書）（order_detail_id ⇒ order_no）
                $query = "
                select
                    order_detail_id as id
                    ,order_no as show
                    ,customer_name
                    ,item_code
                    ,item_name
                    ,round(order_detail_quantity," . GEN_DECIMAL_POINT_DROPDOWN . ")
                    ,seiban
                from
                    order_detail
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    left join (select customer_id, customer_name from customer_master) as t_cust on order_header.partner_id = t_cust.customer_id
                [Where]
                    classification = " . ($category == "order_no" ? "1" : "2") . "
                    and (order_detail_completed = false or order_detail_completed is null)
                    " . ($search == "" ? "" : self::_getSearchCondition(array('order_no', 'customer_name', 'item_code', 'item_name', 'seiban'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("オーダー番号"), _g("注文先"), _g("品目コード"), _g("品目名"), _g("数量"), _g("製番"));
                $orderArray_noEscape = array(array("label" => _g("オーダー番号"), "field" => "show", "desc" => false),
                    array("label" => _g("注文先"), "field" => "customer_name", "desc" => false),
                    array("label" => _g("品目コード"), "field" => "item_code", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("製番"), "field" => "seiban", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left", "right", "center");
                $columnWidthArray = array(0, 0, 100, 100, 100, 100, 60, 100);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select order_no as showtext from order_detail where order_detail_id = {$id}";
                $codeColumn = "order_no";
                break;

            case 'order_id_for_user':    // 注文書（注文書番号）（order_detail_id のまま）
                $query = "
                select
                    order_id_for_user as id
                    ,order_id_for_user as show
                    ,order_date
                    ,customer_name
                from
                    order_header
                    left join (select customer_id, customer_name from customer_master) as t_cust on order_header.partner_id = t_cust.customer_id
                [Where]
                    classification <> 0
                    " . ($search == "" ? "" : self::_getSearchCondition(array('cast(order_id_for_user as text)', 'customer_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("注文書番号"), _g("発注日"), _g("注文先名"));
                $orderArray_noEscape = array(array("label" => _g("注文書番号"), "field" => "show", "desc" => false),
                    array("label" => _g("発注日"), "field" => "order_date", "desc" => false),
                    array("label" => _g("注文先名"), "field" => "customer_name", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 100, 100, 150);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select '{$id}' as showtext";
                $codeColumn = "order_id_for_user";
                break;

            case 'order_lot':    // 特定品目の受入済オーダー（製造指示書・注文書）
                $query = "
                select
                    order_detail.order_detail_id as id
                    ,order_detail.order_no as show
                    ,order_date
                    ,coalesce(achievement_date, accepted_date) as order_dead_line
                    ,round(order_detail_quantity," . GEN_DECIMAL_POINT_DROPDOWN . ")
                    ,order_detail.seiban
                from
                    order_detail
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    left join achievement on order_detail.order_detail_id = achievement.order_detail_id
                    left join accepted on order_detail.order_detail_id = accepted.order_detail_id
                    left join received_detail on order_detail.item_id = received_detail.item_id
                [Where]
                    (achievement_id is not null or accepted_id is not null)
                    " . ($search == "" ? "" : self::_getSearchCondition(array('order_no', 'seiban'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("オーダー番号"), _g("オーダー日"), _g("製造・受入日"), _g("数量"), _g("製番"));
                $orderArray_noEscape = array(array("label" => _g("オーダー番号"), "field" => "show", "desc" => false),
                    array("label" => _g("オーダー日"), "field" => "order_date", "desc" => false),
                    array("label" => _g("製造・受入日"), "field" => "order_dead_line", "desc" => false),
                    array("label" => _g("製番"), "field" => "seiban", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "right", "center");
                $columnWidthArray = array(0, 0, 100, 130, 130, 50, 100);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select order_no as showtext from order_detail where order_detail_id = {$id}";
                $codeColumn = "order_no";
                break;

            case 'received_header':    // 受注（received_header）
                $query = "
                select
                    received_header.received_header_id as id
                    ,max(received_number) as show
                    ,max(customer_received_number) as customer_received_number
                    ,max(customer_name) as customer_name
                    ,max(received_date) as received_date
                    ,max(detail_count) as detail_count
                from
                    received_header
                    -- 明細行数をカウントすると同時に、未納品の受注だけに制限するためのサブクエリ
                    inner join (
                        select
                            received_header_id
                            , count(*) as detail_count
                        from
                            received_detail
                        where (delivery_completed = false or delivery_completed is null)
                        group by received_header_id
                    ) as t_detail on received_header.received_header_id = t_detail.received_header_id
                    left join customer_master on received_header.customer_id = customer_master.customer_id
                    left join received_detail on received_header.received_header_id = received_detail.received_header_id
                    left join item_master on received_detail.item_id = item_master.item_id
                [Where]
                    received_header.guarantee_grade = 0
                        -- 品目コード・品目名でも検索できるようにする。 ag.cgi?page=ProjectDocView&pid=1389&did=123717
                    " . ($search == "" ? "" : self::_getSearchCondition(array('received_number', 'customer_received_number', 'customer_name', 'item_code', 'item_name'), $matchBox, $search)) . "
                group by
                    received_header.received_header_id
                [Orderby]
                ";

                $titleArray = array("ID", _g("受注番号"), _g("客先注番"), _g("得意先名"), _g("受注日"), _g("行数"));
                $orderArray_noEscape = array(array("label" => _g("受注番号"), "field" => "show", "desc" => false),
                    array("label" => _g("客先注番"), "field" => "customer_received_number", "desc" => false),
                    array("label" => _g("得意先名"), "field" => "customer_name", "desc" => false),
                    array("label" => _g("受注日"), "field" => "received_date", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left", "right");
                $columnWidthArray = array(0, 0, 100, 100, 150, 70, 30);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select received_number as showtext from received_header where received_header_id = {$id}";
                $codeColumn = "received_number";

                break;

            case 'received_detail':    // 受注（received_header, received_detail）
            case 'received_detail_customer':
                $query = "
                select
                    received_detail.received_detail_id as id
                    ,seiban as show
                    ,received_number
                    ,item_code
                    ,item_name
                    ,''
                    ,round(received_quantity," . GEN_DECIMAL_POINT_DROPDOWN . ") as received_quantity
                    ,round(coalesce(received_quantity,0)-coalesce(delivery_quantity,0)," . GEN_DECIMAL_POINT_DROPDOWN . ") as remained_quantity
                    ,seiban
                    ,dead_line
                from
                    received_detail
                    inner join received_header on received_header.received_header_id=received_detail.received_header_id
                    left join item_master on received_detail.item_id = item_master.item_id
                    -- where に customer_no が入ることがあるので
                    left join customer_master on received_header.customer_id = customer_master.customer_id
                    left join (
                        select
                            received_detail_id,
                            SUM(delivery_quantity) as delivery_quantity
                        from
                            delivery_detail
                        group by
                            received_detail_id
                    ) as T1 on received_detail.received_detail_id=T1.received_detail_id
                [Where]
                    (received_header.guarantee_grade = 0) AND (delivery_completed = false or delivery_completed is null)
                    " . ($category == "received_detail_customer" ? " and (customer_no='{$param}' or '{$param}'='')" : "") . "
                    " . ($search == "" ? "" : self::_getSearchCondition(array('seiban', 'received_number', 'item_code', 'item_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                // 品目名と数量の間に１カラム挟んで隙間をあけている
                $titleArray = array("ID", _g("受注製番"), _g("受注番号"), _g("品目コード"), _g("品目名"), "", _g("受注数"), _g("受注残"), _g("製番"), _g("受注納期"));
                $orderArray_noEscape = array(array("label" => _g("受注製番"), "field" => "show", "desc" => false),
                    array("label" => _g("受注番号"), "field" => "received_number", "desc" => false),
                    array("label" => "line_no", "field" => "line_no", "desc" => false), // non control
                    array("label" => _g("品目コード"), "field" => "item_code", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("製番"), "field" => "seiban", "desc" => false),
                    array("label" => _g("受注納期"), "field" => "dead_line", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left", "left", "right", "right", "center", "center");
                $columnWidthArray = array(0, 0, 90, 90, 90, 120, 5, 60, 60, 90, 90);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "
                select
                    seiban as showtext
                from
                    received_detail
                    inner join received_header on received_header.received_header_id=received_detail.received_header_id
                where
                    received_detail_id = {$id}
                ";
                $codeColumn = "seiban";

                break;

            case 'estimate_header':    // 見積（estimate_header）
                $query = "
                select
                    estimate_header.estimate_header_id as id
                    ,max(estimate_number) as show
                    ,max(customer_master.customer_name) as customer_name
                    ,max(estimate_date) as estimate_date
                    ,max(detail_count) as detail_count
                from
                    estimate_header
                    -- 明細行数をカウントするとともに、品目IDが記録されている行のみに限定
                    inner join (
                        select
                            estimate_header_id
                            ,count(*) as detail_count
                        from
                            estimate_detail
                        where
                            item_id is not null
                        group by
                            estimate_header_id
                    ) as t_detail on estimate_header.estimate_header_id = t_detail.estimate_header_id
                    left join received_header on estimate_header.estimate_header_id = received_header.estimate_header_id
                    left join customer_master on estimate_header.customer_id = customer_master.customer_id
                    left join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
                    left join item_master on estimate_detail.item_id = item_master.item_id
                [Where]
                    -- 取引先IDが記録されている見積のみ
                    estimate_header.customer_id is not null
                    -- 受注転記済みの見積は排除する
                    and received_header.received_header_id is null
                        -- 品目コード・品目名でも検索できるようにする。
                    " . ($search == "" ? "" : self::_getSearchCondition(array('estimate_number', 'customer_master.customer_name', 'item_master.item_code', 'item_master.item_name'), $matchBox, $search)) . "
                group by
                    estimate_header.estimate_header_id
                [Orderby]
                ";

                $titleArray = array("ID", _g("見積番号"), _g("得意先名"), _g("見積日"), _g("行数"));
                $orderArray_noEscape = array(array("label" => _g("見積番号"), "field" => "show", "desc" => false),
                    array("label" => _g("得意先名"), "field" => "customer_name", "desc" => false),
                    array("label" => _g("見積日"), "field" => "estimate_date", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "right");
                $columnWidthArray = array(0, 0, 100, 150, 70, 30);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select estimate_number as showtext from estimate_header where estimate_header_id = {$id}";
                $codeColumn = "estimate_number";

                break;

            case 'bill_number_each':    // 請求書（都度請求のみ）
                $params = explode(";", $param);
                
                $query = "
                select
                    bill_header.bill_header_id as id
                    ,bill_header.bill_number as show
                    ,max(customer_master.customer_no) as customer_no
                    ,max(customer_master.customer_name) as customer_name
                    ,case when max(bill_header.foreign_currency_id) is null then coalesce(max(bill_header.bill_amount),0) - coalesce(sum(paying_in.amount),0)
                        else coalesce(max(bill_header.foreign_currency_bill_amount),0) - coalesce(sum(paying_in.foreign_currency_amount),0) end as bill_remained
                    ,case when max(bill_header.foreign_currency_id) is null then coalesce(max(bill_header.bill_amount),0) else coalesce(sum(paying_in.foreign_currency_amount),0) end as bill_amount
                from
                    bill_header
                    left join customer_master on bill_header.customer_id = customer_master.customer_id
                    left join paying_in on bill_header.bill_header_id = paying_in.bill_header_id
                [Where]
                    bill_header.bill_pattern = 2    -- 都度請求のみ
                    
                    " . ($search == "" ? "" : self::_getSearchCondition(array('bill_number', 'customer_no', 'customer_name'), $matchBox, $search)) . "
                    " . (isset($params[0]) && is_numeric($params[0]) ? " and bill_header.customer_id = '{$params[0]}'" : " and 1=0") . "
                    " . (isset($params[1]) && is_numeric($params[1]) ? " and paying_in_id <> '{$params[1]}'" : "") . "
                group by
                    bill_header.bill_header_id
                [Orderby]
                 ";

                $titleArray = array("ID", _g("請求書番号"), _g("得意先コード"), _g("得意先名"), _g("請求残額"), _g("請求金額"));
                $orderArray_noEscape = array(array("label" => _g("請求書番号"), "field" => "show", "desc" => false),
                    array("label" => _g("得意先コード"), "field" => "customer_no", "desc" => false),
                    array("label" => _g("得意先名"), "field" => "customer_name", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "right", "right");
                $columnWidthArray = array(0, 0, 100, 100, 150, 90, 90);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select bill_number as showtext from bill_header where bill_header_id = {$id}";
                $codeColumn = "bill_number";

                break;

            case 'stock':    // 在庫　（理論在庫が0以外の在庫レコード。ロケ間移動用）
                Logic_Stock::createTempStockTable("", null, null, null, null, true, true, false);

                $query = "
                select
                    cast(temp_stock.item_id as text) || '_' || temp_stock.seiban || '_' || cast(temp_stock.location_id as text)
                        || '_' || cast(temp_stock.lot_id as text) as id
                    ,item_code as show
                    ,item_name as subtext
                    ,case when temp_stock.seiban='' then '(" . _g("なし") . ")' else temp_stock.seiban end as seiban
                    ,location_name
                    ,temp_stock.logical_stock_quantity
                    ,temp_stock.available_stock_quantity
                from
                    temp_stock
                    inner join item_master on temp_stock.item_id = item_master.item_id
                    left join location_master on temp_stock.location_id = location_master.location_id
                [Where]
                    logical_stock_quantity <> 0
                    " . ($search == "" ? "" : self::_getSearchCondition(array('seiban', 'item_code', 'item_name', 'location_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("品目コード"), _g("品目名"), _g("製番"), _g("ロケーション"), _g("在庫"), _g("有効在庫"));
                $orderArray_noEscape = array(array("label" => _g("品目コード"), "field" => "show", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("製番"), "field" => "seiban", "desc" => false),
                    array("label" => _g("ロケーション"), "field" => "location_name", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left", "right", "right");
                $columnWidthArray = array(0, 0, 110, 130, 100, 80, 70, 70);
                $hasNothingRow = false;
                $hasSubtext = true;
                $hasSelecter = false;
                $arr = explode("_", $id);
                $itemId = (isset($arr[0]) && is_numeric($arr[0]) ? $arr[0] : 'null');
                $idToCodeQuery = "select item_code as showtext, item_name as subtext from item_master where item_id = '{$itemId}'";
                $codeColumn = "";

                break;

            case 'seiban_stock':        // 製番品目在庫（製番引当元用）
            case 'seiban_stock_dist':   // 製番品目在庫（製番引当先用）
                Logic_Stock::createTempStockTable("", null, null, null, null, true, true, false);
                
                if ($category == "seiban_stock") {
                    // 引当元
                    // ドロップダウンseiban_stockで理論在庫0の製番も含まれるようになったため、ここで在庫0は除外するようにした。
                    // また理論在庫がなくても有効在庫があれば含めるようにした
                    $seibanStockWhere = " and (logical_stock_quantity<>0 or available_stock_quantity<>0)";
                } else {
                    // 引当先
                    // 引当元と品目が同じで、かつ製番がことなる在庫を表示。
                    // 引当元製番はテキストボックスから読み取るが、製番ナシの場合は「(なし)」と表示されているためreplaceで置換している。
                    $params = explode(";", $param);
                    if (isset($params[0]) && $params[0] != "" && isset($params[1]) && $params[1] != "") {
                        $seibanStockWhere = " and item_code='{$params[0]}' and temp_stock.seiban<>replace('{$params[0]}','" . _g('(なし)') . "','')";
                    } else {
                        $seibanStockWhere = " and 1 = 0";
                    }
                }

                $query = "
                select
                    cast(temp_stock.item_id as text) || '_' || temp_stock.seiban || '_' || cast(temp_stock.location_id as text)
                        || '_' || cast(temp_stock.lot_id as text) as id
                    ,case when temp_stock.seiban='' then '(" . _g("なし") . ")' else temp_stock.seiban end as show
                    ,item_code
                    ,item_name
                    ,location_name
                    ,temp_stock.logical_stock_quantity
                    ,temp_stock.available_stock_quantity
                from
                    temp_stock
                    inner join item_master on temp_stock.item_id = item_master.item_id
                    left join location_master on temp_stock.location_id = location_master.location_id
                    " . ($category == "seiban_stock" && $selecterSearch == "1" ? "inner join received_detail on temp_stock.seiban = received_detail.seiban and received_detail.seiban <> '' and received_detail.delivery_completed" : "") . "
                [Where]
                    order_class in (0,2)
                    {$seibanStockWhere}
                    " . ($search == "" ? "" : self::_getSearchCondition(array('temp_stock.seiban', 'item_code', 'item_name', 'location_name'), $matchBox, $search)) . "
                    " . ($category == "seiban_stock" && $selecterSearch == "1" ? " and logical_stock_quantity > 0 " : "") . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("製番"), _g("品目コード"), _g("品目名"), _g("ロケーション"), _g("理論在庫"), _g("有効在庫"));
                $orderArray_noEscape = array(array("label" => _g("製番"), "field" => "show", "desc" => false),
                    array("label" => _g("品目コード"), "field" => "item_code", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("ロケーション"), "field" => "location_name", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "left", "right", "right");
                $columnWidthArray = array(0, 0, 100, 100, 100, 100, 70, 70);
                $hasNothingRow = false;
                $hasSubtext = false;
                if ($category == "seiban_stock") {
                    $hasSelecter = true;
                    $selecterTitle = "";
                    $selectOptionTags_noEscape = "<option value=''" . ($selecterSearch != '1' ? " selected" : "") . ">(" . _g("すべて") . ")</option><option value='1'" . ($selecterSearch == '1' ? " selected" : "") . ">" . _g("納品済製番のみ") . "</option>"; 
                } else {
                    $hasSelecter = false;
                }
                $arr = explode("_", $id);
                if (!isset($arr[1])) {
                    $arr[1] = '';
                } else {
                    $code = $gen_db->quoteParam($arr[1]);
                }
                $idToCodeQuery = "select '{$code}' as showtext";
                $codeColumn = "";

                break;

            case 'lot':    // ロット
                $query = "
                select
                    lot_id as id
                    ,lot_no as show
                    ,item_code
                    ,item_name
                from
                    lot_master
                    inner join item_master on lot_master.item_id = item_master.item_id
                [Where]
                    1=1
                    " . ($search == "" ? "" : self::_getSearchCondition(array('lot_no', 'item_code', 'item_name', 'location_name'), $matchBox, $search)) . "
                order by show
                ";

                $titleArray = array("ID", _g("ロット番号"), _g("品目コード"), _g("品目名"));
                $alignArray = array("left", "left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 100, 100, 100);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = false;
                $idToCodeQuery = "select lot_no as showtext from lot_master where lot_id = {$id}";
                $codeColumn = "lot_no";

                break;

            case 'received_price':    // 受注単価
                $params = explode(";", $param);
                
                $query = "
                select
                    product_price as id
                    ,product_price as show
                    ,received_date
                    ,received_number
                    ,customer_name
                    ,received_quantity
                from
                    received_detail
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join customer_master on received_header.customer_id = customer_master.customer_id
                [Where]
                    1=1
                    " . ($search == "" ? "" : self::_getSearchCondition(array('received_number', 'customer_name'), $matchBox, $search)) . "
                    " . ($selecterSearch == "0" && isset($params[0]) && is_numeric($params[0]) ? " and received_header.customer_id = '{$params[0]}'" : "") . "
                    " . (isset($params[1]) && is_numeric($params[1]) ? " and item_id = '{$params[1]}'" : "") . "
                order by
                    received_date desc, received_number desc
                ";
                $titleArray = array("ID", _g("受注単価"), _g("受注日"), _g("受注番号"), _g("得意先"), _g("数量"));
                $alignArray = array("left", "left", "right", "center", "left", "left", "left");
                $columnWidthArray = array(0, 0, 70, 100, 80, 180, 70);
                $hasNothingRow = false;
                $hasSubtext = false;
                $hasSelecter = true;
                $selecterTitle = "";
                $selectOptionTags_noEscape = "<option value=0" . ($selecterSearch == "0" ? " selected" : "") . ">" . _g("現在の得意先のみ") . "</option><option value=1" . ($selecterSearch != "0" ? " selected" : "") . ">" . _g("すべての得意先") . "</option>";
                $idToCodeQuery = "select '{$id}' as showtext";
                $codeColumn = "[id]";   // code がそのままidになる
                $idConvert = true;      // hiddenに単価を埋め込む

                break;

            case 'order_price':    // 発注単価
                $params = explode(";", $param);

                $query = "
                select
                    item_price as id
                    ,item_price as show
                    ,order_date
                    ,order_id_for_user
                    ,order_no
                    ,customer_name
                    ,order_detail_quantity
                from
                    order_detail
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    inner join customer_master on order_header.partner_id = customer_master.customer_id
                [Where]
                    1=1
                    " . ($search == "" ? "" : self::_getSearchCondition(array('cast(order_id_for_user as text)', 'order_no', 'customer_name'), $matchBox, $search)) . "
                    " . ($selecterSearch == "0" && isset($params[0]) && is_numeric($params[0]) ? " and order_header.partner_id = '{$params[0]}'" : "") . "
                    " . (isset($params[1]) && is_numeric($params[1]) ? " and item_id = '{$params[1]}'" : "") . "
                order by
                    order_date desc, order_id_for_user desc, order_no desc
            ";
                $titleArray = array("ID", _g("発注単価"), _g("発注日"), _g("注文書番号"), _g("オーダー番号"), _g("発注先"), _g("数量"));
                $alignArray = array("left", "left", "right", "center", "left", "left", "left", "left");
                $columnWidthArray = array(0, 0, 70, 100, 80, 80, 150, 40);
                $hasNothingRow = false;
                $hasSubtext = false;
                $hasSelecter = true;
                $selecterTitle = "";
                $selectOptionTags_noEscape = "<option value=0" . ($selecterSearch == "0" ? " selected" : "") . ">" . _g("現在の発注先のみ") . "</option><option value=1" . ($selecterSearch != "0" ? " selected" : "") . ">" . _g("すべての発注先") . "</option>";
                $idToCodeQuery = "select '{$id}' as showtext";
                $codeColumn = "[id]";   // code がそのままidになる
                $idConvert = true;      // hiddenに単価を埋め込む

                break;

            case 'received_seiban':    // 受注製番（製造指示書・注文書・外製指示書での製番選択用）
                $query = "
                select
                    seiban as id
                    ,seiban as show
                    ,received_number
                    ,item_name
                    ,received_quantity
                    ,product_price
                    ,customer_name
                    ,received_date
                    ,dead_line
                from
                    received_header
                    inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                    inner join item_master on received_detail.item_id = item_master.item_id
                    inner join customer_master on received_header.customer_id = customer_master.customer_id
                [Where]
                    /* 製番品目で、確定受注のみ */
                    /* 以前は未完了の受注のみに限定していたが、15iでは完了受注も選べるようにした。ag.cgi?page=ProjectDocView&pid=1574&did=217672 */
                    order_class = 0 and guarantee_grade = 0
                    " . ($selecterSearch != "0" ? " and (delivery_completed = false or delivery_completed is null)" : "") . "
                    " . ($search == "" ? "" : self::_getSearchCondition(array('seiban', 'received_number', 'item_name', 'customer_name'), $matchBox, $search)) . "
                [Orderby]
                ";

                $titleArray = array("ID", _g("製番"), _g("受注番号"), _g("品目名"), _g("数量"), _g("受注単価"), _g("得意先名"), _g("受注日"), _g("受注納期"));
                $orderArray_noEscape = array(array("label" => _g("製番"), "field" => "show", "desc" => false),
                    array("label" => _g("受注番号"), "field" => "received_number", "desc" => false),
                    array("label" => _g("品目名"), "field" => "item_name", "desc" => false),
                    array("label" => _g("得意先名"), "field" => "customer_name", "desc" => false),
                    array("label" => _g("受注日"), "field" => "received_date", "desc" => false),
                    array("label" => _g("受注納期"), "field" => "dead_line", "desc" => false));
                $alignArray = array("left", "left", "left", "left", "left", "right", "right", "left", "left", "left");
                $columnWidthArray = array(0, 0, 100, 90, 150, 60, 60, 150, 80, 80);
                $hasNothingRow = true;
                $hasSubtext = false;
                $hasSelecter = true;
                $selecterTitle = "";
                $selectOptionTags_noEscape = "<option value=1" . ($selecterSearch != "0" ? " selected" : "") . ">" . _g("未完了の製番のみ") . "</option><option value=0" . ($selecterSearch == "0" ? " selected" : "") . ">" . _g("すべての製番") . "</option>";
                $idToCodeQuery = "select '{$id}' as showtext";
                $codeColumn = "[id]";   // code がそのままidになる

                break;

            case 'textHistory':      // 過去の登録内容
                $arr = explode(':', $param);
                $actionName = $arr[0];
                $columnName = $arr[1];

                // EditクラスからSQLを取得
                require_once(Gen_File::safetyPathForAction($actionName));
                $actionClass = new $actionName;
                $actionClass->setQueryParam($dummy);
                $query = $actionClass->selectQuery;
                // カスタム項目
                $actionNameArr = explode('_', $actionName);
                $classGroup = $actionNameArr[0] . '_' . $actionNameArr[1];
                $customColumnArr = Logic_CustomColumn::getCustomColumnParamByClassGroup($classGroup);
                if ($customColumnArr) {
                    $table = $customColumnArr[0];
                    $selectPos = stripos($query, 'select') + 6;
                    $originalQuery = $query;
                    $query = substr($originalQuery, 0, $selectPos) . " ";
                    foreach($customColumnArr[1] as $customCol => $customArr) {
                        // select listでワイルドカード指定されていたときのため、エイリアスをつけておく
                        $query .= "{$table}.{$customCol} as gen_custom_{$customCol},";
                    }
                    $query .= substr($originalQuery, $selectPos + 1);
                }
                
                unset($actionClass);
                $existLastUpdate = (strpos($query, 'as gen_last_update') !== FALSE);
                if ($selecterSearch != "1" && $selecterSearch != "2" && $selecterSearch != "3" && $existLastUpdate) {
                    // 最近登録した順
                    $orderby = "max(gen_last_update) desc,{$columnName}";
                } else if ($selecterSearch == "1") {
                    // 登録が多い順
                    $orderby = "count({$columnName}) desc,{$columnName}";
                } else if ($selecterSearch == "2") {
                    // ABC順（昇順） 
                    $orderby = "{$columnName}";
                } else {
                    // ABC順（降順）
                    $orderby = "{$columnName} desc";
                }
                $query = "
                select
                    {$columnName} as show
                from
                    (" . str_replace('[Where]', '', $query) . ") as t_dropdown_texthistory
                where
                    cast({$columnName} as text) <> ''
                    " . ($search == "" ? "" : self::_getSearchCondition(array($columnName), $matchBox, $search)) . "
                group by
                    {$columnName}
                order by
                    {$orderby}
                ";

                // その他のパラメータ
                $titleArray = array("ID", _g("過去の登録内容"));
                $alignArray = array("left", "left");
                $columnWidthArray = array(0, 400);
                $hasNothingRow = false;
                $hasSubtext = false;
                $hasSelecter = true;
                $selecterTitle = "";
                if ($existLastUpdate) {
                    $selectOptionTags_noEscape = "<option value=0" . ($selecterSearch != "1" && $selecterSearch != "2" ? " selected" : "") . ">" . _g("最近登録した順") . "</option>";
                }
                $selectOptionTags_noEscape .= "<option value=1" . ($selecterSearch == "1" ? " selected" : "") . ">" . _g("登録が多い順") . "</option>";
                $selectOptionTags_noEscape .= "<option value=2" . ($selecterSearch == "2" ? " selected" : "") . ">" . _g("ABC/五十音順(昇順)") . "</option>";
                $selectOptionTags_noEscape .= "<option value=3" . ($selecterSearch == "3" ? " selected" : "") . ">" . _g("ABC/五十音順(降順)") . "</option>";
                $idToCodeQuery = "select '{$id}' as showtext";
                $codeColumn = "[id]";   // code がそのままidになる

                break;

            default:
                throw new Exception("categoryが不正です。");    // 以前は$categoryを表示していたが、XSS対策のためやめた
        }

        return array(
            "query" => $query,
            "orderArray_noEscape" => $orderArray_noEscape,
            "titleArray" => $titleArray,
            "alignArray" => $alignArray,
            "columnWidthArray" => $columnWidthArray,
            "hasNothingRow" => $hasNothingRow,
            "hasSubtext" => $hasSubtext,
            "hasSelecter" => $hasSelecter,
            "selecterTitle" => $selecterTitle,
            "selectOptionTags_noEscape" => $selectOptionTags_noEscape,
            "hasSelecter2" => $hasSelecter2,
            "selecterTitle2" => $selecterTitle2,
            "selectOptionTags_noEscape2" => $selectOptionTags_noEscape2,
            "idToCodeQuery" => $idToCodeQuery,
            "codeColumn" => $codeColumn,
            "hasNewButton" => !($masterEditAction == ""),
            "masterEditAction" => $masterEditAction,
            "masterCode" => $masterCode,
            "masterName" => $masterName,
            "masterCheckQuery" => $masterCheckQuery,
            "idConvert" => $idConvert,
            "matchBox" => $isMatchBox,
            "alertForMaster" => isset($alertForMaster) ? $alertForMaster : "",
        );
    }

    //************************************************
    // 拡張Dropdownのテキストボックスに表示するテキストの取得（Base用）
    //************************************************
    // category と id を元に、showtext と subtext を取得する。
    // EditBase.class.php と ListBase.class.php において、Dropdownの表示値設定用に使用。
    //
    // 引数
    //  $category    （受取）カテゴリ
    //  $id          （受取）id
    //
    // 戻り （連想配列）
    //  showtext    （戻し）表示テキスト（品目コードなど）
    //  subtext     （戻し）サブテキスト（品目名など）
    //  hasSubtext  （戻し）サブテキストボックスを表示するかどうか


    static function getDropdownText($category, $id)
    {
        global $gen_db;

        $res = self::_getDropdownParam($category, $id, "", "", "", "", "", "", false);
        $query = $res['idToCodeQuery'];
        $showtext = "";
        $subtext = "";
        if ($query != "" && $id != '') {
            $qr = $gen_db->queryOneRowObject($query);
            $showtext = (isset($qr->showtext) ? $qr->showtext : "");
            $subtext = (isset($qr->subtext) ? $qr->subtext : "");
        }

        return array(
            "showtext" => $showtext,
            "subtext" => $subtext,
            "hasSubtext" => $res['hasSubtext'],
        );
    }

    //************************************************
    // 拡張Dropdownの内部に表示するレコードとパラメータの取得
    //************************************************
    // ドロップダウン表示クラス（Dropdown/Dropdown.class.php）で使用
    //
    // 引数
    //    $category         カテゴリ
    //    $param            パラメータ
    //    $offset           何件目から表示するか
    //    $source_control   拡張DropdownテキストボックスコントロールのID
    //    $search           検索条件
    //    $matchBox         検索マッチボックス
    //    $selecterSearch   絞り込みセレクタの値
    //    $selecterSearch2  絞り込みセレクタ2の値
    //
    // 戻り （連想配列）
    //    titleArray       見出し 配列
    //    alignArray       表示位置 配列
    //    columnWidthArray 列幅 配列
    //    width            全体の幅
    //    height           全体の高さ
    //    prevUrl          「前の○件」URL
    //    nextUrl          「次の○件」URL
    //    data             表示データ 2次元配列
    //    selectOptionTags_noEscape HTMLセレクタ用OPTIONタグ （いまのところcategoryがitemまたはitem_orderのときのみ）

    static function getDropdownData($category, $param, $offset, $source_control, $search, $matchBox, $selecterSearch, $selecterSearch2, $hideNew = false, $noOffsetLimit = false)
    {
        global $gen_db;
        
        // ソート順ユーザー設定取得
        $userId = Gen_Auth::getCurrentUserId();
        $query = "select orderby_field, orderby_desc from dropdown_info where user_id = '{$userId}' and category = '" . $gen_db->quoteParam($category) . "'";
        $ddInfoArr = $gen_db->queryOneRowObject($query);
        if (!$ddInfoArr || $ddInfoArr == null) {
            $orderby_field = "";
            $orderby_desc = "";
        } else {
            $orderby_field = $ddInfoArr->orderby_field;
            $orderby_desc = ($ddInfoArr->orderby_desc == "1" ? true : false);
        }

        // 拡張Dropdown情報取得
        $res = self::_getDropdownParam($category, "", $param, $search, $matchBox, $selecterSearch, $selecterSearch2, "", false);
        $query = str_replace('[Where]', "where", $res['query']);

        // ソート順ユーザー設定情報と拡張Dropdown情報を比較しソート条件を設定する
        $orderbyStr = "";
        $orderArray_noEscape = array();  // 拡張に備えて配列でデータ渡し
        if (isset($res['orderArray_noEscape']) && is_array($res['orderArray_noEscape'])) {
            $isFirst = true;
            $orderbyBefore = "";
            $orderbyAfter = "";
            foreach ($res['orderArray_noEscape'] as $row) {
                // ソート順リンク用
                if ($orderby_field == $row['field']) {
                    $descType = $orderby_desc;
                    $descLink = ($orderby_desc ? 0 : 1);    // 逆ソート順のリンクを作成
                    $colorType = "red";
                    $orderbyBefore = $row['field'] . ($descType ? " desc" : "");
                } else {
                    // ソート順ユーザー情報が存在しない、かつソート配列初期値はデフォルト値として認定。
                    if ($orderby_field == "" && $isFirst) {
                        $descType = $row['desc'];
                        $descLink = ($row['desc'] ? 0 : 1); // 逆ソート順のリンクを作成
                        $colorType = "red";
                    } else {
                        $descType = $row['desc'];
                        $descLink = ($row['desc'] ? 1 : 0); // 通常ソート順のリンクを作成
                        $colorType = "gray";
                    }
                    $orderbyAfter .= ($orderbyAfter != "" ? "," : "") . $row['field'] . ($descType ? " desc" : "");
                }
                $isFirst = false;

                // ソートリンク作成
                $orderby_url = "index.php?action=Dropdown_Dropdown" .
                        "&category={$category}" .
                        "&param=" . urlencode($param) .
                        "&search=" . urlencode($search) .
                        "&source_control={$source_control}" .
                        "&field={$row['field']}" .
                        "&desc={$descLink}" .
                        ($hideNew ? "&hide_new=true" : "");

                $orderArray_noEscape[$row['label']] = "&nbsp;<span onClick=\"location.href='" . h($orderby_url) . "'\"";
                $orderArray_noEscape[$row['label']] .= " style=\"color:" . h($colorType) . "; font-size:11px; cursor:hand; cursor:pointer;\">";
                $orderArray_noEscape[$row['label']] .= ($descType ? "▼" : "▲");
                $orderArray_noEscape[$row['label']] .= "</span>";
            }
            $orderbyStr = $orderbyBefore . ($orderbyBefore != "" && $orderbyAfter != "" ? "," : "") . $orderbyAfter;
            if ($orderbyStr != "")
                $orderbyStr = "order by {$orderbyStr}";
        }
        $query = str_replace('[Orderby]', $orderbyStr, $query);

        // データ数制限
        if (!$noOffsetLimit) {
            if (!is_numeric($offset)) {
                $offset = 0;
            }
            $query .= " offset $offset limit " . GEN_DROPDOWN_PER_PAGE;
        }

        // SQLを実行してデータを取得
        $data = $gen_db->getArray($query);

        // 件数が多いときのための「前の○件」「次の○件」リンクを作成する。
        $urlBase = "index.php?action=Dropdown_Dropdown" .
                "&category={$category}" .
                "&param=" . urlencode($param) .
                "&search=" . urlencode($search) .
                "&source_control={$source_control}" .
                ($hideNew ? "&hide_new=true" : "");
        // 絞り込みセレクタ用
        if (isset($selecterSearch)) {
            $urlBase .= "&selecterSearch={$selecterSearch}";
        }
        if (isset($selecterSearch2)) {
            $urlBase .= "&selecterSearch2={$selecterSearch2}";
        }

        if (count($data) < GEN_DROPDOWN_PER_PAGE) {
            $nextUrl = "";
        } else {
            $nextUrl = $urlBase . "&offset=" . ($offset + GEN_DROPDOWN_PER_PAGE);
        }

        if ($offset <= 0) {
            $prevUrl = "";
        } else if ($offset > GEN_DROPDOWN_PER_PAGE) {
            $prevUrl = $urlBase . "&offset=" . ($offset - GEN_DROPDOWN_PER_PAGE);
        } else {
            $prevUrl = $urlBase . "&offset=0";
        }

        // 幅（調整するときはいくつものDDで確認すること）
        $width = 0;
        foreach ($res['columnWidthArray'] as $col) {
            if ($col != 0)
                $width += $col + 2;     // 2はpaddingの分（padding未指定の場合、どのブラウザも自動的に左右に2ずつのpaddingが入る）
        }
        $width += 17;   // スクロールバーの分(11) + その他(6)

        // 高さ
        $height = 250;

        // 戻り
        return array(
            "titleArray" => $res['titleArray'],
            "orderArray_noEscape" => $orderArray_noEscape,
            "alignArray" => $res['alignArray'],
            "columnWidthArray" => $res['columnWidthArray'],
            "width" => $width,
            "height" => $height,
            "hasNothingRow" => $res['hasNothingRow'],
            "hasSelecter" => $res['hasSelecter'],
            "selecterTitle" => $res['selecterTitle'],
            "selectOptionTags_noEscape" => $res['selectOptionTags_noEscape'],
            "hasSelecter2" => $res['hasSelecter2'],
            "selecterTitle2" => $res['selecterTitle2'],
            "selectOptionTags_noEscape2" => $res['selectOptionTags_noEscape2'],
            "hasNewButton" => $res['hasNewButton'],
            "prevUrl" => $prevUrl,
            "nextUrl" => $nextUrl,
            "data" => $data,
            'matchBox' => $res['matchBox'],
        );
    }

    //************************************************
    // 拡張Dropdownのテキストボックスに入力されたcodeをidに変換
    //************************************************
    // category と code を受け取り、id と subtext(あれば) を返す
    // テキストボックスの入力内容からidを特定できない場合（seiban_stockなど）、
    // idとして-1を返すようにする（ちなみにid=0は「なし」）

    static function dropdownCodeToId($category, $code, $hiddenId, $param, $isDisableNew, &$id, &$idConvert, &$subtext, &$hasSubtext, &$afterScript)
    {
        global $gen_db;

        $res = self::_getDropdownParam($category, "", $param, "", "", "", "", $code, true);

        if ($res['codeColumn'] == "") {
            $id = -1;
            $subtext = "";
        } else if ($res['codeColumn'] == "[id]") {
            $id = $code;
            $subtext = "";
        } else {
            $query = str_replace("[Orderby]", "", str_replace("[Where]", "where {$res['codeColumn']} = '" . $gen_db->quoteParam($code) . "' and ", $res['query']));
            $obj = $gen_db->queryOneRowObject($query);
            $id = (isset($obj->id) ? $obj->id : null);
            if (isset($obj->subtext))
                $subtext = $obj->subtext;
        }
        $hasSubtext = $res['hasSubtext'];

        // マスタ系のカテゴリ（$res['masterXX']が指定されているカテゴリ）で、idが取得できなかったときの処理
        // ダイレクトマスタ登録（該当コードが存在しなかったとき、マスタ登録へ飛ぶ） 。新規登録ボタンでもこの仕組みを利用
        if ($res['masterCheckQuery'] != "" && ($id === null && $code != "")) {
            if ($isDisableNew || $gen_db->existRecord($res['masterCheckQuery'])) {
                // マスタにコードが存在するが、 メインクエリでは抽出されなかったとき。つまり、メインクエリのwhere条件と合致しなかったとき
                if ($res['alertForMaster'] == "")
                    $res['alertForMaster'] = _g("指定されたコードは、この項目では使用できません。");
                $afterScript = "alert('" . h($res['alertForMaster']) . "');document.getElementById('" . h($hiddenId) . "_show').focus();";
            } else {
                // マスタにコードが存在しないとき: ダイレクトマスタ登録へ
                // マスタ登録のアクセス権をチェック
                $arr = explode("_", $res['masterEditAction']);
                $classGroup = $arr[0] . "_" . $arr[1];
                $sessionRes = Gen_Auth::sessionCheck(strtolower($classGroup));
                if ($sessionRes == 2) {
                    // アクセス権あり：　ダイレクトマスタ登録へ
                    $afterScript = self::_getNoCodeScript($id, $code, $res['masterEditAction'], $res['masterCode'], $res['masterName'], $hiddenId, $afterScript);
                } else {
                    // アクセス権なし：
                    if ($code == "gen_dropdownNewRecordButton") {
                        // 拡張DD内新規ボタンから
                        $msg = _g("マスタ登録を行う権限がありません。");
                    } else {
                        // テキストボックスから
                        $msg = _g("指定されたコードは存在しません。（権限がないためマスタへの登録は行えません。）");
                    }
                    $afterScript = "alert('{$msg}');document.getElementById('" . h($hiddenId) . "_show').focus();";
                }
            }
        }
        
         // idの値を強制的にhiddenに変換するか（id値が-1の時、通常は変換されない）
        $idConvert = $res['idConvert'];
   }

    //  dropDownCodeToId() の補助。オーバーラップフレーム（マスタ随時登録用）を表示するスクリプト。
    static function _getNoCodeScript($id, $code, $action, $codeCol, $nameCol, $hiddenId, $afterScript)
    {
        $url = "index.php?action={$action}&gen_overlapFrame=true&gen_overlapCodeCol={$codeCol}";

        if ($code == "gen_dropdownNewRecordButton") {
            // 拡張DD内新規ボタンから
            $isNewButton = "t";
            $msg = "";                                        // 登録前メッセージはなし
            $url .= "&gen_dropdownNewRecordButton";
        } else {
            // テキストボックスから
            $isNewButton = "f";
            $msg = _g("指定されたコードはマスタに存在しません。マスタに新規登録しますか？");
            // 09iでは、入力された文字列をコードと名前のデフォルトとしていた。しかし間違ってマスタ登録にジャンプしてそのまま登録してしまう、
            // という事例が発生したため、名前は空欄とするようにした。
            $url .= "&{$codeCol}={$code}";
            //$url .= "&{$codeCol}={$code}&{$nameCol}={$code}";
        }

        // Editダイアログ（なければ作成）の上にiframeをかぶせてマスタ画面を表示。
        // f.close, f.elmUpdate はオーバーラップフレームを閉じた後に実行されるスクリプト（editmodal.tpl の
        //  閉じるボタンクリック部、および登録時に実行される overlapmodalclose.tpl から呼び出される）。
        //  onchange() は必ず小文字である必要がある（onChange() とすると動かない）
        return "
            if (document.getElementById('gen_parentFrame') == null) {
                if ('{$isNewButton}'=='t' || confirm('{$msg}')) {
                    url = '" . h($url) . "';
                    var frameOpenFlag = false;
                    var mf = $('#gen_modal_frame');
                    if (mf.length == 0) {
                        mf = parent.document.getElementById('gen_modal_frame');
                        if (mf == null) {
                            frameOpenFlag = true;
                            gen.modal.open('');
                            mf = $('#gen_modal_frame');
                        } else {
                            mf = $(mf);
                        }
                    }

                    var f = document.createElement('iframe');
                    f.id = 'gen_parentFrame';
                    f.name = 'gen_parentFrame';
                    f.style.position = 'absolute';
                    f.style.top ='0px';
                    f.style.left ='0px';
                    f.width = '100%';
                    f.height = '100%';
                    f.src = url;
                    f.elmUpdate = function(code) {var el=document.getElementById('" . h($hiddenId) . "_show');el.value=code;if (typeof(el.onfocus)=='function') el.onfocus();el.onchange()};  //onfocusは、placeholderがあったときにそれを消すため
                    f.close = function() {console.log('test1'); $(f).remove(); if (frameOpenFlag) gen.modal.close()};
                    mf.append(f);                        
                } else {
                    " . // ダイレクトマスタ登録に対応。このスクリプトはAjaxで送信されるため、そのまま埋め込んでもセキュリティ的に大丈夫
                    "{$afterScript};
                }
            }
        ";
    }

    // HTMLのセレクタ用OPTIONタグを取得
    static function _getSelectOptionTags_noEscape_ItemGroup($selectedId)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $res = $gen_db->getArray($query);

        $tag = "<option value=''>(" . _g("すべて") . ")</option>";
        if (is_array($res)) {
            foreach ($res as $row) {
                $tag .= "<option value='" . h($row['item_group_id']) . "'";
                if ($row['item_group_id'] == $selectedId) {
                    $tag .= " selected";
                }
                $tag .= ">" . h($row['item_group_name']) . "</option>";
            }
        }
        return $tag;
    }

    // HTMLのセレクタ用OPTIONタグを取得
    static function _getSelectOptionTags_noEscape_CustomerGroup($selectedId)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $res = $gen_db->getArray($query);

        $tag = "<option value=''>(" . _g("すべて") . ")</option>";
        if (is_array($res)) {
            foreach ($res as $row) {
                $tag .= "<option value='" . h($row['customer_group_id']) . "'";
                if ($row['customer_group_id'] == $selectedId) {
                    $tag .= " selected";
                }
                $tag .= ">" . h($row['customer_group_name']) . "</option>";
            }
        }
        return $tag;
    }

    // 検索条件をSQLのWhere文字列として取得
    static function _getSearchCondition($colArr, $matchBox, $search)
    {
        $search = str_replace('　', ' ', $search);    // 全角スペースを半角に
        $searchArr = explode(' ', $search);
        $res = '';
        foreach ($searchArr as $word) {
            $res .= ' and (';
            $isFirst = true;
            foreach ($colArr as $col) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    if (@$matchBox == 4 || @$matchBox == 5 || @$matchBox == 6) {
                        $res .= ' and ';
                    } else {
                        $res .= ' or ';
                    }
                }

                // エスケープ
                //    like では「_」「%」がワイルドカードとして扱われる
                $word = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $word));

                // 「$matchMode」はDropdown_Dropdownで定義
                switch (@$matchBox) {
                    case "1":   // 前方一致
                        $res .= "({$col} ilike '{$word}%')";
                        break;
                    case "2":   // 後方一致
                        $res .= "({$col} ilike '%{$word}')";
                        break;
                    case "3":   // 完全一致
                        $res .= "({$col} ilike '{$word}')";
                        break;
                    case "4":   // 含まない
                        $res .= "({$col} not ilike '%{$word}%')";
                        break;
                    case "5":   // で始まらない
                        $res .= "({$col} not ilike '{$word}%')";
                        break;
                    case "6":   // で終わらない
                        $res .= "({$col} not ilike '%{$word}')";
                        break;
                    case "9":   // 正規表現　-> 現在未使用。不正なパターンを指定されたときSQLエラーになる問題の対処が難しいため
                        $res .= "({$col} ~* '{$word}')";
                        break;
                    default:    // 部分一致（デフォルト）
                        // スペース区切りによるAND検索にも対応
                        $res .= "(cast({$col} as text) ilike '%{$word}%')";
                        break;
                }
            }
            $res .= ')';
        }
        return $res;
    }

    // SQLのWHERE部の組み立て補助
    // ListBaseにあるものとまったく同じ（エスケープ部を除き）
    static function _getMultiWordWhere($colArr, $search)
    {
        $search = str_replace('　', ' ', $search);    // 全角スペースを半角に
        $searchArr = explode(' ', $search);
        $res = '';
        foreach ($searchArr as $word) {
            $res .= ' and (';
            $isFirst = true;
            foreach ($colArr as $col) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $res .= ' or ';
                }
                // エスケープ
                //    like では「_」「%」がワイルドカードとして扱われる
                $word = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $word));

                $res .= "cast({$col} as text) ilike '%{$word}%'";
            }
            $res .= ')';
        }
        return $res;
    }

    /**
     * 拡張Dropdownのユーザー別ソート設定を登録
     *
     * @access  public
     * @param   text    $category   Dropdownカテゴリ
     * @param   text    $field      ソートカラム名
     * @param   int     $desc       ソート順 0:昇順 1:降順
     * @return  bool                成功ならtrue
     */
    static function entryDropdownUserData($category, $field, $desc)
    {
        global $gen_db;

        // カテゴリ未指定
        if (!isset($category) || strlen($category) == 0)
            return false;
        // ソートカラム名未指定
        if (!isset($field) || strlen($field) == 0)
            return false;
        // ソート順未指定
        if (!isset($desc) || !is_numeric($desc))
            return false;

        // user_id取得
        $userId = Gen_Auth::getCurrentUserId();

        // 既存のソート順情報を削除
        $query = "delete from dropdown_info where user_id = '{$userId}' and category = '{$category}'";
        $gen_db->query($query);

        // 不要ソート順情報を削除
        $query = "delete from dropdown_info where user_id not in (select user_id from user_master) and user_id<>-1";
        $gen_db->query($query);

        // ソート順個人設定
        $key = array("user_id" => $userId, "category" => $category);
        $data = array(
            "user_id" => $userId,
            "category" => $category,
            "orderby_field" => $field,
            "orderby_desc" => ($desc == "1" ? 1 : 0),
        );
        $gen_db->updateOrInsert("dropdown_info", $key, $data);

        return true;
    }

}
