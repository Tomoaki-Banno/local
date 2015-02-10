{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

{*** YUI ***}
<script type="text/javascript" src="yui/treeview/treeview-min.js" ></script>
<link rel="stylesheet" type="text/css" href="yui/treeview/assets/skins/sam/treeview.css">

{*** Genesiss ***}
<script type="text/javascript" src="scripts/gen_script.js" ></script>
<script type="text/javascript" src="scripts/gen_master_bom.js" ></script>
<style TYPE="text/css">
.tree-dummyitem-class {literal}{ background-color:#ffff66; }{/literal}
.tree-enditem-class {literal}{ background-color:#cccccc; }{/literal}
</style>

{*************** Javascript ***************}

<script language="javascript">
    {literal}
    $(function () {
        var forms = {
            {/literal}
            gen_dropdown_perpage : {$form.gen_dropdown_perpage|escape},
            parent_item_code : '{$form.parent_item_code|escape}'
            {literal}
        }
        var msgs = {
            {/literal}
            seiban : '{gen_tr}_g("製番"){/gen_tr}',
            mrp : '{gen_tr}_g("MRP"){/gen_tr}',
            order : '{gen_tr}_g("発注"){/gen_tr}',
            subcontract : '{gen_tr}_g("外製(支給なし)"){/gen_tr}',
            inzu : '{gen_tr}_g("員数"){/gen_tr}',
            torisu : '{gen_tr}_g("取数"){/gen_tr}',
            updateInzu : '{gen_tr}_g("員数更新"){/gen_tr}',
            addArrow : '{gen_tr}_g("← 追加"){/gen_tr}',

            pleaseSelectParent : '{gen_tr}_g("親品目を選択してください。"){/gen_tr}',
            pleaseSelectItem : '{gen_tr}_g("品目を選択してください。"){/gen_tr}',
            pleaseSelectLike : '{gen_tr}_g("近似品目を選択してください。"){/gen_tr}',
            nowOnEdit : '{gen_tr}_g("構成の編集中です。[登録]か[元に戻す]を押すまで親品目を変更できません。"){/gen_tr}',
            registerd : '{gen_tr}_g("登録しました。"){/gen_tr}',
            registFail : '{gen_tr}_g("登録に失敗しました。"){/gen_tr}',
            registFailBom : '{gen_tr}_g("登録に失敗しました。構成ループが発生しているか、構成の階層が30を超えた可能性があります。"){/gen_tr}',
            outputBomToCsv : '{gen_tr}_g("構成表マスタをCSVファイルに出力します。"){/gen_tr}',
            outputBomToExcel : '{gen_tr}_g("構成表マスタをExcelファイルに出力します。"){/gen_tr}',
            outputChildOnly : '{gen_tr}_g("指定された親品目以下の構成のみ出力します。"){/gen_tr}',
            outputAll : '{gen_tr}_g("すべての品目を出力します。処理には時間がかかる場合があります。"){/gen_tr}',
            exportCountOver : '{gen_tr}_g("レコードの数が一度に出力可能な件数([MAX]件)を超えています。何番目のレコードから出力するかを指定してください。（指定したレコードから[MAX]件が出力されます。）"){/gen_tr}',
	    invalidInput : '{gen_tr}_g("入力が正しくありません。1以上の数値を指定してください。"){/gen_tr}',
            exportLimit : {$smarty.const.GEN_CSV_EXPORT_MAX_COUNT},
            excelLimit : {$smarty.const.GEN_EXCEL_EXPORT_MAX_COUNT},
            dataCount : {$form.gen_totalCount|escape},

            childAlert : '{gen_tr}_g("この品目の標準手配先は「class_name」です。子品目を登録しても、その情報が所要量計算・原価計算等に使用されることはありません。"){/gen_tr}',
            noCompAlert : '{gen_tr}_g("この品目に対する未完了の製造/外製指示があります。それらに構成の変更を反映させるには、製造/外製指示を再登録する必要があります。"){/gen_tr}',
            inzuAlert : '{gen_tr}_g("員数が正しくありません。"){/gen_tr}',

            noCompConfirm1 : '{gen_tr}_g("以下の製造/外製指示が未完了ですが、構成を変更してもよろしいですか？"){/gen_tr}',
            noCompConfirm2 : '{gen_tr}_g("※これらの製造/外製指示に対する実績/外製受入を登録する際、子品目の引き落としは製造/外製指示登録時の構成に基づいて行われます（今回の構成変更は反映されないことに注意してください）。今回の構成変更を反映させたい場合は、製造指示書/外製指示画面で再登録する必要があります。"){/gen_tr}',
            noCompConfirm3 : '{gen_tr}_g("オーダー番号:"){/gen_tr}',
            modeChangeConfirm : '{gen_tr}_g("表示モードを変更すると編集中の内容は破棄されます。よろしいですか？"){/gen_tr}',
            cancelConfirm : '{gen_tr}_g("編集中の内容を破棄して元に戻します。よろしいですか？"){/gen_tr}',
            copyConfirm : '{gen_tr}_g("現在の構成をクリアして、近似品目の構成をコピーします。よろしいですか？"){/gen_tr}',
            dummy : ''
            {literal}
        }
        initPage(forms, msgs);
    });

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
<div id="main" align='center'>
<div id="gen_page_div">    
<table border="0" width="1000">
    <tr style="height:7px"><td></td></tr>
    <tr>
        <td align="center">
            <div class="listTitle">
                <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{gen_tr}_g("構成表マスタ"){/gen_tr}｜</span>
            </div>
        </td>
    </tr>
    <tr style="height:10px"><td></td></tr>
</table>

<form name="form1" method="POST">
<table border="0" cellspacing="0" cellpadding="0">
    <tr align="left">
        <td width="210" rowspan="4" align="left" nowrap>
            <!-- 絞込み<input type="text" name="show_item_code" id="show_item_code"> -->
            <!-- <font size="1">（品目コードの一部。空欄なら全品目表示）</font> -->

            <input type="checkbox" id="reverseCheck" onClick="onReverseCheckChange()"> {gen_tr}_g("逆展開表示"){/gen_tr}
            <div id="reverseMsg" style="display:none; width:200px;"><font color="red">{gen_tr}_g("逆展開中です。上位階層が下に表示されています。"){/gen_tr}</font></div>

            <table cellspacing="0" cellpadding="0" width="200" height="490" style="border:solid #666666 1px;">
                <tr valign="top" align="left">
                    <td align="left">
                        {* treeDiv1に直接 overflow:scroll 指定をすると、横方向がスクロールではなく折り返しになってしまい表示が綺麗でないため、divを入れ子にしている *}
                        <div style="overflow:scroll; width:340px; height:490px;">
                            <div id="treeDiv1" style="width:1000px; text-align:left;"></div>
                        </div>
                    </td>
                </tr>
            </table>
            {gen_tr}_g("※グレーは非表示品目、イエローはダミー品目"){/gen_tr}
        </td>
        <td width="10" rowspan="4" align="left" nowrap></td>
        <td align="center" colspan="3">
            <table cellspacing="1" cellpadding="2" border="0" bgcolor="#A0A0A0" align="center">
                <tr bgcolor="#F6F6F6">
                    <td>
                        <table border="0">
                            <tr>
                                <td style="width:5px;"></td>
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr style="text-align:left">
                                            <td rowspan="2">{gen_tr}_g("親品目"){/gen_tr}&nbsp;</td>
                                            <td>
                                            <script>function parent_item_id_show_onchange() {literal}{onSelecterChange();}{/literal}</script>
                                            <input type="text" name="parent_item_id_show" id="parent_item_id_show" value="" size="15"
                                                onChange="gen.dropdown.onTextChange('item_forbom','parent_item_id_show','parent_item_id','parent_item_id_sub','onSelecterChange()','',true);" style='background-color:#e4f0fd'>
                                            <input type="button" class="mini_button" value="▼" id="parent_item_id_dropdown"
                                                onClick="gen.dropdown.show('parent_item_id_show','item_forbom','','','',true)">
                                            </td>
                                        </tr>
                                        <tr style="text-align:left">
                                            <td>
                                                <input type="text" name="parent_item_id_sub" id="parent_item_id_sub" value="" size="15" readonly style='background-color:#cccccc' tabindex=-1 >
                                                <input type="hidden" name="parent_item_id" id="parent_item_id" value="">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width:10px;"></td>
                                <td>
                                    <table>
                                        <tr>
                                            <td>{gen_tr}_g("管理区分"){/gen_tr}</td>
                                            <td><input type="text" name="order_class" id="order_class" size="12" style="background-color:#cccccc" readonly></td>
                                        </tr>
                                        <tr>
                                            <td>{gen_tr}_g("標準販売単価1"){/gen_tr}</td>
                                            <td><input type="text" name="default_selling_price" id="default_selling_price" size="12" style="background-color:#cccccc" readonly></td>
                                        </tr>
                                    </table>
                                </td>
                                <td>
                                    <table>
                                        <tr>
                                            <td>{gen_tr}_g("標準原価"){/gen_tr}</td>
                                            <td><input type="text" name="standard_base_cost" id="standard_base_cost" size="12" style="background-color:#cccccc" readonly></td>
                                        </tr>
                                        <tr>
                                            <td>{gen_tr}_g("ダミー品目"){/gen_tr}</td>
                                            <td><input type="text" name="dummy_item" id="dummy_item" size="12" style="background-color:#cccccc" readonly></td>
                                        </tr>
                                    </table>
                                </td>
                                {* 取数モードを実装したが使用中止。理由は ag.cgi?page=ProjectDocView&pid=676&did=52012 を参照。下記styleをはずせば動作する *}
                                <td style="display:none">
                                    {gen_tr}_g("数量表示"){/gen_tr}
                                    <select id="inzu_mode" onChange="onInzuModeChange()">
                                        <option value="inzu" {if $form.inzu_mode!='tori'}selected{/if}>{gen_tr}_g("員数"){/gen_tr}</option>
                                        <option value="tori" {if $form.inzu_mode=='tori'}selected{/if}>{gen_tr}_g("取数"){/gen_tr}</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td align="center" colspan="3" style="height:60px">
                <font color="red"><div id="red_message">&nbsp;</div></font>
                <font color="blue"><div id="message">&nbsp;</div></font>
                <div id="reg_message"></div>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="right">
                {if $form.gen_readonly == 'true'}
                <font color="red">{gen_tr}_g("登録を行う権限がありません。"){/gen_tr}</Font>
                {else}
                <table>
                    <tr>
                        <td>
                            <input type="button" class="gen-button" id="entryButton" value="    {gen_tr}_g("登録"){/gen_tr}    " onClick="entryData()">
                        </td>
                        <td width="10"></td>
                        <td>
                            <input type="button" class="gen-button" id="resetButton" value="{gen_tr}_g("元に戻す"){/gen_tr}" onClick="cancelButton()">
                        </td>
                    </tr>
                </table>
                {/if}
            </td>
            <td align="right">
                <table>
                    <tr>
                        <td align="right">
                            <input type="checkbox" id="exportNotAll" checked>{gen_tr}_g("選択された親品目以下の構成をエクスポート"){/gen_tr}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <a id='gen_importButton' href="javascript:gen.csvImport.showImportDialog('{$form.gen_csv_page_request_id|escape}', 'Master_Bom_List&gen_csvMode=Import', '{$form.gen_importMax|escape}', '{$form.gen_importMsg_noEscape}', '{$form.gen_importFromEncoding|escape}', '{$form.gen_allowUpdateCheck|escape}', '{$form.gen_allowUpdateLabel|escape}');">{gen_tr}_g("CSVインポート"){/gen_tr}</a>
                            &nbsp;&nbsp;
                            <a href="javascript:exportData(true);">{gen_tr}_g("CSVエクスポート"){/gen_tr}</a>
                            <br>
                            <a href="javascript:exportData(false);">{gen_tr}_g("エクセル出力"){/gen_tr}</a>
                            &nbsp;&nbsp;
                            <a href="javascript:exportData(false,true);">{gen_tr}_g("エクセル出力(ツリー形式)"){/gen_tr}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <tr align="center" valign="top" bgcolor="#d5ebff">
        <td width="350">
            {gen_tr}_g("登録されている品目"){/gen_tr}<br>{gen_tr}_g("（Ctrl+クリックで複数選択、Shift+クリックで範囲選択）"){/gen_tr}<br>
            <table>
                <tr>
                    <td>{gen_tr}_g("近似品目"){/gen_tr}</td>
                    <td>
                        <script>function copy_item_id_show_onchange() {literal}{}{/literal}</script>
                        <input type="text" name="copy_item_id_show" id="copy_item_id_show" value=""
                            style='width:150px; background-color:#ffffff'
                            onChange="gen.dropdown.onTextChange('item_forbom_copy','copy_item_id_show','copy_item_id','copy_item_id_sub','','',true);" style='background-color:#e4f0fd'>
                            {* 親品目と同じorder_classの品目だけを表示 *}
                        <input type="button" class="mini_button" value="▼" id="copy_item_id_dropdown"
                            onClick="gen.dropdown.show('copy_item_id_show','item_forbom_copy',gen.util.nz($('#parent_item_id').val()),'','',true)">
                        <input type="button" class="gen-button" style="width:80px" id="copyButton" value="{gen_tr}_g("構成コピー"){/gen_tr}" style="width:80px" onClick="onCopyButton()">
                        <input type="text" name="copy_item_id_sub" id="copy_item_id_sub" value="" size="0" readonly
                            style='width:0px; visibility:hidden; background-color:#cccccc' tabindex=-1 >
                        <input type="hidden" name="copy_item_id" id="copy_item_id" value="">
                    </td>
                </tr>
            </table>
            <div id="leftBoxDiv" style="overflow:scroll; width:340px; height:330px;">
                {if $form.gen_iPad}
                    <select name="leftBox" id="leftBox" label="test" size="30" multiple style="width:340px" onChange="onLeftBoxChange()">
                        <option value="">{gen_tr}_g("タップしてください。"){/gen_tr}</option>
                    </select>
                {else}
                    {* フォント指定に注目。桁そろえのため非プロポーショナルにすること。style heightは指定せずsize=30とする（理由はJSのinitPage参照）  *}
                    <select name="leftBox" id="leftBox" size="30" multiple style="width:1000px; font-family:'ＭＳ ゴシック'" onChange="onLeftBoxChange()">
                    </select>
                {/if}
            </div>
        </td>

        <td align="center">
            <div style='height:100px'></div>
            <input type="button" class="gen-button" style="width:80px" id="addButton" value="← {gen_tr}_g("追加"){/gen_tr}" onClick="onAddButton()" disabled><br>
            <input type="button" class="gen-button" style="width:80px" id="inzuUpdateButton" value="{gen_tr}_g("員数更新"){/gen_tr}" onClick="onInzuUpdateButton()" disabled><br>
            <span id='inzu_label'>{gen_tr}_g("員数"){/gen_tr}</span>
            <input type="text" name="quantity" id="quantity" style="width:40px; ime-mode:disabled;" onClick="this.select()"><br><br>
            <input type="button" class="gen-button" style="width:80px" id="deleteButton" value="{gen_tr}_g("削除"){/gen_tr} →" onClick="onDeleteButton()" disabled><br>
            <input type="button" class="gen-button" style="width:80px" id="allDeleteButton" value="{gen_tr}_g("全削除"){/gen_tr} →" onClick="onAllDeleteButton()" disabled>
            <div style='height:30px'></div>
            <input type="button" class="gen-button" id="upButton" value="↑" style="font-weight:bold;" onClick="onUpButton()" disabled>
            <input type="button" class="gen-button" id="downButton" value="↓" style="font-weight:bold;" onClick="onDownButton()" disabled>
        </td>

        <td width="350">
            {gen_tr}_g("登録可能な品目"){/gen_tr}<br>{gen_tr}_g("（Ctrl+クリックで複数選択、Shift+クリックで範囲選択）"){/gen_tr}<br>
            <table>
                <tr>
                    <td style='white-space:nowrap'>{gen_tr}_g("品目グループ"){/gen_tr}</td>
                    <td>
                        <select name="item_group_id" id="item_group_id" onChange="rightBoxReset()" style="width:220px;">
                        {html_options options=$form.option_item_group selected=$form.item_group_id}
                        </select>
                        {$form.gen_pin_html_itemgroup}
                    </td>
                </tr>
                <tr>
                    <td style='white-space:nowrap'>{gen_tr}_g("絞込み"){/gen_tr}</td>
                    <td>
                        <input type="text" name="searchText" id="searchText" value="{$form.searchText}" style="width:180px;">{$form.gen_pin_html_searchtext}
                        <input type="button" class="gen-button" value="{gen_tr}_g("再表示"){/gen_tr}" onClick="rightBoxReset()">
                    </td>
                </tr>
            </table>

            <div id="rightBoxDiv" style="overflow:scroll; width:340px; height:280px;">
                {if $form.gen_iPad}
                    <select name="rightBox" id="rightBox" multiple size="30" style="width:340px" onClick="onRightBoxClick()" onChange="onRightBoxChange()">
                        <option value="">{gen_tr}_g("タップしてください。"){/gen_tr}</option>
                    </select>
                {else}
                    {* フォント指定に注目。桁そろえのため非プロポーショナルにすること。style heightは指定せずsize=30とする（理由はJSのinitPage参照）  *}
                    <select name="rightBox" id="rightBox" multiple size="30" style="width:1000px; font-family:'ＭＳ ゴシック';" onClick="onRightBoxClick()" onMouseDown="onRightBoxMouseDown()" onMouseUp="onRightBoxMouseUp()" onChange="onRightBoxChange()">
                    </select>
                {/if}
            </div>
            <span id="rightBoxPrev" style="color:#cccccc"><< {gen_tr}_g("前の100件"){/gen_tr}</span>
            &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <span id="rightBoxNext" style="color:#cccccc">{gen_tr}_g("次の100件"){/gen_tr} >></span>
        </td>
    </tr>
</table>
</form>

{* ドラッグ用分身オブジェクト *}
<div id="ddObj2" style="z-index:10;position:absolute;width:200px;height:20px;background-color:#cccccc;visibility:hidden;">
</div>
{* 上の分身オブジェクトの下敷き。IE6のセレクタバグ対策 *}
<iframe id="ddObj" style="z-index:9;position:absolute;width:200px;height:20px;visibility:hidden;" src="javascript:false;">
</iframe>

</div>
</div>
{include file="common_footer.tpl"}
