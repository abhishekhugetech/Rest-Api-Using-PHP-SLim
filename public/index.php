<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../includes/DbOperations.php';
require '../includes/Constants.php';

$app = new \Slim\App;

/*
 * Adding Basic Authentication to the Rest Api
 */

$app->add(new Tuupola\Middleware\HttpBasicAuthentication([
    "secure" => false,
    "users" => [
        "abhishek" => "11AAaa@@"
    ]
]));

/*
 * Creating a new Endpoint for creating New User
 * Parameters: name,email,password,school
 * Method: POST
 */

$app->post('/createuser', function (Request $request, Response $response) {
    if (!haveEmptyParameters(array("name", "email", "password", "school"), $request, $response)) {

        $request_date = $request->getParsedBody();

        $email = $request_date["email"];
        $name = $request_date["name"];
        $password = $request_date["password"];
        $school = $request_date["school"];

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $db->createUser($name, $email, $hashed_password, $school);

        if ($result == USER_CREATED) {

            $message = array();
            $message["error"] = false;
            $message['message'] = "User created";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);

        } elseif ($result == USER_FAILURE) {

            $message = array();
            $message["error"] = true;
            $message['message'] = "Failed to Create new User";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);

        } elseif ($result == USER_EXISTS) {

            $message = array();
            $message["error"] = true;
            $message['message'] = "User already exists";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);

        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);

});

$app->post("/userlogin", function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('email', 'password'), $request, $response)) {
        $request_date = $request->getParsedBody();

        $email = $request_date["email"];
        $password = $request_date["password"];

        $db = new DbOperations;

        $result = $db->userLogin($email, $password);

        if ($result == USER_AUTHENTICATED) {

            $return_data = array();

            $user = $db->getUserByEmail($email);

            $return_data['error'] = false;
            $return_data['message'] = "Login Successful";
            $return_data['user'] = $user;

            $response->write(json_encode($return_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);

        } elseif ($result == USER_NOT_FOUND) {

            $message = array();
            $message["error"] = true;
            $message['message'] = "User Not Found";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(404);


        } elseif ($result == USER_PASSWORD_INCORRECT) {

            $message = array();
            $message["error"] = true;
            $message['message'] = "Invalid Credentials Found";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }

    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});

$app->get("/getallusers", function (Request $request, Response $response) {

    $db = new DbOperations;

    $users = $db->getAllUsers();

    $response_data = array();

    $response_data['error'] = false;
    $response_data['users'] = $users;

    $response->write(json_encode($response_data));


    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

$app->put("/updateuser/{id}", function (Request $request, Response $response, array $args) {

    $id = $args['id'];

    if (!haveEmptyParameters(array('name', 'email', 'school'), $request, $response)) {

        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $db = new DbOperations;

        if ($db->updateUser($id, $name, $email, $school)) {
            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = "User Updated Successfully";
            $user = $db->getUserById($id);
            $response_data['user'] = $user;
            $response->write(json_encode($response_data));


            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else {

            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = "Failed to Update User";
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user;
            $response->write(json_encode($response_data));


            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(304);
        }

    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(304);
});

$app->delete("/deleteuser/{email}", function (Request $request, Response $response, array $args) {

    $email = $args['email'];

    $db = new DbOperations;

    $result = $db->deleteUserByEmail($email);

    if ($result == USER_DELETED) {

        $message = array();
        $message["error"] = false;
        $message['message'] = "User Deleted from Server";

        $response->write(json_encode($message));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(201);

    } elseif ($result == USER_DELETION_FAILED) {

        $message = array();
        $message["error"] = true;
        $message['message'] = "Failed to Delete User";

        $response->write(json_encode($message));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);

    } elseif ($result == USER_NOT_FOUND) {

        $message = array();
        $message["error"] = true;
        $message['message'] = "User not Found";

        $response->write(json_encode($message));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);

    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});

$app->put("/changepassword", function (Request $request, Response $response) {

    if (!haveEmptyParameters(array("currentpassword", "newpassword", "email"), $request, $response)) {

        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $currentpassword = $request_data['currentpassword'];
        $newpassword = $request_data['newpassword'];

        $db = new DbOperations;

        $result = $db->updatePassword($currentpassword, $newpassword, $email);

        if ($result == PASSWORD_CHANGED) {

            $response_data = array();
            $response_data['error'] = false;
            $response_data['message'] = "Password Changed";
            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);

        } elseif ($result == PASSWORD_CHANGE_FAILED) {

            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = "Password Change Failed";
            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(411);

        } elseif ($result == PASSWORD_DO_NOT_MATCH) {

            $response_data = array();
            $response_data['error'] = true;
            $response_data['message'] = "Password You Provided do not match";
            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);

        }


    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(423);
});

function haveEmptyParameters($required_params, $request, $response)
{
    $error = false;
    $error_params = "";
    $request_params = $request->getParsedBody();

    foreach ($required_params as $eachrequest) {
        if (!isset($request_params[$eachrequest]) || strlen($request_params[$eachrequest]) <= 0) {
            $error = true;
            $error_params .= $eachrequest . " , ";
        }
    }

    if ($error) {
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = "Required parameters " . substr($error_params, 0, -2) . "was not found";
        $response->write(json_encode($error_detail));
    }
    return $error;
}

$app->run();