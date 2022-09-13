<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin,Access-Control-Allow-Methods");
$allowedMethods = array(
    'POST'
);

//The current request type.
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);


if(!in_array($requestMethod, $allowedMethods)){
    header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
    exit;
}
// echo json_encode(getallheaders());
$data = json_decode(file_get_contents("php://input"));
$errors = [];
if (!isset($data->username)) {
    $errors["username"]="Veuillez renseigner le username";
}
if (!isset($data->password)) {
    $errors["password"]="Veuillez renseigner le password";
}
if (empty($errors)) {
    $username = htmlspecialchars(strip_tags($data->username));
    $password = htmlspecialchars(strip_tags($data->password));
    $login_post = array(
        'email' => $username,
        'password' => $password,
    );
    $staff = new Staff_model();
    $response = $staff->apiLogin($login_post);
    http_response_code($response["code"]);
    echo json_encode($response);
}else{
    http_response_code(401);
    echo json_encode($errors);
}
