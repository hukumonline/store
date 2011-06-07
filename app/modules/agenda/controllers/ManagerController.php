<?php

/**
 * Description of ManagerController
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Agenda_ManagerController extends Zend_Controller_Action
{
    function browseAction()
    {
        if ($this->getRequest()->isXmlHttpRequest())
        {
            $this->_helper->layout()->disableLayout();
        }
        
        $month = (int) $this->_getParam('month');
        $year = (int) $this->_getParam('year');

        // set month and year to present if month
        // and year not received from query string
        $m = (!$month)? date("n") : $month;
        $y = (!$year)? date("Y") : $year;

        $calendar = new Pandamp_Lib_Calendar();

        $this->view->select_calender = $calendar->writeCalendar($m,$y);
    }
    function openpostingAction()
    {
        $this->_helper->layout->setLayout('layout-event');
        $lang['days'] = array("Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu");
        $lang['months'] = array("Januari","Februari","Maret","April","May","Juni","Juli","Agustus","September","Oktober","November","Desember");

        $pid = $this->_getParam('pid');

        $tblCalendar = new App_Model_Db_Table_Calendar();
        $row = App_Model_Show_Calendar::show()->openPosting($pid);

        $d = $row[0]['d'];
        $m = $row[0]['m'];
        $y = $row[0]['y'];
        $dateline = $d." ".$lang['months'][$m-1] ." ".$y;
        $wday = date("w", mktime(0,0,0,$m,$d,$y));

        $this->view->dateline = $dateline;
        $this->view->wday = $lang['days'][$wday-1];

        // write posting
        $rowposting = App_Model_Show_Calendar::show()->writePosting($pid);

        $title = stripslashes($rowposting[0]['title']);
        $body = stripslashes(str_replace("\n", "<br />", $rowposting[0]['text']));
        $postedby 	= "Posted by : " . $rowposting[0]['username'];

        if (!($rowposting[0]["start_time"] == "55:55:55" && $rowposting[0]["end_time"] == "55:55:55")) {
                if ($rowposting[0]["start_time"] == "55:55:55")
                        $starttime = "- -";
                else
                        $starttime = $rowposting[0]["stime"];

                if ($rowposting[0]["end_time"] == "55:55:55")
                        $endtime = "- -";
                else
                        $endtime = $rowposting[0]["etime"];

                $timestr = "$starttime - $endtime";
        } else {
                $timestr = "";
        }

        $this->view->title = $title;
        $this->view->body = $body;
        $this->view->postedby = $postedby;
        $this->view->timestr = $timestr;
        $this->view->pid = $pid;
        $this->view->month = $m;
        $this->view->year = $y;

        $this->_helper->layout()->headerTitle = "Event Calendar";
    }
}
?>
