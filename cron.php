<?php

include __DIR__ . DIRECTORY_SEPARATOR . "common.php";

if (getenv("ENABLE_LOCK")) {
    if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . ".lock"))
        exit;

    $lock = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . ".lock");
    if (strlen(trim($lock)) > 0) {
        $parts = explode(" ", trim($lock));
        if (count($parts) === 2) {
            list($status, $time) = explode(" ", trim($lock));

            if ($status == 1)
                exit;
        }
    }
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . ".lock", "1" . " " . time());
}

try {
    $call_signs = array();
    $stmt = $db->prepare("SELECT `value` FROM `call_signs`;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $call_signs[] = $row->value;
    }
    $stmt->close();

    $call_signs_buckets = array_chunk($call_signs, 20);
    foreach ($call_signs_buckets as $call_signs_bucket) {
        $loc = $aprs_fi->get(array(
            "name" => implode(",", $call_signs_bucket),
            "what" => "loc"
        ));

        if (!$loc)
            continue;

        if (!isset($loc->result) && $loc->result !== "ok")
            continue;

        if (!isset($loc->entries) || !is_array($loc->entries) || count($loc->entries) === 0)
            continue;

        foreach ($loc->entries as $entry) {
            $stmt = $db->prepare("SELECT `call_sign_id` FROM `call_signs` WHERE `value` = ? LIMIT 1;");
            $stmt->bind_param("s", $entry->name);
            $stmt->execute();
            $stmt->bind_result($call_sign_id);
            $stmt->fetch();
            $stmt->close();

            if ($call_sign_id) {
                $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `status` WHERE `call_sign_id` = ? LIMIT 1;");
                $stmt->bind_param("i", $call_sign_id);
                $stmt->execute();
                $stmt->bind_result($exists);
                $stmt->fetch();
                $stmt->close();

                $date_last_heard = date('Y-m-d H:i:s', $entry->lasttime);
                $path = $entry->path;
                $date_refreshed = date('Y-m-d H:i:s');

                if (!$exists) {
                    $stmt = $db->prepare("INSERT INTO `status`(`call_sign_id`, `date_last_heard`, `path`, `date_refreshed`) VALUES (?, ?, ?, ?);");
                    $stmt->bind_param("isss", $call_sign_id, $date_last_heard, $path, $date_refreshed);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $db->prepare("UPDATE `status` SET `date_last_heard` = ?, `path` = ?, `date_refreshed` = ? WHERE `call_sign_id` = ? LIMIT 1;");
                    $stmt->bind_param("sssi", $date_last_heard, $path, $date_refreshed, $call_sign_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
} catch (Exception $e) {
    if (getenv('ENVIRONMENT') !== 'production') {
        echo $e->getMessage();
    }
    exit;
}

if (getenv("ENABLE_LOCK")) {
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . ".lock", "0" . " " . time());
}