<?php

class Menu_Map
{

    function execute(&$form)
    {
        $form['gen_pageTitle'] = _g("マップ");
        
        return 'menu_map.tpl';
    }
}