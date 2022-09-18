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
 * @package     local_pintar_analytics
 * @copyright   2022 Prihantoosa <toosa@digitos.id> 
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB,$OUTPUT;

use core\report_helper;

require_once('../../config.php');
require_once('../../completion/classes/external.php');
require_once($CFG->dirroot.'/completion/classes/external.php');

# require_login();


$id          = optional_param('id', 0, PARAM_INT);// Course ID.


$params = array();
if (!empty($id)) {
    $params['id'] = $id;
} else {
    $id = $SITE->id;
}


# var_dump($courses);
# die();

# $courseid = $COURSE->id;
# $courseidx = $_GET('courseidx');
# echo "Test";
# echo $_REQUEST('courseidx');
# echo $courseidx;
# die();

# $courses = get_courses();
# foreach ($courses as $courseid => $course){
#         if($course->id==1)continue;
#         $coursecontext = context_course::instance($course->id);
#         $enrolledstudents = get_enrolled_users($coursecontext, 'moodle/course:isincompletionreports');
#         $already70='';
#         $still30='';
#         foreach ($enrolledstudents as $user) {
#                 $course_user_stat = core_completion_external::get_activities_completion_status($course->id,$user->id);
#                 $activities = $course_user_stat['statuses'];
#                 $totalactivities = count($activities);
#                 $completed = 0;
#                 foreach($activities as $activity){
#                         if($activity['timecompleted']!=0)$completed+=1;
#                 }
#                 $studentcompletion=($completed/$totalactivities)*100;
#                 if($studentcompletion>70)$already70+=1;
#                 else $still30 +=1;
# 
#         }
#         echo $course->fullname." diatas 70%: ".$already70."<br>";
#         echo $course->fullname." dibawah 30%: ".$still30."<br>";
# 
# }
# 
# die();

# $context = context_course::instance($courseid);
# $context = context_course::instance($id);
# $PAGE->set_context($context);

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


foreach ($enrolledstudents as $user) {
	$course_user_stat = custom_get_user_course_completion($id,$user->id);

	$activities = $course_user_stat['statuses'];


	$totalactivities = count($activities);

                $completed = 0;
                $iscomplete = false;
                foreach($activities as $activity){
                    if($activity['timecompleted']!=0)$completed+=1;
		}

                if($totalactivities>0){
                $studentcompletion=($completed/$totalactivities)*100;
                } else {$studentcompletion=1;}
                # $studentcompletion=($completed/$totalactivities)*100;
                if($studentcompletion>69)$already70+=1;
                else $still30 +=1;


            }
// End of hitung completion
echo 'Total students:'.$totalenrolledstudents."<br>";
echo 'Total activities:'.$totalactivities."<br>";
echo 'Diatas 70%:'.$already70."<br>";
echo 'Dibawah 30%:'.$still30."<br>";

            // Nilai Prosentase
$persen70 = $already70/$totalenrolledstudents*100;
$persen30 = $still30/$totalenrolledstudents*100;

# var_dump($still30);
# die();
#
// 
// Membaca data yang dikirim melalui URL berupa array yang dikirim menggunakan 
// $url + http_build_query($dataid);
//
// $idArray = explode('&',$_SERVER["QUERY_STRING"]);
# foreach ($idArray as $index => $avPair) {
#  list($ignore, $value) = explode('=',$avPair);
#  $id[$index] = $value;
# }

$chart = new core\chart_bar();
            $serie2 = new core\chart_series('Penyelesaian >70%', [$persen70]);
            $serie1 = new core\chart_series('Penyelesaian <30%', [$persen30]);
            # $serie3 = new core\chart_series('Penugasan >90%', [16, 8.5,7.6,20.3 ]);

            $chart->set_title('Keterlibatan dan Keaktifan Peserta');
            $chart->add_series($serie1);
	    $chart->add_series($serie2);

	    # $yaxis = $chart->get_yaxis(1,true);
	    # $yaxis->set_max(50);
	    # $yaxis->set_min(0);

# $chart = new core\chart_bar();
# $serie1 = new core\chart_series('Penyelesaian <30%', [65, 94, 80,71]);
# $serie2 = new core\chart_series('Penyelesaian >70%', [22, 6, 9,20]);
# $serie3 = new core\chart_series('Penugasan >90%', [16, 8.5,7.6,20.3 ]);
# # $serie4 = new core\chart_series('My series title4', [400, 460, 1120]);
# 
# $chart->set_title('Keterlibatan dan Keaktifan Peserta');
# $chart->add_series($serie1);
# $chart->add_series($serie2);
# $chart->add_series($serie3);
# # $chart->add_series($serie4);
# $chart->set_labels(['PTM Kepsek', 'PJJ-SMP', 'PJJ-SD', 'PJJ-Kepsek']);
# $chart->set_labels($labels);

# echo $OUTPUT->render("Test");
echo $OUTPUT->render($chart);
# foreach ($id as $iduser) {
#  echo $OUTPUT->render($iduser);
# }

echo $OUTPUT->footer();

# public static function custom_get_user_course_completion($courseid,$userid){
#         $course = get_course($courseid);
#         $user = core_user::get_user($userid, '*', MUST_EXIST);
#         core_user::require_active_user($user);
# 
#         $completion = new completion_info($course);
#         $activities = $completion->get_activities();
#         $result = array();
#         foreach ($activities as $activity) {
# 
#         $cmcompletion = \core_completion\cm_completion_details::get_instance($activity, $user->id);
#         $cmcompletiondetails = $cmcompletion->get_details();
# 
#         $details = [];
#         foreach ($cmcompletiondetails as $rulename => $rulevalue) {
#             $details[] = [
#                 'rulename' => $rulename,
#                 'rulevalue' => (array)$rulevalue,
#             ];
#         }
#         $result[]=[
#             'state'         => $cmcompletion->get_overall_completion(),
#             'timecompleted' => $cmcompletion->get_timemodified(),
#             'overrideby'    => $cmcompletion->overridden_by(),
#             'hascompletion'    => $cmcompletion->has_completion(),
#             'isautomatic'      => $cmcompletion->is_automatic(),
#             'istrackeduser'    => $cmcompletion->is_tracked_user(),
#             'overallstatus'    => $cmcompletion->get_overall_completion(),
#             'details'          => $details,
#         ];
#     }
#     $results = array(
#         'statuses' => $result,
#     );
#     return $results;

# }

# }
function custom_get_user_course_completion($courseid,$userid){
        $course = get_course($courseid);
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

        $completion = new completion_info($course);
        $activities = $completion->get_activities();
        $result = array();
        foreach ($activities as $activity) {

        $cmcompletion = \core_completion\cm_completion_details::get_instance($activity, $user->id);
        $cmcompletiondetails = $cmcompletion->get_details();

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
            'details'          => $details,
        ];
    }
    $results = array(
        'statuses' => $result,
    );
    return $results;

   }
