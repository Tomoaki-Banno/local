<?php

class Dropdown_AjaxDropdownParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        $id = "";
        $subtext = "";

        // category と code から、id と subtext(あれば) を取得
        // デコードの際、2バイト文字だと余分なスペースが入ることがあるのでtrim。
        // したがって前後にスペースが入ったカテゴリ名やコードは使用不可。
        $category = trim(@$form['category']);
        $code = trim(urldecode(@$form['code']));
        // ダイレクトマスタ登録に対応するため値をセット
        $afterScript = @$form['afterScript'];
        $param = @$form['param'];
        $isDisableNew = isset($form['disableNew']);
        Logic_Dropdown::dropdownCodeToId($category, $code, @$form['hiddenId'], $param, $isDisableNew, $id, $idConvert, $subtext, $hasSubtext, $afterScript);

        // ロジック側でスクリプトが渡されたら、それをクライアント側指定のスクリプトより前に実行するようセットする。
        //  item で該当コードがなかったときの処理（マスタジャンプ）に使用している
        if ($afterScript != "") {
            // ダイレクトマスタ登録に対応
            $form['afterScript'] = $afterScript;
            //$form['afterScript'] = $afterScript . ";" . @$form['afterScript'];
        }

        return
            array(
                'hiddenId' => @$form['hiddenId'],
                'subtextId' => ($hasSubtext ? @$form['subtextId'] : ""),
                'id' => $id,
                'subtext' => $subtext,
                'afterScript' => @$form['afterScript'],
                'idConvert' => $idConvert,
            );
    }

}
