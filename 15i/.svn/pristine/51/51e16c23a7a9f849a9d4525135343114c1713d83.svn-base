<?php

class Config_Background_Edit
{

    function execute(&$form)
    {
        global $gen_db;

        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("パティオ画像");

        // 最大画像列数
        $maxLine = 6;

        // 個人設定
        // マイセレクトの画像選択チェックは Config_Background_AjaxImageParam で処理される。
        $query = "select background_mode from user_master where user_id = '{$_SESSION['user_id']}'";
        $mode = $gen_db->queryOneValue($query);
        $form['gen_background_mode'] = isset($mode) && is_numeric($mode) ? $mode : 0;

        $themeArr = array();    // タグ配列

        if (GEN_IS_BACKGROUND) {
            // 画像ディレクトリ設定
            $dirArr = explode(";", BACKGROUND_IMAGE_DIR);
            if (isset($dirArr)) {
                foreach ($dirArr as $value) {
                    $path = BACKGROUND_IMAGE_PATH . $value;
                    // 指定のディレクトリをオープンし内容を取得
                    if (is_dir($path)) {
                        if ($dh = opendir($path)) {
                            $fileArr = array();
                            while (($file = readdir($dh)) !== false) {
                                // サムネイル画像を取得
                                if (preg_match("/-thumb.jpg/", $file)) {
                                    $fileArr[] = $file;
                                }
                            }
                            $tag = "";
                            $temp = "";
                            $i = 0;
                            if (count($fileArr) > 0) {
                                sort($fileArr);
                                foreach ($fileArr as $fileName) {
                                    $i++;
                                    $name = str_replace("-thumb.jpg", '', $fileName);
                                    $id = "check_{$value}_{$name}";
                                    $temp .= "<td style='width:25px;' align='center'><input type='checkbox' id='{$id}' name='{$id}' value='1'></td>";
                                    $temp .= "<td><img style='width:160px;height:100px;vertical-align:bottom;' src='" . BACKGROUND_IMAGE_URL . "{$value}/{$fileName}'></td>";
                                    if ($i % $maxLine == 0) {
                                        $tag .= "<tr>{$temp}</tr>";
                                        $temp = "";
                                        $i = 0;
                                    }
                                }
                            }
                            if ($i != 0 && $i < $maxLine) {
                                for ($j = $i; $j < $maxLine; $j++) {
                                    $temp .= "<td></td><td></td>";
                                }
                                $tag .= "<tr>{$temp}</tr>";
                            }
                            $themeArr[] = "<table border='1' cellspacing='0' cellpadding='0' style='font-weight: bold; background-color: #ffffff; border-style: solid; border-color: #696969; border-collapse: collapse;'>{$tag}</table><br>";
                        }
                    }
                }
            }
        }

        if (count($themeArr) > 0) {
            $form['data_table'] = implode($themeArr);
        } else {
            $form['data_table'] = '';
        }

        return 'config_background_edit.tpl';
    }

}
