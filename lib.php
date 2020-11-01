<?php 
    function get_course_image($contextId) {
        global $DB, $CFG;
        $imgName = $DB->get_record('files', ['contextid' => $contextId, 'filearea' => 'overviewfiles', 'component' => 'course'])->filename;
        $url = $CFG->wwwroot . "/pluginfile.php/" . $contextId . "/course/overviewfiles/" . $imgName;
        return $url;
    }