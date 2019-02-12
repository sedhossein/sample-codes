<?php


 function persian_no($str)
{
    return str_replace(array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0'),
        array('۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'),
        $str);
}

 function date($tims) // time()
{
    //if($timestamp==''){
    $timestamp = $tims;
    $format = 'y/m/d';
    //}
    $g_d = date('j', $timestamp);
    $g_m = date('n', $timestamp);
    $g_y = date('Y', $timestamp);
    list($jy, $jm, $jd, $j_all_days) = self::g2p($g_y, $g_m, $g_d);
    $j_days_in_month = array(0, 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    $leap = 0;
    if ($g_m > 1 && (($g_y % 4 == 0 && $g_y % 100 != 0) || ($g_y % 400 == 0))) {
        $j_days_in_month[12]++;
        $leap = 1;
    }
    $j_month_name = array('', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
        'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
    $j_week_name = array('Saturday' => 'شنبه',
        'Sunday' => 'یک شنبه',
        'Monday' => 'دوشنبه',
        'Tuesday' => 'سه شنبه',
        'Wednesday' => 'چهارشنبه',
        'Thursday' => 'پنج شنبه',
        'Friday' => 'جمعه',
        'Sat' => 'ش',
        'Sun' => 'ی',
        'Mon' => 'د',
        'Tue' => 'س',
        'Wed' => 'چ',
        'Thu' => 'پ',
        'Fri' => 'ج');
    $j_week_number = array('Sat' => '1',
        'Sun' => '2',
        'Mon' => '3',
        'Tue' => '4',
        'Wed' => '5',
        'Thu' => '6',
        'Fri' => '7');
    // calculate string
    $output_str = '';
    for ($i = 0; $i < strlen($format); $i++) {
        if ($format[$i] != '\\') {
            switch ($format[$i]) {
                case 'd':
                    if ($jd < 10) $output_str .= '0' . $jd; else $output_str .= $jd;
                    break;
                case 'j':
                    $output_str .= $jd;
                    break;
                case 'D':
                case 'S':
                    $output_str .= $j_week_name[date('D', $timestamp)];
                    break;
                case 'l':
                    $output_str .= $j_week_name[date('l', $timestamp)];
                    break;
                case 'w':
                case 'N':
                    $output_str .= $j_week_number[date('D', $timestamp)];
                    break;
                case 'z':
                    $output_str .= sprintf('%03d', $j_all_days);
                    break;
                case 'W':
                    $output_str .= floor(($j_all_days + 1) / 7);
                    break;
                case 'F':
                case 'M':
                    $output_str .= $j_month_name[$jm];
                    break;
                case 'm':
                    if ($jm < 10) $output_str .= '0' . $jm; else $output_str .= $jm;
                    break;
                case 'n':
                    $output_str .= $jm;
                    break;
                case 't':
                    $output_str .= $j_days_in_month[$jm];
                    break;
                case 'L':
                    $output_str .= $leap;
                    break;
                case 'o':
                case 'Y':
                    $output_str .= $jy;
                    break;
                case 'y':
                    $output_str .= $jy - (floor($jy / 100) * 100);
                    break;
                case 'a':
                case 'A':
                    if (date('a', $timestamp) == 'pm') $output_str .= 'بعد از ظهر'; else $output_str .= 'قبل از ظهر';
                    break;
                case 'B':
                    $output_str .= date('B', $timestamp);
                    break;
                case 'g':
                    $output_str .= date('g', $timestamp);
                    break;
                case 'G':
                    $output_str .= date('G', $timestamp);
                    break;
                case 'h':
                    $output_str .= date('h', $timestamp);
                    break;
                case 'H':
                    $output_str .= date('H', $timestamp);
                    break;
                case 'i':
                    $output_str .= date('i', $timestamp);
                    break;
                case 's':
                    $output_str .= date('s', $timestamp);
                    break;
                case 'e':
                    $output_str .= date('e', $timestamp);
                    break;
                case 'I':
                    $output_str .= date('I', $timestamp);
                    break;
                case 'O':
                    $output_str .= date('O', $timestamp);
                    break;
                case 'Z':
                    $output_str .= date('Z', $timestamp);
                    break;
                case 'c':
                    $output_str .= self::persian_date_utf('d-m-Y\TH:i:sO', $timestamp);
                    break;
                case 'r':
                    $output_str .= self::persian_date_utf('D، j F Y H:i:s O', $timestamp);
                    break;
                case 'U':
                    $output_str .= date('U', $timestamp);
                    break;
                default:
                    $output_str .= $format[$i];
                    break;
            }
        } else {
            $i++;
            $output_str .= $format[$i];
        }
    }
    if (1) {
        return self::persian_no($output_str);
    } else {
        return $output_str;
    }
}
