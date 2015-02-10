/*
 * Script from NETTUTS.com [by James Padolsey]
 * @requires jQuery($), jQuery UI & sortable/draggable UI modules
 */

var iNettuts = {
    
    jQuery : $,
    
    settings : {
        columns : '.column',
        widgetSelector: '.widget',
        handleSelector: '.widget-head',
        contentSelector: '.widget-content',
        widgetDefault : {
            movable: true,
            removable: false,
            collapsible: true
        },
        widgetIndividual : {
            intro : {
                movable: false,
                removable: false,
                collapsible: false
            }
        }
    },

    init : function () {
        this.addWidgetControls();
        this.makeSortable();
        
        // Gen Hack (開閉状態にあわせてアイコンを設定)
        var settings = this.settings;
        $(settings.widgetSelector, $(settings.columns)).each(function () {
            var contentElm = $(this).find(settings.contentSelector);
            var buttonElm = $(this).find('.collapse');
            if (contentElm.css('display') != 'none') {
                buttonElm.css({backgroundPosition: '-52px 0'});
            } else {
                buttonElm.css({backgroundPosition: '-38px 0'});
            }
        });
    },
    
    getWidgetSettings : function (id) {
        var $ = this.jQuery,
            settings = this.settings;
        return (id&&settings.widgetIndividual[id]) ? $.extend({},settings.widgetDefault,settings.widgetIndividual[id]) : settings.widgetDefault;
    },
    
    addWidgetControls : function () {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings;
            
        $(settings.widgetSelector, $(settings.columns)).each(function () {
            var thisWidgetSettings = iNettuts.getWidgetSettings(this.id);
            if (thisWidgetSettings.removable) {
                $('<a href="#" class="remove">×</a>').mousedown(function (e) {
                    e.stopPropagation();    
                }).click(function () {
                    if(confirm('This widget will be removed, ok?')) {
                        $(this).parents(settings.widgetSelector).animate({
                            opacity: 0    
                        },function () {
                            $(this).wrap('<div/>').parent().slideUp(function () {
                                $(this).remove();
                            });
                        });
                    }
                    return false;
                }).appendTo($(settings.handleSelector, this));
            }
            
            if (thisWidgetSettings.collapsible) {
                $('<a href="#" class="collapse">COLLAPSE</a>').mousedown(function (e) {
                    e.stopPropagation();    
                    // Gen Hack (デフォルトhideを可能に。また開閉状態をサーバーに登録)     
                    var contentElm = $(this).parents(settings.widgetSelector).find(settings.contentSelector);
                    if (contentElm.css('display') != 'none') {
                        $(this).css({backgroundPosition: '-38px 0'});
                        contentElm.hide();
                    } else {
                        $(this).css({backgroundPosition: ''});
                        contentElm.show();
                    }
                    iNettuts.saveSort();
                    return false;
                }).prependTo($(settings.handleSelector,this));
                
//              ちなみに .toggle(function(), function()) は jQuery 1.9で廃止                
//                }).toggle(function () {
//                    $(this).css({backgroundPosition: '-38px 0'})
//                        .parents(settings.widgetSelector)
//                            .find(settings.contentSelector).hide();
//                    return false;
//                },function () {
//                    $(this).css({backgroundPosition: ''})
//                        .parents(settings.widgetSelector)
//                            .find(settings.contentSelector).show();
//                    return false;
//                }).prependTo($(settings.handleSelector,this));
            }
        });
        
        $('.edit-box').each(function () {
            $('input',this).keyup(function () {
                $(this).parents(settings.widgetSelector).find('h3').text( $(this).val().length>20 ? $(this).val().substr(0,20)+'...' : $(this).val() );
            });
            $('ul.colors li',this).click(function () {
                
                var colorStylePattern = /\bcolor-[\w]{1,}\b/,
                    thisWidgetColorClass = $(this).parents(settings.widgetSelector).attr('class').match(colorStylePattern)
                if (thisWidgetColorClass) {
                    $(this).parents(settings.widgetSelector)
                        .removeClass(thisWidgetColorClass[0])
                        .addClass($(this).attr('class').match(colorStylePattern)[0]);
                }
                return false;
                
            });
        });
        
    },
    
    makeSortable : function () {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings,
            $sortableItems = (function () {
                var notSortable = '';
                $(settings.widgetSelector,$(settings.columns)).each(function (i) {
                    if (!iNettuts.getWidgetSettings(this.id).movable) {
                        if(!this.id) {
                            this.id = 'widget-no-id-' + i;
                        }
                        notSortable += '#' + this.id + ',';
                    }
                });
// jQuery最新版に対応するためのHack                
                if (notSortable=='') {
                    return $('> li', settings.columns);
                } else {
                    return $('> li:not(' + notSortable + ')', settings.columns);
                }
            })();
        
        $sortableItems.find(settings.handleSelector).css({
            cursor: 'move'
        }).mousedown(function (e) {
            $sortableItems.css({width:''});
            $(this).parent().css({
                width: $(this).parent().width() + 'px'
            });
        }).mouseup(function () {
            if(!$(this).parent().hasClass('dragging')) {
                $(this).parent().css({width:''});
            } else {
                $(settings.columns).sortable('disable');
            }
        });

        $(settings.columns).sortable({
            items: $sortableItems,
            connectWith: $(settings.columns),
            handle: settings.handleSelector,
            placeholder: 'widget-placeholder',
            forcePlaceholderSize: true,
            revert: 300,
            delay: 100,
            opacity: 0.8,
            containment: 'document',
            start: function (e,ui) {
                $(ui.helper).addClass('dragging');
            },
            stop: function (e,ui) {
                $(ui.item).css({width:''}).removeClass('dragging');
                $(settings.columns).sortable('enable');
// Gen Hack
                iNettuts.saveSort();
            }
        });
    }
// Gen Hack 並び順と開閉状態をサーバーに保存
    ,saveSort : function() {
        var settings = this.settings,
        ids = '';                
        $.each(
            $(settings.columns), 
            function() {
                if (ids != "") ids += ',';
                ids += '0';  // ul
                $.each(
                    $(this).find(settings.widgetSelector),
                    function() {
                        if (ids != "") ids += ',';
                        ids += this.id.replace('cell','');  // li
                        if ($(this).find(settings.contentSelector).css('display') == 'none') {
                            ids += 'c';
                        }
                    }
                )
            }
        ); 
        gen.ajax.connect('Config_Setting_AjaxDashboardInfo', {widgetIds : ids}, 
            function(j){
            });
    }
  
};
$(function(){
    iNettuts.init();
});
