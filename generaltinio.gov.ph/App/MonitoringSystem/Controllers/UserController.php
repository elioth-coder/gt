<?php
namespace App\MonitoringSystem\Controllers;
use App\MonitoringSystem\Utility\DBConnection;
use Laminas\Diactoros\Response\JsonResponse;
use Intervention\Image\ImageManagerStatic as Image;
use \PDO;
use \Exception;

class UserController
{
    public function get($id)
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user['password'] = null;

            return new JsonResponse([
                "status"  => "success",
                "user"    => $user,
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
            $stmt = $conn->prepare("SELECT COUNT(`id`) as `result` FROM `user` WHERE `id` <> 1");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "count" => $count['result'],
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
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id` <> 1");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return new JsonResponse([
                "status"  => "success",
                "users" => $users,
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
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("DELETE FROM `user` WHERE `id`=:id");
            $stmt->execute(['id' => $id]);

            if ($user['photo'])
                $this->deletePhoto($user['photo']);

            return new JsonResponse([
                "status"  => "success",
                "message" => "Successfully deleted user.",
            ]);
        } catch (\Exception $e) {
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
            $post['password'] = sha1($post['password']);

            $conn = DBConnection::create();
            $stmt = $conn->prepare(
                "INSERT INTO user (username, password, first_name, last_name, other_details, photo, role)
                VALUES (:username, :password, :first_name, :last_name, :other_details, :photo, :role)
                "
            );
            $stmt->execute($post);
            $user_id = $conn->lastInsertId();

            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=:user_id");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user['password'] = null;

            return new JsonResponse([
                "status"  => "success",
                "user"    => $user,
                "message" => "Successfully added new system user.",
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
            if (trim($post['password'])) {
                $post['password'] = sha1($post['password']);
            } else {
                unset($post['password']);
            }
            $conn = DBConnection::create();

            if (!$post['photo']) {
                unset($post['photo']);

                if (!$post['photo_src']) {
                    $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=:user_id");
                    $stmt->execute(['user_id' => $post['id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        $this->deletePhoto($user['photo']);
                    }
                    $post['photo'] = ""; // to erase existing photo from db.
                }
            }
            unset($post['photo_src']);

            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `username`=:username");
            $stmt->execute(['username' => $post['username']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($users) >= 1) {
                $user = $users[0];
                if ($user['id'] != $post['id']) {
                    return new JsonResponse([
                        "status"         => "error",
                        "ids"            => [$user['user'], $post['id']],
                        "message"        => "Username already exists.",
                    ]);
                }
            }

            $sql =
                "UPDATE `user` SET 
                `username`=:username, " .
                (isset($post['password']) ? "`password`=:password, " : "") .
                "`first_name`=:first_name, 
                `last_name`=:last_name, 
                `other_details`=:other_details,  " .
                (isset($post['photo']) ? "`photo`=:photo, " : "") .
                "`role`=:role
            WHERE `id`=:id
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($post);

            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `id`=:user_id");
            $stmt->execute(['user_id' => $post['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user['password'] = null;

            return new JsonResponse([
                "status"  => "success",
                "user" => $user,
                "message" => "Successfully updated the user.",
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
            $name = 'user_' . microtime(true) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
            $image = Image::make($file['tmp_name']);
            $image->orientate()
                ->fit(300, 300)
                ->save('./uploads/' . $name);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function login()
    {
        try {
            $conn = DBConnection::create();
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `username`=:username and `password`=SHA1(:password)");
            $stmt->execute([
                "username" => $_POST['username'],
                "password" => $_POST['password'],
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return new JsonResponse([
                    "status" => "error",
                    "message" => "Invalid username or password.",
                ]);
            } else {
                $user['password'] = null;
                $_SESSION['user'] = $user;

                return new JsonResponse([
                    "status"  => "success",
                    "user"    => $user,
                    "message" => "Successfully logged in.",
                ]);
            }
        } catch (Exception $e) {
            return new JsonResponse([
                "status"  => "error",
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function logout()
    {
        session_destroy();
        return new JsonResponse([
            "status" => "success",
            "message" => "Successfully logged out.",
        ]);
    }

    public function me()
    {
        return new JsonResponse([
            "status" => "success",
            "user" => $_SESSION['user'] ?? null,
        ]);
    }
}
