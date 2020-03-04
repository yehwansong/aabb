/**
 * 간단한 display 관련 로직 처리
 */

$(function(){
    /**
     * Common Variables
     */
    var $body = $(document.body);
    var searchLayer, listTreeLayer, dirlistTreeLayer;


    /*
     * 자주쓰는 화면, 전체화면 보기 Swipe
     */
    var $tab = $('#aside .tab > li'),
        $favorite = $('#aside .snbFavorite'),
        $all = $('#aside .snbAll'),
        $currentExplorer = $favorite;


    $tab.click(function(evt) {
        var $this = $(this),
            $explorer = $($this.data('selector'));

        $currentExplorer.hide();
        $explorer.show();

        $tab.removeClass('selected');
        $this.addClass('selected');


        $currentExplorer = $explorer;
    });


    /**
     * 왼쪽 메뉴 펼침
     */
    /*rev.b23.20130829.1@sinseki #SDE-4 레이아웃 가로 스크롤 (left: 200px-400px) 기능 구현*/
    $(".controlBar button").click(function(evt) {
        var className = 'eHidden';

        $body.toggleClass(className);

        $(".controlBar button").html(__('MENU', 'EDITOR.RESOURCE.JS.UI') + ' ' + ($body.hasClass(className) ? __('SPREAD', 'EDITOR.RESOURCE.JS.UI') : __('HIDING', 'EDITOR.RESOURCE.JS.UI')));

        $("#aside").css({width:""});
        $(".controlBar").css({left:""});
        $("#container").css({marginLeft:""});

    });
    $(".controlBar").prepend(
        $("<div>").addClass("dummy").css({position:"absolute",left:0,top:0,width:"200%",height:"100%"})
    );
    $(".controlBar .dummy").draggable({
        containment : 'body',
        iframeFix : true,
        cursor : 'e-resize',
        drag : function (event,ui) {
            var $l = ui.offset.left;
            $l = Math.min(Math.max($l,214),414);
            $("#aside").css({width:$l+"px"});
            $(".controlBar").css({left:$l+"px"});
            $("#container").css({marginLeft:($l+6)+"px"});
            $(".controlBar .dummy").css({left:0});
        },
        stop : function (event,ui) {
            $(".controlBar .dummy").css({left:0});
        }
    });

    /**
     * Input PlaceHolder
     */
    $(".ePlaceholder").click(function() {
        var $this = $(this);

        $this.find('span').hide();

        $this.find('input').focus();
    }).find('input').blur(function(){
        var $this = $(this);

        $this.siblings('span')[($.trim($this.val()) === '') ? 'show' : 'hide']();
    });


    /**
     * 파일명 검색
     */
    $('#fileSearch').submit(function() {
        if (!searchLayer) searchLayer = new SDE.Layer.Search();

        searchLayer.search($(this).find('input').val());
    });


    /**
     * 쇼핑몰 화면 추가
     */
    $('#fileAdd').click(function() {
        if (!listTreeLayer) {
            listTreeLayer = new SDE.Layer.ListTree();
        } else {
            listTreeLayer.updateTree();
        }

        listTreeLayer.add();
    });

    /*rev.b12.20130830.1@sinseki #SDE-5 쇼핑몰 화면 추가 영역을 2등분 하여, 앞에 디렉토리 추가버튼과 기능 구현*/
    /**
     * 쇼핑몰 디렉토리 추가
     */
    $('#dirAdd').click(function() {
        if (!dirlistTreeLayer) {
            dirlistTreeLayer = new SDE.Layer.DirListTree();
        } else {
            dirlistTreeLayer.updateTree();
        }

        dirlistTreeLayer.add();
    });


    /**
     * 구형 브라우저 안내 팝업 Close
     */
    $('.ie8 .close').click(function() {
        $(this).parent().remove();
    });
});
$(function() {
    if (!$.browser.webkit) return;

    SDE.EasterEgg = {

        TEMPLATE : '<div style="font-family:\'Gabriela\',serif;color:#FFF;font-size:25px;position:absolute;right:30px;bottom:30px;z-index:110; text-shadow: 0 0 20px #fefcc9, 10px -10px 30px #feec85, -20px -20px 40px #ffae34, 20px -40px 50px #ec760c, -20px -60px 60px #cd4606, 0 -80px 70px #973716, 10px -90px 80px #451b0e;">' +
                        '<link href="http://fonts.googleapis.com/css?family=Gabriela" rel="stylesheet" type="text/css">' +

                        '<script src="https://raw.github.com/paullewis/Fireworks/master/js/requestanimframe.js"></script>' +
                        '<script src="https://raw.github.com/paullewis/Fireworks/master/js/fireworks.js"></script>' +

                        '<p style="font-size:20px;">' +
                            '2013.03.18' +
                        '</p>' +

                        '<p style="line-height:1.6;">' +
                            'Planner : In-A Jung<br/>' +
                            'Publisher : Zina Kim, Young-Ae So<br/>' +
                            'Developer : Jae-Kwang Lee<br/>' +
                            'Manager : Sang-Doo Jung' +
                        '</p>' +

                        '<aside id="library" style="display:none">' +
                            '<img src="https://raw.github.com/paullewis/Fireworks/master/images/nightsky.png" id="nightsky" />' +
                            '<img src="https://raw.github.com/paullewis/Fireworks/master/images/big-glow.png" id="big-glow" />' +
                            '<img src="https://raw.github.com/paullewis/Fireworks/master/images/small-glow.png" id="small-glow" />' +
                        '</aside>' +
                        
                   '</div>',
        

        _init : function() {
            var _this = this;

            this.$element = $(this.TEMPLATE).appendTo(document.body);

            this.intervalId = setInterval(function() {
                if (typeof Fireworks == 'undefined') return;

                _this._setCanvas();
            }, 100);
        },

        _setCanvas : function() {
            Fireworks.initialize();

             $('canvas').css({
               'position' : 'absolute',
               'left' : '0',
               'top' : '0',
               'opacity' : '0.6',
               'z-index' : '100',
               'background' : '#000'
            });

            setInterval(function() {
                Fireworks.createParticle();
            }, 700);

            clearInterval(this.intervalId);
        },

        show : function() {
            this._init();
        }
    };

    // http://en.wikipedia.org/wiki/Konami_code
    $(window).konami({  
        cheat: function() {
            SDE.EasterEgg.show();
        }
    });
});

/* 도움말 */
if (typeof jQuery !== 'undefined') {
    window.sendRequest = function(callback,data,method,url,async,sload,user,password) {
        return jQuery.ajax({
            url: url,
            async: async,
            type: method,
            data: data,
            success: function(data, textStatus, jqXHR) {
                callback(jqXHR);
            },
            cache: sload ? false : true,
            username: user,
            password: password
        });
    }
}

var HelpCode = {
    getPosition: function (e) {
        var mouseX = e.pageX ? e.pageX : document.documentElement.scrollLeft + event.clientX;
        var mouseY = e.pageX ? e.pageX : document.documentElement.scrollLeft + event.clientY;
        return {x: mouseX, y: mouseY};
    },

    getCookie: function (name) {
        var nameOfCookie = name + '=';
        var x = 0;

        while (x <= document.cookie.length) {
            var y = x + nameOfCookie.length;
            if (document.cookie.substring(x, y) == nameOfCookie) {
                if ((endOfCookie=document.cookie.indexOf(";", y)) == -1) {
                    endOfCookie = document.cookie.length;
                }
                return unescape(document.cookie.substring(y, endOfCookie));
            }
            x = document.cookie.indexOf(" ", x) + 1;

            if (x == 0) break;
        }
        return "";
    },

    setCookie: function (cookieName, cookieValue, expireDate) {
        var today = new Date();
        today.setDate( today.getDate() + parseInt( expireDate ) );
        document.cookie = cookieName + "=" + escape( cookieValue ) + "; path=/; expires=" + today.toGMTString() + ";";
    },

    HELP_openPopup: function (url, width, height) {
        var winname = 'adminHelp';
        var option = 'toolbar=no, location=no, scrollbars=yes, status=yes, resizable=no, width='+width+', height='+height+', top=100, left=100';
        window.open(url, winname, option);
    },

    //도움말 라이브러리에 코드를 요청
    getHelpCode: function (helpCode, helpType) {
        var sParam;

        sParam = '&helpCode=' + helpCode;

        if (helpType) {
            sParam += '&helpType=' + helpType;
        }

        if ( document.getElementById(helpCode) == null ) {
            document.write("<span id='"+helpCode+"'></span>");
        }

        sendRequest(HelpCode.settingHelpCode, sParam, 'GET', '/common/settingHelpCode.php', true, false);
    },

    //ajax로 가져온 값을 해당 영역에 출력시킴
    settingHelpCode: function (oj) {
        if ( !oj || !oj.responseXML ) return;

        var xmlDoc = oj.responseXML;
        var areaId = xmlDoc.getElementsByTagName('helpcode')[0].firstChild.nodeValue;

        try {
            document.getElementById(areaId).innerHTML =  xmlDoc.getElementsByTagName('content')[0].firstChild.nodeValue;
        } catch (e) {}
    },

    checkInnerHelp:function (code) {
        if (HelpCode.getCookie(code) == 'close') {
            document.getElementById(code+'open').style.display='none';
            document.getElementById(code+'close').style.display='block';
        }
    },

    toggleInnerHelp:function (code, mode) {
        var value1, value2;

        if (mode == 'close') {
            value1 = 'none';
            value2 = 'block';
        } else {
            value1 = 'block';
            value2 = 'none';
        }

        document.getElementById(code+'open').style.display = value1;
        document.getElementById(code+'close').style.display = value2;

        HelpCode.setCookie(code, mode, 30);
    },

    HELP_openIframe: function (url, width, height) {

        var Obj = document.getElementById('helpIframe_Layer');
        var XY = getPosition(event);

        if (Obj) {

            var oLayer = Obj;
            var oIframe = document.getElementById('helpIframe');

        } else {
            var create_iframe = true;

            //레이어 생성
            var oLayer = document.createElement('div');
            oLayer.setAttribute('id', 'helpIframe_Layer');
            oLayer.style.position = 'absolute';
            oLayer.style.zindex = '3000';
            oLayer.style.width = width+'px';
            oLayer.style.border = '1px solid #ccc';
            oLayer.style.padding = '3px';
            oLayer.style.backgroundColor = '#fff';
            oLayer.style.textAlign = 'right';

            var oLayerClosebtn = document.createElement('img');
            oLayerClosebtn.setAttribute('src', '//img.cafe24.com/images/ec_admin/addservice/info/btn_x_001.gif');
            oLayerClosebtn.setAttribute('alt', __('CLOSE', 'ADMIN.JS.HELPCODE'));
            oLayerClosebtn.onclick = function() { document.getElementById('helpIframe_Layer').style.display = 'none'; }
            oLayerClosebtn.style.cursor = "pointer";

            //iframe 생성
            var oIframe = document.createElement('iframe');
            oIframe.setAttribute('id', 'helpIframe');
            oIframe.setAttribute('frameBorder', '0');
            oIframe.setAttribute('border', '0');
            oIframe.setAttribute('scrolling', 'auto');
            oIframe.style.width = width+'px';
        }

        oLayer.style.height = height+'px';
        oIframe.style.height = height+'px';
        oIframe.src = url;

        oLayer.style.left = document.body.scrollLeft + XY.x;
        oLayer.style.top = document.body.scrollTop + XY.y;
        oLayer.style.display = 'block';

        if (create_iframe == true) {
            document.getElementsByTagName('body')[0].appendChild(oLayer);
            oLayer.appendChild(oLayerClosebtn);
            oLayer.appendChild(oIframe);
        }
    }
};

// 도움말
var serviceGuide = {
    init: function () {
        serviceGuide.setManual();
        serviceGuide.setToolTip();
    },
    // 툴팁 확인
    setToolTip: function () {
        if (SHOP.isMode() === true) {
            var oToolTip = $('div.cTip.eSmartMode');
        } else {
            var oToolTip = $('div.cTip').not('.eSmartMode');
        }

        if (oToolTip.length === 0) return false;
        if (!oToolTip.hasOwnProperty(0)) return false;

        // tpl 에 정의된 툴팁 코드들을 가져옵니다.
        var getToolTipCodeOnTemplate = function () {
            var aToolTipCode = [];
            for (var i = 0; i < oToolTip.length; i++) {
                if (typeof oToolTip[i] !== 'object') continue;

                var sTipCode = oToolTip[i].getAttribute('code').replace(/\.+[0-9]+/, '');
                if (sTipCode !== '' && aToolTipCode.indexOf(sTipCode) === -1) {
                    aToolTipCode.push(sTipCode);
                }
            }
            return aToolTipCode;
        };

        var aToolTipCode = getToolTipCodeOnTemplate();
        var sLangCode = oToolTip[0].getAttribute('lang') || EC_GLOBAL_INFO.getLanguageCode();

        if (aToolTipCode.length < 1) return false;

        for (var i = 0; i < aToolTipCode.length; i++) {
            var sTipCode = aToolTipCode[i];
            var sParam = '&sTipCode=' + sTipCode + '&sLangCode=' + sLangCode;
            sendRequest(serviceGuide.setToolTipIcons, sParam, 'GET', '/exec/admin/guide/HelptipIcons', true, false);
        }
    },
    // 툴팁 아이콘 생성
    setToolTipIcons: function (th) {
        var sResponse = th.responseText || th.response;
        var aJson = JSON.parse(sResponse);

        if (aJson['code'] !== 200) return false;
        if (aJson['data']['icons'] === '') return false;

        var sTipCode = aJson['data']['tip_code'];
        var oJsonIcons = JSON.parse(aJson['data']['icons']);
        var oPrintToolTip = document.querySelectorAll('div.cTip[code^="' + sTipCode + '"]');
        [].forEach.call(oPrintToolTip, function (cTip) {
            var sTipCode = cTip.getAttribute('code');
            var sIconsHtml = oJsonIcons[sTipCode];
            if (sIconsHtml) {
                cTip.innerHTML = sIconsHtml;
                cTip.querySelector('button').onclick = function () {
                    serviceGuide.getToolTipContents(cTip, sTipCode);
                };

                // 앞엘리먼트에 glabel 클래스가 있는경우 호출div에 glabel을 추가해준다
                if (cTip.previousElementSibling !== null
                    && cTip.previousElementSibling.className.split('gLabel').length === 2
                ) {
                    cTip.firstChild.className = cTip.firstChild.className + ' gLabel';
                }
            }
        });
    },
    // 툴팁 컨텐츠 가져오기
    getToolTipContents: function (oTip, sTipCode) {
        var sLangCode = oTip.getAttribute('lang') || EC_GLOBAL_INFO.getLanguageCode();
        var sParam = '&sTipCode=' + sTipCode + '&sLangCode=' + sLangCode;
        var oTipContents = oTip.querySelector('.content');

        if (oTipContents.innerHTML !== '') return;

        // 툴팁 컨텐츠 넣기
        var setToolTipContents = function (th) {
            var sResponse = th.responseText || th.response;
            var aJson = JSON.parse(sResponse);

            if (aJson['code'] !== 200) return false;
            if (aJson['data'] === '') return false;

            oTipContents.innerHTML = aJson['data'];
            oTipContents.innerHTML = oTipContents.innerHTML.split('_blank').join('blankWindow');
        };

        return sendRequest(setToolTipContents, sParam, 'GET', '/exec/admin/guide/HelptipContents', true, false);
    },
    // 매뉴얼 출력
    setManual: function () {
        if (SHOP.isMode() === true) {
            var oManual = document.querySelectorAll('span.cManual.eSmartMode');
        } else {
            var oManual = document.querySelectorAll('span.cManual:not(.eSmartMode)');
        }
        if (oManual.length === 0) return false;

        for(var sKey in oManual) {
            if (typeof oManual[sKey] !== 'object') continue;
            if (!oManual.hasOwnProperty(sKey)) continue;

            var sManualCode = oManual[sKey].getAttribute('code');
            var sLangCode = oManual[sKey].getAttribute('lang') || EC_GLOBAL_INFO.getLanguageCode();
            var sDataSupply = oManual[sKey].getAttribute('data-supply');

            var sManualUrl = '';
            if (SHOP.isMode() === true) {
                sManualUrl = '//serviceguide.cafe24.com/IN/' + sLangCode + '/' + sManualCode + '.html';
            }
            else {
                if (sDataSupply !== null && sDataSupply !== '') {
                    sManualUrl = '//serviceguide.cafe24.com/supply/'+ sLangCode +'/'+ sDataSupply +'.html';
                }

                if (sManualCode !== null && sManualCode !== '') {
                    if (sLangCode === 'ko_KR') {
                        sManualUrl = '//serviceguide.cafe24.com/ko_KR/'+ sManualCode +'.html';
                    }
                    else {
                        sManualUrl = '//serviceguide.cafe24shop.com/'+ sLangCode +'/'+ sManualCode +'.html';
                    }
                }
            }

            if (sManualUrl !== '') {
                var sLink = '<a href="'+sManualUrl+'" target="_blank" class="btnManual" title="'+__('NEW.WINDOW.OPENED', 'ADMIN.JS.HELPCODE')+'">'+__('MANUAL', 'ADMIN.JS.HELPCODE')+'</a>';
                oManual[sKey].innerHTML = sLink;
            }
        }
    }
};

if (typeof jQuery !== 'undefined') {
    $(document).ready(function () {
        serviceGuide.init();
    });
} else {
    if (window.addEventListener) {
        window.addEventListener('load', serviceGuide.init, false);
    } else if (window.attachEvent) {
        window.attachEvent('onload', serviceGuide.init);
    } else if (document.getElementById) {
        window.onload = serviceGuide.init;
    }
}

if (!Function.prototype.bind) {
    Function.prototype.bind = function () {
        var _m = this, _a = [].slice.apply(arguments), _o = _a.shift();
        return function () {
            return _m.apply(_o, _a.concat([].slice.apply(arguments)));
        }
    };
}
SFUpload = {
    $prop: {
        ready: null,
        dialogopen: null,
        queue: null,
        queueerror: null,
        dialogclose: null,
        uploadstart: null,
        uploadprogress: null,
        uploaderror: null,
        uploadend: null,
        uploaded: null
    },
    init: function ($phProp) {
        if (!$phProp || !$phProp.node || !$phProp.swf || !$phProp.url) {
            return false;
        }
        //if (!$phProp.buttonsize && !$phProp.css) {
        //    var $e = document.getElementById($phProp.node);
        //    $phProp.buttonsize = [$e.clientWidth||$e.parentNode.clientWidth,$e.clientHeight||$e.parentNode.clientHeight].join(",");
        //}
        this.$engine = new SWFUpload({
            flash_url: $phProp.swf,
            file_post_name : "file",
            file_types : "*.jpg;*.gif;*.png",
            file_types_description: "Images",
            file_size_limit : "20 MB",
            file_upload_limit : 0,
            file_queue_limit : 0,
            button_placeholder_id: $phProp.node,
            //button_image_url: "http://seki.kr/sin6/root.test/imocon/%E3%85%8D.%E3%85%8D.png",
            //button_width : "1",
            //button_height : "1",
            button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
            file_dialog_start_handler: this._ondialogopen.bind(this),
            file_queued_handler: this._onqueue.bind(this),
            file_queue_error_handler: this._onqueueerror.bind(this),
            file_dialog_complete_handler: this._ondialogclose.bind(this),
            upload_start_handler: this._onuploadstart.bind(this),
            upload_progress_handler: this._onuploadprogress.bind(this),
            upload_error_handler: this._onuploaderror.bind(this),
            upload_success_handler: this._onuploadend.bind(this),
            upload_complete_handler: this._onuploaded.bind(this),
            swfupload_loaded_handler: this._oninit.bind(this)
        });
        this.$userProp = $phProp;
        return this;
    },
    prop: function ($phProp) {
        $phProp = $phProp || {};
        for (var $k in $phProp) {
            this.$prop[$k] = $phProp[$k];
        }
        $phProp.url && this.$engine.setUploadURL($phProp.url);
        $phProp.filepostname && this.$engine.setFilePostName($phProp.filepostname);
        for (var $k in $phProp.params||{}) {
            this.$engine.addPostParam($k, encodeURIComponent($phProp.params[$k]));
        }
        $phProp.maxupload && this.$engine.setFileUploadLimit($phProp.maxupload);
        $phProp.maxqueue && this.$engine.setFileUploadLimit($phProp.maxqueue);
        $phProp.filefilter && this.$engine.setFileTypes.apply(this.$engine, $phProp.filefilter.replace(/\s*,\s*/,",").split(","));
        $phProp.maxfilesize && this.$engine.setFileSizeLimit($phProp.maxfilesize);
        $phProp.buttonsize && this.$engine.setButtonDimensions.apply(this.$engine, $phProp.buttonsize.split(","));
        $phProp.buttonurl && this.$engine.setButtonImageURL($phProp.buttonurl);
        for (var $k in $phProp.css||{}) {
            this.$engine.movieElement.style[$k] = $phProp.css[$k];
        }
    },
    upload: function ($phProp) {
        this.prop($phProp);
        this.$engine.getStats().files_queued > 0 && this.$engine.startUpload();
    },
    cancel: function ($psId) {
        this.$engine.cancelUpload($psId);
    },
    stop: function () {
        this.$engine.stopUpload();
    },
    stats: function () {
        return this.$engine.getStats();
    },
    _oninit: function () {
        this.prop(this.$userProp);
        this.$prop.ready && this.$prop.ready.apply(this, arguments);
    },
    _ondialogopen: function ()
    {
        this.$prop.dialogopen && this.$prop.dialogopen.apply(this, arguments);
    },
    _onqueue: function($poFile)
    {
        this.$prop.queue && this.$prop.queue.apply(this, arguments);
    },
    _onqueueerror: function($poFile, $psErrorCode, $psMessage)
    {
        this.$prop.queueerror && this.$prop.queueerror.apply(this, arguments);
    },
    _ondialogclose: function($piFilesSelected, $piFilesQueued, $piFilesInQueue)
    {
        this.$prop.dialogclose && this.$prop.dialogclose.apply(this, arguments);
    },
    _onuploadstart: function($poFile)
    {
        this.$prop.uploadstart && this.$prop.uploadstart.apply(this, arguments);
    },
    _onuploadprogress: function($poFile, $piBytesLoaded, $piBytesTotal)
    {
        this.$prop.uploadprogress && this.$prop.uploadprogress.apply(this, arguments);
    },
    _onuploaderror: function($poFile, $psErrorCode, $psMessage)
    {
        this.$prop.uploaderror && this.$prop.uploaderror.apply(this, arguments);
    },
    _onuploadend: function($poFile, $psResponseText, $pbResponseReceived)
    {
        this.$prop.uploadend && this.$prop.uploadend.apply(this, arguments);
    },
    _onuploaded: function($poFile)
    {
        this.$prop.uploaded && this.$prop.uploaded.apply(this, arguments);
    }
};

/**
 * SWFUpload: http://www.swfupload.org, http://swfupload.googlecode.com
 *
 * mmSWFUpload 1.0: Flash upload dialog - http://profandesign.se/swfupload/,  http://www.vinterwebb.se/
 *
 * SWFUpload is (c) 2006-2007 Lars Huring, Olov Nilz? and Mammon Media and is released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * SWFUpload 2 is (c) 2007-2008 Jake Roberts and is released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 */


/* ******************* */
/* Constructor & Init  */
/* ******************* */
var SWFUpload;

if (SWFUpload == undefined) {
    SWFUpload = function (settings) {
        this.initSWFUpload(settings);
    };
}

SWFUpload.prototype.initSWFUpload = function (settings) {
    try {
        this.customSettings = {};    // A container where developers can place their own settings associated with this instance.
        this.settings = settings;
        this.eventQueue = [];
        this.movieName = "SWFUpload_" + SWFUpload.movieCount++;
        this.movieElement = null;


        // Setup global control tracking
        SWFUpload.instances[this.movieName] = this;

        // Load the settings.  Load the Flash movie.
        this.initSettings();
        this.loadFlash();
        this.displayDebugInfo();
    } catch (ex) {
        delete SWFUpload.instances[this.movieName];
        throw ex;
    }
};

/* *************** */
/* Static Members  */
/* *************** */
SWFUpload.instances = {};
SWFUpload.movieCount = 0;
SWFUpload.version = "2.2.0 2009-03-25";
SWFUpload.QUEUE_ERROR = {
    QUEUE_LIMIT_EXCEEDED              : -100,
    FILE_EXCEEDS_SIZE_LIMIT          : -110,
    ZERO_BYTE_FILE                      : -120,
    INVALID_FILETYPE                  : -130
};
SWFUpload.UPLOAD_ERROR = {
    HTTP_ERROR                          : -200,
    MISSING_UPLOAD_URL                  : -210,
    IO_ERROR                          : -220,
    SECURITY_ERROR                      : -230,
    UPLOAD_LIMIT_EXCEEDED              : -240,
    UPLOAD_FAILED                      : -250,
    SPECIFIED_FILE_ID_NOT_FOUND        : -260,
    FILE_VALIDATION_FAILED              : -270,
    FILE_CANCELLED                      : -280,
    UPLOAD_STOPPED                    : -290
};
SWFUpload.FILE_STATUS = {
    QUEUED         : -1,
    IN_PROGRESS     : -2,
    ERROR         : -3,
    COMPLETE     : -4,
    CANCELLED     : -5
};
SWFUpload.BUTTON_ACTION = {
    SELECT_FILE  : -100,
    SELECT_FILES : -110,
    START_UPLOAD : -120
};
SWFUpload.CURSOR = {
    ARROW : -1,
    HAND : -2
};
SWFUpload.WINDOW_MODE = {
    WINDOW : "window",
    TRANSPARENT : "transparent",
    OPAQUE : "opaque"
};

// Private: takes a URL, determines if it is relative and converts to an absolute URL
// using the current site. Only processes the URL if it can, otherwise returns the URL untouched
SWFUpload.completeURL = function(url) {
    if (typeof(url) !== "string" || url.match(/^https?:\/\//i) || url.match(/^\//)) {
        return url;
    }

    var currentURL = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ":" + window.location.port : "");

    var indexSlash = window.location.pathname.lastIndexOf("/");
    if (indexSlash <= 0) {
        path = "/";
    } else {
        path = window.location.pathname.substr(0, indexSlash) + "/";
    }

    return /*currentURL +*/ path + url;

};


/* ******************** */
/* Instance Members  */
/* ******************** */

// Private: initSettings ensures that all the
// settings are set, getting a default value if one was not assigned.
SWFUpload.prototype.initSettings = function () {
    this.ensureDefault = function (settingName, defaultValue) {
        this.settings[settingName] = (this.settings[settingName] == undefined) ? defaultValue : this.settings[settingName];
    };

    // Upload backend settings
    this.ensureDefault("upload_url", "");
    this.ensureDefault("preserve_relative_urls", false);
    this.ensureDefault("file_post_name", "Filedata");
    this.ensureDefault("post_params", {});
    this.ensureDefault("use_query_string", false);
    this.ensureDefault("requeue_on_error", false);
    this.ensureDefault("http_success", []);
    this.ensureDefault("assume_success_timeout", 0);

    // File Settings
    this.ensureDefault("file_types", "*.*");
    this.ensureDefault("file_types_description", "All Files");
    this.ensureDefault("file_size_limit", 0);    // Default zero means "unlimited"
    this.ensureDefault("file_upload_limit", 0);
    this.ensureDefault("file_queue_limit", 0);

    // Flash Settings
    this.ensureDefault("flash_url", "swfupload.swf");
    this.ensureDefault("prevent_swf_caching", true);

    // Button Settings
    this.ensureDefault("button_image_url", "");
    this.ensureDefault("button_width", 1);
    this.ensureDefault("button_height", 1);
    this.ensureDefault("button_text", "");
    this.ensureDefault("button_text_style", "color: #000000; font-size: 16pt;");
    this.ensureDefault("button_text_top_padding", 0);
    this.ensureDefault("button_text_left_padding", 0);
    this.ensureDefault("button_action", SWFUpload.BUTTON_ACTION.SELECT_FILES);
    this.ensureDefault("button_disabled", false);
    this.ensureDefault("button_placeholder_id", "");
    this.ensureDefault("button_placeholder", null);
    this.ensureDefault("button_cursor", SWFUpload.CURSOR.ARROW);
    this.ensureDefault("button_window_mode", SWFUpload.WINDOW_MODE.WINDOW);

    // Debug Settings
    this.ensureDefault("debug", false);
    this.settings.debug_enabled = this.settings.debug;    // Here to maintain v2 API

    // Event Handlers
    this.settings.return_upload_start_handler = this.returnUploadStart;
    this.ensureDefault("swfupload_loaded_handler", null);
    this.ensureDefault("file_dialog_start_handler", null);
    this.ensureDefault("file_queued_handler", null);
    this.ensureDefault("file_queue_error_handler", null);
    this.ensureDefault("file_dialog_complete_handler", null);

    this.ensureDefault("upload_start_handler", null);
    this.ensureDefault("upload_progress_handler", null);
    this.ensureDefault("upload_error_handler", null);
    this.ensureDefault("upload_success_handler", null);
    this.ensureDefault("upload_complete_handler", null);

    this.ensureDefault("debug_handler", this.debugMessage);

    this.ensureDefault("custom_settings", {});

    // Other settings
    this.customSettings = this.settings.custom_settings;

    // Update the flash url if needed
    if (!!this.settings.prevent_swf_caching) {
        this.settings.flash_url = this.settings.flash_url + (this.settings.flash_url.indexOf("?") < 0 ? "?" : "&") + "preventswfcaching=" + new Date().getTime();
    }

    if (!this.settings.preserve_relative_urls) {
        //this.settings.flash_url = SWFUpload.completeURL(this.settings.flash_url);    // Don't need to do this one since flash doesn't look at it
        this.settings.upload_url = SWFUpload.completeURL(this.settings.upload_url);
        this.settings.button_image_url = SWFUpload.completeURL(this.settings.button_image_url);
    }

    delete this.ensureDefault;
};

// Private: loadFlash replaces the button_placeholder element with the flash movie.
SWFUpload.prototype.loadFlash = function () {
    var targetElement, tempParent;

    // Make sure an element with the ID we are going to use doesn't already exist
    if (document.getElementById(this.movieName) !== null) {
        throw "ID " + this.movieName + " is already in use. The Flash Object could not be added";
    }

    // Get the element where we will be placing the flash movie
    targetElement = document.getElementById(this.settings.button_placeholder_id) || this.settings.button_placeholder;

    if (targetElement == undefined) {
        throw "Could not find the placeholder element: " + this.settings.button_placeholder_id;
    }

    // Append the container and load the flash
    tempParent = document.createElement("div");
    tempParent.innerHTML = this.getFlashHTML();    // Using innerHTML is non-standard but the only sensible way to dynamically add Flash in IE (and maybe other browsers)
    targetElement.parentNode.replaceChild(tempParent.firstChild, targetElement);

    // Fix IE Flash/Form bug
    if (window[this.movieName] == undefined) {
        window[this.movieName] = this.getMovieElement();
    }

};

// Private: getFlashHTML generates the object tag needed to embed the flash in to the document
SWFUpload.prototype.getFlashHTML = function () {
    // Flash Satay object syntax: http://www.alistapart.com/articles/flashsatay
    return ['<object id="', this.movieName, '" type="application/x-shockwave-flash" data="', this.settings.flash_url, '" width="', this.settings.button_width, '" height="', this.settings.button_height, '" class="swfupload">',
                '<param name="wmode" value="', this.settings.button_window_mode, '" />',
                '<param name="movie" value="', this.settings.flash_url, '" />',
                '<param name="quality" value="high" />',
                '<param name="menu" value="false" />',
                '<param name="allowScriptAccess" value="always" />',
                '<param name="flashvars" value="' + this.getFlashVars() + '" />',
                '</object>'].join("");
};

// Private: getFlashVars builds the parameter string that will be passed
// to flash in the flashvars param.
SWFUpload.prototype.getFlashVars = function () {
    // Build a string from the post param object
    var paramString = this.buildParamString();
    var httpSuccessString = this.settings.http_success.join(",");

    // Build the parameter string
    return ["movieName=", encodeURIComponent(this.movieName),
            "&amp;uploadURL=", encodeURIComponent(this.settings.upload_url),
            "&amp;useQueryString=", encodeURIComponent(this.settings.use_query_string),
            "&amp;requeueOnError=", encodeURIComponent(this.settings.requeue_on_error),
            "&amp;httpSuccess=", encodeURIComponent(httpSuccessString),
            "&amp;assumeSuccessTimeout=", encodeURIComponent(this.settings.assume_success_timeout),
            "&amp;params=", encodeURIComponent(paramString),
            "&amp;filePostName=", encodeURIComponent(this.settings.file_post_name),
            "&amp;fileTypes=", encodeURIComponent(this.settings.file_types),
            "&amp;fileTypesDescription=", encodeURIComponent(this.settings.file_types_description),
            "&amp;fileSizeLimit=", encodeURIComponent(this.settings.file_size_limit),
            "&amp;fileUploadLimit=", encodeURIComponent(this.settings.file_upload_limit),
            "&amp;fileQueueLimit=", encodeURIComponent(this.settings.file_queue_limit),
            "&amp;debugEnabled=", encodeURIComponent(this.settings.debug_enabled),
            "&amp;buttonImageURL=", encodeURIComponent(this.settings.button_image_url),
            "&amp;buttonWidth=", encodeURIComponent(this.settings.button_width),
            "&amp;buttonHeight=", encodeURIComponent(this.settings.button_height),
            "&amp;buttonText=", encodeURIComponent(this.settings.button_text),
            "&amp;buttonTextTopPadding=", encodeURIComponent(this.settings.button_text_top_padding),
            "&amp;buttonTextLeftPadding=", encodeURIComponent(this.settings.button_text_left_padding),
            "&amp;buttonTextStyle=", encodeURIComponent(this.settings.button_text_style),
            "&amp;buttonAction=", encodeURIComponent(this.settings.button_action),
            "&amp;buttonDisabled=", encodeURIComponent(this.settings.button_disabled),
            "&amp;buttonCursor=", encodeURIComponent(this.settings.button_cursor)
        ].join("");
};

// Public: getMovieElement retrieves the DOM reference to the Flash element added by SWFUpload
// The element is cached after the first lookup
SWFUpload.prototype.getMovieElement = function () {
    if (this.movieElement == undefined) {
        this.movieElement = document.getElementById(this.movieName);
    }

    if (this.movieElement === null) {
        throw "Could not find Flash element";
    }

    return this.movieElement;
};

// Private: buildParamString takes the name/value pairs in the post_params setting object
// and joins them up in to a string formatted "name=value&amp;name=value"
SWFUpload.prototype.buildParamString = function () {
    var postParams = this.settings.post_params;
    var paramStringPairs = [];

    if (typeof(postParams) === "object") {
        for (var name in postParams) {
            if (postParams.hasOwnProperty(name)) {
                paramStringPairs.push(encodeURIComponent(name.toString()) + "=" + encodeURIComponent(postParams[name].toString()));
            }
        }
    }

    return paramStringPairs.join("&amp;");
};

// Public: Used to remove a SWFUpload instance from the page. This method strives to remove
// all references to the SWF, and other objects so memory is properly freed.
// Returns true if everything was destroyed. Returns a false if a failure occurs leaving SWFUpload in an inconsistant state.
// Credits: Major improvements provided by steffen
SWFUpload.prototype.destroy = function () {
    try {
        // Make sure Flash is done before we try to remove it
        this.cancelUpload(null, false);


        // Remove the SWFUpload DOM nodes
        var movieElement = null;
        movieElement = this.getMovieElement();

        if (movieElement && typeof(movieElement.CallFunction) === "unknown") { // We only want to do this in IE
            // Loop through all the movie's properties and remove all function references (DOM/JS IE 6/7 memory leak workaround)
            for (var i in movieElement) {
                try {
                    if (typeof(movieElement[i]) === "function") {
                        movieElement[i] = null;
                    }
                } catch (ex1) {}
            }

            // Remove the Movie Element from the page
            try {
                movieElement.parentNode.removeChild(movieElement);
            } catch (ex) {}
        }

        // Remove IE form fix reference
        window[this.movieName] = null;

        // Destroy other references
        SWFUpload.instances[this.movieName] = null;
        delete SWFUpload.instances[this.movieName];

        this.movieElement = null;
        this.settings = null;
        this.customSettings = null;
        this.eventQueue = null;
        this.movieName = null;


        return true;
    } catch (ex2) {
        return false;
    }
};


// Public: displayDebugInfo prints out settings and configuration
// information about this SWFUpload instance.
// This function (and any references to it) can be deleted when placing
// SWFUpload in production.
SWFUpload.prototype.displayDebugInfo = function () {
    this.debug(
        [
            "---SWFUpload Instance Info---\n",
            "Version: ", SWFUpload.version, "\n",
            "Movie Name: ", this.movieName, "\n",
            "Settings:\n",
            "\t", "upload_url:               ", this.settings.upload_url, "\n",
            "\t", "flash_url:                ", this.settings.flash_url, "\n",
            "\t", "use_query_string:         ", this.settings.use_query_string.toString(), "\n",
            "\t", "requeue_on_error:         ", this.settings.requeue_on_error.toString(), "\n",
            "\t", "http_success:             ", this.settings.http_success.join(", "), "\n",
            "\t", "assume_success_timeout:   ", this.settings.assume_success_timeout, "\n",
            "\t", "file_post_name:           ", this.settings.file_post_name, "\n",
            "\t", "post_params:              ", this.settings.post_params.toString(), "\n",
            "\t", "file_types:               ", this.settings.file_types, "\n",
            "\t", "file_types_description:   ", this.settings.file_types_description, "\n",
            "\t", "file_size_limit:          ", this.settings.file_size_limit, "\n",
            "\t", "file_upload_limit:        ", this.settings.file_upload_limit, "\n",
            "\t", "file_queue_limit:         ", this.settings.file_queue_limit, "\n",
            "\t", "debug:                    ", this.settings.debug.toString(), "\n",

            "\t", "prevent_swf_caching:      ", this.settings.prevent_swf_caching.toString(), "\n",

            "\t", "button_placeholder_id:    ", this.settings.button_placeholder_id.toString(), "\n",
            "\t", "button_placeholder:       ", (this.settings.button_placeholder ? "Set" : "Not Set"), "\n",
            "\t", "button_image_url:         ", this.settings.button_image_url.toString(), "\n",
            "\t", "button_width:             ", this.settings.button_width.toString(), "\n",
            "\t", "button_height:            ", this.settings.button_height.toString(), "\n",
            "\t", "button_text:              ", this.settings.button_text.toString(), "\n",
            "\t", "button_text_style:        ", this.settings.button_text_style.toString(), "\n",
            "\t", "button_text_top_padding:  ", this.settings.button_text_top_padding.toString(), "\n",
            "\t", "button_text_left_padding: ", this.settings.button_text_left_padding.toString(), "\n",
            "\t", "button_action:            ", this.settings.button_action.toString(), "\n",
            "\t", "button_disabled:          ", this.settings.button_disabled.toString(), "\n",

            "\t", "custom_settings:          ", this.settings.custom_settings.toString(), "\n",
            "Event Handlers:\n",
            "\t", "swfupload_loaded_handler assigned:  ", (typeof this.settings.swfupload_loaded_handler === "function").toString(), "\n",
            "\t", "file_dialog_start_handler assigned: ", (typeof this.settings.file_dialog_start_handler === "function").toString(), "\n",
            "\t", "file_queued_handler assigned:       ", (typeof this.settings.file_queued_handler === "function").toString(), "\n",
            "\t", "file_queue_error_handler assigned:  ", (typeof this.settings.file_queue_error_handler === "function").toString(), "\n",
            "\t", "upload_start_handler assigned:      ", (typeof this.settings.upload_start_handler === "function").toString(), "\n",
            "\t", "upload_progress_handler assigned:   ", (typeof this.settings.upload_progress_handler === "function").toString(), "\n",
            "\t", "upload_error_handler assigned:      ", (typeof this.settings.upload_error_handler === "function").toString(), "\n",
            "\t", "upload_success_handler assigned:    ", (typeof this.settings.upload_success_handler === "function").toString(), "\n",
            "\t", "upload_complete_handler assigned:   ", (typeof this.settings.upload_complete_handler === "function").toString(), "\n",
            "\t", "debug_handler assigned:             ", (typeof this.settings.debug_handler === "function").toString(), "\n"
        ].join("")
    );
};

/* Note: addSetting and getSetting are no longer used by SWFUpload but are included
    the maintain v2 API compatibility
*/
// Public: (Deprecated) addSetting adds a setting value. If the value given is undefined or null then the default_value is used.
SWFUpload.prototype.addSetting = function (name, value, default_value) {
    if (value == undefined) {
        return (this.settings[name] = default_value);
    } else {
        return (this.settings[name] = value);
    }
};

// Public: (Deprecated) getSetting gets a setting. Returns an empty string if the setting was not found.
SWFUpload.prototype.getSetting = function (name) {
    if (this.settings[name] != undefined) {
        return this.settings[name];
    }

    return "";
};



// Private: callFlash handles function calls made to the Flash element.
// Calls are made with a setTimeout for some functions to work around
// bugs in the ExternalInterface library.
SWFUpload.prototype.callFlash = function (functionName, argumentArray) {
    argumentArray = argumentArray || [];

    var movieElement = this.getMovieElement();
    var returnValue, returnString;

    // Flash's method if calling ExternalInterface methods (code adapted from MooTools).
    try {
        returnString = movieElement.CallFunction('<invoke name="' + functionName + '" returntype="javascript">' + __flash__argumentsToXML(argumentArray, 0) + '</invoke>');
        returnValue = eval(returnString);
    } catch (ex) {
        throw "Call to " + functionName + " failed";
    }

    // Unescape file post param values
    if (returnValue != undefined && typeof returnValue.post === "object") {
        returnValue = this.unescapeFilePostParams(returnValue);
    }

    return returnValue;
};

/* *****************************
    -- Flash control methods --
    Your UI should use these
    to operate SWFUpload
   ***************************** */

// WARNING: this function does not work in Flash Player 10
// Public: selectFile causes a File Selection Dialog window to appear.  This
// dialog only allows 1 file to be selected.
SWFUpload.prototype.selectFile = function () {
    this.callFlash("SelectFile");
};

// WARNING: this function does not work in Flash Player 10
// Public: selectFiles causes a File Selection Dialog window to appear/ This
// dialog allows the user to select any number of files
// Flash Bug Warning: Flash limits the number of selectable files based on the combined length of the file names.
// If the selection name length is too long the dialog will fail in an unpredictable manner.  There is no work-around
// for this bug.
SWFUpload.prototype.selectFiles = function () {
    this.callFlash("SelectFiles");
};


// Public: startUpload starts uploading the first file in the queue unless
// the optional parameter 'fileID' specifies the ID
SWFUpload.prototype.startUpload = function (fileID) {
    this.callFlash("StartUpload", [fileID]);
};

// Public: cancelUpload cancels any queued file.  The fileID parameter may be the file ID or index.
// If you do not specify a fileID the current uploading file or first file in the queue is cancelled.
// If you do not want the uploadError event to trigger you can specify false for the triggerErrorEvent parameter.
SWFUpload.prototype.cancelUpload = function (fileID, triggerErrorEvent) {
    if (triggerErrorEvent !== false) {
        triggerErrorEvent = true;
    }
    this.callFlash("CancelUpload", [fileID, triggerErrorEvent]);
};

// Public: stopUpload stops the current upload and requeues the file at the beginning of the queue.
// If nothing is currently uploading then nothing happens.
SWFUpload.prototype.stopUpload = function () {
    this.callFlash("StopUpload");
};

/* ************************
 * Settings methods
 *   These methods change the SWFUpload settings.
 *   SWFUpload settings should not be changed directly on the settings object
 *   since many of the settings need to be passed to Flash in order to take
 *   effect.
 * *********************** */

// Public: getStats gets the file statistics object.
SWFUpload.prototype.getStats = function () {
    return this.callFlash("GetStats");
};

// Public: setStats changes the SWFUpload statistics.  You shouldn't need to
// change the statistics but you can.  Changing the statistics does not
// affect SWFUpload accept for the successful_uploads count which is used
// by the upload_limit setting to determine how many files the user may upload.
SWFUpload.prototype.setStats = function (statsObject) {
    this.callFlash("SetStats", [statsObject]);
};

// Public: getFile retrieves a File object by ID or Index.  If the file is
// not found then 'null' is returned.
SWFUpload.prototype.getFile = function (fileID) {
    if (typeof(fileID) === "number") {
        return this.callFlash("GetFileByIndex", [fileID]);
    } else {
        return this.callFlash("GetFile", [fileID]);
    }
};

// Public: addFileParam sets a name/value pair that will be posted with the
// file specified by the Files ID.  If the name already exists then the
// exiting value will be overwritten.
SWFUpload.prototype.addFileParam = function (fileID, name, value) {
    return this.callFlash("AddFileParam", [fileID, name, value]);
};

// Public: removeFileParam removes a previously set (by addFileParam) name/value
// pair from the specified file.
SWFUpload.prototype.removeFileParam = function (fileID, name) {
    this.callFlash("RemoveFileParam", [fileID, name]);
};

// Public: setUploadUrl changes the upload_url setting.
SWFUpload.prototype.setUploadURL = function (url) {
    this.settings.upload_url = url.toString();
    this.callFlash("SetUploadURL", [url]);
};

// Public: setPostParams changes the post_params setting
SWFUpload.prototype.setPostParams = function (paramsObject) {
    this.settings.post_params = paramsObject;
    this.callFlash("SetPostParams", [paramsObject]);
};

// Public: addPostParam adds post name/value pair.  Each name can have only one value.
SWFUpload.prototype.addPostParam = function (name, value) {
    this.settings.post_params[name] = value;
    this.callFlash("SetPostParams", [this.settings.post_params]);
};

// Public: removePostParam deletes post name/value pair.
SWFUpload.prototype.removePostParam = function (name) {
    delete this.settings.post_params[name];
    this.callFlash("SetPostParams", [this.settings.post_params]);
};

// Public: setFileTypes changes the file_types setting and the file_types_description setting
SWFUpload.prototype.setFileTypes = function (types, description) {
    this.settings.file_types = types;
    this.settings.file_types_description = description;
    this.callFlash("SetFileTypes", [types, description]);
};

// Public: setFileSizeLimit changes the file_size_limit setting
SWFUpload.prototype.setFileSizeLimit = function (fileSizeLimit) {
    this.settings.file_size_limit = fileSizeLimit;
    this.callFlash("SetFileSizeLimit", [fileSizeLimit]);
};

// Public: setFileUploadLimit changes the file_upload_limit setting
SWFUpload.prototype.setFileUploadLimit = function (fileUploadLimit) {
    this.settings.file_upload_limit = fileUploadLimit;
    this.callFlash("SetFileUploadLimit", [fileUploadLimit]);
};

// Public: setFileQueueLimit changes the file_queue_limit setting
SWFUpload.prototype.setFileQueueLimit = function (fileQueueLimit) {
    this.settings.file_queue_limit = fileQueueLimit;
    this.callFlash("SetFileQueueLimit", [fileQueueLimit]);
};

// Public: setFilePostName changes the file_post_name setting
SWFUpload.prototype.setFilePostName = function (filePostName) {
    this.settings.file_post_name = filePostName;
    this.callFlash("SetFilePostName", [filePostName]);
};

// Public: setUseQueryString changes the use_query_string setting
SWFUpload.prototype.setUseQueryString = function (useQueryString) {
    this.settings.use_query_string = useQueryString;
    this.callFlash("SetUseQueryString", [useQueryString]);
};

// Public: setRequeueOnError changes the requeue_on_error setting
SWFUpload.prototype.setRequeueOnError = function (requeueOnError) {
    this.settings.requeue_on_error = requeueOnError;
    this.callFlash("SetRequeueOnError", [requeueOnError]);
};

// Public: setHTTPSuccess changes the http_success setting
SWFUpload.prototype.setHTTPSuccess = function (http_status_codes) {
    if (typeof http_status_codes === "string") {
        http_status_codes = http_status_codes.replace(" ", "").split(",");
    }

    this.settings.http_success = http_status_codes;
    this.callFlash("SetHTTPSuccess", [http_status_codes]);
};

// Public: setHTTPSuccess changes the http_success setting
SWFUpload.prototype.setAssumeSuccessTimeout = function (timeout_seconds) {
    this.settings.assume_success_timeout = timeout_seconds;
    this.callFlash("SetAssumeSuccessTimeout", [timeout_seconds]);
};

// Public: setDebugEnabled changes the debug_enabled setting
SWFUpload.prototype.setDebugEnabled = function (debugEnabled) {
    this.settings.debug_enabled = debugEnabled;
    this.callFlash("SetDebugEnabled", [debugEnabled]);
};

// Public: setButtonImageURL loads a button image sprite
SWFUpload.prototype.setButtonImageURL = function (buttonImageURL) {
    if (buttonImageURL == undefined) {
        buttonImageURL = "";
    }

    this.settings.button_image_url = buttonImageURL;
    this.callFlash("SetButtonImageURL", [buttonImageURL]);
};

// Public: setButtonDimensions resizes the Flash Movie and button
SWFUpload.prototype.setButtonDimensions = function (width, height) {
    this.settings.button_width = width;
    this.settings.button_height = height;

    var movie = this.getMovieElement();
    if (movie != undefined) {
        movie.style.width = width + "px";
        movie.style.height = height + "px";
    }

    this.callFlash("SetButtonDimensions", [width, height]);
};
// Public: setButtonText Changes the text overlaid on the button
SWFUpload.prototype.setButtonText = function (html) {
    this.settings.button_text = html;
    this.callFlash("SetButtonText", [html]);
};
// Public: setButtonTextPadding changes the top and left padding of the text overlay
SWFUpload.prototype.setButtonTextPadding = function (left, top) {
    this.settings.button_text_top_padding = top;
    this.settings.button_text_left_padding = left;
    this.callFlash("SetButtonTextPadding", [left, top]);
};

// Public: setButtonTextStyle changes the CSS used to style the HTML/Text overlaid on the button
SWFUpload.prototype.setButtonTextStyle = function (css) {
    this.settings.button_text_style = css;
    this.callFlash("SetButtonTextStyle", [css]);
};
// Public: setButtonDisabled disables/enables the button
SWFUpload.prototype.setButtonDisabled = function (isDisabled) {
    this.settings.button_disabled = isDisabled;
    this.callFlash("SetButtonDisabled", [isDisabled]);
};
// Public: setButtonAction sets the action that occurs when the button is clicked
SWFUpload.prototype.setButtonAction = function (buttonAction) {
    this.settings.button_action = buttonAction;
    this.callFlash("SetButtonAction", [buttonAction]);
};

// Public: setButtonCursor changes the mouse cursor displayed when hovering over the button
SWFUpload.prototype.setButtonCursor = function (cursor) {
    this.settings.button_cursor = cursor;
    this.callFlash("SetButtonCursor", [cursor]);
};

/* *******************************
    Flash Event Interfaces
    These functions are used by Flash to trigger the various
    events.

    All these functions a Private.

    Because the ExternalInterface library is buggy the event calls
    are added to a queue and the queue then executed by a setTimeout.
    This ensures that events are executed in a determinate order and that
    the ExternalInterface bugs are avoided.
******************************* */

SWFUpload.prototype.queueEvent = function (handlerName, argumentArray) {
    // Warning: Don't call this.debug inside here or you'll create an infinite loop

    if (argumentArray == undefined) {
        argumentArray = [];
    } else if (!(argumentArray instanceof Array)) {
        argumentArray = [argumentArray];
    }

    var self = this;
    if (typeof this.settings[handlerName] === "function") {
        // Queue the event
        this.eventQueue.push(function () {
            this.settings[handlerName].apply(this, argumentArray);
        });

        // Execute the next queued event
        setTimeout(function () {
            self.executeNextEvent();
        }, 0);

    } else if (this.settings[handlerName] !== null) {
        throw "Event handler " + handlerName + " is unknown or is not a function";
    }
};

// Private: Causes the next event in the queue to be executed.  Since events are queued using a setTimeout
// we must queue them in order to garentee that they are executed in order.
SWFUpload.prototype.executeNextEvent = function () {
    // Warning: Don't call this.debug inside here or you'll create an infinite loop

    var  f = this.eventQueue ? this.eventQueue.shift() : null;
    if (typeof(f) === "function") {
        f.apply(this);
    }
};

// Private: unescapeFileParams is part of a workaround for a flash bug where objects passed through ExternalInterface cannot have
// properties that contain characters that are not valid for JavaScript identifiers. To work around this
// the Flash Component escapes the parameter names and we must unescape again before passing them along.
SWFUpload.prototype.unescapeFilePostParams = function (file) {
    var reg = /[$]([0-9a-f]{4})/i;
    var unescapedPost = {};
    var uk;

    if (file != undefined) {
        for (var k in file.post) {
            if (file.post.hasOwnProperty(k)) {
                uk = k;
                var match;
                while ((match = reg.exec(uk)) !== null) {
                    uk = uk.replace(match[0], String.fromCharCode(parseInt("0x" + match[1], 16)));
                }
                unescapedPost[uk] = file.post[k];
            }
        }

        file.post = unescapedPost;
    }

    return file;
};

// Private: Called by Flash to see if JS can call in to Flash (test if External Interface is working)
SWFUpload.prototype.testExternalInterface = function () {
    try {
        return this.callFlash("TestExternalInterface");
    } catch (ex) {
        return false;
    }
};

// Private: This event is called by Flash when it has finished loading. Don't modify this.
// Use the swfupload_loaded_handler event setting to execute custom code when SWFUpload has loaded.
SWFUpload.prototype.flashReady = function () {
    // Check that the movie element is loaded correctly with its ExternalInterface methods defined
    var movieElement = this.getMovieElement();

    if (!movieElement) {
        this.debug("Flash called back ready but the flash movie can't be found.");
        return;
    }

    this.cleanUp(movieElement);

    this.queueEvent("swfupload_loaded_handler");
};

// Private: removes Flash added fuctions to the DOM node to prevent memory leaks in IE.
// This function is called by Flash each time the ExternalInterface functions are created.
SWFUpload.prototype.cleanUp = function (movieElement) {
    // Pro-actively unhook all the Flash functions
    try {
        if (this.movieElement && typeof(movieElement.CallFunction) === "unknown") { // We only want to do this in IE
            this.debug("Removing Flash functions hooks (this should only run in IE and should prevent memory leaks)");
            for (var key in movieElement) {
                try {
                    if (typeof(movieElement[key]) === "function") {
                        movieElement[key] = null;
                    }
                } catch (ex) {
                }
            }
        }
    } catch (ex1) {

    }

    // Fix Flashes own cleanup code so if the SWFMovie was removed from the page
    // it doesn't display errors.
    window["__flash__removeCallback"] = function (instance, name) {
        try {
            if (instance) {
                instance[name] = null;
            }
        } catch (flashEx) {

        }
    };

};


/* This is a chance to do something before the browse window opens */
SWFUpload.prototype.fileDialogStart = function () {
    this.queueEvent("file_dialog_start_handler");
};


/* Called when a file is successfully added to the queue. */
SWFUpload.prototype.fileQueued = function (file) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("file_queued_handler", file);
};


/* Handle errors that occur when an attempt to queue a file fails. */
SWFUpload.prototype.fileQueueError = function (file, errorCode, message) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("file_queue_error_handler", [file, errorCode, message]);
};

/* Called after the file dialog has closed and the selected files have been queued.
    You could call startUpload here if you want the queued files to begin uploading immediately. */
SWFUpload.prototype.fileDialogComplete = function (numFilesSelected, numFilesQueued, numFilesInQueue) {
    this.queueEvent("file_dialog_complete_handler", [numFilesSelected, numFilesQueued, numFilesInQueue]);
};

SWFUpload.prototype.uploadStart = function (file) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("return_upload_start_handler", file);
};

SWFUpload.prototype.returnUploadStart = function (file) {
    var returnValue;
    if (typeof this.settings.upload_start_handler === "function") {
        file = this.unescapeFilePostParams(file);
        returnValue = this.settings.upload_start_handler.call(this, file);
    } else if (this.settings.upload_start_handler != undefined) {
        throw "upload_start_handler must be a function";
    }

    // Convert undefined to true so if nothing is returned from the upload_start_handler it is
    // interpretted as 'true'.
    if (returnValue === undefined) {
        returnValue = true;
    }

    returnValue = !!returnValue;

    this.callFlash("ReturnUploadStart", [returnValue]);
};



SWFUpload.prototype.uploadProgress = function (file, bytesComplete, bytesTotal) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("upload_progress_handler", [file, bytesComplete, bytesTotal]);
};

SWFUpload.prototype.uploadError = function (file, errorCode, message) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("upload_error_handler", [file, errorCode, message]);
};

SWFUpload.prototype.uploadSuccess = function (file, serverData, responseReceived) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("upload_success_handler", [file, serverData, responseReceived]);
};

SWFUpload.prototype.uploadComplete = function (file) {
    file = this.unescapeFilePostParams(file);
    this.queueEvent("upload_complete_handler", file);
};

/* Called by SWFUpload JavaScript and Flash functions when debug is enabled. By default it writes messages to the
   internal debug console.  You can override this event and have messages written where you want. */
SWFUpload.prototype.debug = function (message) {
    this.queueEvent("debug_handler", message);
};


/* **********************************
    Debug Console
    The debug console is a self contained, in page location
    for debug message to be sent.  The Debug Console adds
    itself to the body if necessary.

    The console is automatically scrolled as messages appear.

    If you are using your own debug handler or when you deploy to production and
    have debug disabled you can remove these functions to reduce the file size
    and complexity.
********************************** */

// Private: debugMessage is the default debug_handler.  If you want to print debug messages
// call the debug() function.  When overriding the function your own function should
// check to see if the debug setting is true before outputting debug information.
SWFUpload.prototype.debugMessage = function (message) {
    if (this.settings.debug) {
        var exceptionMessage, exceptionValues = [];

        // Check for an exception object and print it nicely
        if (typeof message === "object" && typeof message.name === "string" && typeof message.message === "string") {
            for (var key in message) {
                if (message.hasOwnProperty(key)) {
                    exceptionValues.push(key + ": " + message[key]);
                }
            }
            exceptionMessage = exceptionValues.join("\n") || "";
            exceptionValues = exceptionMessage.split("\n");
            exceptionMessage = "EXCEPTION: " + exceptionValues.join("\nEXCEPTION: ");
            SWFUpload.Console.writeLine(exceptionMessage);
        } else {
            SWFUpload.Console.writeLine(message);
        }
    }
};

SWFUpload.Console = {};
SWFUpload.Console.writeLine = function (message) {
    var console, documentForm;

    try {
        console = document.getElementById("SWFUpload_Console");

        if (!console) {
            documentForm = document.createElement("form");
            document.getElementsByTagName("body")[0].appendChild(documentForm);

            console = document.createElement("textarea");
            console.id = "SWFUpload_Console";
            console.style.fontFamily = "monospace";
            console.setAttribute("wrap", "off");
            console.wrap = "off";
            console.style.overflow = "auto";
            console.style.width = "700px";
            console.style.height = "350px";
            console.style.margin = "5px";
            documentForm.appendChild(console);
        }

        console.value += message + "\n";

        console.scrollTop = console.scrollHeight - console.clientHeight;
    } catch (ex) {
        alert("Exception: " + ex.name + " Message: " + ex.message);
    }
};
