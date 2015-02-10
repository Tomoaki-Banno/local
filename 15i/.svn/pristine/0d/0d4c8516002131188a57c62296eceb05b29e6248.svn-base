<?php

class Dropdown_ExcelDropdown
{

    function execute(&$form)
    {
        // category等 を元に、拡張Dropdownの属性と表示データを取得する。
        $res = Logic_Dropdown::getDropdownData(
                        @$form['category']  //    $category         カテゴリ
                        , @$form['param']   //    $param            パラメータ。カテゴリによっては必要（現在のところ受注単価でしか使っていない）
                        , @$form['offset']  //    $offset           何件目から表示するか
                        , null              //    $source_control   拡張DropdownテキストボックスコントロールのID
                        , @$form['search']  //    $search           検索条件
                        , null              //    $matchBox         検索マッチボックス
                        , null              //    $selecterSearch   絞り込みセレクタの値
                        , null              //    $selecterSearch2  絞り込みセレクタ2の値
        );

        $data = "";

        // 「show」が何列目であるか（Excelダイアログで行クリックしたときに、この列の値がセルに書き込まれる）
        $showColPos = 1;
        if (isset($res['data'][0])) {
            foreach ($res['data'][0] as $key => $val) {
                if ($key == "show") {
                    $data .= $showColPos;
                    break;
                }
                $showColPos++;
            }
            $data .= "[yy]";
        } else {
            $data .= "0[yy]";
        }
        // 1ページの最大行数
        $data .= GEN_DROPDOWN_PER_PAGE . "[yy]";

        // 列幅
        foreach ($res['columnWidthArray'] as $col) {
            $data .= $col . "[xx]";
        }
        $data .= "[yy]";

        // タイトル行
        foreach ($res['titleArray'] as $col) {
            $data .= $col . "[xx]";
        }
        $data .= "[yy]";

        // 「なし」行
        if (isset($form['offset']) && $form['offset'] > 0) {

        } else {
            $colPos = 1;
            if ($res['hasNothingRow']) {
                foreach ($res['titleArray'] as $col) {
                    if ($colPos == $showColPos) {
                        // show列は空欄（Excelダイアログで、なし行をクリックしたときにセルに空欄が書き込まれるように）
                        $data .= "[xx]";
                    } else {
                        $data .= _g("(なし)") . "[xx]";
                    }
                    $colPos++;
                }
                $data .= "[yy]";
            }
        }

        // データ行
        if (isset($res['data']) && $res['data'] != '') {
            foreach ($res['data'] as $row) {
                foreach ($row as $cell) {
                    $data .= $cell . "[xx]";
                }
                $data .= "[yy]";
            }
        }

        $form['response_noEscape'] = 'success:' . $data;

        return 'simple.tpl';
    }

}
