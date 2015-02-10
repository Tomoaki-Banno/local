{include file="common_header.tpl"}

{*************** CSS ***************}
{literal}
<style TYPE="text/css">
<!--
#main {
    width: 100%;
    min-height: 640px;
}
.graph {
    position: relative;
    width: 300px;
    height: 30px;
    border: 1px solid #207870;
    padding: 0px;
}
.graph .bar {
    display: block;
    position: absolute;
    top: 0px;
    height: 100%;
    line-height: 250%;
    text-align: center;
    color: #f0ffff;
}
.bar1color {
    background: #6A99C7;
}
.bar2color {
    background: #95C6A8;
}
.bar3color {
    background: #D8CC74;
}
-->
</style>
{/literal}

{*************** Contents ***************}

<div id="main" align='center'>

    <div style='height:10px'></div>

    <table>
        <tr valign="top">
            <td align="center">
                <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
            </td>
        </tr>
    </table>

    <table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
        <tr><td height="30" colspan="6"></td></tr>

        <tr align='center'>
            <td width="10" height="60" colspan="6">
                <table style="text-align:left">
                    <tr valign="top">
                        <td>
                        {if $form.backupSize==''}
                            {gen_tr}_g("バックアップファイルサイズの取得に失敗しました。"){/gen_tr}
                        {else}
                            ■{gen_tr}_g("データストレージ使用量"){/gen_tr}
                            <div style='height:20px'></div>
                            <div class="graph">
                                <span class="bar bar1color" style="left: 0px; width: {$form.backupPercent|escape}%;">{$form.backupPercent|escape}%</span>
                            </div>                            
                            <table>
                                <tr>
                                    <td class="bar1color" width="15px"></td>
                                    <td>{gen_tr}_g("データサイズ"){/gen_tr}:</td>
                                    <td>{$form.backupSize|escape} MB / {$form.backupLimit|escape} MB ( {$form.backupPercent|escape} % )</td>
                                </tr>
                            </table>
                            {if $smarty.session.user_id == -1}
                                <br>({gen_tr}_g("adminのみ表示"){/gen_tr})
                                <br>{gen_tr}_g("最終バックアップ日時"){/gen_tr}：　{$form.lastBackupTime|escape}
                            {/if}
                        {/if}
                        </td>
                        <td width="30px"></td>
                        <td>
                            ■{gen_tr}_g("ファイルストレージ使用量"){/gen_tr}
                            <div style='height:20px'></div>
                            <div class="graph">
                                <span class="bar bar1color" style="left: 0px; width: {$form.uploadFilePercent|escape}%">{$form.uploadFilePercent|escape}%</span>
                                <span class="bar bar2color" style="left: {$form.uploadFilePercent|escape}%; width: {$form.chatFilePercent|escape}%">{$form.chatFilePercent|escape}%</span>
                                {math assign="bar3start" equation="a+b" a=$form.uploadFilePercent b=$form.chatFilePercent}
                                <span class="bar bar3color" style="left: {$bar3start|escape}%; width: {$form.itemImagePercent|escape}%">{$form.itemImagePercent|escape}%</span>
                            </div>                            
                            <table>
                                <tr>
                                    <td></td>
                                    <td>{gen_tr}_g("合計"){/gen_tr}:</td>
                                    <td>{$form.fileTotalSize|escape} MB / {$form.fileStorageSize|escape} MB ( {$form.fileTotalPercent|escape} % )</td>
                                </tr>
                                <tr>
                                    <td class="bar1color" width="15px"></td>
                                    <td>{gen_tr}_g("データ添付ファイル"){/gen_tr}:</td>
                                    <td>{$form.uploadFileSize|escape} MB</td>
                                </tr>
                                <tr>
                                    <td class="bar2color"></td>
                                    <td>{gen_tr}_g("トークボード添付ファイル"){/gen_tr}:</td>
                                    <td>{$form.chatFileSize|escape} MB</td>
                                </tr>
                                <tr>
                                    <td class="bar3color"></td>
                                    <td>{gen_tr}_g("品目画像"){/gen_tr}:</td>
                                    <td>{$form.itemImageFileSize|escape} MB</td>
                                </tr>
                                <tr style='height:50px'></tr>
                                <tr>
                                    <td colspan="3">{$form.adminMessage}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
                                
    <table border="0" cellspacing="0" cellpadding="0">
        <tr><td nowrap style="height: 25px;"><p></p></td></tr>
    </table>
</div>
                                    
{include file="common_footer.tpl"}