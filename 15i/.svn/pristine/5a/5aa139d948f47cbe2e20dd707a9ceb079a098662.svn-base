<?php

class Gen_Reload
{

    // 渡されたリクエストIDをチェックし、不正ならfalse、正常ならtrueを返す。
    //      詳細はsmarty_function_gen_reloadのコメントを参照。
    static function reloadCheck($requestId)
    {
        $key = array_search($requestId, $_SESSION['gen_page_request_id']);

        if ($key !== false) {
            // リクエストID正常
            unset($_SESSION['gen_page_request_id'][$key]); // CookieからIDを削除
            return true;
        } else {
            // リクエストID不正
            return false;
        }
    }

}