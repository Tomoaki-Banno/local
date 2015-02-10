<!DOCTYPE html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Expires" CONTENT="-1"> {* Cache-Control:no-cache だと「戻る」等もキャッシュ無効になってしまう *}
<meta http-equiv="Pragma" content="no-cache">  {* 旧サーバ用 *}

<body id="gen_body" text="#000000">

<script type="text/javascript">
    {* 修正モードの登録後の処理（フレームを閉じる）。ちなみに新規モードの登録後は、閉じるボタンで閉じることになるので、このtplは使用せず直接 gen.modal.close() が呼ばれる *}
    {literal}
    if (parent.document.getElementById('gen_modal_frame') != null) {
        {/literal}
        parent.gen.modal.close({if $form.gen_updated=='true'}true{else}false{/if},'{$form.gen_nextPageReport_noEscape}');
        {literal}
    } else {
        goList();
    }
    function goList() {
        {/literal}{* escapeしないこと。FFでパスワード変更後のalertが出なくなる等の不具合あり *}{literal}
        location.href = '{/literal}index.php?action={$form.gen_listAction|escape}{literal}';
    }
    {/literal}
</script>
</BODY>
</HTML>