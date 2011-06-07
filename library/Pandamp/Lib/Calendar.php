<?php

class Pandamp_Lib_Calendar
{
    /**
     * select month
     * @param $month, $montharray
     * @return month
     */

     function monthPullDown($month, $montharray)
     {
        $monthSelect = "\n<select name=\"month\">\n";
        for($j=0; $j < 12; $j++) {
                if ($j != ($month - 1))
                        $monthSelect .= " <option value=\"" . ($j+1) . "\">$montharray[$j]</option>\n";
                else
                        $monthSelect .= " <option value=\"" . ($j+1) . "\" selected>$montharray[$j]</option>\n";
        }

        $monthSelect .= "</select>\n\n";
        return $monthSelect;
     }

    /**
     * select year
     * @param $year
     * @return $year
     */

     function yearPullDown($year) {
            $yearSelect = "<select name=\"year\" id=\"year\">\n";

            $z = 3;
            for($j=1; $j < 8; $j++) {
                    if ($z==0)
                            $yearSelect .= " <option value=\"" . ($year-$z) . "\" selected>" . ($year-$z) . "</option>\n";
                    else
                            $yearSelect .= " <option value=\"" . ($year-$z) . "\">" . ($year-$z) . "</option>\n";

                    $z--;
            }

            $yearSelect .= "</select>\n\n";
            return $yearSelect;
     }

    /**
     * getFirstDayOfMonthPosition
     * @param $month, $year
     *
     */

    function getFirstDayOfMonthPosition($month, $year)
    {
            $weekpos = date("w",mktime(0,0,0,$month,1,$year));

            // adjust position if weekstart not Sunday
            if (WEEK_START != 0)
                    if ($weekpos < WEEK_START)
                            $weekpos = $weekpos + 7 - WEEK_START;
                    else
                            $weekpos = $weekpos - WEEK_START;

            return $weekpos;
    }

    /**
     * getDayNameHeader
     * @return $string
     */

    function getDayNameHeader($month, $year)
    {
            global $lang;

            // day container
            $lang['abrvdays'] 	= array("S", "M", "T", "W", "T", "F", "S");

            // adjust day name order if weekstart not Sunday
            if (WEEK_START != 0) {
                    for($i=0; $i < WEEK_START; $i++) {
                            $tempday = array_shift($lang['abrvdays']);
                            array_push($lang['abrvdays'], $tempday);
                    }
            }

            $nextyear = ($month!=12)? $year : $year + 1;
            $prevyear = ($month!=1)? $year : $year - 1;
            $prevmonth = ($month==1)? 12 : $month - 1;
            $nextmonth = ($month==12)? 1 : $month + 1;
            $pyear = $year - 1;
            $nyear = $year + 1;

            $month = date("M", mktime(0, 0, 0, $month));

            $s = "<table class=\"calendar\" cellpadding=\"1\" cellspacing=\"1\" border=\"0\">\n";
            $s .= "<tr><th class='header'>&nbsp;<a href=".ROOT_URL."/agenda/year/" . $pyear." onclick=\"$('#showDiv1').load($(this).attr('href'));return false;\" class='headerNav' title='Prev Year'><<</a></th><th class='header'>&nbsp;<a href=".ROOT_URL."/agenda/month/" . $prevmonth . "/year/" . $prevyear." onclick=\"$('#showDiv1').load($(this).attr('href'));return false;\" class='headerNav' title='Prev Month'><</a></th>";
            $s .= "<th colspan='3' class=\"header\">$month $year</th>";
            $s .= "<th class='header'><a href=".ROOT_URL."/agenda/month/" . $nextmonth . "/year/" . $nextyear." onclick=\"$('#showDiv1').load($(this).attr('href'));return false;\" class='headerNav' title='Next Month'>></a>&nbsp;</th><th class='header'>&nbsp;<a href=".ROOT_URL."/agenda/year/" . $nyear." onclick=\"$('#showDiv1').load($(this).attr('href'));return false;\" class='headerNav' title='Next Year'>>></a></th></tr><tr>\n";

            foreach($lang['abrvdays'] as $day) {
                    $s .= "\t<td class=\"column_header\">&nbsp;$day</td>\n";
            }

            $s .= "</tr>\n\n";
            return $s;
    }

    /**
     * getEventDataArray
     * @param $month, $year
     */

     function getEventDataArray($month, $year) {
            $eventdata = array();
            $sql = App_Model_Show_Calendar::show()->EventDateCalendar($month, $year);
            foreach ($sql as $sqlDB) {
                    $eventdata[$sqlDB->d]["id"][] = $sqlDB->id;

                    if (strlen($sqlDB->title) > TITLE_CHAR_LIMIT) {
                            $eventdata[$sqlDB->d]["title"][] = substr(stripslashes($sqlDB->title), 0, TITLE_CHAR_LIMIT) . "...";
                    } else {
                            $eventdata[$sqlDB->d]["title"][] = stripslashes($sqlDB->title);
                    }

                    if (!($sqlDB->start_time == "55:55:55" && $sqlDB->end_time == "55:55:55")) {
                            if ($sqlDB->start_time == "55:55:55") {
                                    $starttime = "- -";
                            } else {
                                    $starttime = $sqlDB->stime;
                            }
                            if ($sqlDB->end_time == "55:55:55") {
                                    $endtime = "- -";
                            } else {
                                    $endtime = $sqlDB->etime;
                            }
                            $timestr = "<div align=\"right\" class=\"time_str\">($starttime - $endtime)&nbsp;</div>";
                    } else {
                            $timestr = "<br>";
                    }

                    $eventdata[$sqlDB->d]["timestr"][] = $timestr;
            }
                    return $eventdata;
     }

    /**
     * writeCalendar
     * @param $month, $year
     * @return $string
     */

    function writeCalendar($month, $year)
    {
        $str = $this->getDayNameHeader($month, $year);
        $eventdata = $this->getEventDataArray($month, $year);
        // get week position of first day of month
        $weekpos = $this->getFirstDayOfMonthPosition($month, $year);
        // get number of days in month
        $days = 31-((($month-(($month<8)?1:0))%2)+(($month==2)?((!($year%((!($year%100))?400:4)))?1:2):0));
        // initialize day variable to zero, unless $weekpos is zero
        if ($weekpos == 0) $day = 1; else $day = 0;
        // initialize today's date variables for color change
        $timestamp = time() + CURR_TIME_OFFSET * 3600;
        $d = date("d", $timestamp); $m = date("n", $timestamp); $y = date("Y", $timestamp);
        // loop writes empty cells until it reaches position of 1st day of month ($wPos)
        // it writes the days, then fills the last row with empty cells after last day
        while($day <= $days) {

            $str .="<tr>\n";

            for($i=0;$i < 7; $i++) {

                if($day > 0 && $day <= $days) {

                        $str .= "	<td class=\"";

                        if (($day == $d) && ($month == $m) && ($year == $y)) {
                                $str .= "today";
                        } elseif (array_key_exists($day,$eventdata)) {
                                $str .= "dayval";
                        } else {
                                $str .= "day";
                        }
                        $str .= "_cell\" valign=\"middle\">";

//					if ($this->user->checkRight(ADMIN))
//						$str .= "<a href=\"javascript: openActionDialog('postMessage', '', $day, $month, $year)\">$day</a>";
//					else
//						$str .= "$day";
//
//					$str .= "</span><br>";

                        // enforce title limit
                        if(array_key_exists($day,$eventdata)){
                        $eventcount = count($eventdata[$day]['title']);
                        if (MAX_TITLES_DISPLAYED < $eventcount) $eventcount = MAX_TITLES_DISPLAYED;

                        // write title link if posting exists for day
                        for($j=0;$j < $eventcount;$j++) {
//						$str .= "<span class=\"title_txt\">-";
//						$str .= "<a href=\"".ROOT_URL."/kalendaracara/" . $eventdata[$day]["id"][$j] . "\" onclick=\"return hs.htmlExpand(this, { contentId: 'highslide-html', objectType: 'iframe', objectWidth: 450, objectHeight: 350} )\" class=\"link\">";
						$str .= "<a href=\"".ROOT_URL."/kalendaracara/" . $eventdata[$day]["id"][$j] . "\">";
//						$str .= "<a href=\"".ROOT_URL."/app/dms/widgets_calendar/open-posting/pid/" . $eventdata[$day]["id"][$j] . "?height=300&width=400\" class=\"thickbox\">";
//						$str .= "<a href=\"javascript:openActionDialog('openPosting'," . $eventdata[$day]["id"][$j] . ")\" class=\"link\">";
//                                $str .= "<a href=\"javascript:openPosting(" . $eventdata[$day]["id"][$j] . ")\" class=\"link\">";
                                $str .= $day . "</a>";
//						$str .= $eventdata[$day]["title"][$j] . "</a></span>" . $eventdata[$day]["timestr"][$j];
                        }
                        }
                        else
                        {
                                $str .= "<span class=\"day_number\">";
                                $str .= "$day";
                                $str .= "</span><br>";
                        }
                        $str .= "</td>\n";
                        $day++;
                } elseif($day == 0)  {
                    $str .= "	<td class=\"empty_day_cell\" valign=\"top\">&nbsp;</td>\n";
                    $weekpos--;
                    if ($weekpos == 0) $day++;
                } else {
                    $str .= "	<td class=\"empty_day_cell\" valign=\"top\">&nbsp;</td>\n";
                }
            }
            $str .= "</tr>";
        }

        $today = strftime("%d %B %Y",strtotime(date("Y-m-d")));
        $str .= "<tr><td colspan='8' class='footer'><a href='' class='footerNav'>Hari ini adalah $today</a></td></tr>\n\n";
        $str .= "</table>\n\n";
        return $str;
    }

    /**
     * get month and year with scrollArrows
     * @param $m, $y
     * @return month and year
     */

     function scrollArrows($m, $y)
     {
        // set variable for month scrolling
        $nextyear = ($m!=12)? $y : $y + 1;
        $prevyear = ($m!=1)? $y : $y - 1;
        $prevmonth = ($m==1)? 12 : $m - 1;
        $nextmonth = ($m==12)? 1 : $m + 1;

        $fobj = new Kutu_Lib_Ajax();

        $s = $fobj->add_get_link("<img src=\"../../../../library/resources/images/leftArrow.gif\" border=\"0\">",KUTU_ROOT_URL."/app/np/widget/calendarmanager/calendarshow/idParse/leftArrow/month/" . $prevmonth . "/year/" . $prevyear,'showDiv1',true);
        $s .= $fobj->add_get_link("<img src=\"../../../../library/resources/images/rightArrow.gif\" border=\"0\">",KUTU_ROOT_URL."/app/np/widget/calendarmanager/calendarshow/idParse/rightArrow/month/". $nextmonth . "/year/" . $nextyear,'showDiv1',true);

//		$s = "<a href='javascript:;' onclick='month=\"$prevmonth\";year=\"$prevyear\"; calendar(month,year);'>\n";
//		$s .= "<img src=\"./public/images/icons/leftArrow.gif\" border=\"0\"></a> ";

        return $s;

     }

    /**
     * get_month_name
     * get current local month name
     * @param int month from 1 to 12
     * @return string month name
     */

    function get_month_name($month,$format = null)
    {
        if ($format != null)
            return strftime($format,gmmktime(0,0,0,$month,1,2007));
        else
            return strftime("%B",gmmktime(0,0,0,$month,1,2007));
    }
}