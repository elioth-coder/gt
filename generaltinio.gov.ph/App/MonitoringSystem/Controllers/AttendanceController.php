<?php
namespace App\MonitoringSystem\Controllers;

use App\MonitoringSystem\Utility\DBConnection;
use Laminas\Diactoros\Response\JsonResponse;
use function _\map;

use \PDO;
use \Exception;

class AttendanceController
{
    public function create()
    {
        try {
            $post = $_POST;
            $time = strtotime($post['date']);
            $date = date('Y-m-d', $time);
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "INSERT INTO `attendance` (`schedule_date`, `remarks`, `status`, `date`, `schedule_id`, `teacher_id`)
                VALUES (:schedule_date, :remarks, :status, :date, :schedule_id, :teacher_id)"
            );

            $values = [
                'schedule_date' => ($date . '_' . $post['schedule_id']),
                'remarks'       => $post['remarks'],
                'status'        => $post['status'],
                'date'          => $date,
                'schedule_id'   => $post['schedule_id'],
                'teacher_id'    => $post['teacher_id'],
            ];
            $stmt->execute($values);
            $attendance_id = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM `attendance` WHERE `id`=:attendance_id");
            $stmt->execute(['attendance_id' => $attendance_id]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"     => "success",
                "attendance" => $attendance,
                "post"       => $values,
                "message"    => "Successfully marked the attendance.",
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function months()
    {
        try {
            $months = [];
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT DISTINCT(UPPER(MONTHNAME(`attendance`.`date`))) AS `month` FROM `schedule`
                    INNER JOIN `attendance` ON `schedule`.`id` = `attendance`.`schedule_id`
                 WHERE `schedule`.`year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                    AND `schedule`.`semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER') 
                 ORDER BY `month` DESC
            "
            );
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $months[] = $row['month'];
            }

            return new JsonResponse([
                "status" => "success",
                "months" => $months,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function monthly_report($month)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT DISTINCT(teacher_id) AS `teacher_id` FROM `schedule`
                 WHERE `year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                 AND `semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER') 
            "
            );
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $teacher_ids = map($schedules, 'teacher_id');

            $sql = "
                SELECT *, id AS instructor_id,  
                (
                    SELECT COUNT(id) FROM attendance
                    WHERE teacher_id = instructor_id
                    AND LOWER(MONTHNAME(`date`))=LOWER(:month)
                    AND status='PRESENT'
                ) AS present_count,
                (
                    SELECT COUNT(id) FROM attendance
                    WHERE teacher_id = instructor_id
                    AND LOWER(MONTHNAME(`date`))=LOWER(:month)
                    AND status='ABSENT'
                ) AS absent_count 
                FROM teacher WHERE id IN (" . implode(',', $teacher_ids) . ")
                GROUP BY instructor_id
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['month' => $month]);
            $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $teachers = [];

            foreach ($instructors as $instructor) {
                $stmt = $conn->prepare(
                    "SELECT `date`, `remarks` FROM `attendance`
                    WHERE `status`='ABSENT' 
                        AND LOWER(MONTHNAME(`date`))=LOWER(:month)
                        AND `teacher_id`=:teacher_id
                "
                );
                $stmt->execute([
                    'teacher_id' => $instructor['id'],
                    'month'      => $month,
                ]);
                $remarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $instructor['remarks'] = $remarks;

                $teachers[] = $instructor;
            }

            return new JsonResponse([
                "status"   => "success",
                "teachers" => $teachers,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                // "debug"   => $stmt->debugDumpParams(),
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function delete($date)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("DELETE FROM `attendance` WHERE `schedule_date`=:schedule_date");
            $stmt->execute(['schedule_date' => $date]);

            return new JsonResponse([
                "status"  => "success",
                "message" => "Successfully deleted the attendance.",
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function to_check($date)
    {
        try {
            $time = strtotime($date);
            $date = date('Y-m-d', $time);

            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT * FROM `schedule`
                 WHERE `year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                    AND `semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER') 
                    AND `day`=UPPER(DAYNAME(:date))
                    AND `id` NOT IN (SELECT `schedule_id` FROM `attendance` WHERE `date`=:date)
            "
            );
            $stmt->execute(['date' => $date]);
            $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendances as $index => $to_check) {
                $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
                $stmt->execute(['teacher_id' => $to_check['teacher_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

                $attendances[$index]['teacher'] = $teacher;
            }

            return new JsonResponse([
                "status"  => "success",
                "date"    => $date,
                "attendances" => $attendances,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function checked($date)
    {
        try {
            $time = strtotime($date);
            $date = date('Y-m-d', $time);

            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT `schedule`.*, `attendance`.`schedule_date`, `attendance`.`remarks`, `attendance`.`status` FROM `attendance`
                 INNER JOIN `schedule` ON `schedule`.`id` = `attendance`.`schedule_id`
                 WHERE `attendance`.`date`=:date
                 ORDER BY `schedule`.`building` ASC, `schedule`.`start_time` ASC
            "
            );
            $stmt->execute(['date' => $date]);
            $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendances as $index => $checked) {
                $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
                $stmt->execute(['teacher_id' => $checked['teacher_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

                $attendances[$index]['teacher'] = $teacher;
            }

            return new JsonResponse([
                "status"  => "success",
                "attendances" => $attendances,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function buildings()
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT DISTINCT(`building`) AS `building` FROM `schedule`
                 WHERE `year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                    AND `semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER') 
            "
            );
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $buildings = [];

            foreach ($results as $result) {
                $buildings[] = $result['building'];
            }

            return new JsonResponse([
                "status"     => "success",
                "buildings"  => $buildings,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }
}
