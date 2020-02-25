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
        var table_w
        var table_h
        var unit_h = 100
        var unit_w = 100
        var current_x = 0
        var current_y = 0
        var next_x = 0
        var next_y = 0
        var sizeani 
        var charactersize = 0.012*h
        var x_dir_array = [
                ['AABB'],
                [2016,2017,2018,2019],
                ['summer','spring','winter','fall'],
                ['January','February','March','April','May','June','July','August','September','October','November','December']]
        var y_dir_array = [
                ['AABB'],
                ['about','idea','projects'],
                ['about','idea','idea','Editorial', 'Posters', 'Identity', 'Signage', 'Pictogram'],
['project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project','project']]
        $('#back').click(function( event ) {
            clearTimeout(sizeani)
                    $('.whole').empty()
            table_w = Math.abs((event.pageX - w/2)*2)
            table_h = Math.abs((event.pageY - h/2)*2)
            console.log( Math.abs((event.pageX - w/2)*2))
            console.log( Math.abs((event.pageY - h/2)*2))
            dividing_x(table_w)
            dividing_y(table_h)
            wholesize(table_w,table_h)
        });



        function dividing_x(table_w){
            if(typeof x_dir_array[current_x+1] !== 'undefined'){
                if((x_dir_array[current_x].length * unit_w < table_w) ){
                    next_x = current_x + 1 
                }
            }
            if(typeof x_dir_array[current_x-1] !== 'undefined'){
                if(x_dir_array[current_x-1].length * unit_w > table_w){
                    next_x = current_x - 1 
                }
            }
            var insertelem = ''
            for (var i = 0; i < x_dir_array[next_x].length; i++) {
                $('<div class="x_dir column_'+i+'"><div class="'+ x_dir_array[next_x][i]+' row_1"><span>' + x_dir_array[next_x][i] + '<span></div></div>').appendTo('.whole')

                if(i == x_dir_array[next_x].length-1){
                    current_x = next_x
                }
            }
        }
        function dividing_y(table_h){
            var about_wrap = false
            if(typeof y_dir_array[current_y+1] !== 'undefined'){
                if((y_dir_array[current_y].length * unit_h < table_h) ){
                    next_y = current_y + 1 
                }else{

                }
            }
            if(typeof y_dir_array[current_y-1] !== 'undefined'){
                if(y_dir_array[current_y-1].length * unit_h > table_h){
                    next_y = current_y - 1 
                }
            }
            var insertelem = ''
            for (var i = 0; i < y_dir_array[next_y].length; i++) {
                $('<div class="elem y_dir row_'+(i+2)+' '+y_dir_array[next_y][i]+'" ><span>' + y_dir_array[next_y][i] + '</span></div>').appendTo('.x_dir')

                if(next_y == 2 && i > 1){
                    $('.elem.y_dir.row_'+(i+2)).addClass('subcat')
                }


                arrangewidth(y_dir_array[next_y][i], 'row_'+(i+2))

                if(i == y_dir_array[next_y].length-1){
                    current_y = next_y
                    remove_project()
                }
            }
        }
        function wholesize(){
                    $('.whole').css({'width':table_w + 'px'})
                    $('.whole').css({'height':table_h + 'px'})
                    setTimeout(function(){sizeanimation(table_w,table_h,current_x,current_y)}, 500);
        }
        function sizeanimation(table_w,table_h,current_x,current_y){
                console.log(current_x)
                console.log(current_y)
            if(x_dir_array[current_x].length*unit_w < table_w){
                current_x--
            }
            if(y_dir_array[current_y].length*unit_h < table_h){
                current_y--
            }
                table_w = table_w - 3 
                table_h = table_h - 3 
                $('.whole').css({'width':table_w + 'px'})
                $('.whole').css({'height':table_h + 'px'})
            sizeani = setTimeout(function(){ sizeanimation(table_w,table_h,current_x,current_y) }, 100);
        }
        function remove_project(){
            for (var i = $('.project').length - 1; i >= 0; i--) {
            console.log($('.project').eq(i))
            if(Math.floor(Math.random()*2)>0){
                $('.project').eq(i).html('')
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
            window_open()
        }
        function window_open(){
            $('.elem').click(function(){
                $('#window').css({ 'width' : $(this).outerWidth()})
                $('#window').css({ 'height' : $(this).outerHeight()})
                $('#window').css({ 'left' : $(this).offset().left})
                $('#window').css({ 'top' : $(this).offset().top})
                $('#window').addClass('transition')
                $('#window').show()
                setTimeout(function(){$('#window').addClass('window_whole')}, 100);
            })
        }

    })