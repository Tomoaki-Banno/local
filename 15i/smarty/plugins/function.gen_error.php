<?php

function smarty_function_gen_error($params, &$smarty)
{
    require_once $smarty->_get_plugin_filepath('shared', 'escape_special_chars');

    $_html_result = "<center>\n";
    if ($params['errorList'] != null) {
        foreach ($params['errorList'] as $error) {
            $_html_result .= "<font color=\"red\">・" . h($error) . "</font><BR>\n";
        }
        $_html_result .= "<br>\n";
    }
    $_html_result .= "</center>\n";

    return $_html_result;
}
