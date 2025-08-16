<?php

namespace block_catquiz_feedbackwizard;

class catquiz_data {

    public static function get_catquiz_by_couseid (int $courseid): array {
        global $DB;

        if ((!$courseid) || ($courseid < 1)) {
            return [];
        }

        $sql = "SELECT aq.id id, aq.name name, aq.course, lct.catscaleid catscaleid
          FROM {adaptivequiz} aq
          LEFT JOIN {local_catquiz_tests} lct ON aq.id = lct.componentid AND lct.component='mod_adaptivequiz'
          WHERE aq.course = :courseid";

        $records = $DB->get_records_sql($sql,['courseid' => $courseid]);

        return $records;
    }

    public static function get_courses_with_catquiz (): array {
        global $DB;

        return [];
    }
}
