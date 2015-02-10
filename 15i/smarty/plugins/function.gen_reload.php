<?php

function smarty_function_gen_reload($params, &$smarty)
{
    require_once $smarty->_get_plugin_filepath('shared', 'escape_special_chars');

    global $_SESSION;

    //  リロード対策の方式を変更
    //  1. 同時に複数のリクエストIDを管理できるようにした。（同一ブラウザ複数ウィンドウへの対応）
    //      従来は発行済みのIDをひとつしかCookieに保存していなかったため、
    //      Edit画面を開く⇒別ウィンドウでEdit画面を開いて登録⇒元画面でも登録　という処理のとき、
    //      リロードチェックにひっかかってしまっていた。
    //      今回の変更でCookie配列に複数のIDを保存するようにしたため、上記のような場合でも問題なく登録できるようになった。
    //      使用済みIDは登録側（Gen_Reload）でCookieから削除している。
    //      登録が行われなかったときは不要IDが蓄積されていくが、次回ログイン時にクリアされる。
    //  2. リクエストIDとして、単純なカウントアップ値ではなくハッシュ値を使用するようにした。（CSRF対策）
    //      IDを外部から推測できない値にすることでCSRF対策になる。

    $reqId = sha1(uniqid(rand(), true));
    $_SESSION['gen_page_request_id'][] = $reqId;

    return "<input type=\"hidden\" name=\"gen_page_request_id\" value=\"{$reqId}\">";
}
