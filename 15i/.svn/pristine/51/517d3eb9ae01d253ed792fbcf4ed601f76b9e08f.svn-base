{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{if $form.gen_done == 'true'}
    alert('{gen_tr}_g("処理を実行しました。"){/gen_tr}');
{/if}

{literal}
    function AdminRestore() {
        {/literal}{if $form.gen_isServerInfo == 'true'}{literal}
            if (!confirm("{/literal}{gen_tr}_g("※※※ 要注意 ※※※\n製品版ジェネシスです。実行してもよろしいですか？"){/gen_tr}{literal}")) {
                alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
                return;
            }
        {/literal}{/if}{literal}
        location.href = "index.php?action=Config_AdminRestore_Restore";
    }

    function AllClear(isSampleClear) {
        {/literal}{if $form.gen_isServerInfo == 'true'}{literal}
            if (!confirm("{/literal}{gen_tr}_g("※※※ 要注意 ※※※\n製品版ジェネシスです。実行してもよろしいですか？"){/gen_tr}{literal}")) {
                alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
                return;
            }
        {/literal}{/if}{literal}

        var msg = '';
        if (isSampleClear) {
            msg = '{/literal}{gen_tr}_g("ユーザーマスタ以外のデータをすべてクリアします。ファイル類も削除されます。"){/gen_tr}{literal}';
        } else {
            msg = '{/literal}{gen_tr}_g("データをすべてクリアします。"){/gen_tr}{literal}';
        }
        msg += '\n{/literal}{gen_tr}_g("大変危険な処理なので、事前にバックアップを取っておくことをお勧めします。処理を実行してよろしいですか？"){/gen_tr}\n{gen_tr}_g("（完了すると自動的にログアウトします。）"){/gen_tr}{literal}';
        if (!confirm(msg)) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        location.href = "index.php?action=SystemUtility_AllClear_AllClear&gen_page_request_id=" + $('[name=gen_page_request_id]').val() + (isSampleClear ? "&sampledata" : "");
    }

    function DataClear(cat) {
        {/literal}{if $form.gen_isServerInfo == 'true'}{literal}
            if (!confirm("{/literal}{gen_tr}_g("※※※ 要注意 ※※※\n製品版ジェネシスです。実行してもよろしいですか？"){/gen_tr}{literal}")) {
                alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
                return;
            }
        {/literal}{/if}{literal}

        switch(cat) {
        case 1: text = "{/literal}{gen_tr}_g("品目マスタとすべてのトランザクションデータ"){/gen_tr}{literal}"; opt = "&item_master"; break;
        case 2: text = "{/literal}{gen_tr}_g("構成表マスタとすべてのトランザクションデータ"){/gen_tr}{literal}"; opt = "&bom_master"; break;
        case 3: text = "{/literal}{gen_tr}_g("admin専用のアクセスログとエラーログ"){/gen_tr}{literal}"; opt = "&log"; break;
        default: text = "{/literal}{gen_tr}_g("トランザクションデータ（マスタ・内部ログ以外のすべてのデータ）"){/gen_tr}{literal}"; opt = ""; break;
        }
        var res = confirm('{/literal}{gen_tr}_g("%sをクリアします。\n大変危険な処理なので、事前にバックアップを取っておくことをお勧めします。処理を実行してよろしいですか？"){/gen_tr}{literal}'.replace('%s',text));

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        location.href = "index.php?action=SystemUtility_DataClear_DataClear" + opt + "&gen_page_request_id=" + $('[name=gen_page_request_id]').val();
    }

    function AllColumnReset() {
        var res = confirm('{/literal}{gen_tr}_g("全ユーザーの表示設定（リスト表示条件・リスト・編集画面）を初期状態に戻します。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        location.href = "index.php?action=SystemUtility_AllColumnReset_AllColumnReset";
    }

    function Vacuum() {
        var res = confirm('{/literal}{gen_tr}_g("バキューム処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        location.href = "index.php?action=SystemUtility_Vacuum_Vacuum";
    }

    function MrpReset() {
        var res = confirm('{/literal}{gen_tr}_g("所要量計算の進捗状況をリセットします。処理を実行してよろしいですか？"){/gen_tr}{literal}');

        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }

        location.href = "index.php?action=Config_MrpReset_MrpReset";
    }

    function entryHoliday() {
        var res = confirm('{/literal}{gen_tr}_g("今日から20年後まで、土曜日・日曜日を休日登録します。処理を実行してよろしいですか？"){/gen_tr}{literal}');
        if (res != true) {
            alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
            return;
        }
        gen.ajax.connect('Master_Holiday_AjaxHolidayBulkEntry', {},
            function(j) {
                alert('{/literal}{gen_tr}_g("休日登録を行いました。"){/gen_tr}{literal}');
            });
    }

    function templateConvert() {
        if (!confirm("13i用の帳票テンプレートを15i用に変換します。実行してもよろしいですか？")) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxTemplateConvert13to15', {},
            function(j){
                $('#msg').html("<div style='width:400px;background-color:#D5EBFF'>テンプレートの変換を行いました。<br><br><div style='text-align:left'>" + j.msg.replace(/&lt;br&gt;/g, '<br>') + "</div></div>");
            });
    }

    function toggleDemoMode() {
        gen.ajax.connect('Config_Setting_AjaxDemoMode', {},
            function(j){
                document.getElementById('gen_demo_mode_show').innerHTML = gen.util.escape(j.state);
            });
    }

{/literal}
</script>

{*************** CSS ***************}
{literal}
<style TYPE="text/css">
<!--
#main {
    width: 100%;
    min-height: 640px;
}
-->
</style>
{/literal}

{*************** Contents ***************}
{ gen_reload }

<div id="main" align='center'>

<div style='height:10px'></div>

<table>
    <tr valign="top">
        <td align="center">
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div style='height:30px'></div>

<table width="800" border="0" cellspacing="2" cellpadding="4" bgcolor='#CCCCCC'>
    <tr bgcolor='#ffffff' align='center'>
        <td>
            <table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
                <tr><td height="30" colspan="6"></td></tr>

                <tr><td colspan="6" id="msg" align="center"></td></tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td>
                        <p><span style="color: #000000;">●{gen_tr}_g("設定確認・ログ解析"){/gen_tr}</span></p>
                    </td>
                    <td width="10" height="40" colspan="4"></td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminParam_Param"><img src="img/menu/submenu/config/log.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("パラメータ一覧"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminAccessLog_List"><img src="img/menu/submenu/config/alog.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("アクセスログ一覧"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminErrorLog_List"><img src="img/menu/submenu/config/alog.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("エラーログ一覧"){/gen_tr}</a></p>
                    </td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_CustomColumn_Edit"><img src="img/menu/submenu/config/log.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("フィールド・クリエイター一覧"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminPriceHistory_List"><img src="img/menu/submenu/config/alog.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("在庫評価単価一覧"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                    </td>
                </tr>

                <tr><td height="25" colspan="6"><div></div></td></tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td>
                        <p><span style="color: #000000;">●{gen_tr}_g("システム"){/gen_tr}</span></p>
                    </td>
                    <td width="10" height="40" colspan="4"></td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminBackup_Backup"><img src="img/menu/submenu/config/backup.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("バックアップ（ファイル取得）"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:AdminRestore()"><img src="img/menu/submenu/config/restore.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("読み込み（ファイルから）"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:Vacuum()"><img src="img/menu/submenu/config/dataclear.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("バキューム処理"){/gen_tr} </a></p>
                    </td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:MrpReset()"><img src="img/menu/submenu/config/dataclear.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("所要量計算リセット"){/gen_tr} ※1</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:entryHoliday()"><img src="img/menu/submenu/config/dataclear.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("休日一括登録"){/gen_tr} ※2</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:AllColumnReset()"><img src="img/menu/submenu/config/dataclear.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("表示設定のクリア"){/gen_tr} ※3</a></p>
                    </td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:templateConvert()"><img src="img/menu/submenu/config/user.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("テンプレート変換（13i⇒15i）"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminUser_AddUser"><img src="img/menu/submenu/config/user.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("ユーザー追加"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="index.php?action=Config_AdminTaxRate_TaxRate"><img src="img/menu/submenu/config/user.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("消費税率一括登録"){/gen_tr}</a></p>
                    </td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:toggleDemoMode()"><img src="img/menu/submenu/config/dataclear.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("デモモード："){/gen_tr}</a><span id='gen_demo_mode_show' style="font-weight: bold;">{if $smarty.session.gen_setting_user->demo_mode}on{else}off{/if}</span> ※4</p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                    </td>
                </tr>

                <tr><td height="25" colspan="6"><div></div></td></tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td>
                        <p><span style="color: #000000;">●{gen_tr}_g("データクリア"){/gen_tr}</span></p>
                    </td>
                    <td width="10" height="40" colspan="4"></td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:DataClear(0)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("トランデータクリア"){/gen_tr} ※5</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:DataClear(1)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("品目マスタ＆トランクリア"){/gen_tr}</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:DataClear(2)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("構成表マスタ＆トランクリア"){/gen_tr}</a></p>
                    </td>
                </tr>

                <tr>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:DataClear(3)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("内部ログクリア"){/gen_tr} ※6</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:AllClear(false)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("オールクリア"){/gen_tr} ※7</a></p>
                    </td>
                    <td width="15" height="40"></td>
                    <td width="215" nowrap>
                        <p><a href="javascript:AllClear(true)"><img src="img/menu/submenu/config/dataclear2.png" width="30" height="30" align="absmiddle" border="0"> {gen_tr}_g("サンプルデータクリア"){/gen_tr} ※8</a></p>
                    </td>
                </tr>

                <tr><td height="40" colspan="6"><div></div></td></tr>

                <tr align='left'>
                    <td width="15" height="40"></td>
                    <td colspan="5">
                    ※1 {gen_tr}_g("トラブル時用"){/gen_tr}<BR>
                    ※2 {gen_tr}_g("今日から20年後までの土日を休日登録"){/gen_tr}<BR>
                    ※3 {gen_tr}_g("全ユーザーの全画面の表示設定（リスト表示条件・リスト・編集画面）がリセットされる"){/gen_tr}<BR>
                    ※4 {gen_tr}_g("onのときはエラー表示が最小限になる。「デモモード」の文字をクリックするとon/offが切り替わる"){/gen_tr}<BR>
                    ※5 {gen_tr}_g("トランザクションデータ（マスタ・内部ログ以外のすべてのデータ）が削除される。危険なので注意"){/gen_tr}<BR>
                    ※6 {gen_tr}_g("admin専用のアクセスログ・エラーログが削除される。危険なので注意"){/gen_tr}<BR>
                    ※7 {gen_tr}_g("すべてのテーブル（company_master以外）がgen_queryに基づいて再作成される。S3/files_dirは削除されない。危険なので注意"){/gen_tr}<BR>
                    ※8 {gen_tr}_g("基本的にオールクリアと同じ。違いは、user_master/permission_master が削除されないことと、S3/files_dir内のファイルが削除される点"){/gen_tr}<BR>
                    </td>
                </tr>

                <tr><td height="40" colspan="6"><div></div></td></tr>
            </table>
        </td>
    </tr>
</table>

<table border="0" cellspacing="0" cellpadding="0">
    <tr><td nowrap style="height: 25px;"><p></p></td></tr>
</table>
</div>

{include file="common_footer.tpl"}