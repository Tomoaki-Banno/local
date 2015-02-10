if (!gen) var gen = {};

gen.contextMenu = {
    init: function(elmId, menuId, showCallback, selectCallback) {
        var jqElm = $('#' + elmId);
        var menu = $('#' + menuId);
        if (gen_iPad) {
            // gesturestart は2本目の指が触れたときに発火するイベント。iOSのみ。
            // 　したがってiPadでコンテキストメニューを出すには、指で対象の列見出しをタッチして、そのまま別の指でどこかをタッチする。
            //   touchend なら普通にタッチしただけで動作するし、iOS以外でも使える。でもソートや削除ボタンのクリックなどがやりづらくなる。
            jqElm.on('gesturestart', function(){
                // 既存のメニューを隠す。数字用メニュー表示中に非数値用メニューを表示したときなどのため
                $(".contextMenu").hide();
                // 表示
                showCallback();
                var pos = jqElm.offset();
                menu.show();
                gen.window.adjustBaloonElmPos(menu.get(0), pos);	// 画面からはみ出さないようにleft, topを調整
                
                // メニュー項目のonClick
                var lis = menu.find('LI:not(.onclick) A');
                lis.on('touchend.gen_context', function(){
                        $(".contextMenu").hide();
                        selectCallback( $(this).attr('href').replace(/^.*#/gi,'') );
                        lis.off('touchend.gen_context');
                    });
            });
        } else {
            jqElm.mousedown(function(e) {
                jqElm.mouseup(function(e) {
                    if (e.button != 2) return; // 右クリックのみ

                    // 既存のメニューを隠す。数字用メニュー表示中に非数値用メニューを表示したときなどのため
                    $(".contextMenu").hide();

                    // 表示
                    showCallback();
                    var pos = gen.window.mouseEventToPos(e);
                    menu.show();
                    gen.window.adjustBaloonElmPos(menu.get(0), pos);	// 画面からはみ出さないようにleft, topを調整
                    jqElm.off('mouseup').off('click');
                    $(document).off('click.gen_context');

                    // メニュー項目のマウスオーバー
                    menu.find('A')
                        .mouseover(function(){
                            menu.find('LI.hover').removeClass('hover');
                            $(this).parent().addClass('hover');
                        })
                        .mouseout(function(){
                            menu.find('LI.hover').removeClass('hover');
                        })
                        .unbind('click');
                    // メニュー項目のonClick
                    var lis = menu.find('LI:not(.onclick) A');
                    lis.on('click', function(){
                            $(".contextMenu").hide();
                            // replaceはIE対策
                            selectCallback( $(this).attr('href').replace(/^.*#/gi,'') );
                            lis.off('click');
                        });
                    // メニュー以外をクリックしたときの処理
                    setTimeout( function() { // Firefox対策
                    $(document).on('click.gen_context', function() {
                        $(document).off('click.gen_context');
                        lis.off('click');
                        $(".contextMenu").hide();
                    });
                    }, 0);
                });
            });
            // 標準のコンテキストメニューを無効にする
            jqElm.add('UL.contextMenu').on('contextmenu', function() { return false; });
        }
    }
};
