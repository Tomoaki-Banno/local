<!DOCTYPE html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Expires" CONTENT="-1">       <!-- Cache-Control:no-cache だと「戻る」等もキャッシュ無効になってしまう -->
<meta http-equiv="Pragma" content="no-cache">  <!-- 旧サーバ用 -->
<BODY id="gen_body" text="#000000">

<script type="text/javascript">
    {*ドロップダウン等からのダイレクト品目マスタ登録で「構成表マスタを登録する」したときの処理。特定機能のためにFW内にコードを書くのはよくないが、ほかにやりようがなかったため。*}
    {if $form.bomWindowOpenWhenOverlapClose!=''}window.open('index.php?action=Master_Bom_List&parent_item_code={$form.item_code|escape}');{/if}

    alert("{gen_tr}_g("登録しました。"){/gen_tr}");
    {literal}
    // 親エレメントの更新
    parent.document.getElementById('gen_parentFrame').elmUpdate('{/literal}{$form.gen_overlapCode|escape}{literal}');
    // オーバーラップ（多重OPEN）されたモーダルウィンドウを閉じる
    parent.document.getElementById('gen_parentFrame').close();
    {/literal}
</script>
</BODY>
</HTML>