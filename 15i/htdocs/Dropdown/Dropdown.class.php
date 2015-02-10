<?php

class Dropdown_Dropdown
{

    function execute(&$form)
    {
        global $gen_db;
        
        // [gen_quot]の処理
        $form['param'] = str_replace("[gen_quot]", "'", @$form['param']);

        // ソート設定
        Logic_Dropdown::entryDropdownUserData(@$form['category'], @$form['field'], @$form['desc']);

        // ピン
        $genPins = array();
        $userId = Gen_Auth::getCurrentUserId();
        $action = "gen_pin_" . $form['category'];
        $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$userId}' and action = '{$action}'");
        // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
        if (($colInfoObj = json_decode(str_replace("￥", "\\", $colInfoJson))) != null) {
            foreach ($colInfoObj as $key => $val) {
                if (!isset($form[$key])) {    // 表示条件がユーザーにより指定された場合は読み出ししない
                    $form[$key] = $val;
                }
                $genPins[] = $key;
            }
        }

        // category等 を元に、拡張Dropdownの属性と表示データを取得する。
        $res = Logic_Dropdown::getDropdownData(
            @$form['category']          //    $category         カテゴリ
            , @$form['param']           //    $param            パラメータ。カテゴリによっては必要
            , @$form['offset']          //    $offset           何件目から表示するか
            , @$form['source_control']  //    $source_control   拡張DropdownテキストボックスコントロールのID
            , @$form['search']          //    $search           検索条件
            , @$form['matchBox']        //    $matchBox         検索マッチボックス
            , @$form['selecterSearch']  //    $selecterSearch   絞り込みセレクタの値
            , @$form['selecterSearch2'] //    $selecterSearch2  絞り込みセレクタ2の値
            , @$form['hide_new']        //    $hideNew          新規登録ボタンを強制的に非表示
        );
        
        // ピンHTML
        $form['gen_pin_html_search'] = Gen_String::makePinControl($genPins, $action, "search", "matchBox");
        if ($res['hasSelecter']) {
            $form['gen_pin_html_selecter'] = Gen_String::makePinControl($genPins, $action, "selecterSearch");
        }
        if ($res['hasSelecter2']) {
            $form['gen_pin_html_selecter2'] = Gen_String::makePinControl($genPins, $action, "selecterSearch2");
        }

        // 戻り値の準備
        $form['gen_dropdown_title'] = $res['titleArray'];
        $form['gen_dropdown_order_noEscape'] = $res['orderArray_noEscape'];
        $form['gen_dropdown_align'] = $res['alignArray'];
        $form['gen_dropdown_columnwidth'] = $res['columnWidthArray'];
        $form['gen_dropdown_width'] = $res['width'];
        $form['gen_dropdown_height'] = $res['height'];
        $form['gen_dropdown_hasnothingrow'] = $res['hasNothingRow'];
        $form['gen_dropdown_hasselecter'] = $res['hasSelecter'];
        $form['gen_dropdown_selecterTitle'] = $res['selecterTitle'];
        $form['gen_dropdown_selectoptiontags_noEscape'] = $res['selectOptionTags_noEscape'];
        $form['gen_dropdown_hasselecter2'] = $res['hasSelecter2'];
        $form['gen_dropdown_selecterTitle2'] = $res['selecterTitle2'];
        $form['gen_dropdown_selectoptiontags_noEscape2'] = $res['selectOptionTags_noEscape2'];
        $form['gen_dropdown_hasnewbutton'] = (isset($form['hide_new']) && $form['hide_new'] ? false : $res['hasNewButton']);
        $form['gen_dropdown_prevpageurl'] = $res['prevUrl'];
        $form['gen_dropdown_nextpageurl'] = $res['nextUrl'];
        $form['gen_dropdown_data'] = $res['data'];
        $form['gen_dropdown_matchBox'] = $res['matchBox'];

        $form['gen_dropdown_colcount'] = count($form['gen_dropdown_title']) - 1;
        $form['gen_dropdown_perpage'] = GEN_DROPDOWN_PER_PAGE;

        $headerHeight = 24;
        if ($res['hasSelecter'])
            $headerHeight += 24;
        if ($res['hasSelecter2'])
            $headerHeight += 24;
        $form['gen_dropdown_headerheight'] = $headerHeight;

        $scrollAreaHeight = ($form['gen_iPad'] ? 400 : $res['height']) - 54;
        if ($res['hasSelecter'])
            $scrollAreaHeight -= 21;
        if ($res['hasSelecter2'])
            $scrollAreaHeight -= 25;
        $form['gen_dropdown_scrollareaheight'] = $scrollAreaHeight;

        if (@$form['bom'] == 'true') {
            return 'master_bom_rightbox.tpl';
        } else {
            return 'dropdown.tpl';
        }
    }

}
