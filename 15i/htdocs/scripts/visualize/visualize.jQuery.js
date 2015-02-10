/*
 * --------------------------------------------------------------------
 * jQuery inputToButton plugin
 * Author: Scott Jehl, scott@filamentgroup.com
 * Copyright (c) 2009 Filament Group 
 * licensed under MIT (filamentgroup.com/examples/mit-license.txt)
 * --------------------------------------------------------------------
*/
(function($) { 
$.fn.visualize = function(options, container){
    return $(this).each(function(){
        //configuration
        var o = $.extend({
            type: 'bar', //also available: area, pie, line
            width: $(this).width(), //height of canvas - defaults to table height
            height: $(this).height(), //height of canvas - defaults to table height
            appendTitle: true, //table caption text is added to chart
            title: null, //grabs from table caption if null
            appendKey: true, //color key is added to chart
            // ***** Gen Hack ***** change 2 lines  jQuery最新版対応
            rowFilter: '*',
            colFilter: '*',
            //rowFilter: ' ',
            //colFilter: ' ',
            colors: ['#666699','#be1e2d','#92d5ea','#ee8310','#8d10ee','#5a3b16','#26a4ed','#f45a90','#e9e744'],
            textColors: [], //corresponds with colors array. null/undefined items will fall back to CSS
            parseDirection: 'x', //which direction to parse the table data
            pieMargin: 20, //pie charts only - spacing around pie
            pieLabelsAsPercent: true,
            pieLabelPos: 'inside',
            lineWeight: 4, //for line and area - stroke weight
            barGroupMargin: 10,
            barMargin: 1, //space around bars in bar chart (added to both sides of bar)
            yLabelInterval: 30 //distance between y labels
            // ***** Gen Hack ***** add 2 lines
            ,emphasis: null
            ,deactivateColor : "#aaaaaa"
        },options);
        
        //reset width, height to numbers
        o.width = parseFloat(o.width);
        o.height = parseFloat(o.height);
        
        var self = $(this);

        // ***** Gen Hack ***** add this section
        var activeLine = null;
        if(o.emphasis != null) {
            activeLine = o.emphasis;
        }
        
        //function to scrape data from html table
        function scrapeTable(){
            var colors = o.colors;
            var textColors = o.textColors;
            var tableData = {
                dataGroups: function(){
                    var dataGroups = [];
                    if(o.parseDirection == 'x'){
                        // データテーブルが1列1レコード形式（左端列が系列名で、右方向に向かってレコードが伸びていく）の場合。
                        // Genでは pie のみこの形式でパースする。（gen.chart.init()で設定）
                        self.find('tr:gt(0)').filter(o.rowFilter).each(function(i){
                            dataGroups[i] = {};
                            dataGroups[i].points = [];
                            // ***** Gen Hack ***** 項目が多いときのFFでのエラーを回避
                            if (i >= colors.length)
                                dataGroups[i].color = '#cccccc';
                            else
                                dataGroups[i].color = colors[i];
                            if(textColors[i]){ dataGroups[i].textColor = textColors[i]; }
                            $(this).find('td').filter(o.colFilter).each(function(){
                                // ***** Gen Hack ***** カンマ区切り数値に対応
                                dataGroups[i].points.push( parseFloat($(this).text().replace(/,/g, '')) );
                                //dataGroups[i].points.push( parseFloat($(this).text()) );
                            });
                        });
                    }
                    else {
                        // データテーブルが1行1レコード形式（最上行が系列名で、下方向に向かってレコードが伸びていく）の場合。
                        // Genでは pie以外はすべてこの形式でパースする。（gen.chart.init()で設定）
                        var cols = self.find('tr:eq(1) td').filter(o.colFilter).size();
                        for(var i=0; i<cols; i++){
                            dataGroups[i] = {};
                            dataGroups[i].points = [];
                            // ***** Gen Hack ***** add this section
                            if (activeLine != null) {
                                if (activeLine == (i + 1)) {
                                    dataGroups[i].color = colors[i];
                                }
                                else {
                                    dataGroups[i].color = o.deactivateColor;
                                }
                            }
                            else {
                                dataGroups[i].color = colors[i];
                            }
                            //dataGroups[i].color = colors[i];
                            
                            if(textColors[i]){ dataGroups[i].textColor = textColors[i]; }
                            self.find('tr:gt(0)').filter(o.rowFilter).each(function(){
                                // ***** Gen Hack ***** カンマ区切り数値に対応
                                dataGroups[i].points.push( parseFloat($(this).find('td').filter(o.colFilter).eq(i).text().replace(/,/g, '')) );
                                //dataGroups[i].points.push( $(this).find('td').filter(o.colFilter).eq(i).text()*1 );
                            });
                        };
                    }
                    return dataGroups;
                },
                allData: function(){
                    var allData = [];
                    $(this.dataGroups()).each(function(){
                        allData.push(this.points);
                    });
                    return allData;
                },
                dataSum: function(){
                    var dataSum = 0;
                    var allData = this.allData().join(',').split(',');
                    $(allData).each(function(){
                        dataSum += parseFloat(this);
                    });
                    return dataSum;
                },  
                // ***** Gen Hack ***** 2軸表示に対応, y軸が切りのいい値になるようにする
                topValue: function(series){
                    var obj = this.topBottomValue(series);
                    return obj.topValue;
                },
                // ***** Gen Hack ***** 2軸表示に対応, y軸が切りのいい値になるようにする
                bottomValue: function(series){
                    var obj = this.topBottomValue(series);
                    return obj.bottomValue;
                },
                // ***** Gen Hack ***** add
                topBottomValue: function(series){
                    var rawTopValue = 0;
                    var rawBottomValue = 0;
                    var topValue = 0;
                    var bottomValue = 0;
                    var allData;
                    if (series == undefined) {
                        allData = this.allData().join(',').split(',');
                    } else {
                        allData = this.dataGroups()[series].points;
                    }
                    $(allData).each(function(){
                        if(parseFloat(this,10)>rawTopValue) rawTopValue = parseFloat(this);
                        if(parseFloat(this,10)<rawBottomValue) rawBottomValue = parseFloat(this);
                    });
                    var numLabels = Math.round(o.height / o.yLabelInterval);
                    // bottomがマイナスの場合、このロジックではtopがはみ出してしまうケースがあるので、
                    // spanを広げている
                    var spanValue = ((rawTopValue - rawBottomValue) * (rawBottomValue < 0 ? 1.2 : 1)) / numLabels;
                    var pow = 1;
                    if (spanValue > 0 && spanValue < 1) {
                        while (spanValue < 1) {
                            spanValue *= 10;
                            pow *= 10;
                        }
                    } else if (spanValue < 0 && spanValue > -1) {
                        while (spanValue > -1) {
                            spanValue *= 10;
                            pow *= 10;
                        }
                    }
                    if (spanValue != 0) {
                        spanValue = Math.ceil(spanValue);
                        var keta = spanValue.toString().length;
                        var pow2 = Math.pow(10, keta - 1);
                        var span = Math.ceil(spanValue / pow2) * pow2;
                        if (rawBottomValue >= 0) {
                            bottomValue = Math.ceil(rawBottomValue / span) * span / pow;
                        } else {
                            bottomValue = Math.floor(rawBottomValue / span) * span / pow;
                        }
                        topValue = bottomValue + (span * numLabels / pow);
                    }
                    return {topValue: topValue, bottomValue: bottomValue};
                },
                memberTotals: function(){
                    var memberTotals = [];
                    var dataGroups = this.dataGroups();
                    $(dataGroups).each(function(l){
                        var count = 0;
                        $(dataGroups[l].points).each(function(m){
                            count +=dataGroups[l].points[m];
                        });
                        memberTotals.push(count);
                    });
                    return memberTotals;
                },
                yTotals: function(){
                    var yTotals = [];
                    var dataGroups = this.dataGroups();
                    var loopLength = this.xLabels().length;
                    for(var i = 0; i<loopLength; i++){
                        yTotals[i] =[];
                        var thisTotal = 0;
                        $(dataGroups).each(function(l){
                            yTotals[i].push(this.points[i]);
                        });
                        yTotals[i].join(',').split(',');
                        $(yTotals[i]).each(function(){
                            thisTotal += parseFloat(this);
                        });
                        yTotals[i] = thisTotal;
                        
                    }
                    return yTotals;
                },
                topYtotal: function(){
                    var topYtotal = 0;
                        var yTotals = this.yTotals().join(',').split(',');
                        $(yTotals).each(function(){
                            if(parseFloat(this,10)>topYtotal) topYtotal = parseFloat(this);
                        });
                        return topYtotal;
                },
                // ***** Gen Hack ***** 2軸表示に対応
                totalYRange: function(series){
                    return this.topValue(series) - this.bottomValue(series);
                },
                xLabels: function(){
                    var xLabels = [];
                    if(o.parseDirection == 'x'){
                        self.find('tr:eq(0) th').filter(o.colFilter).each(function(){
                                                        xLabels.push($(this).html());
                        });
                    }
                    else {
                        self.find('tr:gt(0) th').filter(o.rowFilter).each(function(){
                            xLabels.push($(this).html());
                        });
                    }
                    return xLabels;
                },
                // ***** Gen Hack ***** 2軸表示に対応
                yLabels: function(series){
                    var yLabels = [];
                    yLabels.push(series == 1 ? bottomValue2 : bottomValue); 
                    var numLabels = Math.round(o.height / o.yLabelInterval);
                    var loopInterval = Math.ceil((series == 1 ? totalYRange2 : totalYRange) / numLabels) || 1;
                    while( yLabels[yLabels.length-1] < (series == 1 ? topValue2 : topValue) - loopInterval){
                        yLabels.push(yLabels[yLabels.length-1] + loopInterval); 
                    }
                    yLabels.push((series == 1 ? topValue2 : topValue)); 
                    return yLabels;
                }            
            };
            
            return tableData;
        };
        
        //function to create a chart
        var createChart = {
            pie: function(){    
                
                canvasContain.addClass('visualize-pie');
                
                if(o.pieLabelPos == 'outside'){ canvasContain.addClass('visualize-pie-outside'); }    
                        
                var centerx = Math.round(canvas.width()/2);
                var centery = Math.round(canvas.height()/2);
                var radius = centery - o.pieMargin;                
                var counter = 0.0;
                var toRad = function(integer){ return (Math.PI/180)*integer; };
                var labels = $('<ul class="visualize-labels"></ul>')
                    .insertAfter(canvas);

                //draw the pie pieces
                $.each(memberTotals, function(i){
                    var fraction = (this <= 0 || isNaN(this))? 0 : this / dataSum;
                    ctx.beginPath();
                    ctx.moveTo(centerx, centery);
                    ctx.arc(centerx, centery, radius, 
                        counter * Math.PI * 2 - Math.PI * 0.5,
                        (counter + fraction) * Math.PI * 2 - Math.PI * 0.5,
                        false);
                    ctx.lineTo(centerx, centery);
                    ctx.closePath();
                    ctx.fillStyle = dataGroups[i].color;
                    ctx.fill();
                    // draw labels
                       var sliceMiddle = (counter + fraction/2);
                       var distance = o.pieLabelPos == 'inside' ? radius/1.5 : radius +  radius / 5;
                    var labelx = Math.round(centerx + Math.sin(sliceMiddle * Math.PI * 2) * (distance));
                    var labely = Math.round(centery - Math.cos(sliceMiddle * Math.PI * 2) * (distance));
                    var leftRight = (labelx > centerx) ? 'right' : 'left';
                    var topBottom = (labely > centery) ? 'bottom' : 'top';
                    var percentage = parseFloat((fraction*100).toFixed(2));

                    if(percentage){
                        var labelval = (o.pieLabelsAsPercent) ? percentage + '%' : this;
                        var labeltext = $('<span class="visualize-label">' + labelval +'</span>')
                            .css(leftRight, 0)
                            .css(topBottom, 0);
                            if(labeltext)
                        var label = $('<li class="visualize-label-pos"></li>')
                                   .appendTo(labels)
                                .css({left: labelx, top: labely})
                                .append(labeltext);    
                        labeltext
                            .css('font-size', radius / 8)        
                            .css('margin-'+leftRight, -labeltext.width()/2)
                            .css('margin-'+topBottom, -labeltext.outerHeight()/2);
                            
                        if(dataGroups[i].textColor){ labeltext.css('color', dataGroups[i].textColor); }    
                    }
                      counter+=fraction;
                });
            },
            
            line: function(area){
            
                if(area){ canvasContain.addClass('visualize-area'); }
                else{ canvasContain.addClass('visualize-line'); }
            
                //write X labels
                var xInterval = canvas.width() / (xLabels.length - 1);
                var xlabelsUL = $('<ul class="visualize-labels-x"></ul>')
                    .width(canvas.width())
                    .height(canvas.height())
                    .insertBefore(canvas);
                var integer = 0;
                $.each(xLabels, function(i){ 
                    var xVal = (integer - o.barGroupMargin) - 38;
                    var thisLi = $('<li><span style=\"font-size:11px\">'+this+'</span></li>')
                        .prepend('<span class="line" />')
                        .css('width', '90px')
                        .css('height', '12px')
                        .css('overflow', 'hidden')
                        .css('text-align', 'right')
                        .css('position', 'absolute')
                        .css('top', canvas.height()+50)
                        .css('left', xVal)
                        .css('-ms-transform' , 'rotate(-90deg)')
                        .css('-moz-transform' , 'rotate(-90deg)')
                        .css('-webkit-transform' , 'rotate(-90deg)')
                        .appendTo(xlabelsUL);                        
                    integer+=xInterval;
                });

                //write Y labels
                var yScale = canvas.height() / totalYRange;
                var liBottom = canvas.height() / (yLabels.length-1);
                var ylabelsUL = $('<ul class="visualize-labels-y"></ul>')
                    .width(canvas.width())
                    .height(canvas.height())
                    .insertBefore(canvas);
                    
                $.each(yLabels, function(i){  
                    // ***** Gen Hack ***** 縦軸ラベルのカンマ区切り
                    var lv = this;
                    if (lv != '' && !isNaN(lv) && lv >= 1000) {
                        lv = new String(lv).replace(/,/g, "");
                        while(lv != (lv = lv.replace(/^(-?\d+)(\d{3})/, "$1,$2")));
                    }
                    var thisLi = $('<li><span style="word-wrap:break-word;max-height:90px;overflow:hidden">'+lv+'</span></li>')
                        .prepend('<span class="line"  />')
                        .css('bottom',liBottom*i)
                        .prependTo(ylabelsUL);
                    var label = thisLi.find('span:not(.line)');
                    var topOffset = label.height()/-2;
                    if(i == 0){ topOffset = -label.height(); }
                    else if(i== yLabels.length-1){ topOffset = 0; }
                    label
                        .css('margin-top', topOffset)
                        .addClass('label');
                });

                //start from the bottom left
                ctx.translate(0,zeroLoc);
                //iterate and draw
                $.each(dataGroups,function(h){
                    ctx.beginPath();
                    ctx.lineWidth = o.lineWeight;
                    ctx.lineJoin = 'round';
                    var points = this.points;
                    var integer = 0;
                    ctx.moveTo(0,-(points[0]*yScale));
                    $.each(points, function(){
                        ctx.lineTo(integer,-(this*yScale));
                        integer+=xInterval;
                    });
                    ctx.strokeStyle = this.color;
                    ctx.stroke();
                    if(area){
                        ctx.lineTo(integer,0);
                        ctx.lineTo(0,0);
                        ctx.closePath();
                        ctx.fillStyle = this.color;
                        ctx.globalAlpha = .3;
                        ctx.fill();
                        ctx.globalAlpha = 1.0;
                    }
                    else {ctx.closePath();}
                });
            },
            
            area: function(){
                createChart.line(true);
            },
            
            // ***** Gen Hack ***** 第2系列を折れ線グラフで表示するモードを追加
            bar: function(isBarLine){
            //bar: function(){
                
                canvasContain.addClass('visualize-bar');
            
                //write X labels
                var xInterval = canvas.width() / (xLabels.length);
                var xlabelsUL = $('<ul class="visualize-labels-x"></ul>')
                    .width(canvas.width())
                    .height(canvas.height())
                    .insertBefore(canvas);
                var integer = 0;
                var linewidth = (xInterval - o.barGroupMargin * 2) / (isBarLine ? 1 : dataGroups.length);
                $.each(xLabels, function(i){ 
                    var xVal = (integer - o.barGroupMargin) + linewidth / 2 - 25;
                    var thisLi = $('<li><span style=\"font-size:11px\">'+this+'</span></li>')
                        .prepend('<span class="line" />')
                        .css('width', '90px')
                        .css('height', '12px')
                        .css('overflow', 'hidden')
                        .css('text-align', 'right')
                        .css('position', 'absolute')
                        .css('top', canvas.height()+50)
                        .css('left', xVal)
                        .css('-ms-transform' , 'rotate(-90deg)')
                        .css('-moz-transform' , 'rotate(-90deg)')
                        .css('-webkit-transform' , 'rotate(-90deg)')
                        .appendTo(xlabelsUL);
                    var label = thisLi.find('span.label');
                    label.addClass('label');
                    integer+=xInterval;
                });

                //write Y labels
                var yScale = canvas.height() / totalYRange;
                var liBottom = canvas.height() / (yLabels.length-1);
                var ylabelsUL = $('<ul class="visualize-labels-y"></ul>')
                    .width(canvas.width())
                    .height(canvas.height())
                    .insertBefore(canvas);
                $.each(yLabels, function(i){
                    // ***** Gen Hack ***** 縦軸ラベルのカンマ区切り
                    var lv = this;
                    if (lv != '' && !isNaN(lv) && lv >= 1000) {
                        lv = new String(lv).replace(/,/g, "");
                        while(lv != (lv = lv.replace(/^(-?\d+)(\d{3})/, "$1,$2")));
                    }
                    var thisLi = $('<li><span>'+lv+'</span></li>')
                        .prepend('<span class="line"  />')
                        .css('bottom',liBottom*i)
                        .prependTo(ylabelsUL);
                        var label = thisLi.find('span:not(.line)');
                        var topOffset = label.height()/-2;
                        if(i == 0){ topOffset = -label.height(); }
                        else if(i== yLabels.length-1){ topOffset = 0; }
                        label
                            .css('margin-top', topOffset)
                            .addClass('label');
                });
            
                //start from the bottom left
                ctx.translate(0,zeroLoc);
                //iterate and draw
                // ***** Gen Hack ***** chagnge 1 line 第2系列を折れ線グラフで表示するモードでは、最初の系列だけを表示
                for(var h=0; h<(isBarLine ? 1 : dataGroups.length); h++){
                //for(var h=0; h<dataGroups.length; h++){
                    ctx.beginPath();
                    // ***** Gen Hack ***** chagnge 1 line 
                    var linewidth = (xInterval-o.barGroupMargin*2) / (isBarLine ? 1 : dataGroups.length); //removed +1 
                    var strokeWidth = linewidth - (o.barMargin*2);
                    ctx.lineWidth = strokeWidth;
                    var points = dataGroups[h].points;
                    var integer = 0;
                    for(var i=0; i<points.length; i++){
                        var xVal = (integer-o.barGroupMargin)+(h*linewidth)+linewidth/2;
                        xVal += o.barGroupMargin*2;

                        ctx.moveTo(xVal, 0);
                        ctx.lineTo(xVal, Math.round(-points[i]*yScale));
                        integer+=xInterval;
                    }
                    ctx.strokeStyle = dataGroups[h].color;
                    ctx.stroke();
                    ctx.closePath();
                }
                
                // ***** Gen Hack ***** add this section 第2系列を折れ線グラフで表示するモードを追加
                if (isBarLine) {
                    var yScale2 = canvas.height() / totalYRange2;
                    var liBottom2 = canvas.height() / (yLabels2.length-1);
                    var ylabelsUL2 = $('<ul class="visualize-labels-y"></ul>')
                        .width(canvas.width())
                        .height(canvas.height())
                        .insertBefore(canvas);
                    var yLabelWidthMax = 0;
                    $.each(yLabels2, function(i){  
                        var lv = this;
                        if (lv != '' && !isNaN(lv) && lv >= 1000) {
                            lv = new String(lv).replace(/,/g, "");
                            while(lv != (lv = lv.replace(/^(-?\d+)(\d{3})/, "$1,$2")));
                        }
                        var thisLi = $('<li><span class="gen_visualize_ylabel_span">'+lv+'</span></li>')
                            .prepend('<span class="line"  />')
                            .css('bottom',liBottom2*i)
                            .prependTo(ylabelsUL2);
                            var label2 = thisLi.find('span:not(.line)');
                            var topOffset2 = label2.height()/-2;
                            if(i == 0){ topOffset2 = -label2.height(); }
                            else if(i== yLabels2.length-1){ topOffset2 = 0; }
                            if (yLabelWidthMax < label2.width()) {yLabelWidthMax = label2.width() }
                            label2
                                .css('margin-top', topOffset2)
                                .addClass('label');
                    });
                    $('.gen_visualize_ylabel_span').css('margin-right', (o.width * -1) - 10 - (yLabelWidthMax) + 'px');
            
                    var zeroDelta = zeroLoc - zeroLoc2;
                    //ctx.translate(0,zeroLoc2);    translateの再実行はできない
                    //iterate and draw
                    ctx.beginPath();
                    ctx.lineWidth = o.lineWeight;
                    ctx.lineJoin = 'round';
                    points = dataGroups[1].points;
                    integer = xInterval / 2;
                    ctx.moveTo(integer,-(points[0]*yScale2) - zeroDelta);
                    $.each(points, function(){
                        ctx.lineTo(integer,-(this*yScale2) - zeroDelta);
                        integer+=xInterval;
                    });
                    ctx.strokeStyle = dataGroups[1].color;
                    ctx.stroke();
                    ctx.closePath();
                }
            },
            
            // ***** Gen Hack ***** add this section 第2系列を折れ線グラフで表示するモードを追加
            bar_line: function(){
                createChart.bar(true);
            }
        };
    
        //create new canvas, set w&h attrs (not inline styles)
        var canvasNode = document.createElement("canvas"); 
        canvasNode.setAttribute('height',o.height);
        canvasNode.setAttribute('width',o.width);
        var canvas = $(canvasNode);
            
        //get title for chart
        var title = o.title || self.find('caption').text();
        
        //create canvas wrapper div, set inline w&h, append
        var canvasContain = (container || $('<div class="visualize" role="img" aria-label="Chart representing data from the table: '+ title +'" />'))
            .height(o.height)
            .width(o.width)
            .append(canvas);

        //scrape table (this should be cleaned up into an obj)
        var tableData = scrapeTable();
        var dataGroups = tableData.dataGroups();
        var allData = tableData.allData();
        var dataSum = tableData.dataSum();
        // ***** Gen Hack ***** bar_line（第一系列が棒、第二系列が折れ線）での2軸表示に対応
        var topValue;
        var topValue2;
        var bottomValue;
        var bottomValue2;
        var totalYRange;
        var totalYRange2;
        var zeroLoc;
        var zeroLoc2;
        var yLabels;
        var yLabels2;
  
        if (o.type == 'bar_line' && allData.length <= 1) {
            o.type = 'bar';
        }
        if (o.type == 'bar_line') {
            topValue = tableData.topValue(0);
            topValue2 = tableData.topValue(1);
            bottomValue = tableData.bottomValue(0);
            bottomValue2 = tableData.bottomValue(1);
            totalYRange = tableData.totalYRange(0);
            totalYRange2 = tableData.totalYRange(1);
            zeroLoc = o.height * (topValue/totalYRange);
            zeroLoc2 = o.height * (topValue2/totalYRange2);
            yLabels = tableData.yLabels(0);
            yLabels2 = tableData.yLabels(1);
        } else {
            topValue = tableData.topValue();
            bottomValue = tableData.bottomValue();
            totalYRange = tableData.totalYRange();
            zeroLoc = o.height * (topValue/totalYRange);
            yLabels = tableData.yLabels();
        }
        var xLabels = tableData.xLabels();
        var memberTotals = tableData.memberTotals();
                                
        //title/key container
        if(o.appendTitle || o.appendKey){
            var infoContain = $('<div class="visualize-info"></div>')
                .appendTo(canvasContain);
        }
        
        //append title
        if(o.appendTitle){
            $('<div class="visualize-title">'+ title +'</div>').appendTo(infoContain);
        }
        
        //append key
        if(o.appendKey){
            var newKey = $('<ul class="visualize-key"></ul>');
            var selector;
            if(o.parseDirection == 'x'){
                selector = self.find('tr:gt(0) th').filter(o.rowFilter);
            }
            else{
                selector = self.find('tr:eq(0) th').filter(o.colFilter);
            }
            
            selector.each(function(i){
                // ***** Gen Hack ***** ラベルの文字数制限
                var color = '';
                if (dataGroups[i] != undefined) color = dataGroups[i].color;
                $('<li><span class="visualize-key-color" style="background: '+color+'"></span><span class="visualize-key-label">'+ $(this).text().replace(/^\s+/, "").substr(0,16) +'</span></li>')
                //$('<li><span class="visualize-key-color" style="background: '+dataGroups[i].color+'"></span><span class="visualize-key-label">'+ $(this).text() +'</span></li>')
                    .appendTo(newKey);
            });
            newKey.appendTo(infoContain);
        };        
        
        //append new canvas to page
        if(!container){canvasContain.insertAfter(this); }
        if( typeof(G_vmlCanvasManager) != 'undefined' ){ G_vmlCanvasManager.init(); G_vmlCanvasManager.initElement(canvas[0]); }    
        
        //set up the drawing board    
        var ctx = canvas[0].getContext('2d');
        
        //create chart
        createChart[o.type]();
        
        //clean up some doubled lines that sit on top of canvas borders (done via JS due to IE)
        $('.visualize-line li:first-child span.line, .visualize-line li:last-child span.line, .visualize-area li:first-child span.line, .visualize-area li:last-child span.line, .visualize-bar li:first-child span.line,.visualize-bar .visualize-labels-y li:last-child span.line').css('border','none');
        if(!container){
        //add event for updating
        canvasContain.bind('visualizeRefresh', function(){
            self.visualize(o, $(this).empty()); 
        });
        }
    }).next(); //returns canvas(es)
};
})(jQuery);


