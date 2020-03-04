$('document').ready(function(){
    var h = window.innerHeight
    var dragstart = false
    var init_pos
    var init_width 
    var updated_pos
    var percentage = [0, 10, 20, 15, 60, 40, 70, 65, 57, 80, 90, 85, 95, 40, 70, 65, 57, 80, 90, 85, 95, 80, 10, 20, 15, 60, 80,0, 40, 70, 65, 57, 80, 90, 85, 95, 80, 10, 20, 15, 60]
    $('.bar').mousedown(function(e){
        init_width = $('.bar').outerWidth()
        dragstart = true
        init_pos_w = e.pageX
        console.log('hey')
    })
    $(window).mousemove(function(e){
        if(dragstart){
            updated_pos_w = e.pageX
            var graph_w = updated_pos_w - init_pos_w + init_width
            $('.bar').css({'width': updated_pos_w - init_pos_w + init_width})
            for (var i = $('.elem').length - 1; i >= 0; i--) {
                $('.elem').eq(i).css({'margin-left':(Math.floor((graph_w * (percentage[i] / 100)) / (0.02*h)))*(0.02*h)})
            }
            if(graph_w> 0.06*h){
                $('.cat_1').hide()
                $('.cat_2').show()
            }else{
                $('.cat_1').show()
                $('.cat_2').hide()
            }
            if(graph_w> 0.8*h){
                $('.bot_cat_1').css({'margin-top':-0.04*h})
                $('.bot_cat_2').css({'margin-top':-0.04*h})
                $('.bot_cat_3').css({'margin-top':-0.02*h})
                $('.bot_cat_4').css({'margin-top':-0.02*h})
            }else if(graph_w> 0.6*h){
                $('.bot_cat_1').css({'margin-top':-0.04*h})
                $('.bot_cat_2').css({'margin-top':-0.04*h})
                $('.bot_cat_3').css({'margin-top':-0.02*h})
                $('.bot_cat_4').css({'margin-top':-0.00*h})
            }else if(graph_w> 0.4*h){
                $('.bot_cat_1').css({'margin-top':-0.04*h})
                $('.bot_cat_2').css({'margin-top':-0.02*h})
                $('.bot_cat_3').css({'margin-top':-0.00*h})
                $('.bot_cat_4').css({'margin-top':-0.00*h})
            }else if(graph_w> 0.2*h){
                $('.bot_cat_1').css({'margin-top':-0.02*h})
                $('.bot_cat_2').css({'margin-top':-0.00*h})
                $('.bot_cat_3').css({'margin-top':-0.00*h})
                $('.bot_cat_4').css({'margin-top':-0.00*h})
            }
        }
    })
    $(window).mouseup(function(){
        dragstart = false
    })
    // $('elem').
})