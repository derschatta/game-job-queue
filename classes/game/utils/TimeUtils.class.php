<?php

class TimeUtils
{

    public function secondsToString($secs) {
        $h = 0;
        $m = 0;
        $s = intval ($secs);

        // take care of mins and left-over secs
        if ($s >= 60) {
            $m += (int) floor ($s / 60);
            $s = (int) $s % 60;

            // now handle hours and left-over mins
            if ($m >= 60) {
                $h += (int) floor ($m / 60);
                $m = $m % 60;
            }
        }
        $h = ( $h < 10 ) ? "0" . $h : $h;
        $m = ( $m < 10 ) ? "0" . $m : $m;
        if($s>=0 && $s<10){ $s = "0" . $s; }
        elseif($s < 0){ $s = "??"; }

        return $h.":".$m.":".$s;
    }

    public function getSecondsToString($secs) {
        return self::secondsToString($secs);
    }

}

?>