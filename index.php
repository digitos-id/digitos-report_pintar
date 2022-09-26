<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>;.

/**
 * @package     Report_pintar_analytics
 * @copyright   2022 Prihantoosa <toosa@digitos.id> 
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB,$OUTPUT;

use core\report_helper;

require_once('../../config.php');
require_once('../../completion/classes/external.php');
require_once($CFG->dirroot.'/completion/classes/external.php');

$id          = optional_param('id', 0, PARAM_INT);// Course ID.


$params = array();
if (!empty($id)) {
    $params['id'] = $id;
} else {
    $id = $SITE->id;
}


$url = new moodle_url("/report/pintar/index.php", $params);

$PAGE->set_url(new moodle_url('/report/pintar/index.php'),array('id'=>$id));
$PAGE->set_pagelayout('report');

# $PAGE->set_title($SITE->fullname);
# $string['pluginname']='Greetings';
# $PAGE->set_heading(get_string('pluginname','block_pintar_analytic'));

// Get course details.
if ($id != $SITE->id) {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($course->id);
} else {
    $course = $SITE;
    require_login();
    $context = context_system::instance();
    $PAGE->set_context($context);
}

if ($id == $SITE->id) {
    admin_externalpage_setup('reportlog', '', null, '', array('pagelayout' => 'report'));
    $PAGE->set_title($SITE->shortname .': '. $strlogs);
    $PAGE->set_primary_active_tab('siteadminnode');
} else {
    $PAGE->set_title($course->shortname .': '. $strlogs);
    $PAGE->set_heading($course->fullname);
}


echo $OUTPUT->header();

if (isloggedin()) {
    echo '<h2>PIC: ' . fullname($USER) . '</h2>';
} else {
    echo '<h2>Anda belum login</h2>';
}
echo '<h2>Course ID:'. $id.'</h2>';

//Hitung Completion
//
$coursecontext = context_course::instance($id);
$enrolledstudents = get_enrolled_users($coursecontext, 'moodle/course:isincompletionreports');

$totalenrolledstudents = count($enrolledstudents);

$already70=0;
$still30=0;
$persen70=0;
$persen30=0;
$prosen_assignmentcompleted=0; //di atas 90%
$total_assignmentcompleted=0; //jumlah user yang assignment completed di atas 90%
$total_prosen_assignmentcompleted=0; //di atas 90%
$total_assignmentNOTcompleted=0; //di atas 90%
$prosen_total_assignmentcompleted=0;

   foreach ($enrolledstudents as $user) {
	//Menghitung status setiap user
	$course_user_stat = custom_get_user_course_completion($id,$user->id);

	$activities = $course_user_stat['statuses'];
	// Banyaknya aktivitas
	$totalactivities = count($activities);
		// nilai awal setiap user
                $completed = 0;
		$iscomplete = false;
		$jum_assignment = 0;
		$jum_assignmentcompleted = 0;
		$prosentase_assignmentcomplete = 0;

		foreach($activities as $activity){
		       
	   	    if($activity['modname']=='assign')$jum_assignment+=1;	
		     if($activity['timecompleted']!=0 && 
		        $activity['modname']=='assign')$jum_assignmentcompleted+=1;

		    # var_dump($activity['modname'],$assigncount);
		    # die();

                    if($activity['timecompleted']!=0)$completed+=1;
		}

		$prosen_assignmentcompleted = $jum_assignmentcompleted / $jum_assignment * 100;	

		  # var_dump($completed, $jum_assignment, $jum_assignmentcompleted,$prosen_assignmentcompleted);
	      	  # die();
		if ($prosen_assignmentcompleted >=90)$total_assignmentcompleted+=1;

                if($totalactivities>0){
                $studentcompletion=($completed/$totalactivities)*100;
                } else {$studentcompletion=1;}
                # $studentcompletion=($completed/$totalactivities)*100;
                if($studentcompletion>69)$already70+=1;
                else $still30 +=1;


	}

// End of hitung completion
//

echo 'Total students:'.$totalenrolledstudents."<br>";
echo 'Total activities:'.$totalactivities."<br>";
echo 'Total Complete Assignment:'.$total_assignmentcompleted."<br>";
echo 'Diatas 70%:'.$already70."<br>";
echo 'Dibawah 30%:'.$still30."<br>";
echo 'Total Penugasan > 90%:'.$total_assignmentcompleted." orang<br>";
$total_assignmentNOTcompleted=$totalenrolledstudents-$total_assignmentcompleted;
$prosen_total_assignmentcompleted = ($total_assignmentcompleted / $totalenrolledstudents)*100;
echo 'Total Penugasan < 90%:'.$total_assignmentNOTcompleted." orang<br>";
echo 'Prosentase Penugasan > 90%:'.$prosen_total_assignmentcompleted." %<br>";

            // Nilai Prosentase
$persen70 = $already70/$totalenrolledstudents*100;
$persen30 = $still30/$totalenrolledstudents*100;


$chart = new core\chart_bar();
            $serie1 = new core\chart_series('Penyelesaian <30%', [$persen30]);
            $serie2 = new core\chart_series('Penyelesaian >70%', [$persen70]);
            $serie3 = new core\chart_series('Penugasan >90%', [$prosen_total_assignmentcompleted]);
            # $serie3 = new core\chart_series('Penugasan >90%', [16, 8.5,7.6,20.3 ]);

            $chart->set_title('Keterlibatan dan Keaktifan Peserta');
            $chart->add_series($serie1);
	    $chart->add_series($serie2);
	    $chart->add_series($serie3);

	    # $yaxis = $chart->get_yaxis(1,true);
	    # $yaxis->set_max(50);
	    # $yaxis->set_min(0);
	    # $yaxis->title(Dalam %');

echo $OUTPUT->render($chart);
# foreach ($id as $iduser) {
#  echo $OUTPUT->render($iduser);
# }

echo $OUTPUT->footer();

function custom_get_user_course_completion($courseid,$userid){
        $course = get_course($courseid);
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

        $completion = new completion_info($course);
        $activities = $completion->get_activities();
        $result = array();
        foreach ($activities as $activity) {

	$cmcompletion = \core_completion\cm_completion_details::get_instance($activity, $user->id);
	print_object($activity->modname);


	# var_dump($modtype);
	# die();
        $cmcompletiondetails = $cmcompletion->get_details();
        # $cmcompletiondetails = $cmcompletion->get_details('modname');

	# var_dump($cmcompletiondetails);
	# die();
	
        $details = [];
        foreach ($cmcompletiondetails as $rulename => $rulevalue) {
            $details[] = [
                'rulename' => $rulename,
                'rulevalue' => (array)$rulevalue,
            ];
	}

        $result[]=[
	    'state'         => $cmcompletion->get_overall_completion(),
            'timecompleted' => $cmcompletion->get_timemodified(),
            'overrideby'    => $cmcompletion->overridden_by(),
            'hascompletion'    => $cmcompletion->has_completion(),
            'isautomatic'      => $cmcompletion->is_automatic(),
            'istrackeduser'    => $cmcompletion->is_tracked_user(),
            'overallstatus'    => $cmcompletion->get_overall_completion(),
            'modname'    	=> $activity->modname,
	    'details'          => $details,
	];


	# var_dump($result);
	# die();
	}

    $results = array(
        'statuses' => $result,
    );
    return $results;

   }
