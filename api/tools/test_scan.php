<?php
$url = 'http://localhost/project_lavirameal/api/kantin/kantin_proses_makan.php';
$data = ['student_token' => '80e4e417-0390-4c0d-9c38-31e832493122_91837462', 'kantin_id' => 'de686457-3358-4cfd-a93e-4b480a8d60b1'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "RESPONSE: " . $result;
?>
