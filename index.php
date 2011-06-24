<?php session_start();ob_start();error_reporting(E_PARSE);
require(dirname(__FILE__)."/config.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
    <title><?php echo $config['pagetitle']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/base/jquery-ui.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="airjostats.css" type="text/css" media="screen" />
<!--
It's generally not a bad idea to replace googleapis links with local files.
-->
</head>
<?php
    if ($_POST['pass'] == $config['adminpass']) {
        if ($config['adminpass'] != 'admin_pass') {
            $_SESSION['is_admin'] = 1;
        }
    }

    if ($_SESSION['is_admin'] != 1) {
        $config['charts'] = array_intersect_key($config['charts'],
            array_combine($config['guestcharts'],$config['guestcharts'])
            );
    }
?>
<body>
<div id="airjo" class="round">
<h1><?php echo $config['pagehead']; ?></h1>
<div class="panel">
    <div class="buttons">
        <select class="chart">
        <?php
            foreach ($config['charts'] as $k => $v) {
                echo sprintf('<option value="%s">%s</option>',$k,$v);
            }
        ?>
        </select>
        <input class="date" size="10" type="text" />
        <select class="server">
            <option value="">All</option>
        <?php
            foreach (array_keys($config['servers']) as $k => $v) {
                echo sprintf('<option value="%s">%s</option>',$k,$v);
            }
        ?>
        </select>
        <select class="smooth">
            <option value="1">No averaging</option>
            <option value="2">2 min average</option>
            <option value="5" selected="selected">5 min average</option>
            <option value="10">10 min average</option>
            <option value="20">20 min average</option>
            <option value="30">30 min average</option>
            <option value="60">60 min average</option>
        </select>
        <input class="legend" type="checkbox" checked="checked" />Legend
        <input class="scale" type="checkbox" checked="checked" />Scale&nbsp;image
        <select class="style">
        <?php
            foreach ($config['chart_types'] as $k => $v) {
                echo sprintf('<option value="%s">%s</option>',$v,$v);
            }
        ?>
        </select>
        <input class="reload" type="button" value="Refresh" />
<?php if ($_SESSION['is_admin'] != 1): ?>
        <div style="display:inline-block">
            <div class="key"></div>
            <div class="menu">
                <form action="" method="post">
                    <input name="pass" type="password" value="" />
                    <input type="submit" value="Unlock" />
                </form>
            </div>
        </div>
<?php endif ?>
    </div>
</div>
<div class="label round">
    <img src="showimg.php?d=label" alt="legend" />
</div>
<div class="window">
    <img src="" alt="stats" />
</div>

</div>

<script type="text/javascript">
//<![CDATA[
var stats_state = {
    'chart':'C',
    'smooth':5,
    'server':'',
    'style':'simple',
    'date':''
}
function stats_update() {
    var y = stats_state.date.getFullYear();
    var m = stats_state.date.getMonth() + 1;
    var d = stats_state.date.getDate();
    while (String(y).length < 4) { y = '0'+''+y; }
    while (String(m).length < 2) { m = '0'+''+m; }
    while (String(d).length < 2) { d = '0'+''+d; }
    date = y+''+m+''+d;
    $('#airjo .window img').css('opacity','0.5')
    $('#airjo .date').val(m+'/'+d+'/'+y)
    var img = new Image();
    var qs = 'd='+date+'&c='+stats_state.chart+'&s='+stats_state.server+'&a='+stats_state.smooth+'&t='+stats_state.style;
    var time = new Date();
    qs += '&'+time.getTime();
    img.onload = function() {
        $('#airjo .window img').attr('src','showimg.php?'+qs).css('opacity','1.0');
    }
    img.src = 'showimg.php?'+qs;
}

$(document).ready(function() {
    $('#airjo .label img').load(function() {
        $('#airjo .label').css({
            left:($('#airjo .window').width()-35-$('#airjo .label').width()) + 'px',
            top:'40px'
            });
        });
    stats_state.date = new Date();
    stats_update();
    $('#airjo .date').datepicker({
        onSelect: function(dateText, inst) {
            stats_state.date = $(this).datepicker('getDate');
            stats_update();
        }
    });
    $('#airjo .buttons .reload').click(function() {
        stats_update();
    });
    $('#airjo .buttons .chart').change(function() {
        stats_state.chart = $(this).val();
        stats_update();
    });
    $('#airjo .buttons .smooth').change(function() {
        stats_state.smooth = $(this).val();
        stats_update();
    });
    $('#airjo .buttons .server').change(function() {
        stats_state.server = $(this).val();
        stats_update();
    });
    $('#airjo .buttons .style').change(function() {
        stats_state.style = $(this).val();
        stats_update();
    });

    $('#airjo .key').click(function() {
        if (parseFloat($('#airjo .menu').css('height')) == 0) {
            $('#airjo .menu').animate({height:'24px'});
        } else {
            $('#airjo .menu').animate({height:'0'});
        }
    });

    $('#airjo .legend').change(function() {
        if ($(this).is(':checked')) {
            $('#airjo .label').show();
        } else {
            $('#airjo .label').hide();
        }
    });
    $('#airjo .scale').change(function() {
        if ($(this).is(':checked')) {
            $('#airjo .window img').css('width','100%');
        } else {
            $('#airjo .window img').css('width','auto');
        }
    });
    $('#airjo .label').mousedown(function(e) {
        $(this).attr('drag',1).attr('dragx',$('body').attr('pagex'))
            .attr('dragy',$('body').attr('pagey'))
            .attr('dragoffx',$(this).css('left'))
            .attr('dragoffy',$(this).css('top'))
            ;
    });
    $('#airjo .label').mouseup(function(e) {
        $(this).attr('drag',0);
    });
    $('#airjo .label').mousemove(function(e) {
        if ($(this).attr('drag') == 1) {

            var dx = parseFloat($('body').attr('pagex')) - parseFloat($(this).attr('dragx')) + parseFloat($(this).attr('dragoffx'));
            var dy = parseFloat($('body').attr('pagey')) - parseFloat($(this).attr('dragy')) + parseFloat($(this).attr('dragoffy'));
            window.status = dx+'x'+dy;
            $(this).css({
               'left':parseFloat(dx)+'px',
                'top':parseFloat(dy)+'px',
            });
        }
    });
    $('#airjo *').mousemove(function(e) {
        $('body').attr('pagex',e.pageX).attr('pagey',e.pageY);
    });
});
//]]>
</script>
</body></html>