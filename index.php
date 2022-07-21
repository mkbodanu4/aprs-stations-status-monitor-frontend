<?php
/*
    Simple IGate Status Monitor
    Copyright (C) 2022  Bohdan Manko UR5WKM

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

session_start();

include __DIR__ . DIRECTORY_SEPARATOR . "common.php";

if (isset($_GET['logout'])) {
    $_SESSION['logged'] = FALSE;
    session_destroy();
    header("Location: index.php");
    exit;
}

if (count($_POST) > 0) {
    $action = trim(filter_var($_POST['action'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    switch ($action) {
        case 'login':
            $password = trim(filter_var($_POST['password'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            $_SESSION['logged'] = (bool)($password === getenv('MASTER_PASSWORD'));
            header("Location: index.php");
            exit;
            break;
        case 'add':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $region_id = trim(filter_var($_POST['region_id'], FILTER_SANITIZE_NUMBER_INT));
                    $region_title = trim(filter_var($_POST['region_title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                    $call_sign = trim(filter_var($_POST['call_sign'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($region_id === "" && $region_title) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `regions` WHERE `title` = ? LIMIT 1;");
                        $stmt->bind_param("s", $region_title);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `regions`(`title`) VALUES (?);");
                            $stmt->bind_param("s", $region_title);
                            $stmt->execute();
                            $region_id = $stmt->insert_id;
                            $stmt->close();
                        } else {
                            $stmt = $db->prepare("SELECT `region_id` FROM `regions` WHERE `title` = ? LIMIT 1;");
                            $stmt->bind_param("s", $region_title);
                            $stmt->execute();
                            $stmt->bind_result($region_id);
                            $stmt->fetch();
                            $stmt->close();
                        }
                    }

                    if ($region_id) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `call_signs` WHERE `value` = ? LIMIT 1;");
                        $stmt->bind_param("s", $call_sign);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `call_signs`(`region_id`,`value`) VALUES (?, ?);");
                            $stmt->bind_param("is", $region_id, $call_sign);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?result=success");
                        } else {
                            header("Location: index.php?result=exists");
                        }
                    } else {
                        header("Location: index.php?result=region_error");
                    }

                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }
            } catch (Exception $e) {
                header("Location: index.php");
                exit;
            }
            break;
        case 'delete':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $call_sign_id = trim(filter_var($_POST['call_sign_id'], FILTER_SANITIZE_NUMBER_INT));

                    if ($call_sign_id) {
                        $stmt = $db->prepare("SELECT * FROM `call_signs` WHERE `call_sign_id` = ? LIMIT 1;");
                        $stmt->bind_param("i", $call_sign_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $call_sign = $result->fetch_object();
                        $stmt->close();

                        if ($call_sign) {
                            $stmt = $db->prepare("DELETE FROM `status` WHERE `call_sign_id` = ? LIMIT 1;");
                            $stmt->bind_param("i", $call_sign->call_sign_id);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $db->prepare("DELETE FROM `call_signs` WHERE `call_sign_id` = ? LIMIT 1;");
                            $stmt->bind_param("i", $call_sign->call_sign_id);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $db->prepare("SELECT COUNT(*) as `used` FROM `call_signs` WHERE `region_id` = ?;");
                            $stmt->bind_param("i", $call_sign->region_id);
                            $stmt->execute();
                            $stmt->bind_result($used);
                            $stmt->fetch();
                            $stmt->close();
                            if ($used == 0) {
                                $stmt = $db->prepare("DELETE FROM `regions` WHERE `region_id` = ? LIMIT 1;");
                                $stmt->bind_param("i", $call_sign->region_id);
                                $stmt->execute();
                                $stmt->close();
                            }

                            header("Location: index.php?result=success");
                        } else {
                            header("Location: index.php?result=invalid_request");
                            exit;
                        }
                    } else {
                        header("Location: index.php?result=invalid_request");
                        exit;
                    }
                    exit;
                } else {
                    header("Location: index.php");
                    exit;
                }
            } catch (Exception $e) {
                header("Location: index.php");
                exit;
            }
            break;
        default:
            header("Location: index.php");
            exit;
    }
}

if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
    $call_signs = array();
    $stmt = $db->prepare("
SELECT
    `c`.`call_sign_id` as `call_sign_id`,
    `r`.`region_id` as `region_id`,
    `r`.`title` as `region_title`,
    `c`.`value` as `call_sign`
FROM
    `call_signs` `c`
LEFT JOIN `regions` `r` ON `r`.`region_id` = `c`.`region_id`
;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $call_signs[] = $row;
    }
    $stmt->close();

    $regions = array();
    $stmt = $db->prepare("SELECT * FROM `regions`;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $regions[] = $row;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simple IGate Status Monitor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) { ?>
        <div class="row mt-5">
            <div class="col-sm-8">
                <h3 class="text-primary">
                    Simple IGate Status Monitor
                </h3>
            </div>
            <div class="col-sm-4 text-end">
                <a class="btn btn-danger" href="<?= "index.php?logout"; ?>">
                    Logout
                </a>
            </div>
        </div>
    <?php

    if (isset($_GET['result']) && $_GET['result']) {
        switch ($_GET['result']) {
            case 'success':
                ?>
                    <div class="alert alert-success">
                        Operation performed successfully.
                    </div>
                <?php
                break;
            case 'exists':
                ?>
                    <div class="alert alert-danger">
                        Item already found in the system.
                    </div>
                <?php
                break;
            case 'region_error':
                ?>
                    <div class="alert alert-danger">
                        Please use valid region.
                    </div>
                <?php
                break;
            case 'invalid_request':
                ?>
                    <div class="alert alert-danger">
                        Invalid request.
                    </div>
                <?php
                break;
        }
    }

    ?>
        <div class="row mt-2 mb-2">
            <table class="table">
                <thead>
                <tr>
                    <th>
                        Region
                    </th>
                    <th>
                        Call Sign/SSID
                    </th>
                    <th>
                        Action
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php if (isset($call_signs) && is_array($call_signs) && count($call_signs) > 0) { ?>
                    <?php foreach ($call_signs as $call_sign) { ?>
                        <tr>
                            <td>
                                <?= $call_sign->region_title; ?>
                            </td>
                            <td>
                                <?= $call_sign->call_sign; ?>
                            </td>
                            <td>
                                <form action="<?= "index.php"; ?>" method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="call_sign_id" value="<?= $call_sign->call_sign_id; ?>">
                                    <input type="submit" class="btn btn-sm btn-danger" value="Delete">
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3">
                            No data found in the system.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    <hr/>

        <div class="mt-2">
            <h4 class="text-success">
                Add New Call Sign/SSID
            </h4>
            <div>
                <form action="<?= "index.php"; ?>" method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="region_id">
                            Select Region:
                        </label>
                        <select class="form-select" name="region_id" id="region_id" required>
                            <?php if (isset($regions) && is_array($regions) && count($regions) > 0) { ?>
                                <?php foreach ($regions as $region) { ?>
                                    <option value="<?= $region->region_id; ?>">
                                        <?= $region->title; ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                            <option value="">
                                Add New One
                            </option>
                        </select>
                    </div>
                    <div id="region_title_box" class="d-none mb-3">
                        <label for="region_title">
                            New Region:
                        </label>
                        <input type="text" class="form-control" name="region_title" id="region_title" min="1" value="">
                    </div>
                    <div class="mb-3">
                        <label for="call_sign">
                            Call Sign/SSID:
                        </label>
                        <input type="text" class="form-control" name="call_sign" id="call_sign" min="3" value=""
                               required>
                    </div>
                    <div>
                        <input type="submit" class="btn btn-success" value="Add">
                    </div>
                </form>
            </div>

            <hr/>

            <div class="mt-2 text-end">
                Data source: <a href="https://aprs.fi" target="_blank">aprs.fi</a><br/>
                Application: &copy; UR5WKM 2022
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function (event) {
                var region_id = document.getElementById('region_id').value;
                if (region_id === "") {
                    document.getElementById('region_id').removeAttribute('required');
                    document.getElementById('region_title').setAttribute('required', 'required');
                    document.getElementById('region_title_box').classList.remove('d-none');
                } else {
                    document.getElementById('region_id').setAttribute('required', 'required');
                    document.getElementById('region_title').removeAttribute('required');
                    document.getElementById('region_title').value = "";
                    document.getElementById('region_title_box').classList.add('d-none');
                }

                document.getElementById('region_id').onchange = function () {
                    var region_id = document.getElementById('region_id').value;
                    if (region_id === "") {
                        document.getElementById('region_id').removeAttribute('required');
                        document.getElementById('region_title').setAttribute('required', 'required');
                        document.getElementById('region_title_box').classList.remove('d-none');
                    } else {
                        document.getElementById('region_id').setAttribute('required', 'required');
                        document.getElementById('region_title').removeAttribute('required');
                        document.getElementById('region_title').value = "";
                        document.getElementById('region_title_box').classList.add('d-none');
                    }
                };
            });
        </script>
    <?php } else { ?>
        <div class="row mt-5">
            <div class="col-md-4 mb-5 mb-md-0">
                <div class="card">
                    <form action="<?= "index.php"; ?>" method="post">
                        <div class="card-header">
                            Please log in to manage Call Signs or SSIDs:
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password:
                                </label>
                                <input type="password" class="form-control" name="password" value="" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-center">
                                <input type="submit" class="btn btn-primary" value="Sing in">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <h3 class="text-primary">
                    Simple IGate Status Monitor
                </h3>
                <div class="mb-3">
                    (Very) simple application to collect IGate/Digipeaters status on APRS network. Based on <a
                            href="https://aprs.fi" target="_blank">aprs.fi</a> data. Alternatively, own APRS-IS stream processing software could be used..
                    <br/>
                    <br/>
                    Inspired by <a
                            href="https://iz7boj.wordpress.com/2019/03/04/aprs-how-to-automatically-monitor-a-group-of-digipeaters-and-i-gate/"
                            target="_blank">IZ7BOJ system</a>.
                </div>
                <div class="mb-3">
                    Source code of this application: <a href="https://github.com/mkbodanu4/simple-igate-status-monitor"
                                                target="_blank">https://github.com/mkbodanu4/simple-igate-status-monitor</a>
                    <br/>
                    <br/>
                    Source code of Python-based APRS-IS stream processing application: <a href="https://github.com/mkbodanu4/python-igate-status-monitor"
                                                target="_blank">https://github.com/mkbodanu4/python-igate-status-monitor</a>
                    <br/>
                    <br/>
                    WordPress Plugin, that allows to build table with this application data: <a
                            href="https://github.com/mkbodanu4/simple-igate-status-plugin" target="_blank">https://github.com/mkbodanu4/simple-igate-status-plugin</a>
                </div>
                <div class="mb-3">
                    73!<br/>
                    Bohdan UR5WKM
                </div>
            </div>
        </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
            crossorigin="anonymous"></script>
</div>
</body>
</html>
