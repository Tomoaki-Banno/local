if (!gen) var gen = {};

gen.intro = {
    step: 1,
    start: function(isFirst) {
        if (isFirst) {
            gen.intro._set('gen_introButton', "<b>" + _g("Genesiss へようこそ！") + "</b><br><br>" + _g("これからジェネシスの画面についてご説明いたします。") 
                    + "<br><br>" + _g("右矢印キーで次の項目へ進みます。") + "<br>" + _g("ESCキーを押すか、画面のどこかをクリックすると説明が終了します。") 
                    + "<br><br>" + _g("終了後、再度この説明を見たい場合はこの「ガイド」リンクをクリックしてください。") 
                    + "<br><br>" + _g("では、さっそく右矢印キーを押してスタートしてください。"), 'left');
        }
        
        // 個別画面
        gen.intro._set('homeMenuImage', _g("このフローチャート自体がメニューになっています。画面名をクリックするとその画面が開きます。"), 'left');
        // list
        gen.intro._set($('#D0 .gen_list_header:first').attr('id'), _g("リストの各列の見出し部分をマウスで右クリックすることによりサブメニューが表示されます。サブメニューの内容は列の内容に応じて変わります。"), "left");
        gen.intro._set('gen_contextMenuForNum', _g("見出し部分を右クリックしたときにはこのようなメニューが表示されます。「フィルタ」機能で行を絞り込んだり、「小計」機能でリストに小計を入れることができます。列の表示設定も行えます。"), "left");
        gen.intro._set($('#D0 .gen_list_handle:first').attr('id'), _g("列見出しの右端をドラッグすると列幅を変更できます。") + "<br>" + _g("また、ダブルクリックするとその時点のデータに応じて列幅が自動調整されます。") + "<br>" + _g("変更した列幅はずっと保持されますが、画面右端の設定アイコン内のボタンでリセットできます。"), "left");
        gen.intro._set($('#D0 .gen_list_agg:first').attr('id'), _g("見出し行のすぐ下には、合計などの集計値が表示されます（数値列のみ）。集計の種類（合計・最大値・平均値など）は、画面右端の設定アイコン内で変更できます。"), "left");
        gen.intro._set('gen_search_area_icon', _g("表示条件の表示/非表示を切り替えます。"));
        gen.intro._set('gen_special_search', _g("リスト内のデータを検索します。テキスト項目すべてが検索の対象となります。複数のキーワードをスペースで区切って入力することにより、AND検索もできます。") + "<br><br>" + _g("複雑な検索を行う際には表示条件を使用する必要がありますが、手早く検索したい場合はこの機能が便利です。"), "left");
        gen.intro._set('gen_newRecordButton', _g("このボタンでデータを登録します。"));
        gen.intro._set($('.gen_reportEditButton:first').attr('id'), _g("エクセルを使用して帳票を自由にカスタマイズできます。") + "<br><br>" + _g("ジェネシスの多彩な機能の中でも、とくにご好評いただいているものの一つです。ぜひご活用ください。"));
        gen.intro._set('gen_importButton', _g("CSVデータをインポートします。"), "left");
        gen.intro._set('gen_exportButton', _g("データをCSV形式でエクスポートします。"), "left");
        gen.intro._set('gen_excelExportButton', _g("データをエクセル出力します。"), "left");
        gen.intro._set('gen_excelEditButton', _g("ここからダウンロードしたエクセルファイルを使用すると、エクセル上でデータを登録/編集できます。"), "left");
        gen.intro._set('gen_listSettingButton', _g("表示行数や集計行など、各種の設定を行います。"), "left");
        gen.intro._set('gen_addColumnButton', _g("リストの表示項目を自由に追加・削除できます。"), "left");
        gen.intro._set('gen_intro_nav', _g("データの件数が多い場合、ここでページの切り替えを行います。一度に表示される件数を変更するには、画面右上の設定アイコンをクリックします。"), "top");
        // search
        gen.intro._set('gen_addSearchColumnButton', _g("表示条件の項目を追加・削除できます。"), 'bottom');
        gen.intro._set('gen_intro_savedSearchCondition', _g("現在の表示条件に名前をつけて保存しておくことができます。保存したパターンはセレクタから選択するだけで復元できます。") + "<br>" + _g("クロス集計と組み合わせて使用すると、ワンクリックでさまざまな分析ができるようになり大変便利です。"));
        gen.intro._set('gen_intro_crossTableHorizontal', _g("「クロス集計（横軸）」「（縦軸）」「（値）」の3つの項目をセットすると、リストがクロス集計されます。グラフ表示もできます。さまざまな角度からデータを分析できる非常に強力な機能です。"), 'top');
        // header
        if ($('#gen_addMyMenu').css('display') != 'none' && $('#gen_addMyMenu').css('visibility') != 'hidden') {
            gen.intro._set('gen_addMyMenu', _g("現在の画面を「マイメニュー」に登録します。よく使用する画面を登録しておくと便利です。"));
        }
        gen.intro._set('gen_myMenu', _g("ここに「マイメニュー」が表示されます。画面名の隣の[×]をクリックするとマイメニューから削除されます。"));
        gen.intro._set('gen_showSchedule', _g("スケジュール画面を開きます。個人のスケジュールを登録したり、共有したりすることができます。"), 'left');
        gen.intro._set('gen_showChat', _g("トークボードウィンドウを開きます。社内コミュニケーションに大きな力を発揮するツールですので、ぜひご活用ください。"), 'left');
        gen.intro._set('gen_profileImageTag', _g("この画像をクリックするとプロフィール画像を変更できます。プロフィール画像はトークボードに表示されます。"), 'left');
        gen.intro._set('gen_addStickyNoteButton', _g("画面にメモパッドを貼ることができます。自分専用、あるいは全ユーザー共通のメモとして使用できます。"), 'left');
        gen.intro._set('gen_pageHelpDialogParent', _g("FAQやマニュアルを検索できます。"), 'left');
        
        if (!isFirst) {
            gen.intro._set('gen_introButton', _g("この説明をもう一度見たい場合はこの「ガイド」リンクをクリックしてください。"), 'left');
        }
        
        var myMenuElm = $('#gen_myMenu');
        if (myMenuElm.html().length == 0) {
            myMenuElm.html("<div id='gen_intro_myMenuPlaceHolder' style='width:200px;height:12px;display:inline-block'></div>");
        }
        
        var sElm = $('#gen_search_area');
        if (sElm.length > 0) {
            sElm.css('display','');
            gen.list.table.setListSize();
        }
        var sElmBeforeHeight = sElm.height();
        sElm.css('height', $('#gen_search_area_inner').height());

        var menuElm = $('#gen_contextMenuForNum');
        if (menuElm.length > 0) {
            var menuPos = $('#D0 .gen_list_header:first').offset();
            menuPos.top += 50;
            menuPos.left += 80;
            gen.window.adjustBaloonElmPos(menuElm.get(0), menuPos);
            menuElm.show();
        }
        
        document.body.style.overflow = 'hidden';    //がたつき防止
        
        introJs()
            .setOptions({showStepNumbers:false, prevLabel:_g("前へ"), nextLabel:_g("次へ(右矢印キー)"), doneLabel:_g("終了"), skipLabel:_g("終了")})
            .onbeforechange(function(elm){
                if (this._currentStep >= this._introItems.length - 1) {
                    gen.intro._onexit(menuElm, sElm, sElmBeforeHeight);
                }
            })
            .onexit(function(){
                gen.intro._onexit(menuElm, sElm, sElmBeforeHeight);
            })
            .oncomplete(function(){
                gen.intro._onexit(menuElm, sElm, sElmBeforeHeight);
            })
            .start();
    },
    _onexit: function(menuElm, sElm, sElmBeforeHeight) {
        document.body.style.overflow = 'auto';
        if ($('#gen_intro_myMenuPlaceHolder').length > 0) {
            $('#gen_intro_myMenuPlaceHolder').remove();
        }
        if (menuElm.length > 0)
            menuElm.hide();
         sElm.css('height', sElmBeforeHeight);
    },
    _set: function(id, text, pos) {
        var e = $('#' + id);
        if (e.length > 0) {
            e.attr('data-intro', text).attr('data-step', gen.intro.step);
            if (pos != undefined) {
                e.attr('data-position', pos);
            }
            gen.intro.step++;
        }
    }
};
