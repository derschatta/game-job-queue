<?php

/**
 * @todo: Stu!! make some comments!!
 */

class MathsUtils
{

    public static function divby($val, $by) {
        if ( $by ) {
            $val = round( $val );
    		if ( $val%$by > $by/2 ) {
    			$val += $by - $val%$by ;
            }
    		else {
    			$val -= $val%$by;
            }
        }
		return $val;
	}

	public static function geometricSeriesValue($a, $r, $n, $divby) {
		return MathsUtils::divby(
			$a * pow($r, $n-1) , $divby
		);
	}

	public static function constantIncreaseValue($a, $n) {
		return round( (0.5*$a) + ($n-1)*0.1 );
	}

	public static function calculateLosses($amount, $percentage) {
		return floor($amount * ($percentage/100));
	}
}

?>