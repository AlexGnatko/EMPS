<?php
define('DOT_HITIN', 10);
define('DOT_HITOVER', 20);
define('DOT_HITSTART', 30);
define('DOT_HITEND', 40);

class EMPS_Dates
{
    public $months;
    public $fmonths;
    public $fmonthsi;
    public $rfmonths;
    public $rfmonthsi;

    public $wdays;
    public $rwdays;

    function __construct()
    {
        $this->months = array(0 => "Jan", 1 => "Feb", 2 => "Mar", 3 => "Apr", 4 => "May", 5 => "Jun", 6 => "Jul", 7 => "Aug", 8 => "Sep", 9 => "Oct", 10 => "Nov", 11 => "Dec");
        $this->fmonths = array(0 => "January", 1 => "February", 2 => "March", 3 => "April", 4 => "May", 5 => "June", 6 => "July", 7 => "August", 8 => "September", 9 => "October", 10 => "November", 11 => "December");

        $this->rfmonths = array(0 => "Январь", 1 => "Февраль", 2 => "Март", 3 => "Апрель", 4 => "Май", 5 => "Июнь", 6 => "Июль", 7 => "Август", 8 => "Сентябрь", 9 => "Октябрь", 10 => "Ноябрь", 11 => "Декабрь");
        $this->rfmonthsi = array(0 => "января", 1 => "февраля", 2 => "марта", 3 => "апреля", 4 => "мая", 5 => "июня", 6 => "июля", 7 => "августа", 8 => "сентября", 9 => "октября", 10 => "ноября", 11 => "декабря");

        $this->wdays = array(1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 0 => "Sunday");
        $this->rwdays = array(1 => "Понедельник", 2 => "Вторник", 3 => "Среда", 4 => "Четверг", 5 => "Пятница", 6 => "Суббота", 0 => "Воскресенье");
        $this->rrwdays = array(0 => "Понедельник", 1 => "Вторник", 2 => "Среда", 3 => "Четверг", 4 => "Пятница", 5 => "Суббота", 6 => "Воскресенье");
        $this->srwdays = array(1 => "ПН", 2 => "ВТ", 3 => "СР", 4 => "ЧТ", 5 => "ПТ", 6 => "СБ", 0 => "ВС");
    }

    function next_monday($dt)
    {
        return start_of("week", end_of("week", $dt) + 2 * 60 * 60);
    }

    function start_of($what, $dt)
    {
        switch ($what) {
            case "quarter":
                $s = date("Ym", $dt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2) + 0;
                $q = floor(($mon - 1) / 3) + 1;
                $smon = ($q - 1) * 3 + 1;
                $sdt = mktime(0, 0, 0, $smon, 1, $year);
                break;
            case "year":
                $s = date("Y", $dt);
                $year = substr($s, 0, 4);
                $sdt = mktime(0, 0, 0, 1, 1, $year);
                break;
            case "week":
                $s = date("w", $dt);
                $xs = date("Ymd", $dt);
                $year = substr($xs, 0, 4);
                $mon = substr($xs, 4, 2);
                $day = substr($xs, 6, 2);

                if ($s == 0) $s = 7;
                $s -= 1;
                $sdt = mktime(0, 0, 0, $mon, $day, $year) - ($s * 24 * 60 * 60);
                break;
            case "day":
                $xs = date("Ymd", $dt);
                $year = substr($xs, 0, 4);
                $mon = substr($xs, 4, 2);
                $day = substr($xs, 6, 2);

                $sdt = mktime(0, 0, 0, $mon, $day, $year);
                break;
            default:
                $s = date("Ymd", $dt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);
                $day = substr($s, 6, 2);
                $sdt = mktime(0, 0, 0, $mon, 1, $year);
        }
        return $sdt;
    }

    function end_of($what, $dt)
    {

        switch ($what) {
            case "quarter":
                $s = date("Ym", $dt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2) + 0;
                $q = floor(($mon - 1) / 3) + 1;
                $smon = ($q) * 3 + 1;
                if ($smon > 12) {
                    $smon -= 12;
                    $year++;
                }
                $edt = mktime(0, 0, 0, $smon, 1, $year) - 1;
                break;

            case "year":
                $s = date("Y", $dt);
                $year = substr($s, 0, 4);
                $edt = mktime(0, 0, 0, 12, 31, $year) + 24 * 60 * 60 - 1;
                break;
            case "week":
                $s = date("w", $dt);
                $xs = date("Ymd", $dt);
                $year = substr($xs, 0, 4);
                $mon = substr($xs, 4, 2);
                $day = substr($xs, 6, 2);

                if ($s == 0) $s = 7;
                $s = 8 - $s;
                $edt = mktime(0, 0, 0, $mon, $day, $year) + ($s * 24 * 60 * 60) - 1;
                break;
            case "day":
                $xs = date("Ymd", $dt);
                $year = substr($xs, 0, 4);
                $mon = substr($xs, 4, 2);
                $day = substr($xs, 6, 2);

                $edt = mktime(23, 59, 59, $mon, $day, $year);


                break;

            default:
                $s = date("Ymd", $dt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);

                if ($mon == 12) {
                    $mon = 1;
                    $year++;
                } else $mon++;

                $edt = mktime(0, 0, 0, $mon, 1, $year);
                $edt -= 1;

        }
        return $edt;
    }

    function days($sdt, $edt)
    {
        return floor(($edt - $sdt) / (60 * 60 * 24));
    }

    function hours($sdt, $edt)
    {
        return round(($edt - $sdt) / (60 * 60), 0);
    }

    function form_time($dt)
    {
        if (!$dt) return "(не указано)";
        return date("d.m.Y H:i", $dt + TZ_CORRECT * 60 * 60);
    }

    function month($dt)
    {
        $mon = array(1 => "Янв", 2 => "Фев", 3 => "Мар", 4 => "Апр",
            5 => "Май", 6 => "Июн", 7 => "Июл", 8 => "Авг",
            9 => "Сен", 10 => "Окт", 11 => "Ноя", 12 => "Дек");

        return $mon[date("m", $dt) + 0];
    }

    function form_ctime($dt)
    {
        if (!$dt) return "(не указано)";
        $udt = $dt + TZ_CORRECT * 60 * 60;

        $mon = array(1 => "янв", 2 => "фев", 3 => "мар", 4 => "апр",
            5 => "май", 6 => "июн", 7 => "июл", 8 => "авг",
            9 => "сен", 10 => "окт", 11 => "ноя", 12 => "дек");

        return date("d", $udt) . "&nbsp;" . $mon[date("m", $udt) + 0] . "&nbsp;" . date("Y H:i", $udt);
    }

    function form_cdate($dt)
    {
        if (!$dt) return "(не указано)";
        $udt = $dt + TZ_CORRECT * 60 * 60;

        $mon = array(1 => "янв", 2 => "фев", 3 => "мар", 4 => "апр",
            5 => "май", 6 => "июн", 7 => "июл", 8 => "авг",
            9 => "сен", 10 => "окт", 11 => "ноя", 12 => "дек");

        return date("d", $udt) . "." . $mon[date("m", $udt) + 0] . "." . date("y", $udt);
    }

    function form_edate($dt)
    {
        if (!$dt) return "(не указано)";
        $udt = $dt + TZ_CORRECT * 60 * 60;

        $mon = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr",
            5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug",
            9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");

        return $mon[date("m", $udt) + 0] . "&nbsp;" . date("d", $udt) . ",&nbsp;" . date("Y", $udt);
    }

    function parse_time($v)
    {
        $p = explode(" ", $v);
        $d = explode(".", $p[0]);
        $mon = $d[1];
        $day = $d[0];
        $year = $d[2];
        if (!$p[1]) return false;
        $t = explode(":", $p[1]);
        $hour = $t[0];
        $min = $t[1];
        $sec = $t[2];
        $dt = mktime($hour, $min, $sec, $mon, $day, $year);

        return $dt;
    }


    function form_time_a($dt)
    {
        if (!$dt) return "(не указано)";
        $m = time() - $dt;
        $m = floor($m / 60);
        $s = "$m minutes ago";
        return $s;
    }

    function form_date($dt)
    {
        if (!$dt) return "(не указано)";
        return date("d.m.Y", $dt);
    }

    function form_sdate($dt)
    {
        if (!$dt) return "(не указано)";
        return date("d.m.y", $dt);
    }

    function period_index($type, $sdt, $edt, $dt)
    {
        switch ($type) {
            case "quarter":
                $s = date("Y", $sdt);
                $year = substr($s, 0, 4);
                $cdt = $sdt;
                $idx = 0;
                $tdt = 0;
                while ($cdt <= $edt) {
                    $etdt = end_of("week", $cdt);
                    if ($dt >= $cdt && $dt <= $etdt) {
                        $tdt = $idx;
                        break;
                    }
                    $cdt = next_monday($cdt);
                    $idx++;
                }
                break;
            case "year":
                $xs = date("m", $dt);
                $mon = substr($xs, 0, 2);
                $tdt = $mon - 1;
                break;
            default:
                $tdt = ($dt - $sdt) / (60 * 60 * 24);
        }
        return floor($tdt);
    }

    function period_axis($type, $sdt, $edt)
    {
        global $smon, $emps;
        $axis = array();
        $curdt = time();
        switch ($type) {
            case "quarter":
                $s = date("Y", $sdt);
                $year = substr($s, 0, 4);
                $tdt = $sdt;
                $idx = 0;
                while ($tdt <= $edt) {
                    $idx++;
                    $e = array();
                    $e['dt'] = $tdt;
                    $e['edt'] = $this->end_of("week", $tdt);
                    if ($e['dt'] <= $curdt && $e['edt'] >= $curdt) {
                        $e['cur'] = 1;
                    }

                    if ($e['edt'] >= $curdt) {
                        $e['fut'] = 1;
                    }
                    $e['time'] = $emps->form_time($e['dt']);
                    $e['week'] = date("W", $tdt) + 0;
                    $e['mon'] = date("m", $tdt) + 0;
                    $e['wday'] = 1;
                    $axis[] = $e;
                    //echo form_time($tdt)."<br/>";
                    $tdt = $this->next_monday($tdt);
                }

                break;
            case "year":
                $s = date("Y", $sdt);
                $year = substr($s, 0, 4);

                for ($i = 0; $i < 12; $i++) {
                    $tdt = mktime(0, 0, 0, $i + 1, 1, $year);
                    $e = array();
                    $e['dt'] = $tdt;
                    $e['edt'] = $this->end_of("month", $tdt);
                    if ($e['dt'] <= $curdt && $e['edt'] >= $curdt) {
                        $e['cur'] = 1;
                    }

                    if ($e['edt'] >= $curdt) {
                        $e['fut'] = 1;
                    }

                    $e['time'] = $emps->form_time($e['dt']);
                    $e['year'] = date("Y", $tdt) + 0;
                    $e['mon'] = $i + 1;
                    $e['wday'] = 1;
                    $axis[] = $e;
                }
                break;
            default:
                $tdt = $sdt;
                while ($tdt <= $edt) {
                    $e = array();
                    $e['dt'] = $tdt;
                    $e['edt'] = $this->end_of("day", $tdt);
                    if ($e['dt'] <= $curdt && $e['edt'] >= $curdt) {
                        $e['cur'] = 1;
                    }

                    if ($e['edt'] >= $curdt) {
                        $e['fut'] = 1;
                    }

                    $e['time'] = $emps->form_time($e['dt']);
                    $e['day'] = date("d", $tdt) + 0;
                    $e['mon'] = date("m", $tdt) + 0;
                    $e['wday'] = date("w", $tdt) + 0;
                    $e['smon'] = $smon[$e['mon']];
                    $e['date'] = $emps->form_date($e['dt']);
                    $axis[] = $e;
                    $tdt = $e['edt'] + 1;
                }
        }
        return $axis;

    }

    function prev_period($type, $cdt)
    {
        switch ($type) {
            case "quarter":
                $s = date("Ym", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2) + 0;
                $q = floor(($mon - 1) / 3) + 1;
                $smon = ($q - 2) * 3 + 1;
                if ($smon < 0) {
                    $year--;
                    $smon += 12;
                }

                $dt = mktime(0, 0, 0, $smon, 1, $year);

                break;
            case "year":
                $s = date("YmdHi", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);
                $day = substr($s, 6, 2);
                $hr = substr($s, 8, 2);
                $mn = substr($s, 10, 2);

                $year--;
                $dt = mktime($hr, $mn, 0, $mon, $day, $year);
                break;
            case "week":
                $dt = $cdt - 60 * 60 * 24 * 7;
                break;
            case "month":
                $s = date("YmdHi", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);
                $day = substr($s, 6, 2);
                $hr = substr($s, 8, 2);
                $mn = substr($s, 10, 2);

                $xyear = $year;
                $xmon = $mon - 1;
                if ($xmon < 1) {
                    $xmon = 12;
                    $xyear--;
                }

                $dt = mktime($hr, $mn, 0, $xmon, $day, $xyear);
                break;
        }
        return $dt;
    }

    function next_period($type, $cdt)
    {
        switch ($type) {
            case "quarter":
                $s = date("Ym", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2) + 0;
                $q = floor(($mon - 1) / 3) + 1;
                $smon = ($q) * 3 + 1;
                if ($smon > 12) {
                    $year++;
                    $smon -= 12;
                }

                $dt = mktime(0, 0, 0, $smon, 1, $year);

                break;
            case "year":
                $s = date("YmdHi", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);
                $day = substr($s, 6, 2);
                $hr = substr($s, 8, 2);
                $mn = substr($s, 10, 2);

                $year++;
                $dt = mktime($hr, $mn, 0, $mon, $day, $year);
                break;

            case "week":
                $dt = $cdt + 60 * 60 * 24 * 7;

                break;
            case "month":
                $s = date("YmdHi", $cdt);
                $year = substr($s, 0, 4);
                $mon = substr($s, 4, 2);
                $day = substr($s, 6, 2);
                $hr = substr($s, 8, 2);
                $mn = substr($s, 10, 2);

                $xyear = $year;
                $xmon = $mon + 1;
                if ($xmon > 12) {
                    $xmon = 1;
                    $xyear++;
                }

                $dt = mktime($hr, $mn, 0, $xmon, $day, $xyear);
                break;
        }
        return $dt;
    }

    function count_nights($sdt, $edt)
    {
        $span = $edt - $sdt;
        return round($span / (60 * 60 * 24), 0);
    }

    function get_dm($dt)
    {
        $x = explode(".", $dt);
        $d = sprintf("%02d", $x[0]);
        $m = sprintf("%02d", $x[1]);
        return $m . $d;
    }

    function next_hour($dt, $hour)
    {
        $ch = date("H", $dt) + 1;
        $nh = $hour - $ch;
        if ($nh < 0) $nh += 24;
        $nh += 1;
        return $dt += $nh * 60 * 60;
    }

    function previous_hour($dt, $hour)
    {
        $ch = date("H", $dt) - 1;
        $nh = $hour - $ch;
        if ($nh > 0) $nh -= 24;
        $nh -= 1;
        return $dt += $nh * 60 * 60;
    }

    function whats_left($ltime)
    {
        $days = floor($ltime / (60 * 60 * 24));
        if ($days < 4) {
            $hours = round($ltime / (60 * 60));
            return $hours . " ч.";
        } else {
            return $days . " дн.";
        }
    }

    public function expand_date($dt)
    {
        $ti = array();

        $ti['mday'] = date("d", $dt);

        $ti['wday'] = date("w", $dt);
        $ti['wdayt'] = $this->wdays[$ti['wday']];

        $ti['mday'] = date("d", $dt);
        $month = date("m", $dt);

        $ti['month'] = $this->months[$month - 1];
        $ti['rfmonth'] = $this->rfmonths[$month - 1];
        $ti['rfmonthi'] = $this->rfmonthsi[$month - 1];

        $ti['minutes'] = date("H:i", $dt);
        $ti['year'] = date("Y", $dt);

        return $ti;
    }

    public function test_overlap_dt($sdt, $edt, $psdt, $pedt)
    {
        if ($sdt >= $psdt && $edt <= $pedt) {
            return DOT_HITIN;
        }
        if ($sdt <= $psdt && $edt >= $pedt) {
            return DOT_HITOVER;
        }
        if ($sdt <= $psdt && $psdt <= $edt) {
            return DOT_HITSTART;
        }
        if ($sdt <= $pedt && $pedt <= $edt) {
            return DOT_HITEND;
        }
        return false;
    }


}

?>