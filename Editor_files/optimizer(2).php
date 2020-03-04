/**
 * 스마트 디자인 에디터용 도움말 코드 래퍼
 *
 * @type {{ENDPOINT: string, available: (function(): boolean), print: SDE.Util.HelpCode.print}}
 * @use SDE.Util.HelpCode.print(jQuerySelector);
 */
SDE.Util.HelpCode = {
    /**
     * HelpCode 사용 가능한지 반환
     * 로드되는 시간을 특정할 수 없으므로 함수로 체크
     * @returns {boolean}
     */
    available: function () {
        return (
            typeof window.serviceGuide === 'object'
        );
    },

    /**
     * 해당 jQuery Element 하위의 도움말 삽입
     * @param $element
     * @returns {boolean}
     */
    print: function ($element) {
        if (this.available() === false || typeof $element !== 'object') {
            return false;
        }

        if ($element.length === 0) {
            return false;
        }

        window.serviceGuide.setToolTip();
        return true;
    }
};
SDE.Component.List = Class.extend({
    isRendered : false,

    EXCEPTION_KEYS : [],
    

    /**
     * @param data array [ { key : '', name : ''}, ... ]
     */
    init : function(element, data) {
        this.$element = $(element);
        
        this.data = data;
    },

    setData : function(data) {
        this.data = data;
    },
    
    render : function(isReRender) {
        if (isReRender || !this.isRendered) {
            this._render();
        
            this._setEventHandler();

            this.isRendered = true;
        }
        
        this._triggerClick();
    },

    _triggerClick : function() {
        this.$element.find('li:first').trigger('click');
    },
    
    _onClickList : function(evt) {
        var $target = $(evt.currentTarget);

        this._setSelected($target);
        
        this.$currentList = $target;

        $(this).trigger('list-click', [$target.data('key'), $target.text()]);
    },

    _setSelected : function($target) {
        if (this.$currentList) this.$currentList.removeClass('selected');
        
        $target.addClass('selected');
    },
    
    _setEventHandler : function() {
        var $lists = this.$element.find('li');

        $lists.click($.proxy(this._onClickList, this));
    },
      
    _render : function() {
        var html = '<ul>',
            key, data;
        
        for (key in this.data) {
            data = this.data[key];

            if ($.inArray(data['key'], this.EXCEPTION_KEYS) != -1 || data['isUsing'] === false) continue;

            
            html += '<li data-key="'+ data['key']+'"><a href="#">'+ data['name'] +'</a></li>';
        }

        html += '</ul>';
        
        this.$element.html(html);
    }
});

SDE.Component.BoardList = SDE.Component.List.extend({
    EXCEPTION_KEYS : [10, 11],

    init : function(element) {
        var boardData, key;

        this.$element = $(element);
        this.data = [];

        boardData =  $.parseJSON($.ajax({
            async : false,
            dataType : 'json',
            url : getMultiShopUrl('/exec/admin/board/getboard') 
        }).responseText).results;

        for (key in boardData) {
            this.data.push({ 
                'key' : boardData[key]['board_no'], 
                'name' : boardData[key]['board_name']
            });
        }
    }
});


SDE.Component.CategoryList = SDE.Component.List.extend({
    init : function(element) {
        this.$element = $(element);

        this.data = $.parseJSON($.ajax({
            async : false,
            dataType : 'json',
            url : getMultiShopUrl('/exec/admin/product/categorylist') 
        }).responseText);
    },

    _render : function() {
        var html = '<ul>' + this._renderList(this.data) + '</ul>';
        
        $(html).appendTo(this.$element);
    },

    _renderList : function(data) {
        var html = '', cate, key;

        for (key in data) {
            cate = data[key];
            
            if (cate['is_main'] == 'T')
                html += '<li data-key="'+ cate['category_no']+'"><a href="#">'+ cate['category_name'] +'</a></li>';

            html += this._renderList(cate['sub']);
        }

        return html;
    }
});


SDE.Component.Image = Class.extend({
    TEMPLATE : '<form method="post" enctype="multipart/form-data" target="hidden-submit"><input type="hidden" name="key"><input type="file" class="fFile" accept="image/*" name="image" size="${size}" style="width:${width}px;"><span class="frame"></span></form>',
    
    CONFIG : {
        name : '',
        size : 16,
        width : 208,
        max_width : 204
    },

    ACTION : getMultiShopUrl('/exec/admin/editor/uploadimage'),
    
    DEFAULT_IMAGE_SRC : '//img.echosting.cafe24.com/smartAdmin/img/editor/@img_product.jpg',
    
    init : function($container, config) {
        
        this.config = $.extend({}, this.CONFIG, config);
        
        $.tmpl(this.TEMPLATE, this.config).appendTo($container);
        
        this.$form = $container.find('form');
        this.$input = $container.find('input[type=file]');
        this.$key = $container.find('input[name=key]');
        this.$preview = $container.find('.frame');
        
        
        this.$input.change($.proxy(this._onChangeImage, this));
        this.$form.attr('action', this.ACTION);
        
        SDE.BroadCastManager.listen('layer-image-uploaded', $.proxy(this._onImageUploaded, this));
    },
    
    reset : function() {
        this.$input.val('');
        this.$preview.empty();
    },
    
    set : function(src) {
        this.$preview.html('<img style="max-width:'+ this.config.max_width +'px" src="'+ (src || this.DEFAULT_IMAGE_SRC) +'">');
    },
    
    _onChangeImage : function(evt) {
        var name = this.$input.val(),
            key = makeRandomString();
        
        if (!name) return;
        
        if (!SDE.Util.File.isValidImageName(name)) {
            alert(__('JPG.JPEG.PNG.IMAGES', 'EDITOR.COMPONENT.IMAGE'));
            return;
        }
        
        this.key = key;
        this.$key.val(key);
        this.$form.submit();
    },
    
    _onImageUploaded : function(evt, key, isSuccess, src) {
        if (key != this.key) return;
        
        if (!isSuccess) {
            alert(__('IMAGE.SELECT.NORMAL.IMAGE', 'EDITOR.COMPONENT.IMAGE'));
            this._isEditing = false;
            return;
        }
        
        this.set(src);
        
        $(this).trigger('image-uploaded', [src]);
    }
});

SDE.Component.ColorPicker = Class.extend({
    init : function(element) {
        this.$element = $(element);
        
        this.$element
            .change($.proxy(this._onColorChanged, this));
    },

    _onColorChanged : function(evt) {
        $(this).trigger('color-changed', [this.$element.val()]);
    },
    
    set : function(val) {
        this.$element.
            val(val)
            .colorPicker();
    }
});
SDE.Component.Radio = Class.extend({
    init : function(element) {
        this.$element = $(element);
        
        this.$element
            .change($.proxy(this._onRadioChanged, this));
    },

    _onRadioChanged : function(evt) {
        $(this).trigger('radio-changed', [$(evt.target).val()]);
    },
    
    set : function(val) {
        this.$element.filter('[value='+ val +']').attr('checked', 'checked');
    }
});

/**
 * Layer Abstract Class
 * 
 * dependency : 3rdparty/jquery.template.js (http://api.jquery.com/category/plugins/templates/), View/import/layer.tpl
 */
SDE.Layer.Base = Class.extend({
    TEMPLATE_SELECTOR : '#layTemplate',
    BG_SELECTOR : '#layBg',
    
    DEFAULT : {
        id : '',
        title : '',
        content : '',
        btn_name : __('SAVE', 'EDITOR.LAYER.BASE'),
        close : __('CLOSE', 'EDITOR.LAYER.BASE'),
        layer_close : __('CLOSE.THE.LAYER', 'EDITOR.LAYER.BASE'),
        type : 'Pop' // Pop or Editor
    },
    
    DATA : {
        
    },
    
    init : function() {
        this._render();
        
        this._setEventHandler();
        
        this.$handler = $({});
    },
    
    hide : function() {
        this.$element.hide();
        
        this.$bg.hide();
        
        this.$handler.triggerHandler('hide');
    },
    
    submit : function(data) {
        this.$handler.triggerHandler('save', data);
        
        this.hide();
    },
    
    show : function() {
        this.$element.show();
        
        this.$bg.show();
        
        $(this).triggerHandler('show');
    },
    
    listen : function(eventName, callback) {
        if (typeof eventName == 'object') {
            this.$handler.bind(eventName);
        } else {
            this.$handler.bind(eventName, callback);
        }
    },
    
    _render : function() {
        var data = $.extend({}, this.DEFAULT, this.DATA);
        
        this.$element = $(this.TEMPLATE_SELECTOR).tmpl(data).appendTo(document.body);
        
        this.$content = this.$element.find('.layContainer');
        
        this.$bg = $(this.BG_SELECTOR);
    },
    
    _setEventHandler : function() {
        this.$element.find('.btnCancel, .layClose').click($.proxy(this._onClickClose, this));
        
        this.$element.find('.btnSubmit').click($.proxy(this._onClickSubmit, this));
        
        this.$bg.click($.proxy(this._onClickBg, this));
    },
    
    _onClickBg : function(evt) {
        this.hide();
    },
    
    _onKeyUp : function(evt) {
        if (evt.keyCode != 27) return;
            
        this.hide();  
    },
    
    _onClickClose : function(evt) {
        this.hide();
    },
    
    _onClickSubmit : function(evt) {
        this.submit();
    }
});
SDE.Layer.Favorite = SDE.Layer.Base.extend({
    DATA : {
        id : 'layFavorite',
        title : __('BOOKMARK.DETAILS', 'EDITOR.LAYER.FAVORITE'),
        content : '<div class="layContent">' +
                      '<label class="name"><strong class="title"><span>'+ __('FAVORITE.NAME', 'EDITOR.LAYER.FAVORITE') +'</span></strong><input type="text" value="" class="fText" style="width:160px;"></label>' +
                      '<h3>'+ __('FAVORITE.PATH', 'EDITOR.LAYER.FAVORITE') +'</h3><p class="path"><strong>'+ __('VIEW.DIRECTORY', 'EDITOR.LAYER.FAVORITE') +'</strong><span></span></p>' +
                  '</div>'
    },
    
    show : function(name, url) {
        if (!this.$name) this.$name = this.$content.find('input');
        if (!this.$path) this.$path = this.$content.find('.path span');
        
        this.$name.val(name);
        this.$path.html(url);
        
        this._super();
    },
    
    _onClickSubmit : function(evt) {
        var name = $.trim(this.$name.val());
        
        if (name == '') {
            alert(__('ENTER.YOUR.FAVORITE.NAME', 'EDITOR.LAYER.FAVORITE'));
            this.$name.focus();
            return;
        }
        
        this.submit([name]);
    }
});
SDE.Layer.Search = SDE.Layer.Base.extend({
    DATA : {
        id : 'laySearch',
        title : __('SCREEN.NAME.SEARCH', 'EDITOR.LAYER.SEARCH'),
        btn_name : __('OPEN.SELECTION.FILE', 'EDITOR.LAYER.SEARCH'),
        content : 
                  '<div class="layContent">' +
                      '<form onsubmit="return false">' +
                          '<h3>'+ __('ENTER.SCREEN.NAME.SEARCH', 'EDITOR.LAYER.SEARCH') +'</h3>' +
                          '<fieldset class="name">' +
                              '<legend>'+ __('SCREEN.NAME.SEARCH', 'EDITOR.LAYER.SEARCH') +'</legend>' +
                              '<input type="text" name="keyword" title="'+ __('ENTER.SCREEN.NAME', 'EDITOR.LAYER.SEARCH') +'" class="fText" style="width:355px;">' +
                              ' <input type="image" src="//img.echosting.cafe24.com/smartAdmin/img/editor/'+ EC_GLOBAL_INFO.getAdminLanguageCode() +'/btn-layer-search.gif" alt="'+ __('SEARCH', 'EDITOR.LAYER.SEARCH') +'">' +
                          '</fieldset>' +
                      '</form>' +
                      
                      '<div class="resultContainer" style="display:none">' +
                          '<h3>'+ __('SEARCH.RESULTS', 'EDITOR.LAYER.SEARCH') +'<strong></strong></h3>' +
                          '<ul class="result"></ul>' +
                          '<p class="result"><strong></strong> '+ __('RESULTS.WERE.FOUND.SEARCH', 'EDITOR.LAYER.SEARCH') +'</p>' +
                      '</div>' +
                   '</div>'
    },
    
    SEARCH_URL : getMultiShopUrl('/exec/admin/editor/filesearch'),
    
    init : function() {
        this._super();
        
        this.$form = this.$content.find('form');
        this.$result = this.$content.find('.resultContainer');
        
        this.$form.submit($.proxy(this._onSearch, this));
    },
    
    search : function(keyword) {
        if (!this._search(keyword)) return;
        
        this.$form.find('[name=keyword]').val(keyword);
        
        this.show();
    },
    
    _search : function(keyword) {
        keyword = $.trim(keyword);
        
        if (keyword == '') {
            alert(__('PLEASE.ENTER.SCREEN.NAME', 'EDITOR.LAYER.SEARCH'));
            return false;
        }
        
        var result = $.parseJSON($.ajax({
            url : this.SEARCH_URL,
            data : {
                keyword : keyword,
                skin_no : SDE.SKIN_NO
            },
            
            async : false,
            dataType: "json"
        }).responseText);
        
        this._renderResult(result);
        
        return true;
    },
    
    _renderResult : function(data) {
        if (data.bComplete == false) {
            alert(data.msg);
            return;
        }
        
        var html = '', 
            $empty = this.$result.find('p'),
            $lists = this.$result.find('ul'),
            content, result;
        
        for (var i = 0; i < data.result.length; i++) {
            result = data.result[i];
            content = result.desc ? result.desc + '(' + result.url + ')' : result.url;
            
            html += '<li class="' + SDE.Util.File.getSuffix(result.url) + '" data-url="' + result.url +'"><label><input type="checkbox"><span>' + content  + '</span></label></li>';
        }
        
        if (data.result.length == 0) {
            $lists.hide();
            $empty.show();
        } else {
            $lists.html(html).show();
            $empty.hide();
        }
        
        this.$result.find('h3 strong').html(sprintf(__('UNKNOWN.ID', 'EDITOR.LAYER.SEARCH'), data.result.length));
        this.$result.show();
    },
    
    _onClickSubmit : function(evt) {
        var files = [];
        
        this.$result.find(':checked').parents('li').each(function(index, el) {
            files.push($(el).data('url'));
        });
        
        if (files.length == 0) {
            alert(__('PLEASE.SELECT.FILE', 'EDITOR.LAYER.SEARCH'));
            return;
        }
        
        SDE.File.Manager.open(files);
        
        this.hide();
    },
    
    _onSearch : function(evt) {

        this._search(this.$form.find('[name="keyword"]').val());
    }
});
SDE.Layer.ListTree = SDE.Layer.Base.extend({
    DATA : {
        id : 'layCreat',
        title : __('ADD.SHOPPING.MALL.SCREEN', 'EDITOR.LAYER.LISTTREE'),
        alter_title : __('SAVE.AS', 'EDITOR.LAYER.LISTTREE'),
        content : '<div class="layContent">' +
                        '<h3>'+ __('SELECT.STORAGE.PATH', 'EDITOR.LAYER.LISTTREE') +'</h3>' +
                        '<div class="directory"></div>' +
                        '<h3>'+ __('ENTER.FILE.NAME', 'EDITOR.LAYER.LISTTREE') +'</h3>' +
                        '<label class="fileName"> <strong>'+ __('STORAGE.PATH', 'EDITOR.LAYER.LISTTREE') +'</strong> <input type="text" class="fText" readonly="readonly" name="dir" style="width:180px;"> </label>' +
                        '<label class="fileName"> <strong>'+ __('FILE.NAME', 'EDITOR.LAYER.LISTTREE') +'</strong> <input type="text" class="fText" name="name" style="width:180px;"> </label>' +
                        '<p class="example">'+ __('INDEX.HTML', 'EDITOR.LAYER.LISTTREE') +'</p>' +
                        '<label class="fileName areaRole">' +
                        '    <strong>'+ __('SCREEN.CLASSIFICATION', 'EDITOR.LAYER.LISTTREE') +'</strong>' +
                        '    <select name="role" class="fText" style="width:186px;">' +
                        '        <option value="">'+ __('AUTO.NOT.SELECTED', 'EDITOR.LAYER.LISTTREE') +'</option>' +
                        '      </select>' +
                        '</label>' +
                   '</div>'
    },

    ROLE_DATA : {},

    init : function() {
        var $dir, $name, $role;

        this._super();

        this.$dir = $dir = this.$element.find('input[name="dir"]');
        this.$name = $name = this.$element.find('input[name="name"]');
        this.$role = $role = this.$element.find('select[name="role"]');

        $(new SDE.List.Tree.Controller(this.$element.find('.directory'), { bShowFiles : false })).bind({
            'dir-click' : function(evt, url) {
                $dir.val(url);
            }
        });

        var oThis = this;
        if (SHOP.getProductVer() == 2 && Object.keys(this.ROLE_DATA).length < 1) {
            $.ajax({
                url : '/exec/admin/manage/routepathrole?mode=get_defpathlist',
                dataType: 'json',
                async: false,
                success: function(data) {
                    oThis.ROLE_DATA = data;
                }
            });
        }

        if (SHOP.getProductVer() != 2) {
            $('.areaRole').hide();
        } else {
            if (Object.keys(this.ROLE_DATA).length > 0) {
                var aOption = [];
                var sTitle = '';
                var bIsUseLegacyBoard = typeof EC_ADMIN_JS_CONFIG_EDITOR === 'undefined' || EC_ADMIN_JS_CONFIG_EDITOR.bIsUseLegacyBoard !== false;

                $.each(this.ROLE_DATA, function (key, val) {
                    // 글로벌 여부 체크
                    if (EC_GLOBAL_INFO.isGlobal() == true && val.use_global == 'F') {
                        return true;
                    }

                    // 레거시 게시판 제거
                    if (bIsUseLegacyBoard === false && (key.indexOf('BOARD') === 0 || key === 'MYSHOP_BOARDLIST')) {
                        return true;
                    }
                    
                    sTitle = ('admin_title' in val) ? val.admin_title : val.title;
                    aOption.push('<option value="' + val.key + '">' + sTitle + '</option>');
                });
                $role.append(aOption.join('\n'));
            }
        }
    },

    add : function() {
       this.type = 'add';

       this.$element.find('h2').html(this.DATA.title);

       this.show();
    },

    saveAs : function() {
       this.type = 'saveAs';

       this.$element.find('h2').html(this.DATA.alter_title);

       this.show();
    },

    show : function() {
        this.$dir.val('/');
        this.$name.val('');

        this._super();

        this.$name.focus();
    },

    /**
     * 트리정보 업데이트 (ECHOSTING-158389)
     */
    updateTree : function() {
        var $dir;
        var $target = this.$element.find('.directory');

        $target.html('');
        this.$dir = $dir = this.$element.find('input[name="dir"]');

        $(new SDE.List.Tree.Controller($target, { bShowFiles : false })).bind({
            'dir-click' : function(evt, url) {
                $dir.val(url);
            }
        });
    },

    submit : function() {
        var url,
            dir = $.trim(this.$dir.val()),
            name = $.trim(this.$name.val()),
            role = $.trim(this.$role.find(':selected').val());

        if (!dir) {
            alert(__('SELECT.STORAGE.PATH.001', 'EDITOR.LAYER.LISTTREE'));
            this.$dir.focus();
            return;
        }

        if (!name) {
            alert(__('PLEASE.ENTER.FILE.NAME', 'EDITOR.LAYER.LISTTREE'));
            this.$name.focus();
            return;
        }

        if (!SDE.Util.File.isValidName(name)) {
            alert(__('FILE.NAME.YES.INDEX.HTML', 'EDITOR.LAYER.LISTTREE'));
            this.$name.focus();
            return;
        }

        if (dir == '/') dir = '';

        url = dir + '/' + name;

        if (SDE.File.Manager.isOpened(url)) {
            alert(__('FILE.IS.OPEN.PLEASE', 'EDITOR.LAYER.LISTTREE'));

            SDE.File.Manager.open(url);

            return false;
        }

        if (this.type == 'add' && !SDE.File.Manager.add(url, false, role)) return;
        else if (this.type == 'saveAs' && !SDE.File.Manager.saveAs(SDE.File.Manager.getCurrentUrl(), url, role)) return;

        this._super();
    }
});

/*rev$@sinseki #SDE-5 쇼핑몰 화면 추가 영역을 2등분 하여, 앞에 디렉토리 추가버튼과 기능 구현*/
SDE.Layer.DirListTree = SDE.Layer.Base.extend({
    DATA : {
        id : 'layCreat',
        title : __('ADD.SHOPPING.MALL.FOLDER', 'EDITOR.LAYER.LISTTREE'),
        alter_title : __('SAVE.AS', 'EDITOR.LAYER.LISTTREE'),
        content : '<div class="layContent">' +
                        '<h3>'+ __('SELECT.PATH.TO.GENERATE', 'EDITOR.LAYER.LISTTREE') +'</h3>' +
                        '<div class="directory"></div>' +
                        '<h3>'+ __('ENTER.FOLDER.NAME', 'EDITOR.LAYER.LISTTREE') +'</h3>' +
                        '<label class="fileName"> <strong>'+ __('GENERATION.PATH', 'EDITOR.LAYER.LISTTREE') +'</strong> <input type="text" class="fText" readonly="readonly" name="dir" style="width:180px;"> </label>' +
                        '<label class="fileName"> <strong>'+ __('FOLDER.NAME', 'EDITOR.LAYER.LISTTREE') +'</strong> <input type="text" class="fText" name="name" style="width:180px;"> </label>' +
                        '<p class="example">'+ __('YES.NEWFOLDER', 'EDITOR.LAYER.LISTTREE') +'</p>' +
                   '</div>'
    },

    init : function() {
        var $dir, $name;

        this._super();

        this.$dir = $dir = this.$element.find('input[name="dir"]');
        this.$name = $name = this.$element.find('input[name="name"]');

        $(new SDE.List.Tree.Controller(this.$element.find('.directory'), { bShowFiles : false })).bind({
            'dir-click' : function(evt, url) {
                $dir.val(url);
            }
        });
    },

    add : function() {
       this.type = 'add';

       this.$element.find('h2').html(this.DATA.title);

       this.show();
    },

    saveAs : function() {
       this.type = 'saveAs';

       this.$element.find('h2').html(this.DATA.alter_title);

       this.show();
    },

    show : function() {
        this.$dir.val('/');
        this.$name.val('');

        this._super();

        this.$name.focus();
    },

    /**
     * 트리정보 업데이트 (ECHOSTING-158389)
     */
    updateTree : function() {
        var $dir;
        var $target = this.$element.find('.directory');

        $target.html('');
        this.$dir = $dir = this.$element.find('input[name="dir"]');

        $(new SDE.List.Tree.Controller($target, { bShowFiles : false })).bind({
            'dir-click' : function(evt, url) {
                $dir.val(url);
            }
        });
    },

    submit : function() {
        var url,
            dir = $.trim(this.$dir.val()),
            name = $.trim(this.$name.val());

        if (!dir) {
            alert(__('SELECT.STORAGE.PATH.001', 'EDITOR.LAYER.LISTTREE'));
            this.$dir.focus();
            return;
        }

        if (!name) {
            alert(__('PLEASE.ENTER.FILE.NAME', 'EDITOR.LAYER.LISTTREE'));
            this.$name.focus();
            return;
        }

        if (dir == '/') dir = '';

        url = dir + '/' + name;

        if (this.type == 'add' && !SDE.File.Manager.add(url, true)) return;

        this._super();
    }
});

// 해당 Layer는 iframe 내에서 사용하므로, 중복 객체 생성 방지를 위해 Singleton Wrapper 이용
SDE.Layer.Editing = function() {
    var _this = SDE.Layer.Editing;

    if (!_this.instance) _this.instance = new SDE.Layer._Editing();

    return _this.instance;
};

SDE.Layer._Editing = SDE.Layer.Base.extend({
    DATA : {
        id : 'layEditor',
        title : '<img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt-layer-editor.gif1" alt="'+ __('EDIT', 'EDITOR.LAYER.EDITING') +'">',
        content : '<div class="laySnb">' +
                      '<ul class="tab">' +
                          '<li class="deco" data-menu="deco">'+ __('ADORNMENT', 'EDITOR.LAYER.EDITING') +'</li>' +
                          '<li class="attr" data-menu="attr">'+ __('PROPERTY', 'EDITOR.LAYER.EDITING') +'</li>' +
                          '<li class="html" data-menu="html">HTML</li>' +
                          '<li class="module" data-menu="module">'+ __('EDIT.MODULE', 'EDITOR.LAYER.EDITING') +'</li>' +
                      '</ul>' +
                      '<ul class="list"></ul>' +
                  '</div>' +

                  '<div class="layContents">' +
                      '<p class="info"></p>' +
                      '<div class="shopSetting multilingual">' +
                      '</div>' +
                      '<div class="layContent"></div>' +
                      '<div class="displayHelp" style="display: none;">' +
                          '<strong class="title">'+ __('DISPLAY.SETTING.GUIDE', 'EDITOR.LAYER.EDITING') +'</strong>' +
                          '<p class="helpInfo">'+ __('YOU.CAN.TAKE.ADVANTAGE', 'EDITOR.LAYER.EDITING') +'</p>' +
                          '<ul>test</ul>' +
                      '</div>' +
                  '</div>',

        btn_name : __('APPLY', 'EDITOR.LAYER.EDITING'),

        type : 'Editor'
    },

    MENU : ['Attr', 'Deco', 'Html', 'Module'],

    menus : {},

    isSet : {},

    info : null,

    name : null,

    markset: null,

    init : function() {
        var index, name;

        this._super();

        this.$tab = this.$element.find('.tab');
        this.$title = this.$content.find('.info');
        this.$titleBar = this.$element.find('h2.info');
        this.$content = this.$content.find('.layContent');
        this.$displayHelp = this.$element.find('.displayHelp');

        this.$tab.find('li').click($.proxy(this._onClickTab, this));

        for (index in this.MENU) {
            name = this.MENU[index];

            this.menus[name.toLowerCase()] = new SDE.Layer['Editing' + name]();
        }
    },

    getInfo : function(type, key) {
        if (key == null) return;

        if (type == 'image') {
            this.info = null;
            this.name = __('IMAGE', 'EDITOR.LAYER.EDITING');
            return;
        };

        this.info = SDE.Util.Module.getInfo(key);

        this.name = (this.info && this.info.module_info.action_name) ? this.info.module_info.module_name + ' - ' + this.info.module_info.action_name : null;

        // 표시설정
        this.markset = this.info.module_info.markset;
    },

    setMenu : function(type, key) {
        var name, menu;

        for (name in this.menus) {
            menu = this.menus[name];

            menu.set(type, key, this.info);

            this.$tab.find('[data-menu="'+ name +'"]')[menu.isUsing ? 'show' : 'hide']();
        }
    },

    showMenu: function (menuName) {
        menuName = menuName || 'deco';

        if (!this.menus[menuName].isUsing) {
            menuName = 'html';
        }

        var selector = '.' + menuName;

        this.$tab.find('li').removeClass('selected');
        this.$tab.find(selector).addClass('selected');

        this.currentMenu = this.menus[menuName];
        this.$content.children().detach();
        this.$content.html(this.currentMenu.render());

        this.$title.html(this.currentMenu.getTitle(this.name));

        this.$titleBar.html(this.name || __('BASIC', 'EDITOR.LAYER.EDITING'));
        this.$element.removeClass("layEditor").addClass("moduleEditor");

        // 표시설정 세팅
        this.$displayHelp.hide();
        if (this.markset && this.markset.length > 0) {
            this.$displayHelp.find('ul').html(this.markset);
            this.$displayHelp.show();
        }

        if ($("body").height() < 800) {
            this.$element.addClass("lowDisplay");
        }

        if (menuName == 'deco' && document.charset.toLowerCase() == "utf-8" && SDE.SUPPORT_LANG_LIST.length > 1) {
            this.$element.find(".multilingual").show();
            this.$element.removeClass("editor");
        } else {
            this.$element.find(".multilingual").hide();
            this.$element.addClass("editor");
        }
    },

    show : function(type, key, menuName) {
        this.getInfo(type, key);

        this.setMenu(type, key, menuName);

        this._super();

        this.showMenu(menuName);
    },

    submit : function() {
        if (!this.currentMenu.save()) return;

        params = $.extend(SDE.Util.Module.findSelectedInfo(), this.currentMenu.getParams());

        // 바뀐 모듈을 미리 보기 화면 refresh 후 select 하기 위해 parameter와 함께 전송
        SDE.File.Manager.saveTemp(SDE.File.Manager.getCurrentUrl(), params);

        this._super();
    },

    _onClickTab : function(evt) {
        var menuName = $(evt.currentTarget).data('menu');

        if (this.currentMenu == this.menus[menuName]) {
            return;
        }

        if (this.currentMenu.isEditing() && confirm(__('YOU.NOT.APPLIED.CHANGES', 'EDITOR.LAYER.EDITING')) === false) {
            return;
        }

        this.showMenu(menuName);
    }
});

SDE.Layer.RecentSource = SDE.Layer.Base.extend({
    DATA : {
        id : 'layRecent',
        title : __('VIEW.LATEST.SOURCE.FILES', 'EDITOR.LAYER.RECENTSOURCE'),
        content : '<div class="layContent">' +
                      '<h3>'+ __('VIEW.LATEST.SOURCE.FILES', 'EDITOR.LAYER.RECENTSOURCE') +'</h3>' +
                      '<p class="recentInfo">'+ __('PRESS.THE.APPLY.BUTTON', 'EDITOR.LAYER.RECENTSOURCE') +'</p>' +
                      '<p class="recentFile">'+ __('FILE.NAME', 'EDITOR.LAYER.RECENTSOURCE') +': <strong></strong></p>' +
                      '<div class="htmlView"></div>' +
                  '</div>',

        btn_name : __('APPLY', 'EDITOR.LAYER.RECENTSOURCE')
    },

    EDITOR_TEMPLATE : '<textarea id="recentSource" style="display:none"></textarea>',

    init : function() {
        this._super();

        this.$url = this.$element.find('.recentFile strong');
        this.$source = this.$element.find('.htmlView');
    },

    show : function(url) {
        var source = this.getRecentSource(url);

        if (!source) {
            alert(__('PROVIDED.LATEST.SOURCE', 'EDITOR.LAYER.RECENTSOURCE'));
            return;
        }

        this._super();

        this.$url.html(url);
        this.$source.html(this.EDITOR_TEMPLATE);

        this.editor = SDE.Editor.Pool.create('recentSource');
        this.editor.setOption('mode', SDE.Util.File.getMimeType(url));
        /*rev.b12.20130819.10@sinseki #SDE-3 최신소스 C&P 가능하도록 수정 & 열린 소스 삭제안되게*/
        //this.editor.setOption('readOnly', true);
        this.editor.setOption("onKeyEvent", function(cm,event){
            if (!(event.ctrlKey&&event.keyCode==67)){
                event.preventDefault();
                event.stopPropagation();
                return true;
            }
        });
        this.editor.setValue(source);

        this.source = source;
        this.url = url;
    },

    getRecentSource : function(url) {
        var response = $.parseJSON($.ajax({
            async : false,
            data : {
                path : url,
                skin_code : SDE.SKIN_CODE
            },
            dataType : 'json',
            url : getMultiShopUrl('/exec/admin/editor/FileRecent')
        }).responseText);

        if (!response || !response.bComplete) return false;

        return response.source;
    },

    submit : function() {
        SDE.editor.setValue(this.source);

        SDE.File.Manager.saveTemp(this.url);

        this._super();
    }
});

SDE.Layer.HttpReplace = SDE.Layer.Base.extend({
    oStatusTags : {
        loading: '<img src="//img.echosting.cafe24.com/suio/ico_loading.gif" width="20" alt="'+ __('LOADING', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'">',
        valid: '<em class="icoValidity success">' + __('VALID', 'EDITOR.JS.LAYER.HTTP.REPLACE') + '</em>',
        invalid: '<em class="icoValidity error">' + __('INVALID', 'EDITOR.JS.LAYER.HTTP.REPLACE') + '</em>'
    },

    oMessages: {
        checking: __('HTTPS.ADDRESS.VALID', 'EDITOR.JS.LAYER.HTTP.REPLACE'),
        replacing: __('PROCESSING.PLEASE.WAIT', 'EDITOR.JS.LAYER.HTTP.REPLACE')
    },

    // https가 확실해서 검증이 필요하지 않은 도메인
    aCertifiedDomains: [
        "img.echosting.cafe24.com"
    ],

    // hash: {sType, sOriginSrc, sSrc, bValid, bChecked, bIsHttps}
    oHttpUrls: {},
    // seq: {id, method, params}
    oJsonRpcQueue: {},
    bIsLoading: false,

    DATA: {
        id: "layHttps",
        title: __('HTTPS.CHANGE', 'EDITOR.JS.LAYER.HTTP.REPLACE') + ' <div class="cTip" code="DE.DI.DX.150"></div>',
        content: '<div class="layContent"> \
                    <p class="txtGuide">'+ __('MAKE.SURE.IS.VALID.URL', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'</p> \
                    <div class="mBoardArea"> \
                        <div class="mBoard typeHead"> \
                            <table border="1" summary=""> \
                                <caption>'+ __('CHANGE.URL', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'</caption> \
                                <colgroup> \
                                    <col class="chk"> \
                                    <col style="width:auto"> \
                                    <col style="width:auto"> \
                                    <col style="width:115px"> \
                                </colgroup> \
                                <tbody> \
                                <tr> \
                                    <th scope="col"><input type="checkbox" class="allChk"></th> \
                                    <th scope="col">http://</th> \
                                    <th scope="col">https://</th> \
                                    <th scope="col">'+ __('EFFECTIVENESS', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'</th> \
                                </tr> \
                                </tbody> \
                            </table> \
                        </div> \
                        <div class="mBoard typeBody" style="height:350px"> \
                            <table border="1" class="eChkColor" style="display:none;"> \
                                <caption>'+ __('CHANGE.URL', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'</caption> \
                                <colgroup> \
                                    <col class="chk"> \
                                    <col style="width:auto"> \
                                    <col style="width:auto"> \
                                    <col style="width:98px"> \
                                </colgroup> \
                                <tbody id="httpsTypeTBody"></tbody> \
                            </table> \
                            <p class="empty" style="display:none;"></p> \
                        </div> \
                    </div> \
                </div> \
                <div id="layLoading" class="layPop" style="display:none;"> \
                    <p></p> \
                    <img src="//img.echosting.cafe24.com/suio/ico_loading.gif" alt="'+ __('HTTPS.ADDRESS.VALID', 'EDITOR.JS.LAYER.HTTP.REPLACE') +'"> \
                </div> \
                <iframe id="httpsSandBox" src="about:blank" style="display:none;"></iframe>\
                ',
        footer: __('CHANGE.URL.TO.HTTPS', 'EDITOR.JS.LAYER.HTTP.REPLACE')
    },

    /**
     * 팝업 처음 실행시
     *
     * @override
     */
    init: function () {
        this._super();

        this.$info = this.$element.find('.info');
        this.$loading = this.$element.find('#layLoading');
        this.$allChecker = this.$element.find('.allChk');
        this.$content = this.$element.find('.layContent');
        this.$sandbox = this.$element.find('#httpsSandBox').get(0);
        this.$footer = this.$element.find('.layButton');

        this.$contentEmpty = this.$content.find('.empty');
        this.$contentTable = this.$content.find('.eChkColor');
        this.$loadingContent = this.$loading.children('p');

        var self = this;
        // 리스트 체크박스 선택시 이벤트 바인딩
        this.$contentTable.delegate("input", "click", function () {
            var $checkbox = $(this);
            self.setCheckBox($checkbox.is(':checked'), $checkbox.val());
        });

        // 전체 체크박스 선택시 이벤트 바인딩
        this.$allChecker.click(function () {
            var bChecked = $(this).is(':checked');
            self.setCheckBox(bChecked);
            self.$contentTable.find('input:not(:disabled)').attr('checked', bChecked);
        });

        // 샌드박스에 이벤트 바인딩
        this.$sandbox.contentDocument.write(this.makeListenerHtml());

        // 윈도우에 메세지 이벤트 바인딩
        window.addEventListener("message", this.messageListener.bind(this), false);

        // 도움말 호출
        SDE.Util.HelpCode.print(this.$info);
    },

    /**
     * 레이어가 열릴 시
     *
     * @override
     */
    show: function () {
        // 레이어 보여질지 예외처리
        if (this.checkAvailable() === false) {
            return false;
        }

        // 현재 코드를 파싱
        this.parseHttpUrls();

        // 레이어 열기
        this._super();

        // 체크 시작
        this.checkHeaders();
    },

    /**
     * 레이어 닫힐 시
     *
     * @override
     */
    hide: function () {
        this.oJsonRpcQueue = {};
        this.$allChecker.attr('checked', false);
        this._super();
    },

    /**
     * https:// 변경 버튼 클릭 시
     *
     * @override
     * @returns {boolean}
     */
    submit: function () {
        if (this.bIsLoading === true) {
            alert(__('HTTPS.ADDRESS.VALID', 'EDITOR.JS.LAYER.HTTP.REPLACE'));
            return false;
        }

        // 치환을 해야할 URL 데이터만 추출
        var aValidHttpsUrls = Object.keys(this.oHttpUrls)
            .map(function (sKey) {
                return this.oHttpUrls[sKey];
            }.bind(this))
            .filter(function (oHttpUrl) {
                return (
                    oHttpUrl.bChecked === true
                    && oHttpUrl.bValid === true
                    && oHttpUrl.bIsHttps === false
                );
            });

        if (aValidHttpsUrls.length === 0) {
            alert(__('PLEASE.SELECT.URL', 'EDITOR.JS.LAYER.HTTP.REPLACE'));
            return false;
        }

        var oCurrentEditor = SDE.Editor.Pool.get();
        var sCurrentUrl = SDE.Editor.Pool.getCurrentUrl();

        this.$loadingContent.html(this.oMessages.replacing);
        this.$loading.show();

        // 코드 치환 시작
        SDE.Editor.Pool.setReplacingMode(true);

        var sContents = oCurrentEditor.getValue();
        aValidHttpsUrls.forEach(function (oValidHttpsUrl) {
            // 정규식으로 변환하기 위해 url 을 escape 처리
            var sOriginSrcRegex = oValidHttpsUrl.sOriginSrc.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            // URL 단위 변경
            sContents = sContents.replace(
                new RegExp(sOriginSrcRegex, 'g'),
                oValidHttpsUrl.sSrc
            );
        });

        oCurrentEditor.setValue(sContents);
        // 변경사항 브로드캐스팅
        SDE.BroadCastManager.send('file-content-change', sCurrentUrl, oCurrentEditor.getValue());
        SDE.Editor.Pool.setReplacingMode(false);

        this._super();
    },

    /**
     * 레이어를 띄울지 예외처리
     *
     * @returns {boolean}
     */
    checkAvailable: function () {
        var oCurrentEditor = SDE.Editor.Pool.get();
        if (oCurrentEditor.options.readOnly === true) {
            alert(__('CURRENT.FILE.IS.READONLY', 'EDITOR.JS.LAYER.HTTP.REPLACE'));
            return false;
        }

        return true;
    },

    /**
     * editor contents 를 파싱해서 url 배열로 저장
     */
    parseHttpUrls: function () {
        // 현재 열려있는 CodeMirror
        var oCurrentEditor = SDE.Editor.Pool.get();

        // CodeMirror Contents
        var sContents = oCurrentEditor.getValue();

        // Url 값만 파싱
        this.oHttpUrls = {};

        var aMatches;
        var oTagRegex = /(?:\<?(img|script|link|url)).+?['"]((https?\:)?\/\/(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:[/?#](?:\S*[^\s!"'()*,-.:;<>?\[\]_`{|}~]|)))['"]/g;

        // 정규식으로 tagName 과 src 추출
        while ((aMatches = oTagRegex.exec(sContents)) !== null) {
            var sMatch = aMatches[0];
            var sType = aMatches[1];
            var sSrc = aMatches[2];
            var bValid = false;

            // link 타입일 경우, css 가 아니면 검사할 수 없으므로 제외
            if (sType === "link" && sMatch.indexOf("stylesheet") === -1) {
                continue;
            }

            // url('background-image') 의 경우 image 검사를 실행
            if (sType === "url") {
                sType = "img";
            }

            // https 및 리소스가 확실한 도메인을 포함하고 있다면 검사에서 제외
            var aHasDomain = this.aCertifiedDomains.filter(function (sDomain) {
                return (sSrc.indexOf(sDomain) !== -1);
            });

            if (aHasDomain.length > 0) {
                bValid = true;
            }

            // 중복 체크 키 생성
            var sKey = this.getStringHash(sSrc);

            /**
             * sType: 어떤 유효성 검증 메소드를 사용할지,
             * sOriginSrc: http:// 열에 표시될 URL
             * sSrc: https:// 열에 표시되며 검증할 URL
             * bValid: 유효성
             * bChecked: 선택된 열인지
             * bIsHttps: 기존에 https URL이라 유효성 검사"만" 진행할지
             */
            this.oHttpUrls[sKey] = {
                sType: sType,
                sOriginSrc: sSrc,
                sSrc: sSrc.replace(/^(https?:\/\/)|(\/\/)/, 'https://'),
                bValid: bValid,
                bChecked: false,
                bIsHttps: (sMatch.indexOf('https:') !== -1)
            };
        }

        aMatches = null;
    },

    /**
     * Url 값 검증
     *
     * @returns {boolean}
     */
    checkHeaders: function () {
        var oHttpUrls = this.oHttpUrls;
        var aKeys = Object.keys(oHttpUrls);
        var iLength = aKeys.length;

        if (iLength === 0) {
            var sEmptyMsg = sprintf(
                __('NO.URLS.REGISTERED', 'EDITOR.JS.LAYER.HTTP.REPLACE'),
                SDE.Editor.Pool.getCurrentUrl()
            );

            this.$contentEmpty.html(sEmptyMsg);

            this.$contentEmpty.show();
            this.$contentTable.hide();
            this.$footer.hide();

            return false;
        }

        this.$contentEmpty.hide();
        this.$contentTable.show();

        this.bIsLoading = true;
        this.$loadingContent.html(this.oMessages.checking);
        this.$loading.show();
        this.$footer.show();

        // 리스트 영역 초기화
        var $tBody = this.$contentTable.find('#httpsTypeTBody');
        var sHtml = "";
        aKeys.forEach(function (sKey) {
             sHtml += '<tr id="'+ sKey +'">' +
                 '    <td><input type="checkbox" class="rowChk" value="'+ sKey +'"></td>';

             if (oHttpUrls[sKey].bIsHttps === true) {
                 sHtml += '<td class="center">-</td>';
             } else {
                 sHtml += '<td>'+ oHttpUrls[sKey].sOriginSrc + '</td>';
             }

             sHtml += '<td>'+ oHttpUrls[sKey].sSrc + '</td>' +
                 '    <td class="center">'+ this.oStatusTags.loading +'</td>' +
                 '</tr>';
        }.bind(this));
        $tBody.html(sHtml);

        this.oJsonRpcQueue = {};
        // Url 검증 시작
        aKeys.forEach(function (sKey, iSeq) {
            if (oHttpUrls[sKey].bValid === true) {
                this.setValid(1, sKey);
                return;
            }

            // https://www.jsonrpc.org/specification#request_object
            var oJsonRpc = {
                id: iSeq,
                method: oHttpUrls[sKey].sType,
                params: {
                    sUrl: oHttpUrls[sKey].sSrc,
                    sKey: sKey
                }
            };

            this.oJsonRpcQueue[iSeq] = oJsonRpc;
            this.$sandbox.contentWindow.postMessage(JSON.stringify(oJsonRpc), "*");
        }.bind(this));

        var fWatcher = setInterval(function () {
             if (Object.keys(this.oJsonRpcQueue).length === 0) {
                 this.$loading.hide();
                 this.bIsLoading = false;
                 this.$loadingContent.html(this.oMessages.replacing);
                 clearInterval(fWatcher);
             }
        }.bind(this), 300);
    },

    /**
     * 내부 Url 오브젝트에 접근해 checked 속성 변경
     *
     * @param {boolean} bChecked
     * *@param {string} sKey
     * @returns {boolean}
     */
    setCheckBox: function (bChecked, sKey) {
        var aKeys = Object.keys(this.oHttpUrls);
        var iLength = aKeys.length;
        if (iLength === 0) {
            return false;
        }

        // 하나 변경
        if (sKey) {
            this.oHttpUrls[sKey].bChecked = bChecked;
        // 전체 변경
        } else {
            aKeys.forEach(function (sKey) {
                this.oHttpUrls[sKey].bChecked = bChecked;
            }.bind(this));
        }

        return true;
    },

    /**
     * 내부 Url 배열에 접근해 valid 속성 변경
     * UI 변경
     *
     * @param {number} iValid [0: invalid, 1: valid, 2:timeout]
     * @param {number} sKey
     * @returns {boolean}
     */
    setValid: function (iValid, sKey) {
        var iLength = Object.keys(this.oHttpUrls).length;
        if (iLength === 0) {
            return false;
        }

        this.oHttpUrls[sKey].bValid = (iValid === 1);

        // UI 에 결과 반영
        var $tr = $('#' + sKey);
        var $checkbox = $tr.find('input');
        var $status = $tr.find('td:last');

        if (iValid === 0) {
            $checkbox.attr('disabled', 'disabled');
            $status.html(this.oStatusTags.invalid);
        } else if (iValid === 1) {
            // valid 여도 원래 요청이 https 면 사양에 따라 체크 불가 처리
            if (this.oHttpUrls[sKey].bIsHttps === true) {
                $checkbox.attr('disabled', 'disabled');
            } else {
                $checkbox.removeAttr('disabled');
            }

            $status.html(this.oStatusTags.valid);
        } else if (iValid === 2) {
            $checkbox.attr('disabled', 'disabled');
            $status.html('');
        }

        return true;
    },

    /**
     * java.String.hashCode 를 16진수 변환한 해쉬
     *
     * @param {string} sStr
     * @returns {string} hash
     */
    getStringHash: function (sStr) {
        var hash = 5381;
        var i = sStr.length;

        while (i) {
            hash = (hash * 33) ^ sStr.charCodeAt(--i);
        }

        return (hash >>> 0).toString(16);
    },

    /**
     * sandbox와 통신하는 message Event Lintener
     *
     * @param oEvent
     * @returns {boolean}
     */
    messageListener: function (oEvent) {
        if (oEvent.data) {
            try {
                var oResponse = JSON.parse(oEvent.data);
                if (oResponse.error) {
                    this.setValid(oResponse.error.data.iValid, oResponse.error.data.sKey);
                } else if (oResponse.result) {
                    this.setValid(oResponse.result.iValid, oResponse.result.sKey);
                }

                // 큐에서 데이터 제거
                delete this.oJsonRpcQueue[oResponse.id];
            } catch (e) {}
        }
    },

    /**
     * sandbox용에서 통신을 위해 사용할 스크립트
     * jsonRpc 의 method 타입에 따라 실행
     *
     * @returns {string}
     */
    makeListenerHtml: function () {
        return '<html><head><script> \
            var receiveMessage = function (oEvent) {\
                if (oEvent.data) {\
                    var MAX_LOAD_TIME = 30 * 1000;\
                    var oResponse = JSON.parse(oEvent.data); \
                    var oJsonRpc = {id: oResponse.id, result: null, error: null}; \
                    var oScript, oImage, oLink;\
                    var fTimeout = setTimeout(function () {\
                        oJsonRpc.result = null;\
                        oJsonRpc.error = {code: 504, message: "timeout", data: {sUrl: oResponse.params.sUrl, iValid: 2, sKey: oResponse.params.sKey}};\
                        oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                        if (oScript) {\
                            document.head.removeChild(oScript);\
                        } else if (oImage) {\
                            document.head.removeChild(oImage);\
                        } else if (oLink) {\
                            document.head.removeChild(oLink);\
                        }\
                    }, MAX_LOAD_TIME);\
                    if (oResponse.method === "script") {\
                        oScript = document.createElement("script");\
                        oScript.src = oResponse.params.sUrl;\
                        oScript.type = "text/javascript";\
                        oScript.onload = function () {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 1, sKey: oResponse.params.sKey};\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            document.head.removeChild(oScript);\
                            clearInterval(fTimeout);\
                        };\
                        oScript.onerror = function () {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 0, sKey: oResponse.params.sKey};\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            document.head.removeChild(oScript);\
                            clearInterval(fTimeout);\
                        };\
                        document.head.appendChild(oScript);\
                    } else if (oResponse.method === "img") {\
                        oImage = new Image();\
                        oImage.onload = function () {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 1, sKey: oResponse.params.sKey};\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            oImage = null;\
                            clearInterval(fTimeout);\
                        };\
                        oImage.onerror = function () {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 0, sKey: oResponse.params.sKey};\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            oImage = null;\
                            clearInterval(fTimeout);\
                        };\
                        oImage.src = oResponse.params.sUrl;\
                    } else if (oResponse.method === "link") {\
                        oLink = document.createElement("link");\
                        oLink.rel = "stylesheet";\
                        oLink.type = "text/css";\
                        oLink.media = "print";\
                        oLink.href = oResponse.params.sUrl;\
                        oLink.onload = function (oLoadEvent) {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 1, sKey: oResponse.params.sKey};\
                            var bIsIE = /MSIE|Trident|Edge/i.test(navigator.userAgent);\
                            if (bIsIE && oLoadEvent.target.sheet) {\
                                try {\
                                    var temp = oLoadEvent.target.sheet.cssRules;\
                                } catch (e) {\
                                    oJsonRpc.result.iValid = 0;\
                                }\
                            }\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            document.head.removeChild(oLink);\
                            clearInterval(fTimeout);\
                        };\
                        oLink.onerror = function () {\
                            oJsonRpc.result = {sUrl: oResponse.params.sUrl, iValid: 0, sKey: oResponse.params.sKey};\
                            oEvent.source.postMessage(JSON.stringify(oJsonRpc), oEvent.origin);\
                            document.head.removeChild(oLink);\
                            clearInterval(fTimeout);\
                        };\
                        document.head.appendChild(oLink);\
                    }\
                }\
            };\
            window.addEventListener("message", receiveMessage, false);\
            </script></head><body></body></html>\
        ';
    }
});
/**
 * Module 디자인 꾸미기 Interface
 */

SDE.Layer.EditingBase = Class.extend({
    TITLE : '',
    
    TEMPLATE : '',
    
    isUsing : false,
    
    _isEditing : false,
    
    init : function() {
        this.$element = $(this.TEMPLATE);
    },    
    
    isEditing : function() {
        return this._isEditing;
    },
    
    getTitle : function(name) {
        return $.tmpl('<div>' + this.TITLE + '</div>', { name : name || __('BASIC', 'EDITOR.LAYER.EDITING.BASE') }).html();
    },

    /**
     * 저장 후 SDE.File.Manager.saveTemp 호출 시 넘길 Parameter 반환
     */
    getParams : function() {
        return {};
    },
    
    set : function(type, key, info) {
    },
    
    render : function() {
        
    },
    
    save : function() {
        
    }
});

SDE.Layer.EditingModule = SDE.Layer.EditingBase.extend({
    TEMPLATE : '<div>' +
               '<h3>'+ __('MODULE.SELECTION', 'EDITOR.LAYER.EDITING.MODULE') +'</h3>' +
               '<ul class="tab">' +
                    '<li class="eMajor selected" data-name="major"><a href="#">'+ __('MAJOR.MODULE', 'EDITOR.LAYER.EDITING.MODULE') +'</a></li>' +
                    '<li class="eAll" data-name="all"><a href="#">'+ __('ALL.MODULES', 'EDITOR.LAYER.EDITING.MODULE') +'</a></li>' +
                    //'<li class="eApps" data-name="cstore"><a href="#">C스토어 앱</a></li>' +
               '</ul>' +

               '<div class="module major" data-name="major">' +
                    '<div class="list">' +
                        '<ul>' +
                        '</ul>' +
                    '</div>' +
                    '<p class="thumbnail"><img src="//img.echosting.cafe24.com/smartAdmin/img/editor/@layer_module2.jpg" alt=""></p>' +
                '</div>' +

               '<div class="module all" data-name="all" style="display:none"></div>' +
               '<div class="module" data-name="cstore" style="display:none"></div>' +

               '<p class="control">'+ sprintf(__('SELECTED.MODULE', 'EDITOR.LAYER.EDITING.MODULE'), '<select class="fSelect"><option value="change">'+ __('CURRENT.MODULE.AND.CHANGE', 'EDITOR.LAYER.EDITING.MODULE') + '</option><option value="before">'+ __('ADD.THE.TOP', 'EDITOR.LAYER.EDITING.MODULE') +'</option><option value="after">'+ __('ADD.THE.BOTTOM', 'EDITOR.LAYER.EDITING.MODULE') +'</option></select>') +'</p>' +
               '</div>',

    TITLE : sprintf(__('THE.MODULE.ADD.NEW.MODULE', 'EDITOR.LAYER.EDITING.MODULE'), '<strong>\'${name}\'</strong>'),

    ALTER_TITLE : __('CURRENT.EDITING.LOCATION', 'EDITOR.LAYER.EDITING.MODULE'),

    isUsing : (
        typeof window.EC_GLOBAL_INFO === 'object' &&
        typeof window.SHOP === 'object' &&
        window.EC_GLOBAL_INFO.isGlobal() === false &&
        window.SHOP.isVN() === false
    ),

    isRendered : false,

    appendToModule : function(html) {
        $(html)
            .appendTo(this.$allModule)
            .find('li')
            .click($.proxy(this._onClickList, this))
            .first()
            .trigger('click');
    },

    actionChange : function(html) {
        SDE.editor.replaceSelection(html);
    },

    actionAdd : function(html, position) {
        var module = SDE.editor.getSelection(),
            line = module.split('\n').length,
            start, end;

        if (position == 'before') {
            html = html + '\n' + module;
        } else {
            html = module + '\n\n' + html;
        }

        // 추가된 모듈만 셀렉트 하기 위해 처리
        SDE.editor.replaceSelection(html);

        start = SDE.editor.getCursor(true).line;
        end = SDE.editor.getCursor().line;

        if (position == 'before') {
            end -= line + 1;
        } else {
            start += line + 1;
        }

        SDE.editor.setSelection({
            line : start,
            ch : 0
        }, {
            line : end,
            ch : 0
        });
    },

    actionInsert : function(html) {
        var cursor = SDE.editor.getCursor(true);

        if (cursor.line == 0 && cursor.ch == 0) {
            cursor.line = SDE.editor.lineCount() - 1;
        }

        SDE.editor.replaceRange(html, cursor, cursor);

        SDE.editor.setSelection(cursor, {line: (cursor.line + html.split('\n').length), ch:0 });
    },

    getTitle : function(name) {
        return this.key ? this._super(name) : this.ALTER_TITLE;
    },

    init : function() {
        this._super();

        this.$control = this.$element.find('.control');
        this.$modules = this.$element.find('.module');

        this.$tab = this.$element.find('.tab li').click($.proxy(this._onClickTab, this));
        this.platform = ["pc","mobile"][+SDE.mo()];
        this._reCallPreviewLoaded = 0;
        if (this.isUsing === true) {
            this.loadModuleList();
        }
    },

    loadModuleList : function() {
        if (undefined === SDE.Prop.lang && 10 > this._reCallPreviewLoaded++) {
            return setTimeout(this.loadModuleList.bind(this), 100);
        }
        var t = new Date();
        var response = $.parseJSON($.ajax({
            url : getMultiShopUrl('/exec/admin/editor/moduleProxy'),
            data : {
                command : 'categorymodule',
                cateid : this.platform+'.major,'+this.platform+'.all',
                lang : (SDE.Prop.lang || "ko_KR")
            },

            async : false
        }).responseText);
        this.modulelistdata = response.data;

        this.definedmodulelist = {};
        var instance = this;
        $.each(this.modulelistdata.maplist[this.platform+".all"],function($i,$item){
            instance.definedmodulelist[instance.modulelistdata.module[$item.seq].moduleid.split(/_/).shift()] = true;
        });

        var instance = this;
        this.$majorModule = this.$modules.filter('[data-name=major]').find(".list ul").empty();
        var $html = [];
        $.each(this.modulelistdata.maplist[this.platform+".major"]||[],function($i,$item){
            var $itemdata = instance.modulelistdata.module[$item.seq];
            var $tmp = $itemdata.moduleid.split(/_/), $module = $tmp.shift(), $action = $tmp.join("_");
            $html.push('<li class="'+instance.platform+'" data-platform="'+instance.platform+'" data-moduleid="'+$itemdata.moduleid+'" data-module="'+$module+'" data-action="'+$action+'" data-sectionseq="'+$itemdata.seq+'" data-preview="'+$itemdata.previewimgurl+'"><a href="#none">'+($item.name||$itemdata.title)+'</a></li>');
        });
        $($html.join("")).appendTo(this.$majorModule)
            .first()
                .trigger('click');
    },

    getSelectedInfo : function() {
        var
            tabName = this.$tab.filter('.selected').data('name'),
            $data = this.$modules.filter('[data-name='+ tabName +']').find('.selected'),
            platform, action, section_no,
            module, sample, seq;
        if (tabName == 'major') {
            section_no = $data.data('sectionseq');
            platform = $data.data('platform') || 'pc';
            action = $data.data('action');
            module = $data.data('module');
            sample = '/codeassist/samples/' + $data.data('sample');
            seq = $data.data('seq') || 0;
        } else if (tabName == 'all') {
            section_no = $data.eq(1).data('sectionseq');
            platform = $data.eq(1).data('platform') || 'pc';
            action = $data.eq(1).data('action');
            if (platform == 'mobile') {
                module = $data.eq(1).data('module');
                seq = $data.eq(2).data('key') || 0;
            } else {
                module = $data.eq(0).data('key');
                sample = $data.eq(1).data('key');
                seq = $data.eq(2).data('key') || 0;
            }
        }

        return {
            platform : platform,
            module : module,
            action : action,
            sample : sample,
            seq : seq,
            lang : SDE.Prop.lang,
            skin_no : SDE.SKIN_NO,
            section_no : section_no
        };
    },

    save : function() {
        var data = this.getSelectedInfo(),
            action = this.$control.is(':visible') ? this.$control.find('select').val() : null,
            html = '';

        if (!data.module) {
            alert(__('PLEASE.SELECT.MODULE', 'EDITOR.LAYER.EDITING.MODULE'));
            return false;
        }

        var response = $.parseJSON($.ajax({
            url: getMultiShopUrl('/exec/admin/editor/moduleSample'),
            data : data,
            async : false
        }).responseText);

        html = response.html + '\n';

        if (action == 'change') {
            this.actionChange(html);
        } else if (action == 'before' || action == 'after') {
            this.actionAdd(html, action);
        } else {
            this.actionInsert(html);
        }

        return true;
    },

    set : function(type, key, info) {
        this.key = key;

        this.$control.toggle(!!key);

        this.isRendered = false;
    },

    render : function() {
        if (!this.isRendered) {
            this._renderAppMobile();
            this._renderMajor();

        }

        return this.$element;
    },

    _renderMajor : function() {
        this.$majorModule = this.$modules.filter('[data-name=major]');

        this.$majorThumbnail = this.$majorModule.find('.thumbnail');

        this.$majorModule.filter('[data-name=major]')
            .find('li')
            .filter(!SDE.mo()? '.mobile': '.pc').hide().end()
                .click($.proxy(this._onClickMajorList, this))
                .first()
                    .trigger('click');
    },

    _renderApp : function() {
        var key,
            html = '<div class="list step1" data-type="app"><ul>',
            apps = SDE.CODE_ASSIST;

        this.$allModule = this.$element.find('.module[data-name="all"]').empty();

        for (key in apps) {
            if (!apps[key].samples) continue;

            html += '<li data-key="'+ key +'"><a href="#">' + decodeURIComponent(apps[key].name) + '</a></li>';
        }

        html += '</ul></div>';

        $(html).appendTo(this.$allModule);

        this.$allModule
            .find('li')
            .click($.proxy(this._onClickList, this));
    },

    _renderAction : function(app) {
        var html = '<div class="list step2" data-type="action"><ul>',
            samples = SDE.CODE_ASSIST[app].samples,
            key;

        for (key in samples) {
            html += '<li data-key="'+ samples[key].path +'"><a href="#">'+ (samples[key].name || __('SAMPLE', 'EDITOR.LAYER.EDITING.MODULE')) +'</a></li>';
        }

        html += '</ul></div>';

        this.appendToModule(html);
    },

    _renderAppMobile : function() {
        var key,
            html = '<div class="list step1" data-type="app"><ul>',
            apps = SDE.CODE_ASSIST;

        //var $hAppList = {};
        //var instance = this;
        //$.each(this.modulelistdata.maplist[this.platform+".all"],function($i,$item){
        //    $hAppList[instance.modulelistdata.module[$item.seq].moduleid.split(/_/).shift()] = true;
        //});

        this.$allModule = this.$element.find('.module[data-name="all"]').empty();

        for (key in apps) {
            //if (!(key in $hAppList)) continue;
            if (!apps[key].samples) continue;

            // ECHOSTING-158389 모듈 미노출 - '멀티쇼핑몰'
            if (key == 'Multishop') continue;

            html += '<li data-key="'+ key +'"><a href="#">' + decodeURIComponent(apps[key].name) + '</a></li>';
        }

        html += '</ul></div>';

        $(html).appendTo(this.$allModule);

        this.$allModule
            .find('li')
            .click($.proxy(this._onClickList, this));
    },

    _renderActionMobile : function(app) {
        var html = '<div class="list step2" data-type="action"><ul>',
            key;
        var instance = this;
        var $hActionSet = {};
        var $k = 0;
        $.each(this.modulelistdata.maplist[this.platform+".all"]||[],function($i,$item){
            var $itemdata = instance.modulelistdata.module[$item.seq];
            var $tmp = $itemdata.moduleid.split(/_/), $module = $tmp.shift(), $action = $tmp.join("_");
            if ($module !== app) return;
            $hActionSet[$item.name || $itemdata.title || ("module-"+(++$k))] = '<li class="'+instance.platform+'" data-platform="'+instance.platform+'" data-moduleid="'+$itemdata.moduleid+'" data-module="'+$module+'" data-action="'+$action+'" data-sectionseq="'+$itemdata.seq+'" data-preview="'+$itemdata.previewimgurl+'"><a href="#none">'+($item.name||$itemdata.title)+'</a></li>';
        });
        $.each(Object.arraysort($hActionSet),function($i,$item){
            html += $item;
        });

        html += '</ul></div>';

        $(html)
            .appendTo(this.$allModule)
            .find('li')
            .click($.proxy(this._onClickList, this))
            .first()
            .trigger('click');
    },

    _renderSeq : function(app) {
        var html = '<div class="list step3" data-type="seq"><ul>',
            seq = SDE.CODE_ASSIST[app].seq,
            key;

        if (seq == null || app != 'Board') return;

        for (key in seq) {
            html += '<li data-key="' + seq[key].value +'"><a href="#">'+ decodeURIComponent(seq[key].name.replace('＆', '&')) +'</a></li>';
        }

        html += '</ul></div>';

        this.appendToModule(html);
    },

    _reset : function() {
        this.$allModule.find('[data-type="action"], [data-type="seq"]').remove();
    },

    _onClickList : function(evt) {
        var $target = $(evt.currentTarget),
            $parent = $target.parents('div:first'),
            app;

        $parent.find('li').removeClass('selected');
        $target.addClass('selected');

        if ($parent.data('type') != 'app') return;

        app = $target.data('key');

        this._reset();
        if (app in this.definedmodulelist) {
            this._renderActionMobile(app);
        } else {
            this._renderAction(app);
        }
        this._renderSeq(app);
    },

    _onClickMajorList : function(evt) {
        var instance = this;
        var $target = $(evt.currentTarget),
            $siblings = $target.siblings('li'),
            img = $target.data('img');

        $siblings.removeClass('selected');
        $target.addClass('selected');

        var $sImageUrl = $target.data('preview');
        if (!$sImageUrl) {
            instance.$majorThumbnail.html('<img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt_layer_module1.png" />');
            return;
        }
        var $img = new Image();
        $img.onload = function () {
            instance.$majorThumbnail.html('<img src="'+this.src+'" />');
        }
        $img.onerror = function () {
            instance.$majorThumbnail.html('<img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt_layer_module1.png" />');
        }
        $img.src = "http://section-design1.cafe24.com"+$sImageUrl;
    },

    _onClickTab : function(evt) {
        var $target = $(evt.currentTarget)
            name = $target.data('name');

        this.$tab.removeClass('selected');
        $target.addClass('selected');

        this.$modules.hide();
        this.$modules.filter('[data-name=' + name + ']').show();
    }
});

SDE.Layer.EditingDeco = SDE.Layer.EditingBase.extend({
    TEMPLATE : '<div>' +
                    '<h3 class="objHidden">'+ __('SELECT.DESIGN', 'EDITOR.LAYER.EDITING.DECO') +'</h3>' +
                    '<div class="makeUpModule">' +
                        '<ul class="tab"></ul>' +
                        '<div class="designSelect">' +
                            '<div class="list"></div>' +
                            '<button type="button" class="prev" data-type="prev">'+ __('PREVIOUS', 'EDITOR.LAYER.EDITING.DECO') +'</button>' +
                            '<button type="button" class="next" data-type="next">'+ __('NEXT', 'EDITOR.LAYER.EDITING.DECO') +'</button>' +
                            '<button type="button" class="all" data-type="all">'+ __('VIEW.ALL', 'EDITOR.LAYER.EDITING.DECO') +'</button>' +
                        '</div>' +
                    '</div>' +
                    '<p class="noMakeUpModule">'+ __('MODULES.PROVIDED', 'EDITOR.LAYER.EDITING.DECO') +'</p>' +
                    '<div class="preview choose">' +
                        '<strong><img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt_preview.png" alt="'+ __('PREVIEW', 'EDITOR.LAYER.EDITING.DECO') +'" /></strong>' +
                        '<p></p>' +
                    '</div>' +
                    '<div class="preview info">' +
                        '<p><img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt_layer_module1.png" alt="'+ __('YOU.SELECT.THE.DESIGN', 'EDITOR.LAYER.EDITING.DECO') +'" /></p>' +
                    '</div>' +
                    //'<div class="preview">' +
                    //    '<strong>미리보기</strong>' +
                    //    '<p></p>' +
                    //'</div>' +
                    //
                    //'<div class="preview info">' +
                    //    '<p><img src="//img.echosting.cafe24.com/smartAdmin/img/editor/txt_layer_module.gif" alt="리스트의 디자인을 선택하면 해당 모듈의 변경 가능한 디자인의 상세정보를 확인 할 수 있습니다."><span>해당 꾸미기는 선택한 부분디자인으로 변경되니 주의 하세요.</span></p>' +
                    //'</div>' +
               '</div>',

    SECTION_IMAGE_URL : 'section-design1.cafe24.com',

    TITLE : sprintf(__('CHOOSE.DESIRED.DESIGN', 'EDITOR.LAYER.EDITING.DECO'), '<strong>\'${name}\'</strong>'),

    MULTILINGUAL : '<p class="flag"><a href="#none" class="toggle"><img class="nation-icon" src="//img.cafe24.com/img/common/global/ko_KR_32x24.png"35 alt="" /><span class="nation" style="width:70px;">'+ __('KOREAN', 'EDITOR.LAYER.EDITING.DECO') +'</span><span class="toggleBtn">'+ __('OPEN.CLOSE', 'EDITOR.LAYER.EDITING.DECO') +'</span></a></p>' +
                   '<ul class="flag">' +
                       '<li class="ko_KR" data-lang="ko_KR"><a href="#none"><img src="//img.cafe24.com/img/common/global/ko_KR_32x24.png" alt="" />'+ __('KOREAN', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="en_US" data-lang="en_US"><a href="#none"><img src="//img.cafe24.com/img/common/global/en_US_32x24.png" alt="" />'+ __('ENGLISH', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="ja_JP" data-lang="ja_JP"><a href="#none"><img src="//img.cafe24.com/img/common/global/ja_JP_32x24.png" alt="" />'+ __('JAPANESE', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="zh_CN" data-lang="zh_CN"><a href="#none"><img src="//img.cafe24.com/img/common/global/zh_CN_32x24.png" alt="" />'+ __('SIMPLIFIED.CHINESE', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="zh_TW" data-lang="zh_TW"><a href="#none"><img src="//img.cafe24.com/img/common/global/zh_TW_32x24.png" alt="" />'+ __('TRADITIONAL.CHINESE', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="es_ES" data-lang="es_ES"><a href="#none"><img src="//img.cafe24.com/img/common/global/es_ES_32x24.png" alt="" />'+ __('SPANISH', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="pt_PT" data-lang="pt_PT"><a href="#none"><img src="//img.cafe24.com/img/common/global/pt_PT_32x24.png" alt="" />'+ __('PORTUGUESE', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                       '<li class="vi_VN" data-lang="vi_VN"><a href="#none"><img src="//img.cafe24.com/img/common/global/vi_VN_32x24.png" alt="" />'+ __('VIETNAMESE', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>' +
                   '</ul>',
    LIMIT : 5,

    listData : {},

    isRendered : false,

    lang : "ko_KR",

    init : function() {
        this._super();

        this.$preview = this.$element.find('.preview:first p');
        this.$tab = this.$element.find('.tab');
        this.$info = this.$element.find('.info');
        this.$multilingual = $('.layEditor .multilingual');
        this.$lists = this.$element.find('.list');
        this.$itemExists = this.$element.find('.makeUpModule');
        this.$itemNonExists = this.$element.find('.noMakeUpModule');

        this._setEventHandler();
        this.$element.find('.preview').addClass((SDE.mo()? "mobile": "pc")+'Preview');
    },

    render : function() {
        if (!this.isRendered) {
            this._renderTab();
            this.isRendered = true;
        }
        this._renderMultilingual();

        this.$element.find('.preview.choose').css("min-height",0);

        return this.$element;
    },

    save : function() {
        if (!this.selectedNo) {
            alert(__('SELECT.DESIGN.TO.CHANGE', 'EDITOR.LAYER.EDITING.DECO'));
            return false;
        }

        var response = $.parseJSON($.ajax({
            url : getMultiShopUrl('/exec/admin/editor/moduleDown'),
            dataType : 'json',
            data : {
                skin_no : SDE.SKIN_NO,
                section_no : this.selectedNo,
                module : this.key,
                platform : SDE.mo()? "mobile": "pc"
            },
            async : false
        }).responseText);

        if (!response || !response.html) {
            alert(__('WAS.PROBLEM.PLEASE.TRY', 'EDITOR.LAYER.EDITING.DECO'));
            return false;
        };

        SDE.editor.replaceSelection(response.html);

        return true;
    },

    set : function(type, key, info) {
        //if (!key || !info || !info.module_info.action_name) {
        //    this.isUsing = false;
        //    return;
        //}
        if (!key || !info) {
            this.isUsing = false;
            return;
        }

        this.key = key;
        this.info = info;
        this.listData = {};
        this.lang = SDE.Prop.lang || "ko_KR";

        // 디자인 데이터가 있는지 확인
        if (!this._getModule("A", 1)) {
            this.isUsing = false;
            return;
        }

        this.$info.show();
        this.$preview.hide();
        this.isUsing = (
            typeof window.EC_GLOBAL_INFO === 'object' &&
            typeof window.SHOP === 'object' &&
            window.EC_GLOBAL_INFO.isGlobal() === false &&
            window.SHOP.isVN() === false
        );
        this.isRendered = false;
        this.selectedNo = null;
    },

    _getModule : function(tabNo, page) {
        var tabNo = (tabNo !== null) ? tabNo : this.tabNo,
            page = (page !== null) ? page : this.page,
            key = (SDE.mo()? "mobile": "pc") + '_' + this.lang + '_' + tabNo + '_' + this.LIMIT + '_' + page,
            lists = this.listData[key],
            total = 0;

        if (!lists) {
            var response = $.parseJSON(($.ajax({
                url : getMultiShopUrl('/exec/admin/editor/moduleDesign'),
                data : {
                    module : this.info.module_info.module + '_' + this.info.module_info.action,
                    limit : this.LIMIT,
                    tab_no : tabNo,
                    page : page,
                    platform : SDE.mo()? "mobile": "pc",
                    lang : this.lang
                },
                dataType : 'json',
                async : false

            }).responseText));

            if (!response || response.Err) {
                return false;
            }

            lists = this.listData[key] = {length:+response.total, list: response.list};
        }
        this.totallength = lists.length;

        this._clearSection();


        this.$itemExists.hide();
        this.$itemNonExists.hide();

        this.$lists.find('li').removeClass('selected');
        if (lists.length == 0) {
            if (tabNo === null || tabNo === "A") {
                this.$itemNonExists.css({display:"block"});
                this.$preview.hide();
                this.$info.show();
            } else {
                this.$itemExists.show();
            }
            return;
        } else {
            this.$itemExists.show();
        }

        this.tabNo = tabNo;
        this.page = page;

        this._renderSection(lists.list);

        return true;
    },

    _onClickTab : function(evt) {
        var $target = $(evt.target).closest("li");

        this.$tab.find('li').removeClass('selected');
        $target.addClass('selected');

        if (this.LIMIT !== 5) {
            this.LIMIT = 5;
            this.$element.find('button.all').removeClass('part');
            this.$element.find(".designSelect").css("height","135px");
            this.$element.find(".designSelect .list").css("height","135px");
        }

        this._getModule($target.data('no'), 1);

        if (!this.$lists.find('li.selected:visible').length) {
            this.$preview.hide();
            this.$info.show();
        }
    },

    _onClickButton : function(evt) {
        evt.stopPropagation();
        var type = $(evt.currentTarget).data('type'),
            page = this.page;
        if (type != "all") {
            page += (type == 'next' ? 1 : -1);
            if (page < 1) page = 1;
            if (this.totallength !== 0 && Math.ceil(this.totallength/this.LIMIT) < page) page = Math.ceil(this.totallength/this.LIMIT);
            this._getModule(null, page);
        } else if (type == "all" && this.totallength > 5) {
            if (this.LIMIT === 5) {
                this.LIMIT = 10;
                $(evt.target).addClass('part');
                this.$element.find(".designSelect").css("height","282px");
                this.$element.find(".designSelect .list").css("height","282px");
            } else {
                this.LIMIT = 5;
                $(evt.target).removeClass('part');
                this.$element.find(".designSelect").css("height","135px");
                this.$element.find(".designSelect .list").css("height","135px");
            }
            this._getModule(null, 1);
        }
    },

    _onClickSection : function(evt) {
        var $target = $(evt.currentTarget),
            no = $target.data('no');
            image = $target.data('image'),
            src = image ? this.SECTION_IMAGE_URL + image : 'img.echosting.cafe24.com/design/sample/big.gif';

        this.$lists.find('li').removeClass('selected');
        $target.addClass('selected');

        this.$preview.html('<img src="http://'+ src +'"/>').show();
        this.$element.find('.preview.choose').css("min-height","");
        this.$info.hide();

        this.selectedNo = no;
    },

    _onToggleMultilingual : function(evt) {
        if (this.$multilingual.find("ul.flag").filter(":visible").length) {
            this.$multilingual.find("ul.flag").hide();
        } else {
            this.$multilingual.find("ul.flag").show();
        }
    },

    _onSelectLang : function(evt) {
        this.lang = $(evt.target).closest("li").attr("data-lang");
        this.$multilingual.find("ul.flag").hide();
        this.$multilingual.find(".nation").html($(evt.target).closest("li").find("a").text());
        this.$multilingual.find(".nation-icon").attr("src",$(evt.target).closest("li").find("img").attr("src"));
        this.$tab.find('li').removeClass("selected").first().addClass("selected");
        this._getModule("A", 1);
        return false;
    },

    _renderTab : function() {
        var html = '',
            key, tab, name;

        html += '<li data-no="A"><a href="#">'+ __('ALL', 'EDITOR.LAYER.EDITING.DECO') +'</a></li>';
        for (key in this.info.tab) {
            tab = this.info.tab[key];
            name = ($.trim(tab.name) || __('BASIC', 'EDITOR.LAYER.EDITING.DECO'));
            html += ' <li data-no="'+ key +'"><a href="#">'+ name +'</a></li>';
        }

        this.$tab.html(html);
        this.$tab
            .find("li").eq(0)
            .trigger('click');
    },

    _renderMultilingual : function() {
        this.$multilingual.html(this.MULTILINGUAL).show();
        this.$multilingual.find("ul.flag li").hide();
        var instance = this;
        $.each(SDE.SUPPORT_LANG_LIST,function(i,item){
            instance.$multilingual.find("ul.flag li."+item).show();
        });
        this.$multilingual.find(".toggle")
            .click($.proxy(this._onToggleMultilingual, this))
            .end()
            .find("ul.flag").click("a", $.proxy(this._onSelectLang, this));
        this.$multilingual.find(".nation").html($(".flag ."+this.lang).find("a").text());
        this.$multilingual.find(".nation-icon").attr("src",$(".flag ."+this.lang).find("img").attr("src"));
    },

    _renderSection : function(lists) {
        var html = '',
            key, list, src, className;

        for (key in lists) {
            list = lists[key];

            src = list.thumb_img ? this.SECTION_IMAGE_URL + list.thumb_img : 'img.echosting.cafe24.com/design/sample/thumb.gif';

            className = '';
            if (this.selectedNo == list.section_no) {
                className = 'selected';
                this.$preview.html('<img src="http://'+ this.SECTION_IMAGE_URL + list.image +'"/>').show();
                this.$info.hide();
            }

            html += '<li class="'+ className +'" data-no="'+ list.section_no +'" data-image="'+ list.image +'"><a href="#"><img src="http://' + src + '"/></a><span title="'+list.section_name+'">'+this._strclamp(list.section_name,24,"..")+'</span></li>';
        }

        this.$lists
            .html("<ul>"+html+"</ul>")
            .find('li')
            .click($.proxy(this._onClickSection, this));
    },

    _clearSection : function() {
        this.$lists.html("");
    },

    _setEventHandler : function() {
        this.$element.find('button').click($.proxy(this._onClickButton, this));
        this.$tab
            .bind("click","li",$.proxy(this._onClickTab, this))
            ;
    },

    _strclamp : function (str, limit, suffix) {
        var c, b, rl = 0, l = 0, i = 0;
        suffix = (suffix || "");
        for (;c = suffix.charCodeAt(i), !isNaN(c) && (rl += (c>>11? 2: c>>7? 2: 1)); i++);
        limit-= rl;
        for (i = 0; c = str.charCodeAt(i), !isNaN(c) && (l + (b = c>>11? 2: c>>7? 2: 1)) <= limit; l+= b, i++);
        return str.substr(0, i + 1) + (!isNaN(str.charCodeAt(i + 1))? suffix: "");
    }
});

SDE.Layer.EditingAttr = SDE.Layer.EditingBase.extend({
    isUsing : true,

    TITLE : sprintf(__('ATTRIBUTES.OF.THE.MODULE', 'EDITOR.LAYER.EDITING.ATTR'), '<strong>\'${name}\'</strong>'),

    TYPE : {
        'Logo' : ['layout_logo'],
        'Image' : ['layout_giftbanner', 'layout_calendarbanner', 'layout_opdiarybanner', 'layout_sosbanner', 'layout_attendbanner', 'layout_couponzonebanner', 'layout_shortcout'],

        'MainBanner' : ['mobile_banner'],

        'BoardInfo' : ['layout_boardinfo'],
        'BoardList' : ['board_listpackage', 'board_replypackage', 'board_commentdeletepackage', 'board_securepackage', 'board_readpackage', 'board_commentpackage', 'board_modifypackage', 'board_writepackage', 'board_memopackage'],

        'ProductDetail' : ['product_detail', 'product_detaildesign'],
        'ProductList' : ['product_listmain', 'product_listrecommend', 'product_listnew', 'product_listnormal', 'product_normalpackage'],

        'CategoryList' : ['layout_category'],
        'CategoryHead' : ['product_menupackage', 'product_headcategory']
    },

    contents : {},

    render : function() {
        return this.currentContent.render();
    },

    isEditing : function() {
        return this.currentContent.isEditing;
    },

    save : function() {
        return this.currentContent.save();
    },

    set : function(type, key, info) {
        var attrType = this.getAttrType(type, key);

        if (!attrType) {
            this.isUsing = false;
            return;
        }

        this.isUsing = true;

        if (!this.contents[attrType]) {
            if (SDE.mo() && SDE.Layer['EditingAttr' + 'Mobile' + attrType]) {
                this.contents[attrType] = new SDE.Layer['EditingAttr' + 'Mobile' + attrType];
            } else {
                this.contents[attrType] = new SDE.Layer['EditingAttr' + attrType];
            }
        }

        this.currentContent = this.contents[attrType];

        this.currentContent.set(type, key);
    },

    getParams : function() {
        return this.currentContent.getParams();
    },

    getAttrType : function(type, key) {
        var name, index, data;

        if (!key) return;

        if (type == 'image') return 'Image';

        for (name in this.TYPE) {
            data = this.TYPE[name];

            for (var index in data) {
                if (key.indexOf(data[index]) == 0) return name;
            }
        }
    }
});

SDE.Layer.EditingHtml = SDE.Layer.EditingBase.extend({
    TEMPLATE : '<div class="htmlEdit"><textarea rows="6" cols="20" class="fTextarea"></textarea></div>',
    
    TITLE : sprintf(__('EDIT.THE.HTML', 'EDITOR.LAYER.EDITING.HTML'), '<strong>\'${name}\'</strong>'),
    
    isUsing : true,
    
    _isEditing : false,
    
    init : function() {
        this._super();
        
        this.$textarea = this.$element.find('textarea')            
                            .keyup($.proxy(this._onKeyUp, this));
    },
    
    save : function() {
        if (!this.isEditing) return true;
        
        SDE.editor.replaceSelection(this.$textarea.val());
        
        return true;
    },
    
    set : function(type, key, info) {
        if (!key) {
            this.isUsing = false;
            return;
        }
        
        this.moduleHtml = SDE.editor.getSelection();
        this.isUsing = true;
    },
    
    render : function() {
        this._isEditing = false;
        
        this.$textarea.val(this.moduleHtml);
        
        return this.$element;
    },
    
    _onKeyUp : function() {
        this._isEditing = true;
    }
});
SDE.Layer.EditingAttrBase = Class.extend({
    
    isInit : false,
    
    init : function() {
    },

    getParams : function() {
        return {};
    },
    
    set : function(type, key) {
        
    },
    
    save : function() {
        return false;
    },
    
    render : function() {
        if (!this.isInit) {
            this._render();
            
            this._setEventHandler();
            
            this.isInit = true;
        }
        
        return this.$element;
    },

    _render : function() {
        this.$element = $(this.TEMPLATE);
    },
    
    _setEventHandler : function() {
        
    }
});

SDE.Layer.EditingAttrImage = SDE.Layer.EditingAttrBase.extend({
    /*rev.b7.20130904.2@sinseki #SDE-2 로고 이미지 업로드시 간헐적인 미리보기 업데이트 오류로, 기존 업로드방식인 iframe 을 swfupload 로 교체*/
    TEMPLATE : '<form class="imageuploadform" method="post" enctype="multipart/form-data" target="hidden-submit">' +
                   '<h3>'+ __('CHANGE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') +'</h3>' +
                   '<div class="mBoard">' +
                   '<table border="1" summary="">' +
                        '<caption>'+ __('CHANGE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') +'</caption>' +
                        '<colgroup>' +
                            '<col style="width:20%;">' +
                            '<col style="width:auto;">' +
                        '</colgroup>' +
                        '<tbody>' +
                            '<tr>' +
                                '<th scope="row">'+ __('IMAGE', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') +'</th>' +
                                '<td>' +
                                    '<input type="file" class="fFile" name="image" accept="image/*" size="36" style="width:348px;">' +
                                    '<p class="gFormInfo description" style="display:none">' +
                                        __('LOGO.IMAGES.USE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') + '<br/>' +
                                        __('FILE.APPLY.ALL.PAGES', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') +
                                    '</p>' +
                                    '<div class="gSingle"><span class="frame"></span></div>' +
                                '</td>' +
                            '</tr>' +
                          /*rev.b2.20130902.1@sinseki #SDE-15 이미지에 a href 로 감싸진 경우, 속성에 href 편집 입력 추가*/

                            '<tr>' +
                                '<th scope="row">'+ __('LINK', 'EDITOR.LAYER.EDITING.ATTR.IMAGE') +'</th>' +
                                '<td>' +
                                    '<input type="text" class="attr-href" name="href" style="width:348px;">' +
                                '</td>' +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +

                    '<input type="hidden" name="key">' +

                    // for logo
                    '<input type="hidden" name="type">' +
                    '<input type="hidden" name="update">' +
                    '<input type="hidden" name="path">' +
                '</form>',

    ACTION : getMultiShopUrl('/exec/admin/editor/DecorationImage'),

    init : function() {
        SDE.BroadCastManager.listen('layer-image-uploaded', $.proxy(this._onImageUploaded, this));
    },

    render : function() {
        this._super();

        this._setPreview(this.src);
        /*rev$@sinseki #SDE-15 이미지에 a href 로 감싸진 경우, 속성에 href 편집 입력 추가*/
        this.href = SDE.Util.Module.findSelectedHref();
        this.$element.find('.attr-href').val(this.href);

        this.isEditing = false;
        this.changedSrc = null;

        return this.$element;
    },

    set : function(type, key) {
        this.src = (type == 'module') ? SDE.Util.Module.findSelectedSrc() : key;

        this._resetForm();
    },

    save : function() {
        var content = SDE.editor.getSelection();
        /*rev.b5.20130902.1@sinseki #SDE-15 이미지에 a href 로 감싸진 경우, 속성에 href 편집 입력 추가*/
        var precontent = content;

        var $href = this.$element.find('.attr-href').val();
        if (this.href) {
            content = content.replace(this.href, this.$element.find('.attr-href').val());
        } else if ($href) {
            content = '<a href="'+$href+'">'+content+'<\/a>';
        }
        if (this.changedSrc) {
            content = content.replace(this.src, this.changedSrc);
        }
        (precontent != content) && SDE.editor.replaceSelection(content);

        return true;
    },

    _resetForm : function() {
        if (!this.$element) return;

        this.$element.get(0).reset();
        this.$element.find('[type=hidden]').val('');
    },


    _onChangeImage : function(evt) {
        var name = this.$element.find('[type=file]').val(),
            key = makeRandomString();

        if (!name) return;

        if (!SDE.Util.File.isValidImageName(name)) {
            alert(__('JPG.JPEG.PNG.IMAGES', 'EDITOR.LAYER.EDITING.ATTR.IMAGE'));
            return;
        }

        this.key = key;
        this.$element.find('input[name=key]').val(key);
        this.$element.submit();
    },

    _onImageUploaded : function(evt, key, isSuccess, src) {
        if (key != this.key) return;

        if (!isSuccess) {
            alert(__('IMAGE.SELECT.NORMAL.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.IMAGE'));
            this._isEditing = false;
            return;
        }

        this._setPreview(src);

        this.changedSrc = src;

        this.isEditing = true;
    },

    _render : function() {
        this.$element = $(this.TEMPLATE);

        this.$preview = this.$element.find('.frame');

        this.$element.attr('action', this.ACTION);
    },

    _setPreview : function(src) {
        this.$preview.html('<img style="max-width:495px;" src="' + src + '" onerror="this.src=\'//img.echosting.cafe24.com/design/skin/default/layout/logo.png\'"/>');
    },

    _setEventHandler : function() {
        this.$element.change($.proxy(this._onChangeImage, this));
    },

    /*rev.b20.20130904.3@sinseki #SDE-2 로고 이미지 업로드시 간헐적인 미리보기 업데이트 오류로, 기존 업로드방식인 iframe 을 swfupload 로 교체*/
    renderAfter : function() {
        var commify = function ($p) { $s = (''+$p); while($s != ($s = $s.replace(/(\d+)(\d{3})/, '$1,$2'))){}; return $s; }
        var instance = this;
        SFUpload.init({
            url:"/exec/admin/editor/DecorationImagebyflash",
            swf:"/app/Editor/img/swfupload.swf",
            filepostname:"image",
            node:"imageuploadbutton",
            css:{position:"absolute",width:"100%",height:"100%",background:"white",opacity:.1,"filter":"alpha(opacity=1)"},
            ready: function () {
                $("#imageuploadindicator").val("");
            },
            dialogopen: function () {
                this.queue = [];
            },
            queue: function (file) {
                if (this.$engine.getStats().files_queued == 1) {
                    $("#imageuploadindicator").val([file.name,commify(file.size)+" byte"].join(" : "));
                } else {

                }
                //this.cancel(file.id);
            },
            queueerror: function (file, errorCode, message) {},
            dialogclose: function (numFilesSelected, numFilesQueued, numFilesInQueue)
            {
                var ec = $(".imageuploadform input").get();
                var z = {};
                for (var i=0; i < ec.length; i++) {
                    z[ec[i].name] = ec[i].value;
                }
                var $key = instance.key = makeRandomString();

                instance.$element.find('input[name=key]').val($key);
                this.prop({url:"/exec/admin/editor/DecorationImagebyflash?_x_sess_id="+_sess_id_for_swfup,params:{key:$key,type:instance.$element.find('input[name=type]').val()}});
                this.upload();
            },
            uploadstart: function (file) {},
            uploadprogress: function (file, bytesComplete, bytesTotal) {
            },
            uploaderror: function (file, errorCode, message) {
            },
            uploadend: function (file, serverData, responseReceived) {
                var r = window.JSON? JSON.parse(serverData): eval("("+serverData+")");
                this._onImageUploaded(null, r.key, r.issuccess, r.src);
            }.bind(this),
            uploaded: function (file) {}
        });
    }
});
SDE.Layer.EditingAttrLogo = SDE.Layer.EditingAttrImage.extend({
    ACTION : getMultiShopUrl('/exec/admin/editor/DecorationLogo'),

    LOGO_KEY_PREFIX : 'layout_logo',

    render : function() {
        var result = this._super();

        this.$element.find('.description').show();

        this.$type.val(this._getLogoType());

        return result;
    },

    set : function(type, key) {
        // Logo는 변수 처리 되어있기 때문에 Ghost에서 선택된 모듈을 가져와서 src를 검색
        var $el = SDE.Util.Module.getSelectedElement();

        this._resetForm();

        this.src = $el.find('img').attr('src');

        this.key = key;
    },


    save : function() {
        /*rev.b7.20130902.3@sinseki #SDE-15 이미지에 a href 로 감싸진 경우, 속성에 href 편집 입력 추가*/
        var content = SDE.editor.getSelection();
        var precontent = content;


        var $href = this.$element.find('.attr-href').val();
        if (this.href) {
            content = content.replace(this.href, this.$element.find('.attr-href').val());
        } else if ($href) {
            content = '<a href="'+$href+'">'+content+'<\/a>';
        }
        content = content.replace(/src="(.*?)"/i, 'src="{$logo}"');

        (precontent != content) && SDE.editor.replaceSelection(content);

        if (!this.changedSrc) return true;

        // raw src 변경인 경우
        if (!this.$type.val()) return this._super();

        this.$update.val('1');
        this.$path.val(this.changedSrc);

        this.$element.submit();

        return true;
    },

    _getLogoType : function() {
        var src = SDE.Util.Module.findSelectedSrc(),
            type;

        if (this.LOGO_KEY_PREFIX == this.key) {
            // layout_logo 모듈 안에 변수 없이 raw src가 들어가있는 경우
            if (!src || src.indexOf('{$') === -1) return;

            // layout_logo 모듈 안에 {$logo_top}, {$logo_bottom} 사용하는 경우
            try {
                type = src.match(/top|bottom/)[0];
            } catch (e) {}
        } else {

            // layout_logotop, layout_logobottom 인 경우
            type = this.key.replace(this.LOGO_KEY_PREFIX, '');
        }

        return type;
    },

    _render : function() {
        this._super();

        this.$type = this.$element.find('[name=type]');
        this.$path = this.$element.find('[name=path]');
        this.$update = this.$element.find('[name=update]');
    },
    /*rev.b26.20130922.1@sinseki #SDE-2 로고 이미지 업로드시 간헐적인 미리보기 업데이트 오류로, 기존 업로드방식인 iframe 을 swfupload 로 교체*/
    renderAfter : function() {
        var commify = function ($p) { $s = (''+$p); while ($s != ($s = $s.replace(/(\d+)(\d{3})/, '$1,$2'))){}; return $s; }
        var instance = this;
        SFUpload.init({
            url:"/exec/admin/editor/DecorationLogobyflash",
            swf:"/app/Editor/img/swfupload.swf",
            filepostname:"image",
            node:"imageuploadbutton",
            css:{position:"absolute",width:"100%",height:"100%",background:"white",opacity:.1,"filter":"alpha(opacity=1)"},
            ready: function () {
                $("#imageuploadindicator").val("");
            },
            dialogopen: function () {
                this.queue = [];
            },
            queue: function (file) {
                if (this.$engine.getStats().files_queued == 1) {
                    $("#imageuploadindicator").val([file.name,commify(file.size)+" byte"].join(" : "));
                } else {

                }
                //this.cancel(file.id);
            },
            queueerror: function (file, errorCode, message) {},
            dialogclose: function (numFilesSelected, numFilesQueued, numFilesInQueue)
            {
                var ec = $(".imageuploadform input").get();
                var z = {};
                for (var i=0; i < ec.length; i++) {
                    z[ec[i].name] = ec[i].value;
                }
                var $key = instance.key = makeRandomString();

                instance.$element.find('input[name=key]').val($key);
                this.prop({url:"/exec/admin/editor/DecorationLogobyflash?_x_sess_id="+_sess_id_for_swfup,params:{key:$key,type:instance.$element.find('input[name=type]').val()}});
                this.upload();
            },
            uploadstart: function (file) {},
            uploadprogress: function (file, bytesComplete, bytesTotal) {
            },
            uploaderror: function (file, errorCode, message) {
            },
            uploadend: function (file, serverData, responseReceived) {
                var r = window.JSON? JSON.parse(serverData): eval("("+serverData+")");
                this._onImageUploaded(null, r.key, r.issuccess, r.src);
            }.bind(this),
            uploaded: function (file) {}
        });
    }
});
SDE.Layer.EditingAttrPreferenceBase = SDE.Layer.EditingAttrBase.extend({
    TEMPLATE : '',

    KEYS : {},

    data : {},

    components : {},

    isSectionInit : false,

    REQUIRED_VARIABLES : [],

    HELP_TEMPLATE : '<div><div class="help">' +
                        '<strong>'+ __('CAN.NOT.REFLECT.PROPERTY', 'EDITOR.LAYER.EDITING.ATTR.PREFERENCE') +'</strong>' +

                        '<ul>' +
                            '<li>'+ __('MISSING.INCONSISTENT.HTML.TAGS', 'EDITOR.LAYER.EDITING.ATTR.PREFERENCE') +'</li>' +
                            '<li>'+ __('NOT.APPLIED.REFER', 'EDITOR.LAYER.EDITING.ATTR.PREFERENCE') +'<br>' +
                                '<a href="${helpLink}" class="txtPoint" target="_blank">'+ sprintf(__('HOW.USE.MODULE.ATTRIBUTES', 'EDITOR.LAYER.EDITING.ATTR.PREFERENCE'), '${moduleName}') +'</a>' +
                            '</li>' +
                        '</ul>' +
                    '</div></div>',


    /*
     * Template Render
     */
    render : function() {
        this._super();

        this.isEditing = false;
        this.data = {};

        // render 함수 완료 후에 실행 되도록 처리
        scope = this;
        setTimeout(function() {
            scope._detectAttribute();
        }, 0);

        return this.$element;
    },

    /**
     * Preference 값 저장
     */
    save : function() {
        var data = $.extend({}, this.data);

        for (key in data) {
            delete data[key].isEditing;
        }

        SDE.Util.Preference.set(this._getPreferenceName(), data);

        return true;
    },

    /**
     * Form Component 생성
     */
    _createComponent : function(type, eventName, callback, Component) {
        var i, key, component;

        this.components[type] = {};

        for (i in this.KEYS[type]) {
            key = this.KEYS[type][i];

            component = this.components[type][key] = new Component(this.$section.find('[data-type='+ key +']'));

            component.name = key;

            $(component).bind(eventName, callback);
        }
    },

    _detectAttribute : function() {
        var html, name;

        if (SDE.Util.Module.hasVariables(this.REQUIRED_VARIABLES)) return;

        // 스마트디자인 속성 안내 영역 EC_KOREA 만 제공
        if (typeof EC_GLOBAL_INFO === 'object' && EC_GLOBAL_INFO.isGlobal() === true) return;

        name = SDE.Util.Module.getCurrentName();

        html = $.tmpl(this.HELP_TEMPLATE, {
            moduleName : name ? '[' + name + '] ' : '',
            helpLink : '//img.echosting.cafe24.com/guide/cafe24_smartdesign_editorguide_attribute('+ this.HELP_LINK_KEY +').pdf'
        }).html();

        this.$element
            .first()
            .before(html);
    },

    /**
     * Preference 파일 이름 가져오기
     */
    _getPreferenceName : function(no) {
        // Implement
    },

    _onImageUpload : function(evt, src) {
        var name = evt.target.name;

        this._setData(name, src);
    },

    _onColorChanged : function(evt, color) {
        var name = evt.target.name;

        this._setData(name, color);
    },

    _onRadioChanged : function(evt, val) {
        var name = evt.target.name,
            radio;

        this._setData(name, val);
    },

    /**
     * Preference Data 가공
     */
    _processData : function(data) {
        return data;
    },

    _render : function() {
        this._super();

        this.$list = this.$element.find('[data-view=list]');
        this.$section = this.$element.find('[data-view=section]');
    },

    _renderImages : function() {
        this._createComponent('image',
                              'image-uploaded',
                              $.proxy(this._onImageUpload, this),
                              SDE.Component.Image);
    },

    _renderColorPicker : function() {
        this._createComponent('color_picker',
                              'color-changed',
                              $.proxy(this._onColorChanged, this),
                              SDE.Component.ColorPicker);
    },

    _renderRadio : function() {
        this._createComponent('radio',
                              'radio-changed',
                              $.proxy(this._onRadioChanged, this),
                              SDE.Component.Radio);
    },

    _renderSection : function() {
        this.$section.html(this.SECTION_TEMPLATE);
    },

    _showSection : function(data, name) {
        if (!this.isSectionInit) this._renderSection();

        data = this._processData(data);

        this._setCurrentPref(data);
        this._setSection(data);
    },

    /**
     * 현재 사용중인 데이터 No 설정
     */
    _setCurrentPref : function(data) {
        // Implement
    },

     _setRadioDisplay : function(name, val) {
        this.$section.find('[data-radio=' + name + ']')
            .hide()
            .filter('[data-radio-value='+ val +']')
                .show();
    },

    /**
     * Section Data 설정
     */
    _setSection : function(data) {
        for (type in this.KEYS) {
            for (i in this.KEYS[type]) {
                key = this.KEYS[type][i];

                if (!data) continue;

                if (data[key] === '/web/upload/category/')
                    data[key] = null;
                this.components[type][key].set(data[key]);
            }
        }
    },

    /**
     * Data 값 설정
     */
    _setData : function(name, data) {
        if (!this.data[this.currentPref]) this.data[this.currentPref] = {};

        this.data[this.currentPref][name] = data;
        this.data[this.currentPref].isEditing = true;

        this.isEditing = true;
    },

    /**
     * Data 값 가져오기
     */
    _getData : function(name) {
        if (!(name in this.data[this.currentPref]))
            return;

        return this.data[this.currentPref][name];
    }
});

SDE.Layer.EditingAttrBoardBase = SDE.Layer.EditingAttrPreferenceBase.extend({
    TEMPLATE : '<h3>'+ __('DESIGN.BY.BULLETIN.BOARD', 'EDITOR.LAYER.EDITING.ATTR.BOARD') +'</h3>' +
    '<div class="attrArea">' +
        '<div class="list" data-view="list"></div>' +
        '<div class="section" data-view="section">' +
        '</div>' +
    '</div>',

    preferences : {}, 

    render : function() {
        this._super();
        
        this.BoardList.render();
        
        return this.$element;
    },

    save : function() {
        var key, data = {};
        
        if (!this.isEditing) return true;
        
        for (key in this.data) {
            if (!this.data[key].isEditing) continue;
            
            delete this.data[key].isEditing;
            
            data[this._getPreferenceName(key)] = {
                'board_detail' : this.data[key]
            };
        }

        SDE.Util.Preference.setMulti(data);

        return true;
    },

    _getPreferenceName : function(key) {
        return 'board_title_' + key;
    },
    
    _onClickList : function(evt, key, name) {
        this.currentBoardNo = key;
        
        this.preferences[key] = SDE.Util.Preference.get('board_title_' + key);

        this._showSection(this.preferences[key], name);
    },

    _onRadioChanged : function(evt, val) {
        this._super(evt, val);

        this._setRadioDisplay(evt.target.name, val);
    },
    
    _processData : function(data) {
        var no = data.board_detail.board_no;

        data = data.board_detail;

        if (this.data[no]) {
            data = $.extend({}, data, this.data[no]);
        }

        return data;
    },    
    
    _render : function() {
        this._super();
        
        this.BoardList = new SDE.Component.BoardList(this.$list); 
        
        $(this.BoardList).bind({
            'list-click' : $.proxy(this._onClickList, this)
        });
    },

    _setSection : function(data, name) {
        var radioKeys = this.KEYS['radio'],
            i, key;
        this._super(data);

        // radio button에 따른 display 설정
        for (i in radioKeys) {
            key = radioKeys[i];

            this._setRadioDisplay(key, data[key]);
        }
    }, 

    _setCurrentPref : function(data) {
        this.currentPref = data.board_no;
    }
});

SDE.Layer.EditingAttrBoardInfo = SDE.Layer.EditingAttrBoardBase.extend({
    SECTION_TEMPLATE : '<div class="mBoard">' +
                            '<table border="1" summary="">' +
                                '<caption>'+ __('CLASSIFICATION.BY.PRODUCT', 'EDITOR.LAYER.EDITING.ATTR.BOARDINFO') +'</caption>' +
                                '<colgroup>' +
                                    '<col style="width:25%;">' +
                                    '<col style="width:auto;">' +
                                '</colgroup>' +
                                '<tbody>' +
                                    '<tr>' +
                                        '<th scope="row">'+ __('MENU.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.BOARDINFO') +'</th>' +
                                        '<td>' +
                                            /*'<label class="fChk"><input type="radio" name="radio3"> 사용함</label>' +
                                            '<label class="fChk eSelected"><input type="radio" name="radio3" checked="checked"> 사용안함</label>' +*/
                                            '<div class="gFrame">' +
                                                '<span class="gWidth type1">'+ __('BASIC', 'EDITOR.LAYER.EDITING.ATTR.BOARDINFO') +'</span>' +
                                                '<div class="frameSelect" data-type="menu_image">' +

                                                '</div>' +
                                            '</div>' +
                                            
                                            '<div class="gFrame">' +
                                                '<span class="gWidth type1">'+ __('ROLLOVER', 'EDITOR.LAYER.EDITING.ATTR.BOARDINFO') +'</span>' +
                                                '<div class="frameSelect" data-type="menu_rollover_image">' +

                                                '</div>' +
                                            '</div>' +
                                         '</td>' +
                                     '</tr>' +
                                 '</tbody>' +
                             '</table>' +
                         '</div>',
    
    KEYS : {
        'image' : ['menu_image', 'menu_rollover_image']
    },

    REQUIRED_VARIABLES : ['board_img_name'],

    HELP_LINK_KEY : 'layout_boardinfo',

    render : function() {
        this.data = {};
        
        return this._super();
    },
    
    _renderSection : function() {
        this._super();

        this._renderImages();
    }
});

SDE.Layer.EditingAttrBoardList = SDE.Layer.EditingAttrBoardBase.extend({
    SECTION_TEMPLATE : '<div class="mTitle">' +
                            '<h4>'+ __('COMMON.SETTINGS', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</h4>' +
                             '<p>' + sprintf(__('COMMON.TO.ALL.PAGES.OF', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST'), '<strong class="boardName"></strong>') +'</p>' +
                        '</div>' +
        
                        '<div class="mBoard">' +
                            '<table border="1" summary="">' +
                                '<caption>'+ __('BY.BULLETIN.BOARD', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</caption>' +
                                '<colgroup>' +
                                    '<col style="width:25%;">' +
                                    '<col style="width:auto;">' +
                                '</colgroup>' +
                        
                                '<tbody>' +
                                    '<tr>' +
                                        '<th scope="row">'+ __('TOP.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="is_top_image" name="is_top_image" value="T"> '+ __('USED', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="is_top_image" name="is_top_image" value="F"> '+ __('NOT.USED', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<div class="gImg" data-type="top_image" data-radio="is_top_image" data-radio-value="T"></div>' +
                                        '</td>' +
                                    '</tr>' +
                        
                                    '<tr>' +
                                        '<th scope="row">'+ __('TITLE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="is_title_image" name="is_title_image" value="T"> '+ __('USING.IMAGES', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="is_title_image" name="is_title_image" value="F"> '+ __('USING.TEXT', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                
                                            '<div class="gImg" data-type="title_image" data-radio="is_title_image" data-radio-value="T"></div>' +
                                            '<div class="gTxt" data-radio="is_title_image" data-radio-value="F">' +
                                                '<div class="mColorPicker eColorPicker"><input type="text" data-type="name_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                            '</div>' +
                                        '</td>' +
                                    '</tr>' +
                        
                                    '<tr>' +                            
                                        '<th scope="row">'+ __('TITLE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'<br />('+ __('MAIN.SCREEN', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +')</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="is_home_title_image" name="is_home_title_image" value="T"> '+ __('USING.IMAGES', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="is_home_title_image" name="is_home_title_image" value="F"> '+ __('USING.TEXT', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<div class="gImg" data-type="home_title_image" data-radio="is_home_title_image" data-radio-value="T"></div>' +
                                        
                                            '<div class="gTxt" data-radio="is_home_title_image" data-radio-value="F">' +
                                            '</div>' +
                                        '</td>' +
                                    '</tr>' +
                        
                                    '<tr>' +
                                        '<th scope="row">'+ __('NOTICE.ICON', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="is_notice_image" name="is_notice_image" value="T"> '+ __('USED', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="is_notice_image" name="is_notice_image" value="F"> '+ __('NOT.USED', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</label>' +
                                            '<p class="gFormInfo">'+ __('AS.REGISTERED.ICON', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</p>' +
                                            '<div class="gImg" data-type="notice_image" data-radio="is_notice_image" data-radio-value="T"></div>' +
                                        '</td>' +
                                    '</tr>' +
                                '</tbody>' +
                            '</table>' +
                        '</div>' +
                        
                        '<div class="listPackage" style="padding-top:15px">' +
                            '<div class="mTitle">' +
                                '<h4>'+ __('CURRENT.MODULE.SETTINGS', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</h4>' +
                                 '<p>'+ sprintf(__('APPLIES.LIST.PACKAGES', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST'), '<strong class="boardName"></strong>') +'</p>' +
                            '</div>' +

                            '<div class="mBoard">' +
                                '<table border="1" summary="">' +
                                    '<caption>'+ __('BY.BULLETIN.BOARD', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</caption>' +
                                    '<colgroup>' +
                                        '<col style="width:25%;">' +
                                        '<col style="width:auto;">' +
                                    '</colgroup>' +
                            
                                    '<tbody>' +
                                        '<tr>' +
                                            '<th scope="row">'+ __('TITLE.CHARACTER.COUNT', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</th>' +
                                            '<td>' +
                                                '<input type="text" class="fText" data-type="subject_length">' +
                                            '</td>' +
                                        '</tr>' +
                            
                                        '<tr>' +
                                            '<th scope="row">'+ __('LIST.COLOR', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</th>' +
                                            '<td>' +
                                                '<ul class="gSelectList">' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">'+ __('TEXT.COLOR', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="list_char_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">'+ __('BACKGROUND.COLOR', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="list_bgcolor" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +

                                                    '<li>' +
                                                        '<span class="gWidth type2">'+ __('LINK.COLOR', 'EDITOR.LAYER.EDITING.ATTR.BOARDLIST') +'</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="active_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                '</ul>' +
                                            '</td>' +
                                        '</tr>' +
                            
                                        /*
                                        '<tr>' +                            
                                            '<th scope="row">링크 색상</th>' +
                                            '<td>' +
                                                '<ul class="gSelectList">' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">Active</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="active_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">Hover</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="hover_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">Visited</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="visited_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                    '<li>' +
                                                        '<span class="gWidth type2">Link</span>' +
                                                        '<div class="mColorPicker eColorPicker"><input type="text" data-type="link_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                                    '</li>' +
                                                '</ul>' +
                                            '</td>' +
                                        '</tr>' +
                                        */
                                    '</tbody>' +
                                '</table>' +
                            '</div>' +
                        '</div>',
    
    KEYS : {
        'image' : ['top_image', 'title_image', 'home_title_image', 'notice_image'],
        'color_picker' : ['name_color', 'home_title_color', 'list_char_color', 'list_bgcolor', 'active_color', /*'hover_color', 'visited_color', 'link_color'*/],
        'radio' : ['is_top_image', 'is_title_image', 'is_home_title_image', 'is_notice_image']
    },

    REQUIRED_VARIABLES : ['board_title', 'notice_icon', 'board_top_image', 'list_bg_color', 'list_char_color', 'link_color'],

    HELP_LINK_KEY : 'board_listpackage',
    
    getParams : function() {
        var module = SDE.Util.Module.findSelectedInfo(),
            result = {}, key, count = 0;

        try {
            count = SDE.Util.Module.getCount(module.key.replace(/_[0-9]$/, ''));
        } catch(e) {}

        if (count > 0) return result;

        for (key in this.data) {
            result['board_no'] = key;

            break;
        }

        return result;
    },

    set : function(type, key) {
        this._super(type, key);

        this.moduleKey = key;
    },

    _renderText : function() {
        this.$subjectLength = this.$section.find('[data-type=subject_length]')
                            .blur($.proxy(this._onBlurText, this));
    },

    _onBlurText : function(evt) {
        this._setData('subject_length', this.$subjectLength.val());
    },
    
    _onRadioChanged : function(evt, val) {
        this._super(evt, val);
    },
    
    _renderSection : function() {        
        this._super();

        this._renderImages();
        
        this._renderColorPicker();
        
        this._renderRadio();

        this._renderText();
    },

    _setSection : function(data) {
        this._super(data);

        this.$subjectLength.val(data['subject_length']);
    },

    _showSection : function(data, name) {
        this._super(data, name);

        this.$section.find('.boardName').html('\''+ name +'\'');

        this.$section.find('.listPackage').toggle((this.moduleKey.indexOf('board_listpackage') == 0));
    }
});

SDE.Layer.EditingAttrProductBase = SDE.Layer.EditingAttrPreferenceBase.extend({
    TEMPLATE : '<h3>'+ __('SETTINGS.BY.DISPLAY.ITEM', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</h3>' +
    /*rev.b2.20130830.2@sinseki #SDE-16 리스트모듈의 경우, 속성에 출력갯수 입력 추가*/
    '<input type="hidden" class="attr-count" value="asd"\/>' +
    '<div class="attrArea">' +
        '<div class="list" data-view="list"></div>' +
        '<div class="section" data-view="section">' +
        '</div>' +
    '</div>',

    SECTION_TEMPLATE : '<div class="mBoard">' +
                            '<table border="1" summary="">' +
                                '<caption>'+ __('CLASSIFICATION.BY.PRODUCT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</caption>' +
                                '<colgroup>' +
                                    '<col style="width:25%;">' +
                                    '<col style="width:auto;">' +
                                '</colgroup>' +
                                '<tbody>' +
                                    '<tr>' +
                                        '<th scope="row">'+ __('DISPLAY.ITEMS', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td><span data-type="name"></span></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('DISPLAY.NAME', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td><input data-type="option_name"/></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('SIZE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td>' +
                                            '<select data-type="font_size">' +
                                            '</select>' +
                                        '</td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('BOLD', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td><a href="#none" data-type="font_type" data-font-type="bold" class="icoBold"><span>'+ __('BOLD', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</span></a></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('ITALIC', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td><a href="#none" data-type="font_type" data-font-type="italic" class="icoItalic"><span>'+ __('ITALIC', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</span></a></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('COLOR', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT') +'</th>' +
                                        '<td>' +
                                            '<div class="mColorPicker eColorPicker"><input type="text" data-type="font_color" maxlength="7" readonly="readonly" value="" class="fText" style="width:50px"></div>' +
                                        '</td>' +
                                    '</tr>' +
                                 '</tbody>' +
                             '</table>' +
                         '</div>',

    KEYS : {
        'color_picker' : ['font_color']
    },

    PRIVILEGES : {
        'product_name' : __('PRODUCT.NAME', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'manu_name' : __('MANUFACTURER', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'made_in' : __('ORIGIN', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'product_custom' : __('CONSUMERS', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'product_price' : __('PRICE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'c_dc_price' : __('COUPON.DISCOUNT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'allotment_product' : __('NO.INTEREST.INSTALLMENT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'mileage_value' : __('RESERVES', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'product_code' : __('PRODUCT.CODE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'quantity' : __('QUANTITY', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'prd_price_org' : __('COMMODITY.PRICE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'prd_price_tax' : __('TAX.AMOUNT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'product_buy' : __('SUPPLY.COST', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'prd_brand' : __('BRAND', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'prd_model' : __('MODEL', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'supplier_id' : __('SUPPLIER', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'simple_desc' : __('PRODUCT.BRIEF.DESCRIPTION', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'eng_product_name' : __('ENGLISH.PRODUCT.NAME', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'ma_product_code' : __('OWN.PRODUCT.CODE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'summary_desc' : __('PRODUCT.SUMMARY', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'review_cnt' : __('REVIEWS', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'qna_cnt' : __('CONTACT.US', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'relation_cnt' : __('RELATED.PRODUCT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'qrcode' : __('QR.CODE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'about_price' : __('ABOUT', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        'print_date' : __('DATE.OF.MANUFACTURE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        delivery_title: __('OVERSEAS.SHIPPING', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        delivery: __('SHIPPING.METHOD', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT'),
        delivery_price: __('SHIPPING.FEE', 'EDITOR.LAYER.EDITING.ATTR.PRODUCT')
    },

    REQUIRED_VARIABLES : ['item_title', 'item_content'],

    set : function(type, key) {
        var i;

        this.moduleKey = key;
        this.preferences = SDE.Util.Preference.get(this._getPreferenceName());
    },

    render : function() {
        this._super();

        this._renderCategoryList();

        /*rev.b12.20130829.12@sinseki #SDE-16 리스트모듈의 경우, 속성에 출력갯수 입력 추가*/
        this.attrcount = SDE.editor.getSelection().match(/\$count\s*=\s*(\d+)/i) || [];
        this.$element.filter('.attr-count').val(this.attrcount[1]);

        return this.$element;
    },

    /*rev.b3.20130829.3@sinseki #SDE-16 리스트모듈의 경우, 속성에 출력갯수 입력 추가*/
    save : function() {
        var content = SDE.editor.getSelection();
        var precontent = content;
        if (this.attrcount.length) {
            content = content.replace(this.attrcount[0], "$count = "+(this.$element.filter('.attr-count').val() || 12));
        }
        (precontent != content) && SDE.editor.replaceSelection(content);
        return this._super();
    },

    _getPreferenceName : function(key) {
        // Implement
    },

    _onClickList : function(evt, key) {
        this.currentKey = key;

        this._showSection(this.preferences[key]);
    },

    _processData : function(data) {
        if (this.data[this.currentKey]) {
            data = $.extend({}, data, this.data[this.currentKey]);
        }

        return data;
    },

    _render : function() {
        this._super();

        this.List = new SDE.Component.List(this.$list);

        $(this.List).bind({
            'list-click' : $.proxy(this._onClickList, this)
        });
    },

    _renderCategoryList : function() {
        var data = [], i, key, pref;

        for (i in this.preferences) {
            pref = this.preferences[i],
            key = pref['option_code'];

            if (!key || this._getPreferenceName() != 'product_detaildesign' && key == 'product_name') continue;

            data.push({
                'key' : key,
                'name' : pref['original_title']
            });
        }

        this.List.setData(data);
        this.List.render(true);
    },

    _renderText : function() {
        var name = 'option_name';

        this.$optionName = this.$section.find('[data-type=option_name]')
                            .blur($.proxy(this._onBlurText, this));

        this.$name = this.$section.find('[data-type=name]');
    },

    _renderSelect : function() {
        var i = 8,
            html = '';

        for (; i <= 20; i++) {
            html += '<option value="'+ i +'">'+ i +'px</option>';
        }

        this.$fontSize = this.$section.find('[data-type=font_size]')
                            .html(html)
                            .change($.proxy(this._onChangeFontSize, this));
    },

    _renderButton : function() {
        this.$fontType = this.$section.find('[data-type=font_type]')
                            .click($.proxy(this._onClickFontType, this));
    },

    _renderSection : function() {
        this._super();

        this._renderColorPicker();

        this._renderText();

        this._renderSelect();

        this._renderButton();
    },

    _setSection : function(data) {
        var name, $fontType = $();

        this._super(data);

        this.$optionName.val(data['option_name']);

        this.$fontSize.val(data['font_size']);

        // set font type
        switch (data['font_type']) {
            case 'B':
                $fontType = this.$fontType.filter('[data-font-type=bold]');
                break;

            case 'C':
                $fontType = this.$fontType.filter('[data-font-type=italic]');
                break;

            case 'D':
                $fontType = this.$fontType;
                break;
        }

        $fontType.addClass('selected');
        this.currentFontType = data['font_type'];

        this.$name.html(data['original_title']);
    },

    _onBlurText : function(evt) {
        this._setData('option_name', this.$optionName.val());
    },

    _onChangeFontSize : function(evt) {
        this._setData('font_size', this.$fontSize.val());
    },

    _onClickFontType : function(evt) {
        var $target = $(evt.target),
            fontType = $target.data('font-type'),
            selected = !$target.hasClass('selected'),
            currentValue = this.currentFontType,
            value = 'A';

        $target.toggleClass('selected');

        if (selected) {
            if (currentValue == 'A') {
                value = (fontType == 'bold') ? 'B' : 'C';
            } else {
                value = 'D';
            }
        } else if (currentValue == 'D') {
            value = (fontType == 'bold') ? 'C' : 'B';
        }

        this._setData('font_type', value);
        this.currentFontType = value;
    },

    _setCurrentPref : function(data) {
        this.currentPref = this.currentKey;
    }
});

SDE.Layer.EditingAttrProductDetail = SDE.Layer.EditingAttrProductBase.extend({
    HELP_LINK_KEY : 'product_detail',

    _getPreferenceName : function() {
        return 'product_detaildesign';
    }
});

SDE.Layer.EditingAttrProductList = SDE.Layer.EditingAttrProductBase.extend({
    HELP_LINK_KEY : 'product_normalpackage',

    _getPreferenceName : function(key) {
        return this.moduleKey;
    }
});

SDE.Layer.EditingAttrCategoryBase = SDE.Layer.EditingAttrPreferenceBase.extend({
    TEMPLATE : '<h3>' + __('BY.PRODUCT.CATEGORY', 'EDITOR.JS.LAYER.EDITION.ATTR.CATEGORYBASE') + '</h3>' +
    '<div class="attrArea">' +
        '<div class="list" data-view="list"></div>' +
        '<div class="section" data-view="section">' +
        '</div>' +
    '</div>',

    set : function(type, key) {
        var key, i, names = ['category_image', 'category_image_over', 'banner_image', 'top_image', 'top_image2', 'top_image3'];

        this.moduleKey = key;
        this.preferences = SDE.Util.Preference.get(this._getPreferenceName());

        // 이미지 위치 예외 처리. db에는 파일명만 저장 되어있음
        for (key in this.preferences) {
            for (i in names) {
                var url = this.preferences[key][names[i]];

                if (!url) continue;

                this.preferences[key][names[i]] = '/web/upload/' + url;
            }
        }
    },

    render : function() {
        this._super();

        this.List.render();

        return this.$element;
    },

    _getPreferenceName : function(key) {
        return 'product_category';
    },

    _onClickList : function(evt, key) {
        this.currentKey = key;

        this._showSection(this.preferences[key]);
    },

    _onRadioChanged : function(evt, val) {
        this._super(evt, val);

        this._setRadioDisplay(evt.target.name, val);
    },

    _processData : function(data) {
        if (this.data[this.currentKey]) {
            data = $.extend({}, data, this.data[this.currentKey]);
        }

        return data;
    },

    _render : function() {
        this._super();

        this.List = new SDE.Component.CategoryList(this.$list);

        $(this.List).bind({
            'list-click' : $.proxy(this._onClickList, this)
        });
    },

    _renderSection : function() {
        this._super();

        this._renderImages();

        this._renderRadio();
    },

    _setSection : function(data, name) {
        var radioKeys = this.KEYS['radio'],
            i, key;

        this._super(data);

        // radio button에 따른 display 설정
        for (i in radioKeys) {
            key = radioKeys[i];

            this._setRadioDisplay(key, data[key]);
        }

        var topImage = this.preferences[this.currentPref]['top_image'];
        var topImage2 = this.preferences[this.currentPref]['top_image2'];
        var topImage3 = this.preferences[this.currentPref]['top_image3'];
        if (topImage === null)  topImage = '';
        if (topImage2 === null)  topImage2 = '';
        if (topImage3 === null)  topImage3 = '';
        this._setData('top_image',  topImage);
        this._setData('top_image2', topImage2);
        this._setData('top_image3', topImage3);
    },

    _setCurrentPref : function(data) {
        this.currentPref = this.currentKey;
    }
});

SDE.Layer.EditingAttrCategoryList = SDE.Layer.EditingAttrCategoryBase.extend({
    SECTION_TEMPLATE : '<div class="mBoard">' +
                            '<table border="1" summary="">' +
                                '<caption>'+ __('CLASSIFICATION.BY.PRODUCT', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</caption>' +
                                '<colgroup>' +
                                    '<col style="width:25%;">' +
                                    '<col style="width:auto;">' +
                                '</colgroup>' +

                                '<tbody>' +
                                    '<tr>' +
                                        '<th scope="row">'+ __('MENU.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="use_image" name="use_image" value="T"> '+ __('USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="use_image" name="use_image" value="F"> '+ __('NOT.USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</label>' +
                                            '<div data-radio="use_image" data-radio-value="T">' +
                                                '<div class="gFrame">' +
                                                    '<span class="gWidth type1">'+ __('BASIC', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</span>' +
                                                    '<div class="frameSelect" data-type="category_image">' +

                                                    '</div>' +
                                                '</div>' +

                                                '<div class="gFrame">' +
                                                    '<span class="gWidth type1">'+ __('ROLLOVER', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYLIST') +'</span>' +
                                                    '<div class="frameSelect" data-type="category_image_over">' +

                                                    '</div>' +
                                                '</div>' +
                                            '</div>' +
                                        '</td>' +
                                    '</tr>' +

                                '</tbody>' +
                            '</table>' +
                        '</div>',

    KEYS : {
        'image' : ['category_image', 'category_image_over'],
        'radio' : ['use_image']
    },

    REQUIRED_VARIABLES : ['name_or_img_tag'],

    HELP_LINK_KEY : 'layout_category',
});

SDE.Layer.EditingAttrCategoryHead = SDE.Layer.EditingAttrCategoryBase.extend({
    SECTION_TEMPLATE : '<div class="mBoard">' +
                            '<table border="1" summary="">' +
                                '<caption>'+ __('CLASSIFICATION.BY.PRODUCT', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</caption>' +
                                '<colgroup>' +
                                    '<col style="width:25%;">' +
                                    '<col style="width:auto;">' +
                                '</colgroup>' +

                                '<tbody>' +
                                    '<tr>' +
                                        '<th scope="row">'+ __('TOP.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="use_top_image" name="use_top_image" value="T"> '+ __('USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="use_top_image" name="use_top_image" value="F"> '+ __('NOT.USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</label>' +
                                            '<ul class="gSelectList">' +
                                            '<li>' +
                                                '<div class="gSingle" data-radio="use_top_image" data-radio-value="T">' +
                                                    //'<span class="gWidth type1">기본</span>' +
                                                    '<div class="frameSelect" data-type="top_image">' +
                                                    '</div>' +
                                                '</div>' +
                                            '</li>' +
                                            '<li>' +
                                                '<div class="gSingle" data-radio="use_top_image" data-radio-value="T">' +
                                                    //'<span class="gWidth type1">기본</span>' +
                                                    '<div class="frameSelect" data-type="top_image2">' +
                                                    '</div>' +
                                                '</div>' +
                                            '</li>' +
                                            '<li>' +
                                                '<div class="gSingle" data-radio="use_top_image" data-radio-value="T">' +
                                                    //'<span class="gWidth type1">기본</span>' +
                                                    '<div class="frameSelect" data-type="top_image3">' +
                                                    '</div>' +
                                                '</div>' +
                                            '</li>' +
                                            '</ul>' +
                                            '<ul class="gFormInfo">' +
                                                '<li>'+ __('IMAGE.CAN.ADDED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</li>' +
                                                '<li>'+ __('SET.ITEM.MANAGEMENT.CLASSIFICATION.CLASSIFICATION', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</li>' +
                                            '</ul>' +
                                        '</td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<th scope="row">'+ __('TITLE.IMAGE', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</th>' +
                                        '<td>' +
                                            '<label class="fChk"><input type="radio" data-type="use_b_image" name="use_b_image" value="T"> '+ __('USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</label>' +
                                            '<label class="fChk"><input type="radio" data-type="use_b_image" name="use_b_image" value="F"> '+ __('NOT.USED', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</label>' +

                                            '<div class="gFrame" data-radio="use_b_image" data-radio-value="T">' +
                                                '<span class="gWidth type1">'+ __('BASIC', 'EDITOR.LAYER.EDITING.ATTR.CATEGORYHEAD') +'</span>' +
                                                '<div class="frameSelect" data-type="banner_image">' +
                                                '</div>' +
                                            '</div>' +
                                        '</td>' +
                                    '</tr>' +

                                '</tbody>' +
                            '</table>' +
                        '</div>',

    KEYS : {
        'image' : ['banner_image', 'top_image', 'top_image2', 'top_image3'],
        'radio' : ['use_b_image', 'use_top_image']
    },

    REQUIRED_VARIABLES : ['top_image_tag', 'title_text_or_image'],

    HELP_LINK_KEY : 'product_menupackage',

    getParams : function() {
        return {
            'cate_no': this.currentPref
        };

        //var result = {}, key;
        //
        //for (key in this.data) {
        //    result['cate_no'] = key;
        //
        //  break;
        //}

        //return result;
    },
    renderAfter : function() {
        //var self = this;

        //$('.gSelectList li .frame').dblclick(function() {
        //   var dataType = $(this).parent().parent().attr('data-type');
        //   self._setData(dataType, '');
        //   $(this).find('img').attr('src', '//img.echosting.cafe24.com/smartAdmin/img/editor/@img_product.jpg');
        //});

        //$('.gSelectList').sortable({
        //    stop: function(ev, ui) {
        //        $(ui.item).parent().find('li').each(function(i) {
        //            var key = i + 1;
        //            var name = 'top_image';
        //            if (key > 1)
        //                name += key;

        //            var src = self.data[self.currentPref][name];
        //            self._setData(name, src);

        //            $(this).find('.frameSelect').attr('data-type', name);
        //        });
        //    }
        //});
    }
});

