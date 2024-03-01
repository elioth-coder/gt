<?php
namespace App\MonitoringSystem\Controllers;
use App\MonitoringSystem\Utility\DBConnection;
use Laminas\Diactoros\Response\JsonResponse;
use function _\orderBy;

use \PDO;
use \PDOException;
use \Exception;

class ScheduleController
{
    public function get($id)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("SELECT * FROM `schedule` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "schedule" => $schedule,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function count()
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT COUNT(`id`) as `result` FROM `schedule`
                 WHERE `year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                 AND `semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER')
            ");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status" => "success",
                "count"  => $count['result'],
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function read()
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "SELECT * FROM `schedule`
                 WHERE `year`=(SELECT `value` FROM `setting` WHERE `field`='SCHOOL_YEAR') 
                 AND `semester`=(SELECT `value` FROM `setting` WHERE `field`='SEMESTER')
            ");
            $stmt->execute();
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($schedules as $index => $schedule) {
                $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
                $stmt->execute(['teacher_id' => $schedule['teacher_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

                $schedules[$index]['teacher'] = $teacher;
            }
            $sortedSchedules = orderBy($schedules, ['teacher_id'], ['asc']);

            return new JsonResponse([
                "status"   => "success",
                "schedules" => $sortedSchedules,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function delete($id)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("DELETE FROM `schedule` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);

            return new JsonResponse([
                "status"  => "success",
                "message" => "Successfully deleted schedule.",
            ]);
        } catch (PDOException $e) {
            $errorCode = $e->errorInfo[1];

            return new JsonResponse([
                "status"  => "error",
                "message" => ($errorCode == 1451) 
                    ? 'Failed to delete, there is an existing related data.'
                    : $e->getMessage()
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        } 
    }

    public function create()
    {
        try {
            $post = $_POST;

            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "INSERT INTO schedule (year, semester, building, room, start_time, end_time, day, teacher_id)
                VALUES (:year, :semester, :building, :room, :start_time, :end_time, :day, :teacher_id)
                "
            );
            $stmt->execute($post);
            $schedule_id = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM `schedule` WHERE `id`=:schedule_id");
            $stmt->execute(['schedule_id' => $schedule_id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "schedule" => $schedule,
                "message" => "Successfully added schedule.",
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function update()
    {
        $post = $_POST;
        try {
            $conn = DBConnection::create();

            $sql =
                "UPDATE `schedule` SET 
                    `year`=:year, 
                    `semester`=:semester, 
                    `building`=:building, 
                    `room`=:room, 
                    `start_time`=:start_time,
                    `end_time`=:end_time,
                    `day`=:day,
                    `teacher_id`=:teacher_id
                WHERE `id`=:id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($post);

            $stmt = $conn->prepare("SELECT * FROM `schedule` WHERE `id`=:schedule_id");
            $stmt->execute(['schedule_id' => $post['id']]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "schedule" => $schedule,
                "message"  => "Successfully updated the schedule.",
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }
}
