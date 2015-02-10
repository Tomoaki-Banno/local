{include file="common_header.tpl"}

{*************** Include Javascript and CSS ***************}

<link rel="stylesheet" type="text/css" href="css/object.css">

{*************** Javascript ***************}

<script type="text/javascript">
{literal}

    /*********************** 処理実行 ***********************/

    function doEntry() {
        var count = 0;
        var p = {};
        $('[name^=check_]').each(function() {
            if (this.checked) {
                p[this.name] = this.value;
                count++;
            }
        });
        if (count == 0) {
            alert("{/literal}{gen_tr}_g("登録するデフォルト値を選択してください。"){/gen_tr}{literal}");
            return false;
        } else {
            if (!confirm("{/literal}{gen_tr}_g("デフォルト値を登録してもよろしいですか？"){/gen_tr}{literal}")) {
                alert("{/literal}{gen_tr}_g("処理を中止します。"){/gen_tr}{literal}");
                return false;
            }
        }
        gen.ajax.connect('{/literal}{$form.ajaxAction}{literal}', p,
            function(j) {
                if (j.status == 'success') {
                    alert('{/literal}{gen_tr}_g("デフォルト値を登録しました。"){/gen_tr}{literal}');
                } else {
                    alert('{/literal}{gen_tr}_g("処理に失敗しました。"){/gen_tr}{literal}');
                }
                location.reload();
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
<div id="main" align='center'>
<table width="800">
    <tr style="height: 7px"><td></td></tr>
    <tr>
        <td align="center">
            <div class="listTitle">
                {$form.gen_pageTitle}{* escapeしないこと *}
            </div>
        </td>
    </tr>
    <tr style="height: 30px"><td></td></tr>
</table>

<form name="form1" method="POST">
{ gen_reload }
<table border="0">
    <tr align="center">
        <td>
            <div id="msg">
            {$form.addMsg}<br><br>
            <table border="0">
                <tr>
                    <td width="500" style="padding: 20px; border: solid 1px #999999; background-color: {$form.bgColor}" valign="middle" align="center">
                        {if $form.titleMsg != ''}
                        <font color='#000000'><b>{$form.titleMsg}</b></font><br><br><br>
                        {/if}
                        <table border="1" style="border-collapse: collapse; border: 1px #999999 solid;">
                            <tr style="height: 27px;">
                                <td class='gen_cell' style="width: 50px; border: 1px #999999 solid; text-align: center;
                                    background: rgb(255,255,255);
                                    background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNkOGQ4ZDgiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
                                    background: -moz-linear-gradient(top,  rgb(255,255,255) 0%, rgb(216,216,216) 100%);
                                    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgb(255,255,255)), color-stop(100%,rgb(216,216,216)));
                                    background: -webkit-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -o-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -ms-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: linear-gradient(to bottom,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#d8d8d8',GradientType=0 );">
                                    {gen_tr}_g("登録"){/gen_tr}</td>
                                <td class='gen_cell' style="width: 100px; border: 1px #999999 solid; text-align: center;
                                    background: rgb(255,255,255);
                                    background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNkOGQ4ZDgiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
                                    background: -moz-linear-gradient(top,  rgb(255,255,255) 0%, rgb(216,216,216) 100%);
                                    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgb(255,255,255)), color-stop(100%,rgb(216,216,216)));
                                    background: -webkit-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -o-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -ms-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: linear-gradient(to bottom,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#d8d8d8',GradientType=0 );">
                                    {gen_tr}_g("施行日"){/gen_tr}</td>
                                <td class='gen_cell' style="width: 60px; border: 1px #999999 solid; text-align: center;
                                    background: rgb(255,255,255);
                                    background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNkOGQ4ZDgiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
                                    background: -moz-linear-gradient(top,  rgb(255,255,255) 0%, rgb(216,216,216) 100%);
                                    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgb(255,255,255)), color-stop(100%,rgb(216,216,216)));
                                    background: -webkit-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -o-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -ms-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: linear-gradient(to bottom,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#d8d8d8',GradientType=0 );">
                                    {gen_tr}_g("税率"){/gen_tr} (%)</td>
                                <td class='gen_cell' style="width: 270px; border: 1px #999999 solid; text-align: center;
                                    background: rgb(255,255,255);
                                    background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNkOGQ4ZDgiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
                                    background: -moz-linear-gradient(top,  rgb(255,255,255) 0%, rgb(216,216,216) 100%);
                                    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgb(255,255,255)), color-stop(100%,rgb(216,216,216)));
                                    background: -webkit-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -o-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: -ms-linear-gradient(top,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    background: linear-gradient(to bottom,  rgb(255,255,255) 0%,rgb(216,216,216) 100%);
                                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#d8d8d8',GradientType=0 );">
                                    {gen_tr}_g("税率備考"){/gen_tr}</td>
                            </tr>
                            {foreach from=$form.gen_taxRateArray item=item value=value name=icon}
                            <tr style="height: 25px;">
                                <td class='gen_cell' style="border: 1px #999999 solid; text-align: center; background: rgb(255,255,255);"><input type='checkbox' id="check_{$item.id|escape}" name="check_{$item.id|escape}" value='true'></td>
                                <td class='gen_cell' style="border: 1px #999999 solid; text-align: center; background: rgb(255,255,255);">{$item.date|escape}</td>
                                <td class='gen_cell' style="border: 1px #999999 solid; text-align: center; background: rgb(255,255,255);">{$item.value|escape}</td>
                                <td class='gen_cell' style="border: 1px #999999 solid; text-align: center; background: rgb(255,255,255);">{$item.remarks|escape}</td>
                            <tr>
                            {/foreach}
                        </table><br><br>
                        <input type="button" class="gen-button" value="{gen_tr}_g("消費税率を登録"){/gen_tr}" onClick="doEntry()">
                    </td>
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>
</form>

</div>
{include file="common_footer.tpl"}