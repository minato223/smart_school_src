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


if (!in_array($requestMethod, $allowedMethods)) {
    header($_SERVER["SERVER_PROTOCOL"] . " 405 Method Not Allowed", true, 405);
    exit;
}
if (isset(getallheaders()["authorization"])) {
    $data = json_decode(file_get_contents("php://input"));
    $errors = [];
    if (!isset($data->code)) {
        $errors["code"] = "Veuillez renseigner le code";
    }
    if (empty($errors)) {
        $code = htmlspecialchars(strip_tags($data->code));
        $token = explode("Bearer ", getallheaders()["authorization"])[1];
        $staff = new Staff_model();
        $response = $staff->createAttendance($token, $code);
        http_response_code($response["code"]);
        echo json_encode($response);
    } else {
        http_response_code(401);
        echo json_encode($errors);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Token invalide"]);
}
