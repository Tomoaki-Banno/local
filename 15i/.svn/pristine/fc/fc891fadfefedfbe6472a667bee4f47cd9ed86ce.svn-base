var treeview_tree;
var treeview_node;
var treeview_callback;
var prev_item_id;
var rightBoxOffset = 1;
var con = null;
var leftBoxElm;
var rightBoxElm;
var leftBoxLineHeight;
var rightBoxLineHeight;
var leftBoxMaxHeight;
var rightBoxMaxHeight;
var boxBorderHeight = 16;   // 固定値

var boxInzuCharCount = 6;    // 左box員数桁数（カッコ含む）
var boxCodeCharCount = 20;   // 左右boxコード桁数

var forms;   // list.tplから受け取った$form変数
var msgs;   // list.tplから受け取ったメッセージ集（JS内ではgetTextが使えないので、メッセージはinitPageの引数で受け取る）

function initPage(formargs, msgargs) {
    forms = formargs;
    msgs = msgargs;

    if (forms.parent_item_code!='') {
    	var showElm = $('#parent_item_id_show');
    	showElm.val(forms.parent_item_code);
    	showElm.trigger('onchange');
    }
    document.getElementById('treeDiv1').innerHTML = '<br>・' + _g("親品目を選択してください。");

    // 左右ボックスの高さを制御するための処理。
    //   リストボックス（SELECT）には横スクロールバーが出ないため、ボックスの横幅を伸ばし、div囲みしてoverflow:scroll
    //   指定し、divのスクロールバーを使うようにしている。
    //   これだと横はいいが、縦方向はボックスとdivの両方の縦スクロールバーが表示され使いづらい。
    //   そこで内容に合わせてボックスの縦幅を制御することで、ボックスの縦スクロールバーを出させないようにしている。
    leftBoxElm = document.getElementById('leftBox');
    leftBoxLineHeight = leftBoxElm.offsetHeight / 30 + (/*@cc_on!@*/0 ? 0 : 2);   // 行高を測る。30はタグ内のsizeで指定した行数
    var leftBoxDivHeight = document.getElementById('leftBoxDiv').offsetHeight;
    leftBoxMaxHeight = leftBoxDivHeight - boxBorderHeight;
    leftBoxElm.style.height = (leftBoxDivHeight - boxBorderHeight) + 'px';

    rightBoxElm = document.getElementById('rightBox');
    rightBoxLineHeight = rightBoxElm.offsetHeight / 30 + (/*@cc_on!@*/0 ? 0 : 2);
    var rightBoxDivHeight = document.getElementById('rightBoxDiv').offsetHeight;
    rightBoxMaxHeight = rightBoxDivHeight - boxBorderHeight;
    rightBoxElm.style.height = (rightBoxDivHeight - boxBorderHeight) + 'px';
    // D&D（テスト中）
    //initDD();
}

/****************** ツリービュー関係 **********************/

// ツリーの初期化
function treeInit() {
    // 親品目が選択されていないときは処理しない
    var itemId = document.getElementById('parent_item_id').value;
    var itemName = document.getElementById('parent_item_id_show').value + ':' + document.getElementById('parent_item_id_sub').value;
    // 文字化け回避
    itemName = itemName.replace(/</g,'＜').replace(/>/g,'＞').replace(/\\/g,'’').replace(/\"/g,'”');
    if (!gen.util.isNumeric(itemId)) {
        treeview_tree = null;
        document.getElementById('treeDiv1').innerHTML = "<br>・" + _g("親品目を選択してください。すべての品目を出力する場合は、「選択された親品目以下の..」チェックを外してください。");
        return;
    }

    // ツリーの新規作成
    treeview_tree = new YAHOO.widget.TreeView("treeDiv1");
    treeview_tree.setDynamicLoad(loadDataForNode);                              // 展開時コールバック関数の定義
    treeview_tree.subscribe("expand", function(node) { onNodeClick(node); });    // 展開時イベントハンドラ関数の定義
    treeview_tree.subscribe("collapse", function(node) { onNodeClick(node); });  // 縮小時イベントハンドラ関数の定義
    treeview_tree.subscribe("labelClick", function(node) { onNodeClick(node); });

    // 現在選択されている親品目をツリーのトップノードとして追加
    var root = treeview_tree.getRoot();
    myobj = { label: itemName, id:itemId } ;
    var newNode = new YAHOO.widget.TextNode(myobj, root, false);

    // 描画
    treeview_tree.draw();
    // 1段階開く。drawの後に行うこと
    newNode.expand();
}

// ツリーの展開のたびに呼び出されるコールバック関数
function loadDataForNode(node, onCompleteCallback) {
    treeview_node = node;
    treeview_callback = onCompleteCallback;
    var param = {itemId : node.data.id};
    // 取数モードを実装したが、使用中止になったので下行は無意味（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
    if (document.getElementById('inzu_mode').value=='tori') {
        param['inzu_mode'] = 'tori';
    }
    if (document.getElementById('reverseCheck').checked) {
        param['reverse'] = 'true';
    }
    gen.ajax.connect('Master_Bom_AjaxTreeview', param, 
        function(j) {
            // ツリーにノードを追加する
            if (j.data.length > 0) {
                if (j.parent_end_item=='t') {  // 親品目がダミー/非表示品目だったときにノードを着色。トップノード追加時はダミー/非表示判定が行ないにくいので、ここで行う
                    node.labelStyle = 'tree-enditem-class';
                    treeview_tree.draw();
                } else if (j.parent_dummy_item=='t') {
                    node.labelStyle = 'tree-dummyitem-class';
                    treeview_tree.draw();
                }
                for (i in j.data) {
                    var newNode = new YAHOO.widget.TextNode({ label : j.data[i][1], id : j.data[i][0], isLeaf: j.data[i][4]=='f', expanded : false}, treeview_node);
                    if (j.data[i][3] == 't') {
                        newNode.labelStyle = 'tree-enditem-class';
                    } else if (j.data[i][2] == 't') {
                        newNode.labelStyle = 'tree-dummyitem-class';
                    }
                }
            } else {
                node.isLeaf = true;
            }
            // データの読み込み完了をTreeviewコンポーネントに通知
            treeview_callback();
        }, false);    
}

// ノードクリック時イベント
function onNodeClick(node) {
    // editモードのときはボックス更新を行わない
    if (document.getElementById('parent_item_id_show').disabled == true) return;

    document.form1.parent_item_id.value = node.data.id;
    showParent();    // 親品目表示
    boxInit();      // 左右ボックスの更新
    document.getElementById('reg_message').innerHTML = "";
}

// 逆展開チェックボックスの変更イベント
function onReverseCheckChange() {
    if (document.getElementById('reverseCheck').checked) {
        document.getElementById('reverseMsg').style.display = '';
    } else {
        document.getElementById('reverseMsg').style.display = 'none';
    }
    treeInit();
}

/****************** 親品目セレクタ関係 **********************/

// 親品目セレクタ変更イベント
function onSelecterChange() {
    showParent();   // 親品目表示
    boxInit();      // 左右ボックスの更新
    treeInit();     // ツリーの初期化
    document.getElementById('reg_message').innerHTML = "";
}

// 親品目表示
function showParent() {
    if (document.getElementById('parent_item_id_show').disabled == true)
        return;

    var itemId = document.getElementById('parent_item_id').value;
    gen.ajax.connect('Master_Bom_AjaxItemParam', {itemId : itemId}, 
        function(j) {
            // 親品目の表示
            if (j.item_code !== undefined && j.item_code !== '') {
                document.getElementById('parent_item_id').value = j.item_id;
                document.getElementById('parent_item_id_show').value = j.item_code;
                document.getElementById('parent_item_id_sub').value = j.item_name;
                document.getElementById('order_class').value = (j.order_class=='0' ? _g("製番") : j.order_class=='2' ? _g("ロット") : _g("MRP"));
                document.getElementById('default_selling_price').value = j.default_selling_price;
                document.getElementById('standard_base_cost').value = j.base_cost;
                document.getElementById('dummy_item').value = j.dummy_item;

                var msgstr = "";
                if (j.partner_class == "0" || j.partner_class == "1") {
                    var class_name = _g("発注");
                    if (j.partner_class == "1") 
                        class_name = _g("外製(支給なし)");
                    msgstr = "<br>" + _g("この品目の標準手配先は「class_name」です。子品目を登録しても、その情報が所要量計算・原価計算等に使用されることはありません。").replace('class_name',class_name);
                }
                if (j.remained_exist == "1") {
                    msgstr += "<br>" + _g("この品目に対する未完了の製造/外製指示があります。それらに構成の変更を反映させるには、製造/外製指示を再登録する必要があります。");
                }
                document.getElementById('red_message').innerHTML = msgstr;
            } else {
                document.getElementById('order_class').value = "";
                document.getElementById('default_selling_price').value = "";
                document.getElementById('standard_base_cost').value = "";
                document.getElementById('dummy_item').value = "";
                document.getElementById('red_message').innerHTML = "";
            }
        }, false);    
}

// 表示モード変更イベント
function onInzuModeChange() {
    if (document.getElementById('parent_item_id_show').disabled == true) {
        if (!confirm(_g("表示モードを変更すると編集中の内容は破棄されます。よろしいですか？"))) {   // 2007-11-13
            return;
        }
    }
    label = _g("員数");
    // 取数モードを実装したが、使用中止になったので下行は無意味（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
    if (document.getElementById('inzu_mode').value=='tori') label = _g("取数");
    document.getElementById('inzu_label').innerHTML = label;
    onSelecterChange();  // 表示の更新
}

/****************** 左右ボックス関係 **********************/

// 左ボックス用データ表示テキスト作成
function leftBoxFormat(inzu, itemCode, itemName) {
    var str =
        rpadEx('(' + inzu + ')', boxInzuCharCount) +
        rpadEx(itemCode.substr(0, boxCodeCharCount), boxCodeCharCount) +
        itemName;
    return str;
}

// 左ボックス表示内容から各項目を取り出す
function leftBoxDecode(str) {
    kakko1 = str.indexOf('(');
    kakko2 = str.indexOf(')');
    if (kakko1 == -1 || kakko2 == -1) return;

    // 員数部の文字数をカウント。
    if (boxInzuCharCount > kakko2) {
    	// 員数部を抜き出すために使用している boxInzuCharCount は、パディング用の全角スペース1つを2文字と数えている。そのことを考慮する必要がある。
    	inzuLen = str.replace('　', '  ').substr(0,boxInzuCharCount).replace('  ', '　').length;
    } else {
    	inzuLen = kakko2 + 1;
    }
    inzu = str.substr(kakko1 + 1, (kakko2 - kakko1 - 1));
    code = trim(str.substr(inzuLen, boxCodeCharCount));
    name = trim(str.substr(inzuLen + boxCodeCharCount));
    return {inzu:inzu, code:code, name:name};
}

// 右ボックス用データ表示テキスト作成
function rightBoxFormat(itemCode, itemName) {
    var str =
        rpadEx(itemCode.substr(0, boxCodeCharCount), boxCodeCharCount) +
        itemName;
    return str;
}

// 右ボックス表示内容から各項目を取り出す
function rightBoxDecode(str) {
    code = trim(str.substr(0, boxCodeCharCount));
    name = trim(str.substr(boxCodeCharCount));

    var obj = {code: code, name: name};
    return obj;
}

// 親品目に基づき、左右ボックスの内容を更新
function boxInit() {
    leftBoxDeSelect();
    rightBoxDeSelect();

    // もし親品目が設定されていなければ左右BOXとデータのクリアだけ行ってexit
    var parent = document.getElementById('parent_item_id').value;
    if (!gen.util.isNumeric(parent)) {
        leftBoxClear();
        rightBoxClear();
        return;
    }

    leftBoxShow();
}

function leftBoxShow() {
    // 左ボックスの選択肢をサーバーから取得
    var parent = document.getElementById('parent_item_id').value;

    // 取数モードを実装したが、使用中止になったので下行は無意味（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
    if (document.getElementById('inzu_mode').value=='tori') 
        url += '&inzu_mode=tori';
    gen.ajax.connect('Master_Bom_AjaxSelecter', {itemId : parent}, leftBoxAfterAjax, false);    
}

// このfunctionは構成コピー機能からも利用していることに注意
function leftBoxAfterAjax(j) {
    // いったん左ボックスの選択肢を全部消す
    leftBoxClear();

    // サーバーから受け取ったデータをもとに左ボックスにデータ表示
    if (j.length > 0) {
        // oの内容はHTMLエスケープされていない（文字化け回避のため）。よって、そのままinnerHTMLに流したりしないこと
        var leftCount = 0;
        for (i in j) {
            var str = leftBoxFormat(j[i][1], j[i][2], j[i][3]);
            leftBoxElm.options[leftCount++] = new Option(str, j[i][0]);
        }
        leftBoxHeightAdjust();
        document.getElementById('allDeleteButton').disabled = false;
    }
    rightBoxReset();
}

function rightBoxReset() {
    rightBoxOffset = 0;
    rightBoxShow();
}

// 右ボックスの表示/更新
// 右ボックス表示条件は、親品目自身以外で、左ボックスに出ていないもの。親がMRP品目ならMRP品目のみ
function rightBoxShow() {

    // 右ボックスの選択肢をサーバーから取得
    var parent = document.getElementById('parent_item_id').value;
    
    var param = {parentItemId : parent, offset : rightBoxOffset};

    var searchBox = document.getElementById('searchText');
    if (searchBox != null) {
        var searchText = searchBox.value;

        if (searchText != "") 
            param['searchText'] = encodeURI(searchText);
    }

    var itemGroupIdElm = document.getElementById('item_group_id');
    if (itemGroupIdElm != null) {
        param['itemGroupId'] = itemGroupIdElm.value;
    }

    var idList = '';
    var count = leftBoxElm.length;
    if (count > 0) {
        for (i=0;i < count;i++) {
            idList += leftBoxElm.options[i].value + ',';
        }
        idList = idList.substr(0,idList.length-1);
    }
    var postData = null;
    if (idList != '' ) {
        param['omitIdList'] = idList;
    }

    gen.ajax.connect('Master_Bom_AjaxRightBox', param, 
        function(j) {
            // いったんボックスの選択肢を全部消す
            rightBoxClear();

            // サーバーから受け取ったデータをもとに右ボックスにデータ表示
            if (j.length > 0) {
                var leftCount = 0;
                for (i in j) {
                    if (i < forms.gen_dropdown_perpage) {
                        var str = rightBoxFormat(j[i][1], j[i][2]);
                        rightBoxElm.options[leftCount++] = new Option(str, j[i][0]);
                    }
                }
                rightBoxHeightAdjust();

                var elm = document.getElementById('rightBoxPrev');
                if (rightBoxOffset > 1) {
                    elm.style.color="blue";
                    elm.style.textDecoration = "underline";
                    elm.onclick = function() {rightBoxOffset -= forms.gen_dropdown_perpage; rightBoxShow();};
                    elm.style.cursor = "pointer";
                } else {
                    elm.style.color="#cccccc";
                    elm.style.textDecoration = "none";
                    elm.onclick = null;
                    elm.style.cursor = null;
                }
                elm = document.getElementById('rightBoxNext');
                if (j.length > forms.gen_dropdown_perpage) {
                    elm.style.color="blue";
                    elm.style.textDecoration = "underline";
                    elm.onclick = function() {rightBoxOffset += forms.gen_dropdown_perpage; rightBoxShow();};
                    elm.style.cursor = "pointer";
                } else {
                    elm.style.color="#cccccc";
                    elm.style.textDecoration = "none";
                    elm.onclick = null;
                    elm.style.cursor = null;
                }
            }
        }, false);    
}

// 右ボックスの項目が選択されたときの処理
function onRightBoxChange() {
    //leftBoxDeSelect(); 【2009】 comment out
    document.getElementById('addButton').disabled = false;  // 【2009】 add
}

// 左ボックスの項目が選択されたときの処理
function onLeftBoxChange() {
    //document.getElementById('addButton').value = _g("員数更新"); 【2009】 comment out
    document.getElementById('inzuUpdateButton').disabled = false;  // 【2009】 add
    document.getElementById('deleteButton').disabled = false;  // 【2009】 add
    document.getElementById('upButton').disabled = false;  // 【2009】 add
    document.getElementById('downButton').disabled = false;  // 【2009】 add
    updateQuantity();
}

// 左ボックスの選択状態により員数欄を更新
function updateQuantity() {
    var index = leftBoxElm.selectedIndex;
    if (index < 0) return;
    var text = leftBoxElm.options[index].text;
    var qty = leftBoxDecode(text).inzu;
    if (!isNaN(qty)) document.getElementById('quantity').value = qty;
}

// 左ボックスの選択解除
function leftBoxDeSelect() {
    leftBoxElm.selectedIndex = -1;
    //document.getElementById('addButton').value = _g("← 追加"); 【2009】 comment out
    document.getElementById('inzuUpdateButton').disabled = true;  // 【2009】 add
    document.getElementById('deleteButton').disabled = true;  // 【2009】 add
    document.getElementById('upButton').disabled = true;  // 【2009】 add
    document.getElementById('downButton').disabled = true;  // 【2009】 add
}

// 右ボックスの選択解除
function rightBoxDeSelect() {
    rightBoxElm.selectedIndex = -1;
    document.getElementById('addButton').disabled = true;  // 【2009】 add
}

// 左ボックスのクリア
function leftBoxClear() {
    boxClear(leftBoxElm);
}

// 右ボックスのクリア
function rightBoxClear() {
    boxClear(rightBoxElm);
}

function boxClear(elm) {
    for (i=elm.length-1; i>=0; i--) {
        elm.options[i] = null;
    }
    leftBoxHeightAdjust();
    rightBoxHeightAdjust();
}

// 左ボックスの全選択
function leftBoxAllSelect() {
    allSelect(leftBoxElm);
}

// 右ボックスの全選択
function rightBoxAllSelect() {
    allSelect(rightBoxElm);
}

function allSelect(elm) {
    for (i=elm.length-1; i>=0; i--) {
        elm.options[i].selected = true;
    }
}

// 左ボックスの高さ調整　（高さ調整する理由はinitPage()参照）
function leftBoxHeightAdjust() {
    var h = leftBoxLineHeight * leftBoxElm.options.length + boxBorderHeight;
    if (h < leftBoxMaxHeight) h = leftBoxMaxHeight;
    leftBoxElm.style.height = h + 'px';
}

// 右ボックスの高さ調整
function rightBoxHeightAdjust() {
    var h = rightBoxLineHeight * rightBoxElm.options.length + boxBorderHeight;
    if (h < rightBoxMaxHeight) h = rightBoxMaxHeight;
    rightBoxElm.style.height = h + 'px';
}

function onRightBoxClick() {
    var qtyElm = document.getElementById('quantity');
    if (qtyElm.value == '') {
        qtyElm.value = '1';
    }
}

/****************** 追加/削除ボタン関係 **********************/

// 追加ボタン
function onAddButton() {
    var qty = document.getElementById('quantity').value;
    if (!gen.util.isNumeric(qty) || qty <= 0) {
        alert(_g("員数が正しくありません。"));
        return;
    }

    if (rightBoxElm.selectedIndex < 0) {
        alert(_g("品目を選択してください。"));
        return;
    }

    // 追加処理
    var leftIdx = leftBoxElm.length;
    for (i=0; i<=leftBoxElm.length-1; i++) {
        if (leftBoxElm.options[i].selected) {
            leftIdx = i;
            break;
        }
    }
    var topIndex = -1;
    for (i=0; i<=rightBoxElm.length-1; i++) {
        if (rightBoxElm.options[i].selected) {
            var id = rightBoxElm.options[i].value;
            var obj = rightBoxDecode(rightBoxElm.options[i].text);
            var text = leftBoxFormat(qty, obj.code, obj.name);
            leftBoxElm.options[leftBoxElm.length] = new Option(text, id); // とりあえず1番下に新項目
            for (j=leftBoxElm.length-2; j>=leftIdx; j--) { // 1つずつ下へずらしていく
                leftBoxElm.options[j+1].text = leftBoxElm.options[j].text;
                leftBoxElm.options[j+1].value = leftBoxElm.options[j].value;
            }
            leftBoxElm.options[leftIdx].text = text; // 挿入
            leftBoxElm.options[leftIdx].value = id;
            leftIdx++;
            rightBoxElm.options[i] = null;
            topIndex = i;
            i--;
        }
    }

    if (topIndex != -1) {
        if (topIndex >= rightBoxElm.length-1) topIndex--;
        rightBoxElm.options[topIndex].selected = true;
    }
    document.getElementById('allDeleteButton').disabled = false;

    leftBoxHeightAdjust();
    rightBoxHeightAdjust();
    editModeChange(true);
}

// 員数更新ボタン
function onInzuUpdateButton() {
    var qty = document.getElementById('quantity').value;
    if (!gen.util.isNumeric(qty) || qty <= 0) {
        alert(_g("員数が正しくありません。"));
        return;
    }
    if (leftBoxElm.selectedIndex == -1) {
        alert(_g("品目を選択してください。"));
    }

    for (i=0; i<=leftBoxElm.length-1; i++) {
        if (leftBoxElm.options[i].selected) {
            var text = leftBoxElm.options[i].text;
            var obj = leftBoxDecode(text);
            leftBoxElm.options[i].text = leftBoxFormat(qty, obj.code, obj.name);
        }
    }
    editModeChange(true);
}

// 削除ボタン
function onDeleteButton() {
    var index = leftBoxElm.selectedIndex;
    if (index < 0) {
        alert(_g("品目を選択してください。"));
        return;
    }

    for (i=0; i<=leftBoxElm.length-1; i++) {
        if (leftBoxElm.options[i].selected) {
            var id = leftBoxElm.options[i].value;
            var obj = leftBoxDecode(leftBoxElm.options[i].text);
            var text = rightBoxFormat(obj.code, obj.name);
            rightBoxElm.options[rightBoxElm.length] = new Option(text, id);
            leftBoxElm.options[i] = null;
            i--;
        }
    }
    leftBoxHeightAdjust();
    rightBoxHeightAdjust();
    if (leftBoxElm.length > 0) {
        leftBoxElm.options[0].selected = true;
    }
    editModeChange(true);

    prev_item_id = "";
    updateQuantity();
}

// 全削除ボタン
function onAllDeleteButton() {
    leftBoxAllSelect();
    onDeleteButton();
}

// 元に戻すボタン
function cancelButton() {
    if (!confirm(_g("編集中の内容を破棄して元に戻します。よろしいですか？"))) {
        return;
    }
    editModeChange(false);
    boxInit();
    prev_item_id = "";
}

// 構成コピーボタン
function onCopyButton() {
    var parentItemId = document.getElementById('parent_item_id').value;
    if (parentItemId == '') {
        alert(_g("親品目を選択してください。"));
        return;
    }
    var copyItemId = document.getElementById('copy_item_id').value;
    if (copyItemId == '') {
        alert(_g("近似品目を選択してください。"));
        return;
    }
    if (!confirm(_g("現在の構成をクリアして、近似品目の構成をコピーします。よろしいですか？"))) {
        return;
    }
    editModeChange(true);

    gen.ajax.connect('Master_Bom_AjaxCopy', {parentItemId : parentItemId, copyItemId : copyItemId}, leftBoxAfterAjax, false);    
    prev_item_id = "";
}

// 編集モード on/off
function editModeChange(isEditMode) {
    if (isEditMode) {
        // 編集モードではロックしておく（変更されると再リクエストが発生し、編集内容が失われるため）
        document.getElementById('parent_item_id_show').disabled = true;
        document.getElementById('parent_item_id_sub').disabled = true;
        document.getElementById('parent_item_id_dropdown').disabled = true;
        document.getElementById('message').innerHTML = _g("構成の編集中です。[登録]か[元に戻す]を押すまで親品目を変更できません。") + "<br>";
    } else {
        document.getElementById('parent_item_id_show').disabled = false;
        document.getElementById('parent_item_id_sub').disabled = false;
        document.getElementById('parent_item_id_dropdown').disabled = false;
        document.getElementById('message').innerHTML = "&nbsp;";
    }
}

// 左ボックスの選択項目をひとつ上へ
function onUpButton() {
    for (var i=1; i<=leftBoxElm.length-1; i++) { // 2番目の項目から下へ
        var opt = leftBoxElm.options[i];
        if (opt.selected) {
            var opt1 = leftBoxElm.options[i-1];
            var text = opt.text;
            var value = opt.value;
            opt.text = opt1.text;
            opt.value = opt1.value;
            opt1.text = text;
            opt1.value = value;
            opt.selected = false;
            opt1.selected = true;

            editModeChange(true);
            return;
        }
    }
}

// 左ボックスの選択項目をひとつ下へ
function onDownButton() {
    for (var i=0; i<=leftBoxElm.length-2; i++) { // 1番目の項目から下へ、最後から2番目まで
        var opt = leftBoxElm.options[i];
        if (leftBoxElm.options[i].selected) {
            var opt1 = leftBoxElm.options[i+1];
            var text = opt.text;
            var value = opt.value;
            opt.text = opt1.text;
            opt.value = opt1.value;
            opt1.text = text;
            opt1.value = value;

            opt.selected = false;
            opt1.selected = true;

            editModeChange(true);
            return;
        }
    }
}

/****************** データベース登録関係 **********************/

// 登録ボタン
// 登録前にオーダー残チェック
function entryData() {
    var parentItemId = document.getElementById('parent_item_id').value;
    if (!gen.util.isNumeric(parentItemId)) {
        alert(_g("親品目を選択してください。"));
        return;
    }

    ajaxModeChange(true);

    gen.ajax.connect('Master_Bom_AjaxOrderRemained', {itemId : parentItemId}, 
        function(j) {
            if (j != '') {
                var msg = _g("以下の製造/外製指示が未完了ですが、構成を変更してもよろしいですか？") + "\n\n" + _g("※これらの製造/外製指示に対する実績/外製受入を登録する際、子品目の引き落としは製造/外製指示登録時の構成に基づいて行われます（今回の構成変更は反映されないことに注意してください）。今回の構成変更を反映させたい場合は、製造指示/外製指示登録画面で再登録する必要があります。") + "\n\n" + _g("オーダー番号:") + "\n"; // 「未完了の指示がある」警告
                for (i in j) {
                    msg += j[i] + "\n";
                }
                if (!confirm(msg)) {
                    ajaxModeChange(false);
                    return;
                }
            }

            var parentItemId = document.getElementById('parent_item_id').value;
            var entryData = "";
            var count = leftBoxElm.length;
            for (i=0;i < count;i++) {
                var itemId = leftBoxElm.options[i].value;
                var qty = leftBoxDecode(leftBoxElm.options[i].text).inzu;
                entryData += itemId + ';' + qty + ':';
            }
            if (entryData.length > 0) {
                entryData = entryData.substr(0, entryData.length-1);
            }
            // 取数モードを実装したが、使用中止になったので下行は無意味（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
            inzuMode = (document.getElementById('inzu_mode').value=='tori' ? 'tori' : '');

            gen.ajax.connect('Master_Bom_AjaxEntry', {parent_item_id : parentItemId, inzu_mode : inzuMode, entryData : entryData}, 
                function(j) {
                    if (j.status == 'success') {
                        var isFirst = true
                        treeview_tree.getNodesBy(function(node){
                            if (node.data.id==parentItemId) {
                                treeview_tree.removeChildren(node);
                                node.isLeaf = false;
                                // 登録した品目のノードがツリー内に複数ある場合、最初のノードのみ開いた状態とし、その他は閉じる。
                                // 複数ノードを連続して開くとajaxが追い付かないため。
                                if (isFirst) {
                                    $('#' + node.contentElId).click(); // フォーカスをあて、ノードを開く
                                    isFirst = false;
                                } else {
                                    node.collapse();
                                }
                            }
                        });                        
                        document.getElementById('reg_message').innerHTML = "<span style='background:#99ffcc'>" + _g("登録しました。") + "</span>";
                    } else {
                        alert(_g("登録に失敗しました。"));
                    }
                    editModeChange(false);
                    ajaxModeChange(false);
                    showParent();	// 標準原価更新
                }, false);    
        }, false);    
}

function entry_Failure(o) {
    alert(_g("登録に失敗しました。"));
    editModeChange(false);
    ajaxModeChange(false);
}

// Ajax通信中のカーソル・ボタン状態切り替え
function ajaxModeChange(isAjax) {
    document.getElementById('entryButton').disabled = isAjax;
    document.getElementById('resetButton').disabled = isAjax;
    document.getElementById('addButton').disabled = isAjax;
    document.getElementById('deleteButton').disabled = isAjax;
    document.getElementById('allDeleteButton').disabled = isAjax;
    document.getElementById('copyButton').disabled = isAjax;
    document.body.style.cursor = (isAjax ? "wait" : "default");
}

/****************** CSV/Excel **********************/

function exportData(isCsv, isTree) {
    var limit;
    if (isCsv) {
        limit = msgs.exportLimit;
        action='Master_Bom_Export'; 
    } else {
        limit = msgs.excelLimit;
        action='Master_Bom_Excel';
    }
    var url = 'index.php?action=' + action + (isTree ? '&tree' : '');
    var itemId = $('#parent_item_id').val();
    var notAll = $('#exportNotAll').is(':checked');
    if (!gen.util.isNumeric(itemId) && notAll) {
        alert(_g("親品目を選択してください。"));
        return;
    }
    //逆展開中の場合、Excelは逆展開で出力されるが、CSVは常に正展開となることに注意
    var reverse = ($('#reverseCheck').is(':checked'));
    if (notAll) 
        url += '&itemId=' + itemId + '&reverse=' + reverse;    

    var msg1 = _g("レコードの数が一度に出力可能な件数([MAX]件)を超えています。何番目のレコードから出力するかを指定してください。（指定したレコードから[MAX]件が出力されます。）").replace(/\[MAX\]/g,limit);
    if (notAll) {
        gen.ajax.connect('Master_Bom_AjaxChildDataCount', {itemId : itemId, reverse : reverse, isCsv : isCsv}, 
            function(j) {
                exportData2(url, j.dataCount, limit, msg1);
            });
    } else {
        exportData2(url, msgs.dataCount, limit, msg1);
    }
}

function exportData2(url, dataCount, limit, msg1)
{
    if (dataCount > limit) {
        while (true) {
            number = window.prompt(msg1, 1);
            if (number == null) {
                return;
            }

            if (gen.util.isNumeric(number)) {
                if (number > 0) {
                    break;
                }
            }
            alert(_g("入力が正しくありません。1以上の数値を指定してください。"));
        }
        url += "&gen_csvOffset=" + number;
    }
    location.href = url;
}

/****************** Util **********************/

// 指定した文字数（半角）になるまで、右に全角スペースを詰める
function rpadEx(argValue, argLength) {
    var res = argValue;
    var len = gen.util.lengthEx(argValue);
    while(argLength > len) {
        if (argLength - len <= 1) {
            res += " "; // 半角
        } else {
            res += "　"; // 全角
        }
        len += 2;
    }
    return res;
}

function trim(str){
    str = str.replace(/^[ 　]+/,"");
    str = str.replace(/[ 　]+$/,"");
    return(str);
}




// ******* DD関連（テスト中）　************************

    var buttonFlag = false;
    var ddFlag = false;
    var ddObj;
    var ddObj2;
    document.onmouseup = function() {
return;
        buttonFlag = false;
        if (!ddFlag) return;
        ddFlag = false;
        ddObj.style.visibility = "hidden";
        ddObj2.style.visibility = "hidden";
        rightBoxElm.disabled = false;

        var mx = window.event.clientX + document.body.scrollLeft;
        var my = window.event.clientY + document.body.scrollTop;
        var leftElm = document.getElementById('leftBoxDiv');
        var pos = gen_getControlPostion(leftElm);

        if (pos[0] <= mx && (pos[0] + leftElm.offsetWidth) >= mx
          && pos[1] <= my && (pos[1] + leftElm.offsetHeight) >= my) {
            onRegistButton();
        }
    };

    document.onmousemove = function() {
return;
        if (!ddFlag) {
            if (buttonFlag) {
                ddFlag = true;
                ddObj.style.visibility = "visible";
                ddObj2.style.visibility = "visible";
                rightBoxElm.disabled = true;
            } else {
                return;
            }
        }
        ddObj.style.left = window.event.clientX + document.body.scrollLeft + "px";
        ddObj.style.top = window.event.clientY + document.body.scrollTop + "px";
        ddObj2.style.left = window.event.clientX + document.body.scrollLeft + "px";
        ddObj2.style.top = window.event.clientY + document.body.scrollTop + "px";
    };

    function gen_getControlPostion(elm) {
return;
        var x = elm.offsetLeft - elm.scrollLeft;
        var y = elm.offsetTop - elm.scrollTop;
        while(elm.offsetParent) {
           elm = elm.offsetParent;
           x += (elm.offsetLeft - elm.scrollLeft);
           y += (elm.offsetTop - elm.scrollTop);
        }
        // 上の処理では親エレメントを最上位までたどって位置補正を行うが、最上位（body）のスクロール分は補正する
        // 必要がない（チップヘルプの位置はbodyに対する相対位置であるため）。ここで戻しておく
        //x += document.body.scrollLeft;
        //y += document.body.scrollTop;

        var res = new Array(2);
        res[0] = x;
        res[1] = y;
        return res;
    }

    function onRightBoxMouseDown() {
        buttonFlag = true;
    }

    function onRightBoxMouseUp() {
    }

    function initDD() {
        ddObj = document.getElementById('ddObj');
        ddObj2 = document.getElementById('ddObj2');
    }

// ******* DD関連（ここまで）　************************
