<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Lecture des données
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "ERROR", "message" => "Données vides"]);
    exit;
}

// Données minimales pour PawaPay Sandbox
$apiKey = "eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjE2MTk5IiwibWF2IjoiMSIsImV4cCI6MjA4OTY5OTY3NCwiaWF0IjoxNzc0MDgwNDc0LCJwbSI6IkRBRixQQUYiLCJqdGkiOiI2OTVjZmU5Zi05YWExLTQxNTUtODRjNC0zN2M2MjY1ZTBiNDcifQ.asYDBa_NnVrAtHBubSv5jN3a2y-y0GDBxz3rfDB5TGjUG6rxzwF8WJCJrNALYgPM5TUL-3hCRuFf4EI0cecGYw";
$apiUrl = "https://api.sandbox.pawapay.cloud/v1/deposits";

$phone = str_replace([' ', '+'], '', $input['phone']);
$ref = "INS-" . time();

$payload = [
    "depositId" => $ref,
    "amount" => (string)$input['amount'],
    "currency" => "USD",
    "correspondent" => $input['operator'],
    "pawaPayContactId" => $phone,
    "customerFirstName" => $input['client_name'],
    "customerLastName" => "Client",
    "billingAddress" => "RDC",
    "description" => "Formation",
    "payer" => [
        "address" => ["country" => "COD"],
        "name" => ["firstName" => $input['client_name'], "lastName" => "Client"],
        "contactId" => $phone
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $apiKey", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// On renvoie la réponse brute de PawaPay pour voir ce qui se passe
echo $response;
exit;
