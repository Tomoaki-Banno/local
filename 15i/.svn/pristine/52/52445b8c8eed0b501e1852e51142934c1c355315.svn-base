    {if $gen_nofooter != 'true'}{* list.tpl はフッタなし *}
    <tr>
      <td>
        <hr>
          <div id='gen_footer_area'>{* home.tpl用。画面最下部に表示するためのdiv *}
            <table>
                <tr>
                    <td width="20px"></td>
                    <td>Genesiss 15i&nbsp;&nbsp;rev.20150205</td>
                    <td width="40px"></td>
                    <td>{gen_tr}_g("前回ログイン日時"){/gen_tr}：{$smarty.session.last_login|escape}</td>
                    <td width="40px"></td>
                    <td>{if $smarty.session.user_id=='-1'}接続先データベース名： {$smarty.const.GEN_DATABASE_NAME}{/if}</td>
                </tr>
            </table>
         </div>
      </td>
    </tr>
    {/if}
</table>
</body>
</html>
