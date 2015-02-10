{include file="common_header.tpl"}
<script>
    {literal}
    $.event.add(window, "load", function() {
        onModeChange();
    });
    function onModeChange() {
        var mode = $("input[name='mode']:checked").val();
        if (mode=='1') {
            $("span.all_check").css("visibility", "visible");
            $("input:checkbox[name^='check_']").removeAttr('disabled');
            gen.ajax.connect('Config_Background_AjaxImageParam', {},
                function(j) {
                    if (j != '') {
                        var nameArr = j.image.split(';');
                        for (i=0; i<nameArr.length; i++) {
                            $('#check_'+nameArr[i]).prop({'checked':'checked'});
                        }
                    }
                });
        } else {
            $("span.all_check").css("visibility", "hidden");
            $("input:checkbox[name^='check_']").prop({'checked':false}).attr('disabled', 'disabled');
        }
    }
    function allChange(isCheck) {
        if (isCheck) {
            $("input:checkbox[name^='check_']").prop({'checked':'checked'});
        } else {
            $("input:checkbox[name^='check_']").prop({'checked':false});
        }
    }
    function onEntry() {
        var mode = $("input[name='mode']:checked").val();
        if (mode=='1') {
            var isCheck = false;
            $('*[name^=check_]').each(function() {
                if (this.checked) {
                    isCheck = true;
                }
            });
            if (!isCheck) {
                alert(_g("画像が選択されていません。"));
                return false;
            }
        }
        document.forms[0].submit();
    }
    {/literal}
</script>

<center>

<div style='height:10px'></div>

<table>
    <tr valign="top">
        <td align="center">
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div style='height:30px'></div>

<form name="form1" method="POST" action="index.php?action=Config_Background_Entry">
    {gen_reload}
    {if $form.error_msg!=''}<font color="red">{$form.error_msg|escape}</font><br><br>{/if}
    {if $form.result=='success'}
        <span style='background: #99ffcc'>{gen_tr}_g("パティオ画像を登録しました。"){/gen_tr}</span><br><br>
    {/if}
    {if $form.data_table!=''}
        <input type="button" onClick="javascript:onEntry()" value="{gen_tr}_g("登録"){/gen_tr}" style="width:150px">
        <br><br><br>
        <input type="radio" name="mode" value="0" onChange="onModeChange()" {if $form.gen_background_mode=='0'}checked{/if}> {gen_tr}_g("自動セレクト"){/gen_tr}&nbsp;&nbsp;
        <input type="radio" name="mode" value="1" onChange="onModeChange()" {if $form.gen_background_mode=='1'}checked{/if}> {gen_tr}_g("マイセレクト"){/gen_tr}&nbsp;&nbsp;
        <span id="all_check" name="all_check" class="all_check" style="visibility:hidden">
            <a style="color:black;font-size:11px" href="javascript:allChange(true)">{gen_tr}_g("全選択"){/gen_tr}</a>&nbsp;
            <a style="color:black;font-size:11px" href="javascript:allChange(false)">{gen_tr}_g("全解除"){/gen_tr}</a>
        </span>
        <br><br>
        {gen_tr}_g("画像の自動更新"){/gen_tr}
        <select name="slideshowSpeed">
            <option value='0'{if $smarty.session.gen_setting_user->slideshowSpeed=='0'} selected{/if}>({gen_tr}_g("なし"){/gen_tr})</option>
            <option value='10'{if $smarty.session.gen_setting_user->slideshowSpeed=='10'} selected{/if}>{gen_tr}_g("10秒"){/gen_tr}</option>
            <option value='20'{if $smarty.session.gen_setting_user->slideshowSpeed=='20'} selected{/if}>{gen_tr}_g("20秒"){/gen_tr}</option>
            <option value='30'{if $smarty.session.gen_setting_user->slideshowSpeed=='30' || $smarty.session.gen_setting_user->slideshowSpeed==''} selected{/if}>{gen_tr}_g("30秒"){/gen_tr}</option>
            <option value='60'{if $smarty.session.gen_setting_user->slideshowSpeed=='60'} selected{/if}>{gen_tr}_g("1分"){/gen_tr}</option>
            <option value='300'{if $smarty.session.gen_setting_user->slideshowSpeed=='300'} selected{/if}>{gen_tr}_g("5分"){/gen_tr}</option>
            <option value='1800'{if $smarty.session.gen_setting_user->slideshowSpeed=='1800'} selected{/if}>{gen_tr}_g("30分"){/gen_tr}</option>
            <option value='3600'{if $smarty.session.gen_setting_user->slideshowSpeed=='3600'} selected{/if}>{gen_tr}_g("1時間"){/gen_tr}</option>
        </select>
        <br><br><br>
        {$form.data_table}
        <br><br><br>
    {else}
        <span style='background: #99ffcc'>{gen_tr}_g("パティオ画像が設定されていません。"){/gen_tr}</span><br><br><br>
    {/if}
</form>
</center>

{include file="common_footer.tpl"}