<?php

class DbOperations
{

    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    /*
     * Creating a New Student
     */
    public function createUser($name, $email, $password, $school)
    {
        if (!$this->doesEmailExists($email)) {
            $stmt = $this->con->prepare("INSERT INTO users ( name , email , password , school ) VALUES ( ? , ? , ? , ? )");
            $stmt->bind_param("ssss", $name, $email, $password, $school);
            if ($stmt->execute()) {
                return USER_CREATED;
            } else {
                return USER_FAILURE;
            }
        }
        return USER_EXISTS;
    }

    /*
     * Logging the User in
     */
    public function userLogin($email, $password)
    {
        if ($this->doesEmailExists($email)) {
            $hashed_password = $this->getUserPasswordByEmail($email);
            if (password_verify($password, $hashed_password)) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_INCORRECT;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }

    /*
     * Get User details by Email Id
     */
    public function getUserByEmail($email)
    {
        $stmt = $this->con->prepare("SELECT id,email,name,school from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $stmt->fetch();
        $user = array();
        $user['id'] = $id;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['school'] = $school;
        return $user;
    }

    /*
     * Delete User by their Email Id
     */
    public function deleteUserByEmail($email)
    {
        if ($this->doesEmailExists($email)) {
            $stmt = $this->con->prepare("DELETE FROM users where email = ?");
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                return USER_DELETED;
            } else {
                return USER_DELETION_FAILED;
            }
        }
        return USER_NOT_FOUND;

    }

    /*
     * Get User details by Id
     */
    public function getUserById($id)
    {
        $stmt = $this->con->prepare("SELECT id,email,name,school from users WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $stmt->fetch();
        $user = array();
        $user['id'] = $id;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['school'] = $school;
        return $user;
    }

    /*
     * Get All Users inside the Database
     */
    public function getAllUsers()
    {
        $stmt = $this->con->prepare("SELECT id,email,name,school from users ");
        $stmt->execute();
        $stmt->bind_result($id, $email, $name, $school);
        $users = array();
        while ($stmt->fetch()) {
            $user = array();
            $user['id'] = $id;
            $user['name'] = $name;
            $user['email'] = $email;
            $user['school'] = $school;
            array_push($users, $user);
        }
        return $users;
    }

    /*
     * Method for Updating an Existing User
     */
    public function updateUser($id, $name, $email, $school)
    {
        $stmt = $this->con->prepare("UPDATE users set name = ? , email = ? , school = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $school, $id);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Method for Updating User's Password
     */
    public function updatePassword($old_password, $new_password, $email)
    {
        $hashed_password = $this->getUserPasswordByEmail($email);
        if (password_verify($old_password, $hashed_password)) {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $this->con->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);

            if ($stmt->execute()) {
                return PASSWORD_CHANGED;
            } else {
                return PASSWORD_CHANGE_FAILED;
            }

        } else {
            return PASSWORD_DO_NOT_MATCH;
        }
    }

    /*
     * Private methods only for In Class access
     */

    private function getUserPasswordByEmail($email)
    {
        $stmt = $this->con->prepare("SELECT password from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->fetch();
        return $password;
    }

    private function doesEmailExists($email)
    {
        $stmt = $this->con->prepare(" SELECT id FROM users WHERE email = ? ");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->store_result();

        return $stmt->num_rows > 0;


    }

}