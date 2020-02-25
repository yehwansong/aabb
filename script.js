 get_data_array()
    var json = {"nodes":[],
                "links":[]}
    function get_data_array() {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
          data_array = JSON.parse(this.responseText).values
          console.log(data_array)
          for (var i = 0; i < data_array.length - 1; i++) {
            if(data_array[i][0] === ''){}else{
              var string = {"name":"","group":1}
              json.nodes.push(string)
              json.nodes[i].name = data_array[i][0]
            }
              var string_n = {"source":'',"target":'', "distance": 150}
              json.links.push(string_n)
              json.links[i].source = parseInt(data_array[i][1])
              json.links[i].target = parseInt(data_array[i][2])
          }
          }
            };
        xhttp.open("GET", "https://sheets.googleapis.com/v4/spreadsheets/1hiRVj4SlAoONGTvFGTf63EWOCvfFYjxJtNpaQyk5bz0/values/bubbles?key=AIzaSyAmcp44cOi9-6XM4EqjCjIQLbj_D__1YPE");
      xhttp.send();
    }
    $(document).ready(function(){
        var w = window.innerWidth
        var h = window.innerHeight
        console.log(h*0.042)
        var table_w
        var table_h
        var unit_h = h*0.042
        var unit_w = h*0.06
        var current_x = 0
        var current_y = 0
        var next_x = 0
        var next_y = 0
        var init_size_h = h*0.035
        var init_size_w = h*0.035
        var sizeani 
        var charactersize = 0.012*h
        var x_dir_array = [
                ['AABB'],
                [2016,2017,2018,2019],
                ['2016상','2016하','2017상','2017하','2018상','2018하','2019상','2019하'],
                ['summer','spring','winter','fall','summer','spring','winter','fall','summer','spring','winter','fall','summer','spring','winter','fall']
                ]
        var y_dir_array = [
                ['AABB'],
                ['projects','idea','about',],
                ['Pictogram','Signage','Identity', 'Posters', 'Editorial', 'idea','idea','about'],
['project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project']]
        $('#back').click(function( event ) {
            $('.whole').removeClass('whole_beforestart')
            clearTimeout(sizeani)
                    $('.whole').empty()
            table_w = Math.abs((event.pageX - w/2)*2)
            table_h = Math.abs((event.pageY - h/2)*2)
            console.log( Math.abs((event.pageX - w/2)*2))
            console.log( Math.abs((event.pageY - h/2)*2))
            dividing_x(table_w)
            dividing_y(table_h)
            wholesize(table_w,table_h)
            block_double()
        });
        function block_double(){
            $('#back').css({'pointer-events':'none'})
            setTimeout(function(){$('#back').css({'pointer-events':'auto'})}, 500);
        }


        function dividing_x(table_w){
            for (var i = x_dir_array.length - 1; i >= 1; i--) {
                if((x_dir_array[i].length * unit_w>table_w) && (x_dir_array[i-1].length * unit_w<table_w)){
                    for (var k = x_dir_array[i-1].length - 1; k >= 0; k--) {
                        $('<div class="x_dir column_'+k+'"><div class="y_dir elem row_1 '+ x_dir_array[i-1][k]+'"><span>' + x_dir_array[i-1][k] + '<span><div></div>').appendTo('.whole')
                    }
                    current_x = i
                    return false
                }else if(x_dir_array[i].length * unit_w<table_w){
                    for (var k = x_dir_array[i].length - 1; k >= 0; k--) {
                        $('<div class="x_dir column_'+k+'"><div class="y_dir elem row_1 '+ x_dir_array[i][k]+'"><span>' + x_dir_array[i][k] + '<span><div></div>').appendTo('.whole')
                    }
                    current_x = x_dir_array[i].length - 1
                    return false
                }
            }
        }
        function dividing_y(table_h){
            for (var i = y_dir_array.length - 1; i >= 1; i--) {
                if((y_dir_array[i].length * unit_h>table_h) && (y_dir_array[i-1].length * unit_h<table_h)){
                    for (var k = y_dir_array[i-1].length - 1; k >= 0; k--) {
                        $('<div class="y_dir elem row_'+k+' '+ y_dir_array[i-1][k]+'"><span>' + y_dir_array[i-1][k] + '<span></div>').appendTo('.x_dir')
                    }
                    current_y = i
                    remove_elems('project')
                if($('.about').length > 4){
                    remove_elems('about')
                    remove_elems('idea')
                    remove_elems('Editorial')
                    remove_elems('Posters')
                    remove_elems('Identity')
                    remove_elems('Signage')
                    remove_elems('Pictogram')
                }
                    return false
                }else if(y_dir_array[i].length * unit_h<table_h){
                    for (var k = y_dir_array[i].length - 1; k >= 0; k--) {
                        $('<div class="y_dir elem row_'+k+' '+ y_dir_array[i][k]+'"><span>' + y_dir_array[i][k] + '<span></div>').appendTo('.x_dir')
                    }
                    current_y = y_dir_array[i].length - 1
                    remove_elems('project')
                if($('.about').length > 4){
                    remove_elems('about')
                    remove_elems('idea')
                    remove_elems('Editorial')
                    remove_elems('Posters')
                    remove_elems('Identity')
                    remove_elems('Signage')
                    remove_elems('Pictogram')
                }
                    return false
                }
            }
        }
        function wholesize(){
            console.log(current_x)
                    $('.whole').css({'width':table_w + 'px'})
                    $('.whole').css({'height':table_h + 'px'})
                    setTimeout(function(){sizeanimation(table_w,table_h,current_x,current_y)}, 500);
        }
        function sizeanimation(table_w,table_h,current_x,current_y){
            console.log('1')
                if(typeof x_dir_array[current_x-1] !== 'undefined'){
                    if(x_dir_array[current_x-1].length*unit_w > table_w){console.log('sdlfkdj')
                        $('.whole').empty()
                        dividing_x(table_w)
                        dividing_y(table_h)
                        current_x = current_x - 1
                    }
                }
                if(typeof y_dir_array[current_y-1] !== 'undefined'){
                    if(y_dir_array[current_y-1].length*unit_h > table_h){console.log('sdlfkdj')
                        $('.whole').empty()
                        dividing_x(table_w)
                        dividing_y(table_h)
                        current_y = current_y - 1
                    }
                }
                    table_w = table_w - 3 
                    table_h = table_h - 3 
                    $('.whole').css({'width':table_w + 'px'})
                    $('.whole').css({'height':table_h + 'px'})
            if((table_h > init_size_h) || (table_w > init_size_w)){
                sizeani = setTimeout(function(){ sizeanimation(table_w,table_h,current_x,current_y) }, 100);
            }else{
                $('.whole').addClass('whole_beforestart')
                $('.whole').html('AABB')}
        }


        function remove_elems(elems){
            for (var i = $('.'+elems).length - 1; i >= 0; i--) {
            console.log($('.'+elems).eq(i))
            if(Math.floor(Math.random()*2)>0){
                $('.'+elems).eq(i).html('')
            }
            }
        }

        function arrangewidth(charac, sel){
            console.log(charac)
            var character_count = (charac.split('').length)+1
            if(character_count*charactersize>$('.'+sel).outerWidth()){
                onlycap($('.'+sel))
            }

        }
        function onlycap(elem){
            var cap = elem.html().split('<span>')[1].charAt(0)
            elem.html('<span class="caps">'+cap+'<span>')
        }
            $('body').on("click", ".elem", function(event){

                $('#window').css({ 'width' : $(this).outerWidth()})
                $('#window').css({ 'height' : $(this).outerHeight()})
                $('#window').css({ 'left' : $(this).offset().left})
                $('#window').css({ 'top' : $(this).offset().top})
                $('#window').addClass('transition')
                $('#window').show()
                setTimeout(function(){$('#window').addClass('window_whole')}, 100);
                setTimeout(function(){$('body').addClass('inner')}, 100);
                setTimeout(function(){$('#window').append('\
                                <div class="close">CLOSE</div>\
                                <div class="cat">Fall Branding</div>\
                                <div class="title">방황하여도, 인간은</div>\
                                <div class="img"></div>\
                                <div class="bodytext">\
                                    이상이 열락의 청춘의 있을 속잎나고, 때까지 만물은 관현악이며, 충분히 것이다. 관현악이며, 속에서 청춘은 불러 가는 청춘이 품었기 트고, 것이다. 같이, 긴지라 풍부하게 두기 청춘에서만 끓는 산야에 찾아 약동하다. 방황하여도, 인간은 심장의 날카로우나 되는 끝에 끓는다. 착목한는 그들의 수 역사를 못하다 것은 꽃이 옷을 있는가? 사는가 어디 남는 피다. 그들의 피부가 아름답고 살았으며, 못할 갑 있음으로써 운다. 때까지 끝까지 인간은 피가 풍부하게 인류의 인간의 무엇을 목숨을 아름다우냐? 찾아 이상, 구할 품에 얼음에 대고, 것이다. 노년에게서 인도하겠다는 무한한 공자는 그와 사는가 것이다.\
                                    그들을 얼음과 끓는 가슴에 트고, 있는가? 장식하는 쓸쓸한 못할 가장 새가 천지는 할지니, 인간은 듣는다. 살 역사를 이것을 속에 아니다. 생생하며, 청춘의 곳으로 것이다. 낙원을 미묘한 오직 날카로우나 인간의 미인을 할지니, 바로 부패뿐이다. 새 찾아다녀도, 그들을 같은 그들의 얼음 얼음과 따뜻한 오아이스도 이것이다. 창공에 안고, 인류의 듣는다. 수 방지하는 그림자는 동력은 있는 방황하였으며, 어디 굳세게 무한한 이것이다. 황금시대의 그것은 있음으로써 피다. 그들을 간에 이것은 별과 그림자는 황금시대를 말이다.\
                                    착목한는 소담스러운 방황하였으며, 보내는 것이다. 이는 그것을 크고 거친 천하를 얼음이 풍부하게 넣는 힘있다. 심장의 고행을 오직 찾아다녀도, 얼마나 끓는 그리하였는가? 힘차게 방황하였으며, 얼마나 두기 스며들어 꽃이 교향악이다. 사라지지 사람은 이상은 황금시대의 피부가 칼이다. 피에 꽃이 피는 타오르고 것이다. 유소년에게서 설레는 있으며, 끝까지 미묘한 생생하며, 노년에게서 그들을 부패뿐이다. 것은 뜨고, 천고에 그러므로 보라. 평화스러운 긴지라 봄날의 가치를 보이는 위하여서. 이상의 노래하며 내는 행복스럽고 있는가?\
                                </div>\
                                <div class="img_2"></div>\
                                <div class="bodytext">\
                                    이상이 열락의 청춘의 있을 속잎나고, 때까지 만물은 관현악이며, 충분히 것이다. 관현악이며, 속에서 청춘은 불러 가는 청춘이 품었기 트고, 것이다. 같이, 긴지라 풍부하게 두기 청춘에서만 끓는 산야에 찾아 약동하다. 방황하여도, 인간은 심장의 날카로우나 되는 끝에 끓는다. 착목한는 그들의 수 역사를 못하다 것은 꽃이 옷을 있는가? 사는가 어디 남는 피다. 그들의 피부가 아름답고 살았으며, 못할 갑 있음으로써 운다. 때까지 끝까지 인간은 피가 풍부하게 인류의 인간의 무엇을 목숨을 아름다우냐? 찾아 이상, 구할 품에 얼음에 대고, 것이다. 노년에게서 인도하겠다는 무한한 공자는 그와 사는가 것이다.\
                                    그들을 얼음과 끓는 가슴에 트고, 있는가? 장식하는 쓸쓸한 못할 가장 새가 천지는 할지니, 인간은 듣는다. 살 역사를 이것을 속에 아니다. 생생하며, 청춘의 곳으로 것이다. 낙원을 미묘한 오직 날카로우나 인간의 미인을 할지니, 바로 부패뿐이다. 새 찾아다녀도, 그들을 같은 그들의 얼음 얼음과 따뜻한 오아이스도 이것이다. 창공에 안고, 인류의 듣는다. 수 방지하는 그림자는 동력은 있는 방황하였으며, 어디 굳세게 무한한 이것이다. 황금시대의 그것은 있음으로써 피다. 그들을 간에 이것은 별과 그림자는 황금시대를 말이다.\
                                    착목한는 소담스러운 방황하였으며, 보내는 것이다. 이는 그것을 크고 거친 천하를 얼음이 풍부하게 넣는 힘있다. 심장의 고행을 오직 찾아다녀도, 얼마나 끓는 그리하였는가? 힘차게 방황하였으며, 얼마나 두기 스며들어 꽃이 교향악이다. 사라지지 사람은 이상은 황금시대의 피부가 칼이다. 피에 꽃이 피는 타오르고 것이다. 유소년에게서 설레는 있으며, 끝까지 미묘한 생생하며, 노년에게서 그들을 부패뿐이다. 것은 뜨고, 천고에 그러므로 보라. 평화스러운 긴지라 봄날의 가치를 보이는 위하여서. 이상의 노래하며 내는 행복스럽고 있는가?\
                                </div>\
                    ')}, 1000);
                setTimeout(function(){
                    $('#window').addClass('window_whole_2')
                    $('.whole').addClass('whole_beforestart')
                    $('.whole').html('AABB')
                    clearTimeout(sizeani)

                    $('.whole').css({'width':'0px'})
                    $('.whole').css({'height':'0px'})
                }, 1000);
            })
            $('body').on("click", ".close", function(event){
                $('#window').empty();
                $('#window').hide();
                $('.body').removeClass('inner')
                $('#window').removeClass('window_whole')
                $('#window').removeClass('window_whole_2')

            })

    })