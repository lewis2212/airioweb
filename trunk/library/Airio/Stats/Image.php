<?php
/**
 * Here well do some painting
 *
 * @package AirjoStats
 * @author joruss
 */

require_once(dirname(__FILE__)."/Abstract.php");

/**
 * Class providing interface for drawing charts
 *
 */
class Airio_Stats_Image extends Airio_Stats_Abstract {
protected $reader;
protected $chartType;
    /**
     * Setup image class
     *
     * @param Airio_Stats_Reader reader class
     * @param string chart type
     *          If you extend id with custom getChart<Custom Name> function
     *          you'd be able to use <Custom Name> as a chart type
     * @access public
     * @return undefined
     */
    function __construct(&$reader,$type = 'simple') {
        $this->reader = $reader;
        if (method_exists($this,'getChart'.ucfirst($type))) {
            $this->chartType = 'getChart'.ucfirst($type);
        } else {
            $this->chartType = 'getChartSimple';
        }
        $this->setConfig('row_height',40);
        $this->setConfig('padding_left',20);
        $this->setConfig('padding_right',10);
        $this->setConfig('padding_top',10);
        $this->setConfig('padding_bottom',10);
        // let's show at least grid if there's not enough data
        $this->setConfig('min_rows',15);

        // delimit parameters with comma (if necessary)
        // ie. `jpg,cached_file_name,99` will output image to file
        // instead showing it. You can display it later by issuing
        // readfile(cached_file_name), since headers were sent anyway
        $this->setConfig('image_type','png');
        // setup chart colors
        $this->setConfig('chart_colors',Array(
            'axes' => array(0x00,0x00,0x00),
            'background' => array(0xEB,0xEB,0xFF),
            'hline' => array(0x66,0x66,0x66),
            'vline' => array(0x44,0x44,0x44),
            'demostrip' => array(0xAA,0xAA,0xAA),
            'text' => array(0x00,0x00,0x00),
            ));
        // setup server names
        $this->setConfig('server_names',Array(
            'Server #01','Server #02','Server #03','Server #04',
            'Server #05','Server #06','Server #07','Server #08',
            'Server #09','Server #10','Server #11','Server #12',
            ));
        // ...and colors. 12 of 'em should be enough for everyone, right? ;-)
        $this->setConfig('server_colors',Array(
            array(0xff,0x00,0x00), // red
            array(0x00,0xff,0x00), // green
            array(0x00,0x00,0xff), // blue
            array(0x00,0xff,0xff), // cyan
            array(0xff,0x00,0xff), // magenta
            array(0x80,0x00,0x00), // darkred
            array(0x00,0x80,0x00), // darkgreen
            array(0x00,0x80,0x80), // navy blue
            array(0x40,0x40,0x40), // dark grey
            array(0x40,0x00,0x40), // violet
            array(0x80,0x80,0x80), // grey
            array(0xff,0x60,0x00), // orange
            ));
    }

    /**
     * Show image with servers label
     *
     * @param integer How many servers to display
     *          if null the whole content of config['server_names']
     *          will be used.
     * @access public
     * @return undefined
     */
    function label($server_count = null) {
        if (strlen($server_count) == 0) { $server_count = null; }
        $names = $this->getConfig('server_names');
        $maxlength = 0;
        foreach ($names as $v) {
            $maxlength = max($maxlength,strlen($v));
        }
        if ($server_count == null) {
            $server_count = count($names);
        }
        $img = ImageCreateTruecolor(
            $maxlength*ImageFontWidth(1)+60,
            $server_count * 20
            );
        $colors = $this->getConfig('server_colors');
        foreach ($colors as $s => $c) {
            $this->sc[$s] = ImageColorAllocate($img,$c[0],$c[1],$c[2]);
        }
        $colors = $this->getConfig('chart_colors');
        foreach ($colors as $s => $c) {
            $this->c[$s] = ImageColorAllocate($img,$c[0],$c[1],$c[2]);
        }
        $black = ImageColorAllocate($img,0,0,0);
        $white = ImageColorAllocate($img,255,255,255);
        ImageFill($img,0,0,$white);

        for ($i=0;$i<$server_count;$i++) {
            ImageLine($img,0,$i*20+10,50,$i*20+10,$this->sc[$i]);
            ImageString($img,1,60,$i*20+5 ,$names[$i],$black);
        }
        $this->outputImage($img);
    }

    /**
     * Display chart image
     *
     * @param string date in yyyymmdd format
     * @param string chart type (C,K,B,T etc...)
     * @param integer server number
     * @access public
     * @return undefined
     */
    function display($date,$chart,$server) {
        if (!isset($date)) { $date = date('Ymd'); }
        if ($date == 'label') { return $this->label(); }

        if (strlen($chart) == 0) { $chart = 'C'; }
        if (strlen($server) == 0) { $server = null; }

        if (($chart == 'T') || ($chart == 'X')) {
            // with tracks and test data smoothing is dumb...
            $this->reader->setConfig('smooth',1);
        }

        if (($chart == 'B') || ($chart == 'K')) {
            $this->reader->setConfig('sum_values',true);
        }

        $this->reader->getDay($date,$data,$chart);

        foreach ($data as $time => $servers) {
            if ($server !== null) {
                if (isset($servers[$server])) {
                    $data[$time] = array(
                        $server => $servers[$server]
                        );
                } else {
                    unset($data[$time]);
                }
            }
            foreach ($servers as $sv => $val) {
                switch ($chart) {
                    case 'X':
                        $data[$time][$sv] = $val/10;
                        break;
                }
            }
        }

        $datastats = $this->getStats($data);
        $max = 0;

        switch ($chart) {
            case 'T':
                $this->tracks = $this->getTracks();
                $this->track_ids = $this->getTrackIDs($this->tracks);
                $max = count($this->tracks);
                break;
            default:
                foreach ($datastats as $v) {
                    if ($max == 0) {
                        $max = $v['max'];
                    } else {
                        $max = max($v['max'],$max);
                    }
                }
                break;
        }
        $wi = 1440;

        $max = max($this->getConfig('min_rows'),$max);
        $max++;
        $img = ImageCreateTruecolor(
            $this->getConfig('padding_left')+$this->getConfig('padding_right')+$wi,
            $this->getConfig('padding_top')+$this->getConfig('padding_bottom')+$max * $this->getConfig('row_height')
            );
        $colors = $this->getConfig('server_colors');
        foreach ($colors as $s => $c) {
            $this->sc[$s] = ImageColorAllocate($img,$c[0],$c[1],$c[2]);
        }
        $colors = $this->getConfig('chart_colors');
        foreach ($colors as $s => $c) {
            $this->c[$s] = ImageColorAllocate($img,$c[0],$c[1],$c[2]);
        }

        // lay down some lines
        $this->putGrid($img,$max,$chart);
        // if we dealing with tracks, we gotta do some magic
        if ($chart == 'T') {
            foreach ($data as $time => $servers) {
                foreach ($servers as $server => $track) {
                    while (strlen($track) < 3) { $track = '0'.$track; }
                    $data[$time][$server] = $this->tracks[$track]['y'];
                }
            }
        }

        // draw charts
        call_user_func(array('Airio_Stats_Image',$this->chartType),$img,$data);
        // output image
        $this->outputImage($img);
        ImageDestroy($img);
    }

    /**
     * Draw empty chart
     *
     * @param &image Image resource
     * @param integer height of the chart
     * @access private
     * @return undefined
     */
    private function putGrid(&$img,&$max,$chart) {
        $h = $this->getConfig('row_height');
        $pl = $this->getConfig('padding_left');
        $pt = $this->getConfig('padding_top');
        $pr = $this->getConfig('padding_right');
        $pb = $this->getConfig('padding_bottom');

//        echo sprintf('%dx%d',ImageSx($img),ImageSy($img));

        ImageFill($img,0,0,$this->c['background']);
        // hline
        for ($i=0;$i<$max;$i++) {
            if ($i % 10 == 0) {
                ImageSetThickness($img,2);
            } else {
                ImageSetThickness($img,1);
            }
            ImageLine($img,$pl,ImageSy($img)-$pb-$i*$h,ImageSx($img)-$pr,ImageSy($img)-$pb-$i*$h,$this->c['hline']);
            if ($chart == 'T') {
                $str = str_repeat(sprintf('%4s --- ',$this->track_ids[$i]),33);
                ImageString($img,1,0,ImageSy($img)-18-$i*$h,
                $str,
                $this->c['demostrip']);
                $str = sprintf('%4s ',$this->track_ids[$i]);
                ImageString($img,1,0,ImageSy($img)-18-$i*$h,
                $str,
                $this->c['text']);
            } else {
                ImageString($img,1,0,ImageSy($img)-8-$i*$h,sprintf('%02d',$i),$this->c['text']);
            }
        }
        // vline
        for ($i = 0;$i<24;$i++) {
            ImageLine($img,$pl+$i*60,$pt,$pl+$i*60,ImageSy($img)-$pb+2,$this->c['vline']);
            ImageString($img,1,$pl+$i*60-10,ImageSy($img)-$pb+2,sprintf('%02d',$i),$this->c['text']);
        }
        // axes
        ImageLine($img,$pl,$pt,$pl,ImageSy($img)-$pr,$this->c['axes']);
        ImageLine($img,$pl,ImageSy($img)-$pb,ImageSx($img)-$pr,ImageSy($img)-$pb,$this->c['axes']);

        ImageLine($img,$pl,$pt,$pl-2,$pt+10,$this->c['axes']);
        ImageLine($img,$pl,$pt,$pl+2,$pt+10,$this->c['axes']);

        ImageLine($img,ImageSx($img)-$pr,ImageSy($img)-$pb,ImageSx($img)-$pr-10,ImageSy($img)-$pb-2,$this->c['axes']);
        ImageLine($img,ImageSx($img)-$pr,ImageSy($img)-$pb,ImageSx($img)-$pr-10,ImageSy($img)-$pb+2,$this->c['axes']);

        $demostrip = str_repeat(' --- DEMO',33);
        if ($chart == 'C') {
            ImageString($img,1,$pl,ImageSy($img)-$pb-15*$h+2,$demostrip,$this->c['demostrip']);
        }
        if ($chart == 'P') {
            ImageString($img,1,$pl,ImageSy($img)-$pb-12*$h+2,$demostrip,$this->c['demostrip']);
        }
    }

    /**
     * Get data statistics
     *
     * Gather some information about how big out chart has to become to fit
     * the data we have.
     *
     * @param &array data
     * @access private
     * @return array with min/max values
     */
    private function getStats(&$data) {
        $minmax = array();
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                if (isset($minmax[$k]['min'])) {
                    $minmax[$k]['min'] = min($minmax[$k]['min'],$v);
                } else {
                    $minmax[$k]['min'] = $v;
                }
                if (isset($minmax[$k]['max'])) {
                    $minmax[$k]['max'] = max($minmax[$k]['max'],$v);
                } else {
                    $minmax[$k]['max'] = $v;
                }
            }
        }
        return $minmax;
    }

    /**
     * Draw simple Chart
     *
     * Simple point-to-point lines
     *
     * @param &image
     * @param &array data
     * @access public
     * @return undefined
     */
    function getChartSimple(&$img,&$data) {
        ImageSetThickness($img,2);
        $lastxy = array();
        $h = $this->getConfig('row_height');
        $pl = $this->getConfig('padding_left');
        $pt = $this->getConfig('padding_top');
        $pr = $this->getConfig('padding_right');
        $pb = $this->getConfig('padding_bottom');
        foreach ($data as $time => $servers) {
            $i = ($time/60) % 1440;
            foreach($servers as $server => $count) {
                if (!isset($lastxy[$server])) {
                    $lastxy[$server] = array(
                        'x' => $pl+$i,
                        'y' => ImageSy($img)-$pb-$server-$count*$h,
                    );
                }
                ImageLine($img,$lastxy[$server]['x'],$lastxy[$server]['y'],$pl+$i,ImageSy($img)-$pb-$server-$count*$h,$this->sc[$server]);
                $lastxy[$server]['x'] = $pl+$i;
                $lastxy[$server]['y'] = ImageSy($img)-$pb-$server-$count*$h;
            }
        }
    }

    /**
     * Simple stairs-like charts
     *
     * @param &image
     * @param &array data
     * @access public
     * @return undefined
     */
    function getChartStairs(&$img,&$data) {
        ImageSetThickness($img,2);
        $lastxy = array();
        $h = $this->getConfig('row_height');
        $pl = $this->getConfig('padding_left');
        $pt = $this->getConfig('padding_top');
        $pr = $this->getConfig('padding_right');
        $pb = $this->getConfig('padding_bottom');
        foreach ($data as $time => $servers) {
            $i = ($time/60) % 1440;
            foreach($servers as $server => $count) {
                if (!isset($lastxy[$server])) {
                    $lastxy[$server] = array(
                        'x' => $pl+$i,
                        'y' => ImageSy($img)-$pb-$server-$count*$h,
                    );
                }
                ImageLine($img,$lastxy[$server]['x'],
                               $lastxy[$server]['y'],
                               $pl+$i,
                               $lastxy[$server]['y'],
                               $this->sc[$server]);
                ImageLine($img,$pl+$i,
                               $lastxy[$server]['y'],
                               $pl+$i,
                               ImageSy($img)-$pb-$server-$count*$h,
                               $this->sc[$server]);
                $lastxy[$server]['x'] = $pl+$i;
                $lastxy[$server]['y'] = ImageSy($img)-$pb-$server-$count*$h;
            }
        }
    }


    private function getTrackIDs(&$tracks) {
        $ids = Array();
        foreach ($tracks as $v) {
            $ids[$v["y"]] = $v["name"];
        }
        return $ids;
    }

    private function getTracks() {
        $tracks=Array(3,6,6,4,3,1,7);
        $wrldnames = Array("BL","SO","FE","AU","KY","WE","AS");
        $nonrev = Array("BL3","AU1","AU2","AU3","AU4");
        $dirnames = Array("","R");
        $wrld = 0;
        $race = 0;
        $dir = 0;
        $ntrack = "";
        $c = 0;
        $atracks = Array();
        while ($ntrack != "FIN") {
            if (in_array($wrldnames[$wrld].($race+1),$nonrev) && ($dir == 1)) {
                $c--;
            } else {
                $atracks[$wrld.$race.$dir]["y"] = $c;
                $atracks[$wrld.$race.$dir]["name"] = $wrldnames[$wrld].($race+1).$dirnames[$dir];
            }
            if ($dir >= 1) {
                $dir = 0;
                if ($race >= $tracks[$wrld]-1) {
                    $race = 0;
                    if ($wrld >= count($tracks)-1) {
                        $ntrack = "FIN";
                    } else {
                        $wrld++;
                    }
                } else {
                    $race++;
                }
            } else {
                $dir++;
            }
            $c++;
        }
        return $atracks;
    }

    /**
     * Output image
     *
     * You can congifure output via $this->setConfig('image_type',...
     *
     * @param &image
     * @access public
     * @return undefined
     */
    function outputImage(&$img) {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 1 Jan 1970 00:00:00 GMT");
        $imgtype = explode(',',$this->getConfig('image_type'));
        switch (strtolower($imgtype[0])) {
            case 'jpg':
                Header("Content-type: image/jpeg");
                ImageJPEG($img,$imagetype[1],$imgtype[2]);
                break;
            case 'gif':
                Header("Content-type: image/gif");
                ImageGIF($img,$imagetype[1]);
                break;
            default:
                Header("Content-type: image/png");
                ImagePNG($img,$imagetype[1]);
                break;

        }
    }

    /**
     *  Check what kind of charts we have to offer
     *
     *  Any new public method named as getChart* will be retrieved
     *
     * @access public
     * @return array available charts
     */
    function getAvailableCharts() {
        $methods = get_class_methods(get_class());
        $charts = Array();
        foreach ($methods as $v) {
            if (substr($v,0,8) == 'getChart') {
                $charts[] = substr($v,8);
            }
        }
        return $charts;
    }


    /**
     * Override of error handler.
     *
     * Since we're expected to output imege, let's do so...
     */
    function showError($msg) {
        $img = ImageCreate(1440,$this->getConfig('min_rows')*$this->getConfig('row_height'));
        $colors = $this->getConfig('chart_colors');
        $black = ImageColorAllocate($img,$colors['text'][0],$colors['text'][1],$colors['text'][2]);
        $white = ImageColorAllocate($img,$colors['background'][0],$colors['background'][1],$colors['background'][2]);
        ImageFill($img,0,0,$white);
        ImageString($img,5,10,10,$msg,$black);
        $this->outputImage($img);
        die();
    }

} // Airio_Stats_Image()