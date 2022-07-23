<?php

include __DIR__ . DIRECTORY_SEPARATOR . "common.php";

if (!isset($_GET['key']) || $_GET['key'] !== getenv('API_KEY')) {
    http_response_code(403);
    exit;
}

$groups = array();
$groups_stmt = $db->prepare("SELECT * FROM `groups`;");
$groups_stmt->execute();
$groups_result = $groups_stmt->get_result();
while ($groups_row = $groups_result->fetch_object()) {
    $groups[] = $groups_row;
}
$groups_stmt->close();

$call_signs = array();
$call_signs_stmt = $db->prepare("
SELECT
    `c`.`call_sign_id` as `call_sign_id`,
    `r`.`group_id` as `group_id`,
    `r`.`title` as `group_title`,
    `c`.`value` as `call_sign`
FROM
    `call_signs` `c`
LEFT JOIN `groups` `r` ON `r`.`group_id` = `c`.`group_id`
;");
$call_signs_stmt->execute();
$call_signs_result = $call_signs_stmt->get_result();
while ($call_signs_row = $call_signs_result->fetch_object()) {
    $call_sign = array(
        'call_sign' => $call_signs_row->call_sign,
        'group_id' => $call_signs_row->group_id,
        'group_title' => $call_signs_row->group_title,
        'date_last_activity' => NULL,
        'last_activity' => NULL,
        'last_from' => NULL,
        'last_path' => NULL,
        'last_raw' => NULL,
        'symbol_table' => NULL,
        'symbol' => NULL,
        'latitude' => NULL,
        'longitude' => NULL,
        'position_packet' => NULL,
        'object_packet' => NULL,
        'routing_packet' => NULL,
        'status_packet' => NULL,
        'telemetry_packet' => NULL,
        'weather_packet' => NULL
    );

    $position_stmt = $db->prepare("SELECT * FROM `positions` WHERE `call_sign_id` = ? LIMIT 1;");
    $position_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $position_stmt->execute();
    $position_result = $position_stmt->get_result();
    $position = $position_result->fetch_object();
    $position_stmt->close();
    if ($position) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($position->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $position->date;
            $call_sign['last_activity'] = 'position';
            $call_sign['last_from'] = $position->from;
            $call_sign['last_path'] = $position->path;
            $call_sign['last_raw'] = $position->raw;
        }

        if($call_sign['symbol_table'] === NULL && $call_sign['symbol'] === NULL) {
            $call_sign['symbol_table'] = $position->symbol_table;
            $call_sign['symbol'] = $position->symbol;
        }
        if($call_sign['latitude'] === NULL && $call_sign['longitude'] === NULL) {
            $call_sign['latitude'] = $position->latitude;
            $call_sign['longitude'] = $position->longitude;

        }

        $call_sign['position_packet'] = array(
            'date' => $position->date,
            'from' => $position->from,
            'path' => $position->path,
            'symbol_table' => $position->symbol_table,
            'symbol' => $position->symbol,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'comment' => $position->comment,
            'raw' => $position->raw,
        );
    }

    $object_stmt = $db->prepare("SELECT * FROM `objects` WHERE `call_sign_id` = ? LIMIT 1;");
    $object_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $object_stmt->execute();
    $object_result = $object_stmt->get_result();
    $object = $object_result->fetch_object();
    $object_stmt->close();
    if ($object) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($object->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $object->date;
            $call_sign['last_activity'] = 'object';
            $call_sign['last_from'] = $object->from;
            $call_sign['last_path'] = $object->path;
            $call_sign['last_raw'] = $object->raw;
        }

        $call_sign['object_packet'] = array(
            'date' => $object->date,
            'from' => $object->from,
            'object' => $object->object,
            'path' => $object->path,
            'symbol_table' => $object->symbol_table,
            'symbol' => $object->symbol,
            'latitude' => $object->latitude,
            'longitude' => $object->longitude,
            'comment' => $object->comment,
            'raw' => $object->raw,
        );
    }

    $routing_stmt = $db->prepare("SELECT * FROM `routing` WHERE `call_sign_id` = ? LIMIT 1;");
    $routing_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $routing_stmt->execute();
    $routing_result = $routing_stmt->get_result();
    $routing = $routing_result->fetch_object();
    $routing_stmt->close();
    if ($routing) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($routing->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $routing->date;
            $call_sign['last_activity'] = 'routing';
            $call_sign['last_from'] = $routing->from;
            $call_sign['last_path'] = $routing->path;
            $call_sign['last_raw'] = $routing->raw;
        }

        $call_sign['routing_packet'] = array(
            'date' => $routing->date,
            'from' => $routing->from,
            'path' => $routing->path,
            'symbol_table' => $routing->symbol_table,
            'symbol' => $routing->symbol,
            'latitude' => $routing->latitude,
            'longitude' => $routing->longitude,
            'comment' => $routing->comment,
            'raw' => $routing->raw,
        );
    }

    $status_stmt = $db->prepare("SELECT * FROM `status` WHERE `call_sign_id` = ? LIMIT 1;");
    $status_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $status = $status_result->fetch_object();
    $status_stmt->close();
    if ($status) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($status->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $status->date;
            $call_sign['last_activity'] = 'status';
            $call_sign['last_from'] = $status->from;
            $call_sign['last_path'] = $status->path;
            $call_sign['last_raw'] = $status->raw;
        }

        if($call_sign['symbol_table'] === NULL && $call_sign['symbol'] === NULL) {
            $call_sign['symbol_table'] = $status->symbol_table;
            $call_sign['symbol'] = $status->symbol;
        }
        if($call_sign['latitude'] === NULL && $call_sign['longitude'] === NULL) {
            $call_sign['latitude'] = $status->latitude;
            $call_sign['longitude'] = $status->longitude;

        }

        $call_sign['status_packet'] = array(
            'date' => $status->date,
            'from' => $status->from,
            'path' => $status->path,
            'symbol_table' => $status->symbol_table,
            'symbol' => $status->symbol,
            'latitude' => $status->latitude,
            'longitude' => $status->longitude,
            'comment' => $status->comment,
            'raw' => $status->raw,
        );
    }

    $telemetry_stmt = $db->prepare("SELECT * FROM `telemetry` WHERE `call_sign_id` = ? LIMIT 1;");
    $telemetry_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $telemetry_stmt->execute();
    $telemetry_result = $telemetry_stmt->get_result();
    $telemetry = $telemetry_result->fetch_object();
    $telemetry_stmt->close();
    if ($telemetry) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($telemetry->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $telemetry->date;
            $call_sign['last_activity'] = 'telemetry';
            $call_sign['last_from'] = $telemetry->from;
            $call_sign['last_path'] = $telemetry->path;
            $call_sign['last_raw'] = $telemetry->raw;
        }

        $call_sign['telemetry_packet'] = array(
            'date' => $telemetry->date,
            'from' => $telemetry->from,
            'path' => $telemetry->path,
            'comment' => $telemetry->comment,
            'raw' => $telemetry->raw,
        );
    }

    $weather_stmt = $db->prepare("SELECT * FROM `weather` WHERE `call_sign_id` = ? LIMIT 1;");
    $weather_stmt->bind_param('i', $call_signs_row->call_sign_id);
    $weather_stmt->execute();
    $weather_result = $weather_stmt->get_result();
    $weather = $weather_result->fetch_object();
    $weather_stmt->close();
    if ($weather) {
        if ($call_sign['date_last_activity'] === NULL || strtotime($weather->date) > strtotime($call_sign['date_last_activity'])) {
            $call_sign['date_last_activity'] = $weather->date;
            $call_sign['last_activity'] = 'weather';
            $call_sign['last_from'] = $weather->from;
            $call_sign['last_path'] = $weather->path;
            $call_sign['last_raw'] = $weather->raw;
        }

        if($call_sign['symbol_table'] === NULL && $call_sign['symbol'] === NULL) {
            $call_sign['symbol_table'] = $weather->symbol_table;
            $call_sign['symbol'] = $weather->symbol;
        }
        if($call_sign['latitude'] === NULL && $call_sign['longitude'] === NULL) {
            $call_sign['latitude'] = $weather->latitude;
            $call_sign['longitude'] = $weather->longitude;

        }

        $call_sign['weather_packet'] = array(
            'date' => $weather->date,
            'from' => $weather->from,
            'path' => $weather->path,
            'symbol_table' => $weather->symbol_table,
            'symbol' => $weather->symbol,
            'latitude' => $weather->latitude,
            'longitude' => $weather->longitude,
            'comment' => $weather->comment,
            'raw' => $weather->raw,
        );
    }


    $call_signs[] = $call_sign;
}
$call_signs_stmt->close();

http_response_code(200);
header("Content-type:application/json");
echo json_encode(array(
    'groups' => $groups,
    'data' => $call_signs
));
exit;