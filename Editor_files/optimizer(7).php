(function(window){
    window.htmlentities = {
        /**
         * Converts a string to its html characters completely.
         *
         * @param {String} str String with unescaped HTML characters
         **/
        encode : function(str) {
            var buf = [];

            for (var i=str.length-1;i>=0;i--) {
                buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
            }

            return buf.join('');
        },
        /**
         * Converts an html characterSet into its original character.
         *
         * @param {String} str htmlSet entities
         **/
        decode : function(str) {
            return str.replace(/&#(\d+);/g, function(match, dec) {
                return String.fromCharCode(dec);
            });
        }
    };
})(window);
/**
 * 비동기식 데이터
 */
var CAPP_ASYNC_METHODS = {
    DEBUG: false,
    IS_LOGIN: (document.cookie.match(/(?:^| |;)iscache=F/) ? true : false),
    EC_PATH_ROLE: $('meta[name="path_role"]').attr('content') || '',
    aDatasetList: [],
    $xansMyshopMain: $('.xans-myshop-main'),
    init : function()
    {
    	var bDebug = CAPP_ASYNC_METHODS.DEBUG;

        var aUseModules = [];
        var aNoCachedModules = [];

        $(CAPP_ASYNC_METHODS.aDatasetList).each(function(){
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (bDebug) {
                console.log(sKey);
            }
            var bIsUse = oTarget.isUse();
            if (bDebug) {
                console.log('   isUse() : ' + bIsUse);
            }

            if (bIsUse === true) {
                aUseModules.push(sKey);

                if (oTarget.restoreCache === undefined || oTarget.restoreCache() === false) {
                    if (bDebug) {
                        console.log('   restoreCache() : true');
                    }
                    aNoCachedModules.push(sKey);
                }
            }
        });

        if (aNoCachedModules.length > 0) {
            var sEditor = '';
            try {
                if (bEditor === true) {
                    // 에디터에서 접근했을 경우 임의의 상품 지정
                    sEditor = '&PREVIEW_SDE=1';
                }
            } catch(e) { }

            var sPathRole = '&path_role=' + CAPP_ASYNC_METHODS.EC_PATH_ROLE;

            $.ajax(
            {
                url : '/exec/front/manage/async?module=' + aNoCachedModules.join(',') + sEditor + sPathRole,
                dataType : 'json',
                success : function(aData)
                {
                	CAPP_ASYNC_METHODS.setData(aData, aUseModules);
                }
            });

        } else {
        	CAPP_ASYNC_METHODS.setData({}, aUseModules);

        }
    },
    setData : function(aData, aUseModules)
    {
        aData = aData || {};

        $(aUseModules).each(function(){
            var sKey = this;

            var oTarget = CAPP_ASYNC_METHODS[sKey];

            if (oTarget.setData !== undefined && aData.hasOwnProperty(sKey) === true) {
                oTarget.setData(aData[sKey]);
            }

            if (oTarget.execute !== undefined) {
                oTarget.execute();
            }
        });
    },

    _getCookie: function(sCookieName)
    {
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        return aCookieValue ? aCookieValue[1] : null;
    }
};
/**
 * 비동기식 데이터 - 회원 정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('member');
CAPP_ASYNC_METHODS.member = {
    __sEncryptedString: null,
    __isAdult: 'F',

    // 회원 데이터
    __sMemberId: null,
    __sName: null,
    __sNickName: null,
    __sGroupName: null,
    __sEmail: null,
    __sPhone: null,
    __sCellphone: null,
    __sBirthday: null,
    __sGroupNo: null,
    __sBoardWriteName: null,
    __sAdditionalInformation: null,
    __sCreatedDate: null,

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if ($('.xans-layout-statelogon, .xans-layout-logon').length > 0) {
                return true;
            }

            if (CAPP_ASYNC_METHODS.recent.isUse() === true
                && typeof(EC_FRONT_JS_CONFIG_SHOP) !== 'undefined'
                && EC_FRONT_JS_CONFIG_SHOP.adult19Warning === 'T') {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }

        } else {
            // 비 로그인 상태에서 삭제처리
            this.removeCache();
        }

        return false;
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        // 데이터 복구 유무
        var bRestored = false;

        try {
            // 데이터 복구
            var oCache = JSON.parse(window.sessionStorage.getItem('member_' + EC_SDE_SHOP_NUM));

            // expire 체크
            if (oCache.exp < Date.now()) {
                throw 'cache has expired.';
            }

            // 데이터 체크
            if (typeof oCache.data.member_id === 'undefined'
                || oCache.data.member_id === ''
                || typeof oCache.data.name === 'undefined'
                || typeof oCache.data.nick_name === 'undefined'
                || typeof oCache.data.group_name === 'undefined'
                || typeof oCache.data.group_no === 'undefined'
                || typeof oCache.data.email === 'undefined'
                || typeof oCache.data.phone === 'undefined'
                || typeof oCache.data.cellphone === 'undefined'
                || typeof oCache.data.birthday === 'undefined'
                || typeof oCache.data.board_write_name === 'undefined'
                || typeof oCache.data.additional_information === 'undefined'
                || typeof oCache.data.created_date === 'undefined'
            ) {
                throw 'Invalid cache data.'
            }

            // 데이터 복구
            this.__sMemberId = oCache.data.member_id;
            this.__sName = oCache.data.name;
            this.__sNickName = oCache.data.nick_name;
            this.__sGroupName = oCache.data.group_name;
            this.__sGroupNo   = oCache.data.group_no;
            this.__sEmail = oCache.data.email;
            this.__sPhone = oCache.data.phone;
            this.__sCellphone = oCache.data.cellphone;
            this.__sBirthday = oCache.data.birthday;
            this.__sBoardWriteName = oCache.data.board_write_name;
            this.__sAdditionalInformation = oCache.data.additional_information;
            this.__sCreatedDate = oCache.data.created_date;

            bRestored = true;
        } catch(e) {
            // 복구 실패시 캐시 삭제
            this.removeCache();
        }

        return bRestored;
    },

    cache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        // 캐시
        window.sessionStorage.setItem('member_' + EC_SDE_SHOP_NUM, JSON.stringify({
            exp: Date.now() + (1000 * 60 * 10),
            data: this.getData()
        }));
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        // 캐시 삭제
        window.sessionStorage.removeItem('member_' + EC_SDE_SHOP_NUM);
    },

    setData: function(oData)
    {
        this.__sEncryptedString = oData.memberData;
        this.__isAdult = oData.memberIsAdult;
    },

    execute: function()
    {
        if (this.__sMemberId === null) {
            AuthSSLManager.weave({
                'auth_mode'          : 'decryptClient',
                'auth_string'        : this.__sEncryptedString,
                'auth_callbackName'  : 'CAPP_ASYNC_METHODS.member.setDataCallback'
            });
        } else {
            this.render()
        }
    },

    setDataCallback: function(sData)
    {
        try {
            var sDecodedData = decodeURIComponent(sData);

            if (AuthSSLManager.isError(sDecodedData) == true) {
                console.log(sDecodedData);
                return;
            }

            var oData = AuthSSLManager.unserialize(sDecodedData);
            this.__sMemberId = oData.id || '';
            this.__sName = oData.name || '';
            this.__sNickName = oData.nick || '';
            this.__sGroupName = oData.group_name || '';
            this.__sGroupNo   = oData.group_no || '';
            this.__sEmail = oData.email || '';
            this.__sPhone = oData.phone || '';
            this.__sCellphone = oData.cellphone || '';
            this.__sBirthday = oData.birthday || 'F';
            this.__sBoardWriteName = oData.board_write_name || '';
            this.__sAdditionalInformation = oData.additional_information || '';
            this.__sCreatedDate = oData.created_date || '';

            // 데이터 랜더링
            this.render();

            // 데이터 캐시
            this.cache();
        } catch(e) {}
    },

    render: function()
    {
        // 친구초대
        if ($('.xans-myshop-asyncbenefit').length > 0) {
            $('#reco_url').attr({value: $('#reco_url').val() + this.__sMemberId});
        }

        $('.xans-member-var-id').html(this.__sMemberId);
        $('.xans-member-var-name').html(this.__sName);
        $('.xans-member-var-nick').html(this.__sNickName);
        $('.xans-member-var-group_name').html(this.__sGroupName);
        $('.xans-member-var-group_no').html(this.__sGroupNo);
        $('.xans-member-var-email').html(this.__sEmail);
        $('.xans-member-var-phone').html(this.__sPhone);

        if ($('.xans-board-commentwrite').length > 0 && typeof BOARD_COMMENT !== 'undefined') {
            BOARD_COMMENT.setCmtData();
        }
    },

    getMemberIsAdult: function()
    {
        return this.__isAdult;
    },

    getData: function()
    {
        return {
            member_id: this.__sMemberId,
            name: this.__sName,
            nick_name: this.__sNickName,
            group_name: this.__sGroupName,
            group_no: this.__sGroupNo,
            email: this.__sEmail,
            phone: this.__sPhone,
            cellphone: this.__sCellphone,
            birthday: this.__sBirthday,
            board_write_name: this.__sBoardWriteName,
            additional_information: this.__sAdditionalInformation,
            created_date: this.__sCreatedDate
        };
    }
};
/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Ordercnt');
CAPP_ASYNC_METHODS.Ordercnt = {
    __iOrderShppiedBeforeCount: null,
    __iOrderShppiedStandbyCount: null,
    __iOrderShppiedBeginCount: null,
    __iOrderShppiedComplateCount: null,
    __iOrderShppiedCancelCount: null,
    __iOrderShppiedExchangeCount: null,
    __iOrderShppiedReturnCount: null,

    __$target: $('#xans_myshop_orderstate_shppied_before_count'),
    __$target2: $('#xans_myshop_orderstate_shppied_standby_count'),
    __$target3: $('#xans_myshop_orderstate_shppied_begin_count'),
    __$target4: $('#xans_myshop_orderstate_shppied_complate_count'),
    __$target5: $('#xans_myshop_orderstate_order_cancel_count'),
    __$target6: $('#xans_myshop_orderstate_order_exchange_count'),
    __$target7: $('#xans_myshop_orderstate_order_return_count'),

    isUse: function()
    {
        if ($('.xans-myshop-orderstate').length > 0) {
            return true; 
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'ordercnt_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = jQuery.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderShppiedBeforeCount = aData.shipped_before_count;
            this.__iOrderShppiedStandbyCount = aData.shipped_standby_count;
            this.__iOrderShppiedBeginCount = aData.shipped_begin_count;
            this.__iOrderShppiedComplateCount = aData.shipped_complate_count;
            this.__iOrderShppiedCancelCount = aData.order_cancel_count;
            this.__iOrderShppiedExchangeCount = aData.order_exchange_count;
            this.__iOrderShppiedReturnCount = aData.order_return_count;
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderShppiedBeforeCount = aData['shipped_before_count'];
        this.__iOrderShppiedStandbyCount = aData['shipped_standby_count'];
        this.__iOrderShppiedBeginCount = aData['shipped_begin_count'];
        this.__iOrderShppiedComplateCount = aData['shipped_complate_count'];
        this.__iOrderShppiedCancelCount = aData['order_cancel_count'];
        this.__iOrderShppiedExchangeCount = aData['order_exchange_count'];
        this.__iOrderShppiedReturnCount = aData['order_return_count'];
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderShppiedBeforeCount);
        this.__$target2.html(this.__iOrderShppiedStandbyCount);
        this.__$target3.html(this.__iOrderShppiedBeginCount);
        this.__$target4.html(this.__iOrderShppiedComplateCount);
        this.__$target5.html(this.__iOrderShppiedCancelCount);
        this.__$target6.html(this.__iOrderShppiedExchangeCount);
        this.__$target7.html(this.__iOrderShppiedReturnCount);
    },

    getData: function()
    {
        return {
            shipped_before_count: this.__iOrderShppiedBeforeCount,
            shipped_standby_count: this.__iOrderShppiedStandbyCount,
            shipped_begin_count: this.__iOrderShppiedBeginCount,
            shipped_complate_count: this.__iOrderShppiedComplateCount,
            order_cancel_count: this.__iOrderShppiedCancelCount,
            order_exchange_count: this.__iOrderShppiedExchangeCount,
            order_return_count: this.__iOrderShppiedReturnCount
        };
    }
};
/**
 * 비동기식 데이터 - 장바구니 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketcnt');
CAPP_ASYNC_METHODS.Basketcnt = {
    __iBasketCount: null,

    __$target: $('.xans-layout-orderbasketcount span a'),
    __$target2: $('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: $('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iBasketCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__iBasketCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iBasketCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iBasketCount + '개');
        } else {
            this.__$target2.html(this.__iBasketCount);
        }

        this.__$target3.html(this.__iBasketCount);
        
        this.__$target4.html(this.__iBasketCount);
        
        if (this.__iBasketCount > 0 && this.__$target4.length > 0) {
            var $oCountDisplay = $('.EC-Layout_Basket-count-display');

            if ($oCountDisplay.length > 0) {
                $oCountDisplay.removeClass('displaynone');
            }
        }
    },

    getData: function()
    {
        return {
            count: this.__iBasketCount
        };
    }
};
/**
 * 비동기식 데이터 - 장바구니 금액
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Basketprice');
CAPP_ASYNC_METHODS.Basketprice = {
    __sBasketPrice: null,

    __$target: $('#xans_myshop_basket_price'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'basketprice_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__sBasketPrice = decodeURIComponent((aCookieValue[1]+ '').replace(/\+/g, '%20'));
            return true;
        }
        
        return false;
    },

    setData: function(sData)
    {
        this.__sBasketPrice = sData;
    },

    execute: function()
    {
        this.__$target.html(this.__sBasketPrice);
    },

    getData: function()
    {
        // 데이터 없는경우 0
        var sBasketPrice = (this.__sBasketPrice || 0) + '';

        return {
            basket_price: parseFloat(SHOP_PRICE_FORMAT.detachFormat(htmlentities.decode(sBasketPrice))).toFixed(2)
        };
    }
};
/*
 * 비동기식 데이터 - 장바구니 상품리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketProduct');
CAPP_ASYNC_METHODS.BasketProduct = {

    STORAGE_KEY: 'BasketProduct_' +  EC_SDE_SHOP_NUM,

    __aData: null,

    __$target: $('.xans-layout-orderbasketcount span a'),
    __$target2: $('#xans_myshop_basket_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_basket_cnt'),
    __$target4: $('.EC-Layout-Basket-count'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }
        if (this.__$target3.length > 0) {
            return true;
        }
        if (this.__$target4.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }
    },

    restoreCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        try {
            this.__aData = [];
            var aStorageData = JSON.parse(sSessionStorageData);

            for (var iKey in aStorageData) {
                this.__aData.push(aStorageData[iKey]);
            }

            return true;
        } catch(e) {

            // 복구 실패시 캐시 삭제
            this.removeCache();

            return false;
        }
    },

    removeCache: function()
    {
        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }
        // 캐시 삭제
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    setData: function(oData)
    {
        this.__aData = oData;

        // sessionStorage 지원 여부 확인
        if (!window.sessionStorage) {
            return;
        }

        try {
            sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.getData()));
        } catch (error) {
        }
    },

    execute: function()
    {

    },

    getData: function()
    {
        var aStorageData = this.__aData;

        if (aStorageData != null) {
            var oNewStorageData = [];

            for (var iKey in aStorageData) {
                oNewStorageData.push(aStorageData[iKey]);
            }

            return oNewStorageData;
        } else {
            return false;
        }
    }
};
/**
 * 비동기식 데이터 - 쿠폰 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Couponcnt');
CAPP_ASYNC_METHODS.Couponcnt = {
    __iCouponCount: null,

    __$target: $('.xans-layout-myshopcouponcount'),
    __$target2: $('#xans_myshop_coupon_cnt'),
    __$target3: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_coupon_cnt'),
    __$target4: $('#xans_myshop_bankbook_coupon_cnt'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('promotion', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },
    
    restoreCache: function()
    {
        var sCookieName = 'couponcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iCouponCount = parseInt(aCookieValue[1], 10);
            return true;
        }
        
        return false;
    },
    setData: function(sData)
    {
        this.__iCouponCount = Number(sData);
    },

    execute: function()
    {
        this.__$target.html(this.__iCouponCount);

        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target2.html(this.__iCouponCount + '개');
        } else {
            this.__$target2.html(this.__iCouponCount);
        }

        this.__$target3.html(this.__iCouponCount);
        this.__$target4.html(this.__iCouponCount);
    },

    getData: function()
    {
        return {
            count: this.__iCouponCount
        };
    }
};
/**
 * 비동기식 데이터 - 적립금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Mileage');
CAPP_ASYNC_METHODS.Mileage = {
    __sAvailMileage: null,
    __sUsedMileage: null,
    __sTotalMileage: null,
    __sUnavailMileage: null,
    __sReturnedMileage: null,

    __$target: $('#xans_myshop_mileage'),
    __$target2: $('#xans_myshop_bankbook_avail_mileage, #xans_myshop_summary_avail_mileage'),
    __$target3: $('#xans_myshop_bankbook_used_mileage, #xans_myshop_summary_used_mileage'),
    __$target4: $('#xans_myshop_bankbook_total_mileage, #xans_myshop_summary_total_mileage'),
    __$target5: $('#xans_myshop_summary_unavail_mileage'),
    __$target6: $('#xans_myshop_summary_returned_mileage'),
    __$target7: $('#xans_myshop_avail_mileage'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAvailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_avail_mileage_' + EC_SDE_SHOP_NUM);
        var sReturnedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_returned_mileage_' + EC_SDE_SHOP_NUM);
        var sUnavailMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_unavail_mileage_' + EC_SDE_SHOP_NUM);
        var sUsedMileage = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_mileage_' + EC_SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAvailMileage === null
            || sReturnedMileage === null
            || sUnavailMileage === null
            || sUsedMileage === null
        ) {
            return false;
        }

        // 전체 마일리지 계산
        var sTotalMileage = (parseFloat(sAvailMileage) +
            parseFloat(sUnavailMileage) +
            parseFloat(sUsedMileage)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sAvailMileage = parseFloat(sAvailMileage).toFixed(2);
        this.__sReturnedMileage = parseFloat(sReturnedMileage).toFixed(2);
        this.__sUnavailMileage = parseFloat(sUnavailMileage).toFixed(2);
        this.__sUsedMileage = parseFloat(sUsedMileage).toFixed(2);
        this.__sTotalMileage = parseFloat(sTotalMileage).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sAvailMileage = parseFloat(aData['avail_mileage'] || 0).toFixed(2);
        this.__sUsedMileage = parseFloat(aData['used_mileage'] || 0).toFixed(2);
        this.__sTotalMileage = parseFloat(aData['total_mileage'] || 0).toFixed(2);
        this.__sUnavailMileage = parseFloat(aData['unavail_mileage'] || 0).toFixed(2);
        this.__sReturnedMileage = parseFloat(aData['returned_mileage'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target2.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
        this.__$target3.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUsedMileage));
        this.__$target4.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sTotalMileage));
        this.__$target5.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sUnavailMileage));
        this.__$target6.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sReturnedMileage));
        this.__$target7.html(SHOP_PRICE_FORMAT.toShopMileagePrice(this.__sAvailMileage));
    },

    getData: function()
    {
        return {
            available_mileage: this.__sAvailMileage,
            used_mileage: this.__sUsedMileage,
            total_mileage: this.__sTotalMileage,
            returned_mileage: this.__sReturnedMileage,
            unavailable_mileage: this.__sUnavailMileage
        };
    }
};
/**
 * 비동기식 데이터 - 예치금
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Deposit');
CAPP_ASYNC_METHODS.Deposit = {
    __sTotalDeposit: null,
    __sAllDeposit: null,
    __sUsedDeposit: null,
    __sRefundWaitDeposit: null,
    __sMemberTotalDeposit: null,

    __$target: $('#xans_myshop_deposit'),
    __$target2: $('#xans_myshop_bankbook_deposit'),
    __$target3: $('#xans_myshop_summary_deposit'),
    __$target4: $('#xans_myshop_summary_all_deposit'),
    __$target5: $('#xans_myshop_summary_used_deposit'),
    __$target6: $('#xans_myshop_summary_refund_wait_deposit'),
    __$target7: $('#xans_myshop_total_deposit'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }

            if (this.__$target4.length > 0) {
                return true;
            }

            if (this.__$target5.length > 0) {
                return true;
            }

            if (this.__$target6.length > 0) {
                return true;
            }

            if (this.__$target7.length > 0) {
                return true;
            }

            if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('customer', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
                return true;
            }
        }

        return false;
    },

    restoreCache: function()
    {
        // 특정 경로 룰의 경우 복구 취소
        if (PathRoleValidator.isInvalidPathRole()) {
            return false;
        }

        // 쿠키로부터 데이터 획득
        var sAllDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_all_deposit_' + EC_SDE_SHOP_NUM);
        var sUsedDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_used_deposit_' + EC_SDE_SHOP_NUM);
        var sRefundWaitDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_deposit_refund_wait_' + EC_SDE_SHOP_NUM);
        var sMemberTotalDeposit = CAPP_ASYNC_METHODS._getCookie('ec_async_cache_member_total_deposit_' + EC_SDE_SHOP_NUM);

        // 데이터가 하나라도 없는경우 복구 실패
        if (sAllDeposit === null
            || sUsedDeposit === null
            || sRefundWaitDeposit === null
            || sMemberTotalDeposit === null
        ) {
            return false;
        }

        // 사용 가능한 예치금 계산
        var sTotalDeposit = (parseFloat(sAllDeposit) -
            parseFloat(sUsedDeposit) -
            parseFloat(sRefundWaitDeposit)).toString();

        // 단위정보를 계산하여 필드에 셋
        this.__sTotalDeposit = parseFloat(sTotalDeposit).toFixed(2);
        this.__sAllDeposit = parseFloat(sAllDeposit).toFixed(2);
        this.__sUsedDeposit = parseFloat(sUsedDeposit).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(sRefundWaitDeposit).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(sMemberTotalDeposit).toFixed(2);

        return true;
    },

    setData: function(aData)
    {
        this.__sTotalDeposit = parseFloat(aData['total_deposit'] || 0).toFixed(2);
        this.__sAllDeposit = parseFloat(aData['all_deposit'] || 0).toFixed(2);
        this.__sUsedDeposit = parseFloat(aData['used_deposit'] || 0).toFixed(2);
        this.__sRefundWaitDeposit = parseFloat(aData['deposit_refund_wait'] || 0).toFixed(2);
        this.__sMemberTotalDeposit = parseFloat(aData['member_total_deposit'] || 0).toFixed(2);
    },

    execute: function()
    {
        this.__$target.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target2.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target3.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sTotalDeposit));
        this.__$target4.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sAllDeposit));
        this.__$target5.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sUsedDeposit));
        this.__$target6.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sRefundWaitDeposit));
        this.__$target7.html(SHOP_PRICE_FORMAT.toShopDepositPrice(this.__sMemberTotalDeposit));
    },

    getData: function()
    {
        return {
            total_deposit: this.__sTotalDeposit,
            used_deposit: this.__sUsedDeposit,
            refund_wait_deposit: this.__sRefundWaitDeposit,
            all_deposit: this.__sAllDeposit,
            member_total_deposit: this.__sMemberTotalDeposit
        };
    }
};
/**
 * 비동기식 데이터 - 위시리스트
 */
CAPP_ASYNC_METHODS.aDatasetList.push('WishList');
CAPP_ASYNC_METHODS.WishList = {
    STORAGE_KEY: 'localWishList' +  EC_SDE_SHOP_NUM,
    __$targetWishIcon: $('.icon_img.ec-product-listwishicon'),
    __$targetWishList: $('.xans-myshop-wishlist'),
    __aWishList: null,
    __aTags_on: null,
    __aTags_off: null,

    isUse: function()
    {
        if (this.__$targetWishIcon.length > 0 || this.__$targetWishList.length > 0) {
            return true;
        }
        return false;
    },

    restoreCache: function()
    {
        if (!window.sessionStorage) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var aStorageData = $.parseJSON(sSessionStorageData);
        if (this.__$targetWishList.length > 0 || aStorageData['isLogin'] !== CAPP_ASYNC_METHODS.IS_LOGIN) {
            this.clearStorage();
            return false;
        }

        if (CAPP_ASYNC_METHODS.EC_PATH_ROLE === 'MAIN') {
            var aWishList = aStorageData['wishList'];
            this.__aTags_on = aStorageData['on_tags'];
            this.__aTags_off = aStorageData['off_tags'];
            this.__aWishList = [];
            for (var i = 0; i < aWishList.length; i++) {
                var aTempWishList = [];
                aTempWishList.product_no = aWishList[i];
                this.__aWishList.push(aTempWishList);
            }
            return true;
        }
        return false;
    },

    setData: function(aData)
    {
        if (aData.hasOwnProperty('wishList') === false || aData.hasOwnProperty('on_tags') === false) {
            return;
        }

        this.__aWishList = aData.wishList;
        this.__aTags_on = aData.on_tags;
        this.__aTags_off = aData.off_tags;

        if (window.sessionStorage) {
            var aWishList = [];

            for (var i = 0 ; i < aData.wishList.length ; i++) {
                aWishList.push(aData.wishList[i].product_no);
            }

            var oNewStorageData = {
                'wishList' : aWishList,
                'on_tags' : aData.on_tags,
                'off_tags' : aData.off_tags,
                'isLogin' : CAPP_ASYNC_METHODS.IS_LOGIN
            };

            if (typeof oNewStorageData !== 'undefined') {
                sessionStorage.setItem(this.STORAGE_KEY , JSON.stringify(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var aWishList = this.__aWishList;
        var aTagsOn = this.__aTags_on;
        var aTagsOff = this.__aTags_off;

        if (aWishList === null || typeof aWishList === 'undefined') {
            aWishList = [];
        }

        var oTarget = $('.ec-product-listwishicon');
        for (var sKey in aTagsOff) {
            oTarget.attr(sKey, aTagsOff[sKey]);
        }

        for (var i = 0 ; i < aWishList.length ; i++) {
            assignAttribute(aWishList[i]);
        }

        /**
         * oTarget 엘레먼트에 aData의 정보를 어싸인함.
         * @param array aData 위시리스트 정보
         */
        function assignAttribute(aData)
        {
            var iProductNo = aData['product_no'];
            var oTarget = $('.ec-product-listwishicon:[productno="'+iProductNo+'"]');

            // oTarget의 src, alt, icon_status attribute의 값을 할당
            for (var sKey in aTagsOn) {
                oTarget.attr(sKey, aTagsOn[sKey]);
            }
        }

    },

    /**
     * 세션스토리지 삭제
     */
    clearStorage: function()
    {
        if (!window.sessionStorage) {
            return;
        }
        window.sessionStorage.removeItem(this.STORAGE_KEY);
    },

    /**
     * sCommand에 따른 sessionStorage Set
     * @param iProductNo
     * @param sCommand 추가(add)/삭제(del) sCommand
     */
    setSessionStorageItem: function(iProductNo, sCommand)
    {
        if (this.isUse() === false) {
            return;
        }

        var oStorageData = $.parseJSON(sessionStorage.getItem(this.STORAGE_KEY));
        var aWishList = oStorageData['wishList'];
        var iLimit = 200;

        if (aWishList === null) {
            aWishList = [];
        }

        var iProductNo = parseInt(iProductNo, 10);
        var iIndex = aWishList.indexOf(iProductNo);

        if (sCommand === 'add') {
            if (aWishList.length >= iLimit) {
                aWishList.splice(aWishList.length - 1, 1);
            }
            if (iIndex < 0) {
                aWishList.unshift(iProductNo);
            }
        } else {
            if (iIndex > -1) {
                aWishList.splice(iIndex, 1);
            }
        }

        oStorageData['wishList'] = aWishList;
        sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(oStorageData));
    }
};

/**
 * 비동기식 데이터 - 관심상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Wishcount');
CAPP_ASYNC_METHODS.Wishcount = {
    __iWishCount: null,

    __$target: $('#xans_myshop_interest_prd_cnt'),
    __$target2: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_interest_prd_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        if (this.__$target2.length > 0) {
            return true;
        }

        if ( typeof EC_APPSCRIPT_SDK_DATA != "undefined" && jQuery.inArray('personal', EC_APPSCRIPT_SDK_DATA ) > -1 ) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'wishcount_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iWishCount = parseInt(aCookieValue[1], 10);
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iWishCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iWishCount + '개');
        } else {
            this.__$target.html(this.__iWishCount);
        }

        this.__$target2.html(this.__iWishCount);
    },

    getData: function()
    {
        return {
            count: this.__iWishCount
        };
    }
};
/**
 * 비동기식 데이터 - 최근 본 상품
 */
CAPP_ASYNC_METHODS.aDatasetList.push('recent');
CAPP_ASYNC_METHODS.recent = {
    STORAGE_KEY: 'localRecentProduct' +  EC_SDE_SHOP_NUM,

    __$target: $('.xans-layout-productrecent'),

    __aData: null,

    isUse: function()
    {
        this.__$target.hide();

        if (this.__$target.find('.xans-record-').length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        this.__aData = [];

        var iTotalCount = CAPP_ASYNC_METHODS.RecentTotalCount.getData();
        if (iTotalCount == 0) {
            // 총 갯수가 없는 경우 복구할 것이 없으므로 복구한 것으로 리턴
            return true;
        }

        var sAdultImage = '';

        if (window.sessionStorage === undefined) {
            return false;
        }

        var sSessionStorageData = window.sessionStorage.getItem(this.STORAGE_KEY);
        if (sSessionStorageData === null) {
            return false;
        }

        var iViewCount = EC_FRONT_JS_CONFIG_SHOP.recent_count;

        this.__aData = [];
        var aStorageData = $.parseJSON(sSessionStorageData);
        var iCount = 1;
        var bDispRecent = true;
        for (var iKey in aStorageData) {
            var sProductImgSrc = aStorageData[iKey].sImgSrc;

            if (isFinite(iKey) === false) {
                continue;
            }

            var aDataTmp = [];
            aDataTmp.recent_img = getImageUrl(sProductImgSrc);
            aDataTmp.name = aStorageData[iKey].sProductName;
            aDataTmp.disp_recent = true;
            aDataTmp.is_adult_product = aStorageData[iKey].isAdultProduct;
            aDataTmp.link_product_detail = aStorageData[iKey].link_product_detail;

            //aDataTmp.param = '?product_no=' + aStorageData[iKey].iProductNo + '&cate_no=' + aStorageData[iKey].iCateNum + '&display_group=' + aStorageData[iKey].iDisplayGroup;
            aDataTmp.param = filterXssUrlParameter(aStorageData[iKey].sParam);
            if ( iViewCount < iCount ) {
                bDispRecent = false;
            }
            aDataTmp.disp_recent = bDispRecent;

            iCount++;
            this.__aData.push(aDataTmp);
        }

        return true;

        /**
         * get SessionStorage image url
         * @param sNewImgUrl DB에 저장되어 있는 tiny값
         */
        function getImageUrl(sImgUrl)
        {
            if (typeof(sImgUrl) === 'undefined' || sImgUrl === null) {
                return;
            }
            var sNewImgUrl = '';

            if (sImgUrl.indexOf('http://') >= 0 || sImgUrl.indexOf('https://') >= 0 || sImgUrl.substr(0, 2) === '//') {
                sNewImgUrl = sImgUrl;
            } else {
                sNewImgUrl = EC_FRONT_JS_CONFIG_SHOP.cdnUrl + '/web/product/tiny/' + sImgUrl;
            }

            return sNewImgUrl;
        }

        /**
         * 파라미터 URL에서 XSS 공격 관련 파라미터를 필터링합니다. ECHOSTING-162977
         * @param string sParam 파라미터
         * @return string 필터링된 파라미터
         */
        function filterXssUrlParameter(sParam)
        {
            sParam = sParam || '';

            var sPrefix = '';
            if (sParam.substr(0, 1) === '?') {
                sPrefix = '?';
                sParam = sParam.substr(1);
            }

            var aParam = {};

            var aParamList = (sParam).split('&');
            $.each(aParamList, function() {
                var aMatch = this.match(/^([^=]+)=(.*)$/);
                if (aMatch) {
                    aParam[aMatch[1]] = aMatch[2];
                }
            });

            return sPrefix + $.param(aParam);
        }

    },

    setData: function(aData)
    {
        this.__aData = aData;

        // 쿠키엔 있지만 sessionStorage에 없는 데이터 복구
        if (window.sessionStorage) {

            var oNewStorageData = [];

            for ( var i = 0 ; i < aData.length ; i++) {
                if (aData[i].bNewProduct !== true) {
                    continue;
                }

                var aNewStorageData = {
                    'iProductNo': aData[i].product_no,
                    'sProductName': aData[i].name,
                    'sImgSrc': aData[i].recent_img,
                    'sParam': aData[i].param,
                    'link_product_detail': aData[i].link_product_detail
                };

                oNewStorageData.push(aNewStorageData);
            }

            if ( oNewStorageData.length > 0 ) {
                sessionStorage.setItem(this.STORAGE_KEY , $.toJSON(oNewStorageData));
            }
        }
    },

    execute: function()
    {
        var sAdult19Warning = EC_FRONT_JS_CONFIG_SHOP.adult19Warning;

        var aData = this.__aData;

        var aNodes = this.__$target.find('.xans-record-');
        var iRecordCnt = aNodes.length;
        var iAddedElementCount = 0;

        var aNodesParent = $(aNodes[0]).parent();
        for ( var i = 0 ; i < aData.length ; i++) {
            if (!aNodes[i]) {
                $(aNodes[iRecordCnt - 1]).clone().appendTo(aNodesParent);
                iAddedElementCount++;
            }
        }

        if (iAddedElementCount > 0) {
            aNodes = this.__$target.find('.xans-record-');
        }

        if (aData.length > 0) {
            this.__$target.show();
        }

        for ( var i = 0 ; i < aData.length ; i++) {
            assignVariables(aNodes[i], aData[i]);
        }

        // 종료 카운트 지정
        if (aData.length < aNodes.length) {
            iLength = aData.length;
            deleteNode();
        }

        recentBntInit(this.__$target);

        /**
         * 패치되지 않은 노드를 제거
         */
        function deleteNode()
        {
            for ( var i = iLength ; i < aNodes.length ; i++) {
                $(aNodes[i]).remove();
            }
        }

        /**
         * oTarget 엘레먼트에 aData의 변수를 어싸인합니다.
         * @param Element oTarget 변수를 어싸인할 엘레먼트
         * @param array aData 변수 데이터
         */
        function assignVariables(oTarget, aData)
        {
            var recentImage = aData.recent_img;

            if (sAdult19Warning === 'T' && CAPP_ASYNC_METHODS.member.getMemberIsAdult() === 'F' && aData.is_adult_product === 'T') {
                    recentImage = EC_FRONT_JS_CONFIG_SHOP.adult19BaseTinyImage;
            };

            var $oTarget = $(oTarget);

            var sHtml = $oTarget.html();

            sHtml = sHtml.replace('about:blank', recentImage)
                         .replace('##param##', aData.param)
                         .replace('##name##',aData.name)
                         .replace('##link_product_detail##', aData.link_product_detail);
            $oTarget.html(sHtml);

            if (aData.disp_recent === true) {
                $oTarget.removeClass('displaynone');
            }
        }

        function recentBntInit($target)
        {
            // 화면에 뿌려진 갯수
            var iDisplayCount = 0;
            // 보여지는 style
            var sDisplay = '';
            var iIdx = 0;
            //
            var iDisplayNoneIdx = 0;

            var nodes = $target.find('.xans-record-').each(function()
            {
                sDisplay = $(this).css('display');
                if (sDisplay != 'none') {
                    iDisplayCount++;
                } else {
                    if (iDisplayNoneIdx == 0) {
                        iDisplayNoneIdx = iIdx;
                    }

                }
                iIdx++;
            });

            var iRecentCount = nodes.length;
            var bBtnActive = iDisplayCount > 0;
            $('.xans-layout-productrecent .prev').unbind('click').click(function()
            {
                if (bBtnActive !== true) return;
                var iFirstNode = iDisplayNoneIdx - iDisplayCount;
                if (iFirstNode == 0 || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 첫번째 상품입니다.'));
                    return;
                } else {
                    iDisplayNoneIdx--;
                    $(nodes[iDisplayNoneIdx]).hide();
                    $(nodes[iFirstNode - 1]).removeClass('displaynone');
                    $(nodes[iFirstNode - 1]).fadeIn('fast');

                }
            }).css(
            {
                cursor : 'pointer'
            });

            $('.xans-layout-productrecent .next').unbind('click').click(function()
            {
                if (bBtnActive !== true) return;
                if ( (iRecentCount ) == iDisplayNoneIdx || iDisplayCount == iRecentCount) {
                    alert(__('최근 본 마지막 상품입니다.'));
                } else {
                    $(nodes[iDisplayNoneIdx]).fadeIn('fast');
                    $(nodes[iDisplayNoneIdx]).removeClass('displaynone');
                    $(nodes[ (iDisplayNoneIdx - iDisplayCount)]).hide();
                    iDisplayNoneIdx++;
                }
            }).css(
            {
                cursor : 'pointer'
            });

        }

    }
};

/**
 * 비동기식 데이터 - 최근본상품 총 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('RecentTotalCount');
CAPP_ASYNC_METHODS.RecentTotalCount = {
    __iRecentCount: null,

    __$target: CAPP_ASYNC_METHODS.$xansMyshopMain.find('.xans_myshop_main_recent_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }

        return false;
    },

    restoreCache: function()
    {
        var sCookieName = 'recent_plist';
        if (EC_SDE_SHOP_NUM > 1) {
            sCookieName = 'recent_plist' + EC_SDE_SHOP_NUM;
        }
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            this.__iRecentCount = decodeURI(aCookieValue[1]).split('|').length;
        } else {
            this.__iRecentCount = 0;
        }
    },

    execute: function()
    {
        this.__$target.html(this.__iRecentCount);
    },

    getData: function()
    {
        if (this.__iRecentCount === null) {
            // this.isUse값이 false라서 복구되지 않았는데 이 값이 필요한 경우 복구
            this.restoreCache();
        }

        return this.__iRecentCount;
    }
};
/**
 * 비동기식 데이터 - 주문정보
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Order');
CAPP_ASYNC_METHODS.Order = {
    __iOrderCount: null,
    __iOrderTotalPrice: null,
    __iGradeIncreaseValue: null,

    __$target: $('#xans_myshop_bankbook_order_count'),
    __$target2: $('#xans_myshop_bankbook_order_price'),
    __$target3: $('#xans_myshop_bankbook_grade_increase_value'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }
        }
        
        return false;        
    },

    restoreCache: function()
    {
        var sCookieName = 'order_' + EC_SDE_SHOP_NUM;
        var re = new RegExp('(?:^| |;)' + sCookieName + '=([^;]+)');
        var aCookieValue = document.cookie.match(re);
        if (aCookieValue) {
            var aData = jQuery.parseJSON(decodeURIComponent(aCookieValue[1]));
            this.__iOrderCount = aData.total_order_count;
            this.__iOrderTotalPrice = aData.total_order_price;
            this.__iGradeIncreaseValue = Number(aData.grade_increase_value);
            return true;
        }

        return false;
    },

    setData: function(aData)
    {
        this.__iOrderCount = aData['total_order_count'];
        this.__iOrderTotalPrice = aData['total_order_price'];
        this.__iGradeIncreaseValue = Number(aData['grade_increase_value']);
    },

    execute: function()
    {
        this.__$target.html(this.__iOrderCount);
        this.__$target2.html(this.__iOrderTotalPrice);
        this.__$target3.html(this.__iGradeIncreaseValue);
    },

    getData: function()
    {
        return {
            total_order_count: this.__iOrderCount,
            total_order_price: this.__iOrderTotalPrice,
            grade_increase_value: this.__iGradeIncreaseValue
        };
    }
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Benefit');
CAPP_ASYNC_METHODS.Benefit = {
    __aBenefit: null,
    __$target: $('.xans-myshop-asyncbenefit'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aBenefit = aData;
    },

    execute: function()
    {
        var aFilter = ['group_image_tag', 'group_icon_tag', 'display_no_benefit', 'display_with_all', 'display_mobile_use_dc', 'display_mobile_use_mileage'];
        var __aData = this.__aBenefit;
        
        // 그룹이미지
        $('.myshop_benefit_group_image_tag').attr({alt: __aData['group_name'], src: __aData['group_image']});

        // 그룹아이콘
        $('.myshop_benefit_group_icon_tag').attr({alt: __aData['group_name'], src: __aData['group_icon']});

        if (__aData['display_no_benefit'] === true) {
            $('.myshop_benefit_display_no_benefit').removeClass('displaynone').show();
        }
        
        if (__aData['display_with_all'] === true) {
            $('.myshop_benefit_display_with_all').removeClass('displaynone').show();
        }
        
        if (__aData['display_mobile_use_dc'] === true) {
            $('.myshop_benefit_display_mobile_use_dc').removeClass('displaynone').show();
        } 
        
        if (__aData['display_mobile_use_mileage'] === true) {
            $('.myshop_benefit_display_mobile_use_mileage').removeClass('displaynone').show();
        }

        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                $('.myshop_benefit_' + key).html(val);
            }
        });
    }    
};
/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('BasketLayer');
CAPP_ASYNC_METHODS.BasketLayer = {
    __sBasketLayerHtml: null,
    __$target: $('#ec_async_basket_layer_container'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        $.ajax({
            url: '/order/async_basket_layer.html?__popupPage=T',
            async: false,
            success: function(data) {
                var sBasketLayerHtml = data;
                var sBasketLayerStyle = '';
                var sBasketLayerBody = '';

                sBasketLayerHtml = sBasketLayerHtml.replace(/<script([\s\S]*?)<\/script>/gi,''); // 스크립트 제거
                sBasketLayerHtml = sBasketLayerHtml.replace(/<link([\s\S]*?)\/>/gi,''); // 옵티마이져 제거

                var regexStyle = /<style([\s\S]*?)<\/style>/; // Style 추출
                if (regexStyle.exec(sBasketLayerHtml) != null) sBasketLayerStyle = regexStyle.exec(sBasketLayerHtml)[0];

                var regexBody = /<body[\s\S]*?>([\s\S]*?)<\/body>/; // Body 추출
                if (regexBody.exec(sBasketLayerHtml) != null) sBasketLayerBody = regexBody.exec(sBasketLayerHtml)[1];

                CAPP_ASYNC_METHODS.BasketLayer.__sBasketLayerHtml = sBasketLayerStyle + sBasketLayerBody;
            }
        });
        this.__$target.html(this.__sBasketLayerHtml);
    }
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Grade');
CAPP_ASYNC_METHODS.Grade = {
    __aGrade: null,
    __$target: $('#sGradeAutoDisplayArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;
        var aFilter = ['bChangeMaxTypePrice', 'bChangeMaxTypePriceAndCount', 'bChangeMaxTypePriceOrCount', 'bChangeMaxTypeCount'];

        var aMaxDisplayJson = {
            "bChangeMaxTypePrice": [
                {"sId": "sChangeMaxTypePriceArea"}
            ],
            "bChangeMaxTypePriceAndCount": [
                {"sId": "sChangeMaxTypePriceAndCountArea"}
            ],
            "bChangeMaxTypePriceOrCount": [
                {"sId": "sChangeMaxTypePriceOrCountArea"}
            ],
            "bChangeMaxTypeCount": [
                {"sId": "sChangeMaxTypeCountArea"}
            ]
        };

        if ($('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                $('.sNextGroupIconArea').removeClass('displaynone').show();
                $('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                $('.sNextGroupIconArea').addClass('displaynone');
            }
        }

        var sIsAutoGradeDisplay = "F";
        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                return true;
            }
            if (val === true) {
                if ($('#'+aMaxDisplayJson[key][0].sId).length > 0) {
                    $('#' + aMaxDisplayJson[key][0].sId).removeClass('displaynone').show();
                }
                sIsAutoGradeDisplay = "T";
            }
        });
        if (sIsAutoGradeDisplay == "T" && $('#sGradeAutoDisplayArea .sAutoGradeDisplay').length > 0) {
            $('#sGradeAutoDisplayArea .sAutoGradeDisplay').addClass('displaynone');
        }

        $.each(__aData, function(key, val) {
            if ($.inArray(key, aFilter) === -1) {
                if ($('.xans-member-var-' + key).length > 0) {
                    $('.xans-member-var-' + key).html(val);
                }
            }
        });
    }    
};
/**
 * 비동기식 데이터 - Benefit
 */
CAPP_ASYNC_METHODS.aDatasetList.push('AutomaticGradeShow');
CAPP_ASYNC_METHODS.AutomaticGradeShow = {
    __aGrade: null,
    __$target: $('#sAutomaticGradeShowArea'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }
        }
        return false;
    },

    setData: function(aData)
    {
        this.__aGrade = aData;
    },

    execute: function()
    {
        var __aData = this.__aGrade;

        /**
         * 아이콘 표기 제외
        if ($('.sNextGroupIconArea').length > 0) {
            if (__aData['bDisplayNextGroupIcon'] === true) {
                $('.sNextGroupIconArea').removeClass('displaynone').show();
                $('.myshop_benefit_next_group_icon_tag').attr({alt: __aData['sNextGrade'], src: __aData['sNextGroupIcon']});
            } else {
                $('.sNextGroupIconArea').addClass('displaynone');
            }
        }
         */

        var sIsAutoGradeDisplay = "F";
        $.each(__aData, function(key, val) {
            if (val === true) {
                sIsAutoGradeDisplay = "T";
                return false;
            }
        });
        if (sIsAutoGradeDisplay == "T" && $('#sAutomaticGradeShowArea .sAutoGradeDisplay').length > 0) {
            $('#sAutomaticGradeShowArea .sAutoGradeDisplay').addClass('displaynone');
        }

        $.each(__aData, function(key, val) {
            if ($('.xans-member-var-' + key).length > 0) {
                $('.xans-member-var-' + key).html(val);
            }
        });
    }    
};
/**
 * 비동기식 데이터 - 비동기장바구니 레이어
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MobileMutiPopup');
CAPP_ASYNC_METHODS.MobileMutiPopup = {
    __$target: $('div[class^="ec-async-multi-popup-layer-container"]'),

    isUse: function()
    {
        if (this.__$target.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        for (var i=0; i < this.__$target.length ; i++) {
            $.ajax({
                url: '/exec/front/popup/AjaxMultiPopup?index='+i,
                data : EC_ASYNC_MULTI_POPUP_OPTION[i],
                dataType: "json",
                success : function (oResult) {
                    switch (oResult.code) {
                        case '0000' :
                            if (oResult.data.length < 1) {
                                break;
                            }
                            $('.ec-async-multi-popup-layer-container-' + oResult.data.html_index).html(oResult.data.html_text);
                            if (oResult.data.type == 'P') {
                                BANNER_POPUP_OPEN.setPopupSetting();
                                BANNER_POPUP_OPEN.setPopupWidth();
                                BANNER_POPUP_OPEN.setPopupClose();
                            } else {
                                /**
                                 * 이중 스크롤 방지 클래스 추가(비동기) 
                                 *
                                 */
                                $('body').addClass('eMobilePopup');
                                $('body').width('100%');

                                BANNER_POPUP_OPEN.setFullPopupSetting();
                                BANNER_POPUP_OPEN.setFullPopupClose();
                            }
                            break;
                        default :
                            break;
                    }
                },
                error : function () {
                }
            });
        }
    }
};
/**
 * 비동기식 데이터 - 좋아요 상품 갯수
 */
CAPP_ASYNC_METHODS.aDatasetList.push('MyLikeProductCount');
CAPP_ASYNC_METHODS.MyLikeProductCount = {
    __iMyLikeCount: null,

    __$target: $('#xans_myshop_like_prd_cnt'),

    isUse: function()
    {
        if (this.__$target.length > 0 && SHOP.getLanguage() === 'ko_KR') {
            return true;
        }

        return false;
    },

    setData: function(sData)
    {
        this.__iMyLikeCount = Number(sData);
    },

    execute: function()
    {
        if (SHOP.getLanguage() === 'ko_KR') {
            this.__$target.html(this.__iMyLikeCount + '개');
        }
    }
};
/**
 * 라이브 링콘 on/off이미지
 */
CAPP_ASYNC_METHODS.aDatasetList.push('Livelinkon');
CAPP_ASYNC_METHODS.Livelinkon = {
    __$target: $('#ec_livelinkon_campain_on'),
    __$target2: $('#ec_livelinkon_campain_off'),

    isUse: function()
    {
        if (this.__$target.length > 0 && this.__$target2.length > 0) {
            return true;
        }
        return false;
    },

    execute: function()
    {
        var sCampaignid = '';
        if (EC_ASYNC_LIVELINKON_ID != undefined) {
            sCampaignid = EC_ASYNC_LIVELINKON_ID
        }
        $.ajax({
            url: '/exec/front/Livelinkon/Campaignajax?campaign_id='+sCampaignid,
            async: false,
            success: function(data) {
                if (data == 'on') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').show();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                } else if (data == 'off') {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').show();
                } else {
                    CAPP_ASYNC_METHODS.Livelinkon.__$target.removeClass('displaynone').hide();
                    CAPP_ASYNC_METHODS.Livelinkon.__$target2.removeClass('displaynone').hide();
                }
            }
        });
    }
};
/**
 * 비동기식 데이터 - 마이쇼핑 > 주문 카운트 (주문 건수 / CS건수 / 예전주문)
 */
CAPP_ASYNC_METHODS.aDatasetList.push('OrderHistoryCount');
CAPP_ASYNC_METHODS.OrderHistoryCount = {
    __sTotalOrder: null,
    __sTotalOrderCs: null,
    __sTotalOrderOld: null,

    __$target: $('#ec_myshop_total_orders'),
    __$target2: $('#ec_myshop_total_orders_cs'),
    __$target3: $('#ec_myshop_total_orders_old'),

    isUse: function()
    {
        if (CAPP_ASYNC_METHODS.IS_LOGIN === true) {
            if (this.__$target.length > 0) {
                return true;
            }

            if (this.__$target2.length > 0) {
                return true;
            }

            if (this.__$target3.length > 0) {
                return true;
            }
        }

        return false;
    },

    setData: function(aData)
    {
        this.__sTotalOrder = aData['total_orders'];
        this.__sTotalOrderCs = aData['total_orders_cs'];
        this.__sTotalOrderOld = aData['total_orders_old'];

    },

    execute: function()
    {
        this.__$target.html(this.__sTotalOrder);
        this.__$target2.html(this.__sTotalOrderCs);
        this.__$target3.html(this.__sTotalOrderOld);
    }
};
var PathRoleValidator = (function() {
    /**
     * Milage, Deposit 의 경우 처리되지 말아야할 페이지 확인
     * @returns {boolean}
     */
    function isInvalidPathRole()
    {
        // path role
        var sCurrentPathRole = null;

        // // euckr 환경에서 path role 획득
        if (SHOP.getProductVer() === 1) {
            // path 와 role 매핑
            var aPathRoleMap = {
                '/myshop/index.html': 'MYSHOP_MAIN',
                '/myshop/mileage/historyList.html': 'MYSHOP_MILEAGE_LIST',
                '/myshop/deposits/historyList.html': 'MYSHOP_DEPOSIT_LIST',
                '/order/orderform.html': 'ORDER_ORDERFORM'
            };

            // 페이지 경로로부터 path role 획득
            sCurrentPathRole = aPathRoleMap[document.location.pathname];

            // utf8 환경에서 path role 획득
        } else {
            // 현재 페이지 path role 획득
            sCurrentPathRole = $('meta[name="path_role"]').attr('content');
        }

        // 처리되면 안되는 경로
        var aInvalidPathRole = [
            'MYSHOP_MAIN',
            'MYSHOP_MILEAGE_LIST',
            'MYSHOP_DEPOSIT_LIST',
            'ORDER_ORDERFORM'
        ];

        return $.inArray(sCurrentPathRole, aInvalidPathRole) >= 0;
    }

    return {
        isInvalidPathRole: isInvalidPathRole
    };
})();
$(document).ready(function()
{
	CAPP_ASYNC_METHODS.init();
});
var EC_MANAGE_PRODUCT_RECENT = {
    getRecentImageUrl : function()
    {
        var sStorageKey = 'localRecentProduct' + EC_SDE_SHOP_NUM;

        if (typeof(sessionStorage[sStorageKey]) !== 'undefined') {
            var sRecentData = sessionStorage.getItem(sStorageKey);
            var oJsonData = JSON.parse(sRecentData);
            var sImageSrc = '';

            if (oJsonData[0] !== undefined) {
                sImageSrc = oJsonData[0].sImgSrc;
            }
            
            document.location.replace('recentproduct://setinfo?simg_src=' + sImageSrc);
        }
    }
};

