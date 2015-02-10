<?php
function smarty_block_gen_tr($params, $content, $smarty)
{
    if (is_null($content)) {    // 終端タグの場合
        return;
    }

    // 10iまでは単純に eval('$text = '.$content.';'); のようにしていたが、
    // これだともし{gen_tr}タグ内のgettext関数（「_g("")」）以外の箇所に、クライアントから
    // POSTされた値などを埋め込むような書き方をしてしまった場合に、脆弱性が発生することになる。
    // 例： {gen_tr}_g("..."){$form.value}{/gen_tr} のようにすると、&form=;phpinfo();
    //      がPOSTされた場合に phpinfo() がそのまま実行されてしまう。
    //      
    // それで、12iでは $content全体が_g("...") で囲われていることを前提に、
    // evalを使わずに全体を _() するようにした。
//    if ((substr($content,0,3) != "_(\"" && substr($content,0,3) != "_g('")
//            || (substr($content,-2) != "\")" && substr($content,-2) != "')")) {
//        throw new Exception("gen_trブロック内が_(...)で囲われていません。");
//    }
//    $text = _(substr($content,3,strlen($content)-5));
    if ((substr($content,0,4) != "_g(\"" && substr($content,0,4) != "_g('")
            || (substr($content,-2) != "\")" && substr($content,-2) != "')")) {
        throw new Exception("gen_trブロック内が_g(...)で囲われていません。");
    }
    $text = _g(substr($content,4,strlen($content)-6));
    
    if (@$params['name']!="") $text = sprintf($text, $params['name']);
    
    return $text;
}
?>
