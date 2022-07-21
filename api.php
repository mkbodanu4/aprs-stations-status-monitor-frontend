<?php

include __DIR__ . DIRECTORY_SEPARATOR . "common.php";

if (!isset($_GET['key']) || $_GET['key'] !== getenv('API_KEY')) {
    http_response_code(403);
    exit;
}

$regions = array();
$stmt = $db->prepare("SELECT * FROM `regions`;");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_object()) {
    $regions[] = $row;
}
$stmt->close();

$statuses = array();
$stmt = $db->prepare("
SELECT
    `r`.`region_id` as `region_id`,
    `r`.`title` as `region_title`,
    `c`.`value` as `call_sign`,
    `s`.`date_last_heard`,
    `s`.`path`,
    `s`.`date_refreshed`
FROM
    `status` `s`
LEFT JOIN `call_signs` `c` ON `c`.`call_sign_id` = `s`.`call_sign_id`
LEFT JOIN `regions` `r` ON `r`.`region_id` = `c`.`region_id`
;");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_object()) {
    $statuses[] = $row;
}
$stmt->close();

http_response_code(200);
header("Content-type:application/json");
echo json_encode(array(
    'regions' => $regions,
    'data' => $statuses
));
exit;