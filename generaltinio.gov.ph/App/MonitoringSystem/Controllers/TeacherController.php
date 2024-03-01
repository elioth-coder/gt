<?php
namespace App\MonitoringSystem\Controllers;
use App\MonitoringSystem\Utility\DBConnection;
use Laminas\Diactoros\Response\JsonResponse;
use Intervention\Image\ImageManagerStatic as Image;
use \PDO;
use \PDOException;
use \Exception;

class TeacherController
{
    public function get($id)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "teacher" => $teacher,
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
            $stmt = $conn->prepare("SELECT COUNT(`id`) as `result` FROM `teacher`");
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
            $stmt = $conn->prepare("SELECT * FROM `teacher`");
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "teachers" => $teachers,
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
            $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("DELETE FROM `teacher` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);

            if ($teacher['photo'])
                $this->deletePhoto($teacher['photo']);

            return new JsonResponse([
                "status"  => "success",
                "message" => "Successfully deleted teacher.",
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
            $file = $_FILES['photo'];

            $post['photo'] = ($file['name']) ? $this->savePhoto($file) : "";

            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "INSERT INTO teacher (first_name, last_name, gender, email, photo)
                VALUES (:first_name, :last_name, :gender, :email, :photo)
                "
            );
            $stmt->execute($post);
            $teacher_id = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
            $stmt->execute(['teacher_id' => $teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "teacher" => $teacher,
                "message" => "Successfully added teacher.",
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
            $file = $_FILES['photo'];

            $post['photo'] = ($file['name']) ? $this->savePhoto($file) : "";

            $conn = DBConnection::create();

            if (!$post['photo']) {
                if (!$post['photo_src']) {
                    $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
                    $stmt->execute(['teacher_id' => $post['id']]);
                    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($teacher) {
                        $this->deletePhoto($teacher['photo']);
                    }
                    $post['photo'] = ""; // to erase existing photo from db.
                } else {
                    $post['photo'] = $post['photo_src'];
                }
            }
            unset($post['photo_src']);

            $sql =
                "UPDATE `teacher` SET 
                    `first_name`=:first_name, 
                    `last_name`=:last_name, 
                    `gender`=:gender,  
                    `photo`=:photo, 
                    `email`=:email
                WHERE `id`=:id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($post);
            // $stmt->debugDumpParams();

            $stmt = $conn->prepare("SELECT * FROM `teacher` WHERE `id`=:teacher_id");
            $stmt->execute(['teacher_id' => $post['id']]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "teacher" => $teacher,
                "message" => "Successfully updated the teacher.",
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    private function savePhoto($file)
    {
        try {
            $name = 'teacher_' . microtime(true) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
            $image = Image::make($file['tmp_name']);
            $image->orientate()
                ->fit(300, 300)
                ->save('./uploads/' . $name);
        } catch (Exception $e) {
            throw $e;
        }

        return $name;
    }

    private function deletePhoto($name)
    {
        try {
            $file_path = './uploads/' . $name;

            if (is_file($file_path)) {
                unlink($file_path);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
