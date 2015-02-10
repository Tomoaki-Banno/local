<?php

class Config_Personalize_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_noSavedSearchCondition'] = true;

        $form['gen_searchControlArray'] = array(
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "select 1 from company_master ";    // ダミーSQL
        $this->orderbyDefault = '';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = 'issue.tpl';

        $form['gen_pageTitle'] = _g("パーソナライズ");
        $form['gen_pageHelp'] = _g("パーソナライズ");

        $form['gen_onLoad_noEscape'] = "onLoad()";

        $userId = Gen_Auth::getCurrentUserId();
        $query = "select user_id, user_name, case when user_id = -1 then '0' else user_name end as for_sort from user_master";
        if ($userId == -1) {
            $query .= " union all select -1 as user_id, 'admin' as user_name, '0' as for_sort";
        }
        $query .= " order by for_sort";
        $optionUser = $gen_db->getHtmlOptionArray($query, false);
        
        $copyUserSelectHtml = Gen_String::makeSelectHtml("copySrcUserId", $optionUser, $userId);
        $copyUserHtml = "<table>";
        foreach($optionUser as $optionUserId => $userName) {
            $copyUserHtml .= "<tr><td><input type='checkbox' id='copyUser{$optionUserId}' value='true'></td><td width='150px'>" . h($userName) . "</td></tr>";
        }
        $copyUserHtml .= "</table>";
        $exportUserSelectHtml = Gen_String::makeSelectHtml("exportUserId", $optionUser, $userId);
        
        $importUserHtml = "<table>";
        foreach($optionUser as $optionUserId => $userName) {
            $importUserHtml .= "<tr><td><input type='checkbox' id='importUser{$optionUserId}' value='true'></td><td width='150px'>" . h($userName) . "</td></tr>";
        }
        $importUserHtml .= "</table>";
        
        // 対象データについては Logic_Personalize を参照
        $form['gen_message_noEscape'] = "
            <center>
            <table border='0'>
                <tr align='center'>
                    <td>
                        <div id='msg'>
                        " . _g("以下のパーソナライズ設定をユーザー間でコピー、もしくはエクスポート/インポートします。") . "<br><br>
                        <table cellpadding=0 cellspacing=0>
                        <tr>
                            <td width='600px'>
                            ●" . _g("全体") . "<br>
                            " . _g("メニューバーの表示/非表示、マイメニュー、帳票テンプレートの選択状態、拡張ドロップダウンの並び順、コンパス") . "<br><br>
                            ●" . _g("リスト画面") . "<br>
                            " . _g("表示条件項目の表示/非表示、保存された表示条件、ピン、ソート、小計基準、列の表示/非表示、並び順、列幅、小数点以下桁数、桁区切り、寄せ、折返し、表示行数、クリックして明細行を開く") . "<br><br>
                            ●" . _g("編集画面") . "<br>
                            " . _g("項目の表示/非表示、並び順") . "<br><br>
                            " . _g("※トークボード、メモパッド、プロフィール画像、編集画面の明細リストの表示行数は対象外です。また、ユーザー別ではない情報（例：フィールド・クリエイター、ネーム・スイッチャー等）も対象外です。") . "<br>
                            " . _g("※一部の項目は再ログイン後に反映されます。") . "<br>
                            " . _g("※リスト画面・編集画面の設定は使用言語別に保存されます。") . "<br>
                            </td>
                        </tr>
                        </table>
                        <br>
                        <table>
                            <tr>
                                <td style='width:300px; position:relative; padding: 20px; border: solid 1px #999999;' valign='top' align='center'>
                                    <span style='color:#000; font-size:15px; font-weight:bold'>■" . _g("コピー") . "</span><br><br>
                                    <div id='copyMsgArea'></div>
                                    <input type='button' value='" . _g("パーソナライズデータのコピー") . "' onclick='doCopy()'><br><br>
                                    <table><tr><td>●" . _g("コピー元ユーザー") . " : </td><td>{$copyUserSelectHtml}</td></tr>
                                    <tr height='15px'></tr>
                                    <tr><td colspan='2'><table><tr><td>●" . _g("コピー先ユーザー") . "</td><td width='10px'></td><td><input type='checkbox' id='alterCheck_copy' onchange='alterCheck(true)'></td><td>" . _g("全チェック/全解除") . "</td></tr></table></td></tr>
                                    </table>
                                    {$copyUserHtml}
                                </td>
                                
                                <td width='10'></td>

                                <td style='width:300px; position:relative; padding: 20px; padding-bottom:50px; border: solid 1px #999999;' valign='top' align='center'>
                                    <span style='color:#000; font-size:15px; font-weight:bold'>■" . _g("エクスポート") . "</span><br><br>
                                    <input type='button' value='" . _g("パーソナライズデータのエクスポート") . "' onclick='doExport()'><br><br>
                                    <table><tr><td>●" . _g("ユーザー") . " : </td><td>{$exportUserSelectHtml}</td></tr></table><br>
                                </td>

                                <td width='10'></td>

                                <td style='width:400px; position:relative; padding: 20px; border: solid 1px #999999;' valign='top' align='center'>
                                    <span style='color:#000; font-size:15px; font-weight:bold'>■" . _g("インポート") . "</span><br><br>
                                    <div id='importMsgArea'></div>
                                    <div id='personalizeDataImport' style='width:400px'></div>
                                    <table width='250px'><tr><td>●" . _g("対象ユーザー") . "<td width='10px'></td><td><input type='checkbox' id='alterCheck_import' onchange='alterCheck(false)'></td><td>" . _g("全チェック/全解除") . "</td></tr></table>
                                    {$importUserHtml}
                                </td>
                            </tr>
                        </table>
                        </div>
                    </td>
                </tr>
            </table>
            </center>
        ";

        $form['gen_javascript_noEscape'] = "
            // ページロード
            function onLoad() {
                gen.fileUpload.init2('personalizeDataImport', 'index.php?action=Config_Personalize_Import&userId=[importUserId]', 'personalizeDataBeforeImport', 'personalizeDataImportCallback', '', '" . _g("パーソナライズデータのインポート") . "', 200, false);
            }
            
            function alterCheck(isCopy) {
                var pfx = (isCopy ? 'copy' : 'import');
                var checked = $('#alterCheck_' + pfx).is(':checked');
                $('[id^=' + pfx + 'User]').val(checked ? [true] : [false]);
            }
            
            function doCopy() {
                var ids = '';
                $('[id^=copyUser]').each(function(){
                    if ($(this).is(':checked')) {
                        if (ids != '') {
                            ids += ',';
                        }
                        ids += this.id.substr(8, this.id.length - 8);
                    }
                });
                if (ids == '') {
                    alert('" . _g("対象ユーザーを選択してください。") . "');
                    return false;
                }
                if (!confirm('" . _g("パーソナライズデータをコピーします。この操作を取り消すことはできません。コピー元およびコピー先ユーザーが正しいかどうかを確認してください。実行してもよろしいですか？") . "')) {
                    return false
                }
                
                document.body.style.cursor = 'wait';
                var p = {
                    srcUserId : $('#copySrcUserId').val(),
                    distUserId : ids,
                };
                gen.ajax.connect('Config_Personalize_AjaxCopy', p, 
                    function(j) {
                        var color = (j.result=='success' ? '99ffff' : 'ffcccc');
                        var html = \"<table width='100%'><tr><td bgcolor='#\" + color + \"' align='center'>\";
                        if (j.result=='success') {
                            html += \"" . _g("パーソナライズデータをコピーしました。") . "\";
                        } else {
                            html += j.msg;
                        }
                        html += '</td></tr></table>';
                        $('#copyMsgArea').html(html);
                        document.body.style.cursor = 'auto';
                    });
            }
            
            function doExport() {
                $('#importMsgArea').html('');
                location.href = 'index.php?action=Config_Personalize_Export&userId=' + $('#exportUserId').val();
            }
            
            function personalizeDataBeforeImport(files) {
                if (files.length == 0) {
                    alert('" . _g("ファイルを指定してください。") . "');
                    return false;
                }
                var nameArr = files[0].name.split('.');
                if (nameArr.length == 0) {
                    alert('" . _g("ファイルの形式が正しくありません。.gpdファイルを選択してください。") . "');
                    return false;
                }
                if (nameArr[nameArr.length-1] != 'gpd') {
                    alert('" . _g("ファイルの形式が正しくありません。.gpdファイルを選択してください。") . "');
                    return false;
                }
      
                $('#importUserId').remove();
                var ids = '';
                $('[id^=importUser]').each(function(){
                    if ($(this).is(':checked')) {
                        if (ids != '') {
                            ids += ',';
                        }
                        ids += this.id.substr(10, this.id.length - 10);
                    }
                });
                if (ids == '') {
                    alert('" . _g("対象ユーザーを選択してください。") . "');
                    return false;
                }
                $('body').after(\"<input type='hidden' id='importUserId' value='\" + ids + \"'>\");
                if (!confirm('" . _g("パーソナライズデータをインポートします。この操作を取り消すことはできません。ファイルおよび対象ユーザーが正しいかどうかを確認してください。インポートしてもよろしいですか？") . "')) {
                    return false
                }
                return true;
            }

            function personalizeDataImportCallback(res) {
                var color = (res.success ? '99ffff' : 'ffcccc');
                var html = \"<table width='100%'><tr><td bgcolor='#\" + color + \"' align='center'>\";
                if (res.success) {
                    html += \"" . _g("パーソナライズデータをインポートしました。") . "\";
                } else {
                    html += gen.util.escape(res.msg);
                }
                html += '</td></tr></table>';
                $('#importMsgArea').html(html);
            }
        ";
    }

}
