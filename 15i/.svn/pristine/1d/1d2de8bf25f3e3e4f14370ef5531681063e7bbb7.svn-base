<?php

class Mobile_ShowError_PermissionError
{
    function execute(&$form)
    {
        $form['gen_message_noEscape'] = 
            _g("この画面にアクセスする権限がありません。詳細はシステム管理者にお問い合わせください。")
            ."<br>"
            ."<a href='index.php?action=Mobile_Home'>"._g("ホームへ")."</a>";
        
        return 'mobile_simplepage.tpl';
    }
}