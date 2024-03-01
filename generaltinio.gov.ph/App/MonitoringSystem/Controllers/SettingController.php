<?php
namespace App\MonitoringSystem\Controllers;
use App\MonitoringSystem\Utility\DBConnection;
use Laminas\Diactoros\Response\JsonResponse;

use \PDO;
use \PDOException;
use \Exception;

class SettingController
{
    public function get($id)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("SELECT * FROM `setting` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "setting" => $setting,
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
            $stmt = $conn->prepare("SELECT COUNT(`id`) as `result` FROM `setting`");
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
            $stmt = $conn->prepare("SELECT * FROM `setting`");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "settings" => $settings,
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
            $stmt = $conn->prepare("DELETE FROM `setting` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);

            return new JsonResponse([
                "status"  => "success",
                "message" => "Successfully deleted setting.",
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
                "INSERT INTO setting (field, value)
                VALUES (:field, :value)
                "
            );
            $stmt->execute($post);
            $setting_id = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM `setting` WHERE `id`=:setting_id");
            $stmt->execute(['setting_id' => $setting_id]);
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "setting" => $setting,
                "message" => "Successfully added setting.",
            ]);
        } catch (PDOException $e) {
            $errorCode = $e->errorInfo[1];

            return new JsonResponse([
                "status"  => "error",
                "message" => ($errorCode == 1062) 
                    ? 'Failed to add, setting already exists.'
                    : $e->getMessage()
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
                "UPDATE `setting` SET 
                    `field`=:field, 
                    `value`=:value 
                WHERE `id`=:id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($post);

            $stmt = $conn->prepare("SELECT * FROM `setting` WHERE `id`=:setting_id");
            $stmt->execute(['setting_id' => $post['id']]);
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"   => "success",
                "setting" => $setting,
                "message"  => "Successfully updated the setting.",
            ]);
        } catch (PDOException $e) {
            $errorCode = $e->errorInfo[1];

            return new JsonResponse([
                "status"  => "error",
                "message" => ($errorCode == 1062) 
                    ? 'Failed to update, setting already exists.'
                    : $e->getMessage()
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }
}
