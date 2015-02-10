<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Genesiss Mobile</title>
        <meta name="viewport" content="width=device-width, initial-scale=1,minimum-scale=1, maximum-scale=1">
        <link href="scripts/jquery.mobile/jquery.mobile.css" rel="stylesheet" type="text/css" />
        <!--[if lt IE 9]>
           <script type="text/javascript" src="scripts/jquery/jquery-1.9.0.min.js"></script>
        <![endif]-->
        <!-[if gte IE 9]><!->
            <script type="text/javascript" src="scripts/jquery/jquery-2.0.1.min.js"></script>
        <!-[endif]->
        <script src="scripts/jquery.mobile/jquery.mobile.min.js"></script>
        <script type="text/javascript" src="scripts/jquery.lazyload/jquery.lazyload.min.js"></script>

        {*** JS Gettext/wordConvert ***}
        {assign var="getTextLastModPropertyName" value="jsGetText"|cat:$smarty.session.gen_language|cat:"LastMod"}
        <link rel="gettext" type="application/json" href="index.php?action=download&cat=jsgettext&{$smarty.session.gen_setting_company->$getTextLastModPropertyName}{*.json*}">{*GetText.jsに読ませるために最後に.jsonが必要*}
        <script type="text/javascript" src="scripts/gettext/Gettext.js"></script>

        <script>
        {* getText/wordConvert *}{literal}
        var gen_gettext = new Gettext({"domain": "messages"});
        function _g(msgid) { return gen_gettext.gettext(msgid); }    
        {/literal}

        {*----- ページキャッシュを完全に無効にするための処理 -----*}
        {* jqmではファーストページがDOM内にキャッシュされる。 *}
        {* しかしGenの場合はそれだと様々な問題が発生するため、キャッシュを無効にする。詳細は Tips「jQuery Mobile」参照 *}
        {literal}
        $(document).on( "pagebeforechange", function( e, data ) {
            data.options.reloadPage = true;
        });
        $(document).on( "pagehide", function( e, data ) { // pagehideはファーストページでは発生しない
            $.mobile.firstPage.remove();
        });
        {/literal}

        {*----- ページ遷移のたびに実行 -----*}
        {literal}
        $(document).on('pageinit', '#gen_mobile_page', function(event){
        });
        {/literal}

        {*----- 最初に読み込まれたページのみで実行 -----*}
        {literal}
        $(document).ready(function(event){
            // autocomplete
            {/literal}{*この処理は最初に読み込まれたページのみで実行される。しかしonは後から追加された要素にも適用されるので全ページで有効となる*}{literal}
            $(".gen_autocomplete").on("input", function(e) {
                var ajaxAction;
                var thisElm = $(this);
                if (thisElm.hasClass("ac_item")) {
                    ajaxAction = "AjaxItemParam";
                } else if (thisElm.hasClass("ac_received_item")) {
                    ajaxAction = "AjaxItemParam&received";
                } else if (thisElm.hasClass("ac_customer")) {
                    ajaxAction = "AjaxCustomerParam";
                } else if (thisElm.hasClass("ac_received_customer")) {
                    ajaxAction = "AjaxCustomerParam&received";
                } else {
                    return;
                }
                var id = this.id;
                var listId = id+"_gen_autocomplete";
                if ($('#'+listId).length==0) {
                    thisElm.after("<ul id='"+listId+"' data-role='listview' data-inset='true'></ul>").closest('form').trigger('create');
                }

                var sugList = $('#'+listId);
                var text = $(this).val();
                if(text.length < 1) {
                    sugList.html("");
                    sugList.listview("refresh");
                } else {
                    $.get("index.php?action=Mobile_Common_" + ajaxAction, {search:text}, function(res) {
                        if (res != '') {
                            // &quot を " に変換 してから eval
                            obj = eval("(" + (res.replace(/&quot;/g,'"')) + ")");
                            var str = "";
                            $.each(obj, function(key, value){
                                str += "<li><a href=\"javascript:gen_autocompleteSelect('"+id+"', '"+key+"', '"+value+"')\">"
                                    +"<span style='font-size:12px'>"+key+"</span><br>"
                                    +"<span style='font-size:10px'>"+value+"</span>"
                                    +"</a></li>";
                            });
                        }
                        sugList.html(str);
                        sugList.listview("refresh");
                    });
                }
            });
        });
        function gen_autocompleteSelect(id, key, value) {
            // 次のinput項目にフォーカスを移動
            var f = false;
            $('input').each(function() {
                if (f) {
                    this.focus();
                    return false;
                }
                if (this.id == id) {
                    f = true;
                }
            });
    
            $("#"+id).val(key);
            $("#"+id+"_gen_autocomplete")
                .before(value)
                .remove();
        }
        {/literal}
        </script>        
        
        {literal}
        <style TYPE="text/css">
        <!--
            #gen_mobile_page{
                padding-top:0!important
            }
                
            .wordbreak{
                overflow: visible;
                white-space: normal;
            }

            .detailTable {
                width: 100%;
                font-size: 13px;
                border-collapse: collapse; /* 枠線の表示方法 */
                border: 1px #cccccc solid; /* テーブル全体の枠線（太さ・色・スタイル） */
            }
            .detailTable TD {
                border: 1px #cccccc solid; /* セルの枠線（太さ・色・スタイル） */
            }

            .formtable1 {
                font-size: 13px;
                border-collapse: collapse; /* 枠線の表示方法 */
                border: 1px #1C79C6 solid; /* テーブル全体の枠線（太さ・色・スタイル） */
            }
            .formtable1 TD {
                border: 1px #1C79C6 solid; /* セルの枠線（太さ・色・スタイル） */
            }
        -->
        </style>        
        {/literal}
    </head>
    <body>
        <input type='hidden' id='gen_ajax_token' value='{$smarty.session.gen_ajax_token|escape}'>
        {* jQuery Mobile の ajax画面遷移の際、これ以降のセクションが読み込まれる（ここより上は読み込まれない） *}
        {* ちなみに本来、単一ページテンプレート（1ファイル1ページ）の場合はページにIDは不要なのだが、コードのいくつかの *}
        {* 箇所でページを操作しているため、あえてIDをつけてある。ページキャッシュを完全に無効化しているので、ID重複の心配はない *}
        <div data-role="page" id="gen_mobile_page" data-theme="c">
            {*  data-position="fixed" をつければ固定ヘッダになるが、棚卸登録でサジェストから品目を選択した直後に表示が乱れるし、なんとなく不自然な動きをすることがあるのでとりあえず無効 *}
            <div id="gen_mobile_header" data-role="header" data-theme="b">
                {if $form.gen_headerLeftButtonURL==''}
                    {if $form.action!='Mobile_Home'}
                        <a href="index.php?action=Mobile_Home" data-role="button" data-icon="home" data-iconpos="notext"></a>
                    {/if}
                {else}
                    <a href="{$form.gen_headerLeftButtonURL|escape}" data-icon="{$form.gen_headerLeftButtonIcon|escape}" class="ui-btn-left" {$form.gen_headerLeftButtonParam|escape}>{$form.gen_headerLeftButtonText|escape}</a>
                {/if}
                <h1>{if $form.gen_pageTitle!=''}{$form.gen_pageTitle|escape}{else}Genesiss{/if}</h1>
                {if $form.gen_headerRightButtonURL!=''}<a href="{$form.gen_headerRightButtonURL|escape}" data-icon="{$form.gen_headerRightButtonIcon|escape}" class="ui-btn-right" {$form.gen_headerRightButtonParam|escape}>{$form.gen_headerRightButtonText|escape}</a>{/if}
            </div>
            <div id="gen_mobile_content" data-role="content">
