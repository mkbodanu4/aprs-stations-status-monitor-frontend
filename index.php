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
                    $group_id = trim(filter_var($_POST['group_id'], FILTER_SANITIZE_NUMBER_INT));
                    $group_title = trim(filter_var($_POST['group_title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                    $call_sign = trim(filter_var($_POST['call_sign'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($group_id === "" && $group_title) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `groups` WHERE `title` = ? LIMIT 1;");
                        $stmt->bind_param("s", $group_title);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `groups`(`title`) VALUES (?);");
                            $stmt->bind_param("s", $group_title);
                            $stmt->execute();
                            $group_id = $stmt->insert_id;
                            $stmt->close();
                        } else {
                            $stmt = $db->prepare("SELECT `group_id` FROM `groups` WHERE `title` = ? LIMIT 1;");
                            $stmt->bind_param("s", $group_title);
                            $stmt->execute();
                            $stmt->bind_result($group_id);
                            $stmt->fetch();
                            $stmt->close();
                        }
                    }

                    if ($group_id) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `call_signs` WHERE `value` = ? LIMIT 1;");
                        $stmt->bind_param("s", $call_sign);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `call_signs`(`group_id`,`value`) VALUES (?, ?);");
                            $stmt->bind_param("is", $group_id, $call_sign);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?result=success");
                        } else {
                            header("Location: index.php?result=exists");
                        }
                    } else {
                        header("Location: index.php?result=group_error");
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

                            $stmt = $db->prepare("SELECT COUNT(*) as `used` FROM `call_signs` WHERE `group_id` = ?;");
                            $stmt->bind_param("i", $call_sign->group_id);
                            $stmt->execute();
                            $stmt->bind_result($used);
                            $stmt->fetch();
                            $stmt->close();
                            if ($used == 0) {
                                $stmt = $db->prepare("DELETE FROM `groups` WHERE `group_id` = ? LIMIT 1;");
                                $stmt->bind_param("i", $call_sign->group_id);
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
    `r`.`group_id` as `group_id`,
    `r`.`title` as `group_title`,
    `c`.`value` as `call_sign`
FROM
    `call_signs` `c`
LEFT JOIN `groups` `r` ON `r`.`group_id` = `c`.`group_id`
;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $call_signs[] = $row;
    }
    $stmt->close();

    $groups = array();
    $stmt = $db->prepare("SELECT * FROM `groups`;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $groups[] = $row;
    }
    $stmt->close();

    $proposals = array();
    $stmt = $db->prepare("SELECT * FROM `proposals`;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $proposals[] = $row;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>APRS Stations Status Monitor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
</head>
<body>
<?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) { ?>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6" href="<?= "index.php"; ?>">
            APRS Stations Status Monitor
        </a>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="<?= "index.php?logout"; ?>">Sign out</a>
            </div>
        </div>
    </header>

    <div class="container">
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
                case 'group_error':
                    ?>
                    <div class="alert alert-danger">
                        Please use valid group.
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
        <h4 class="mt-3 text-center">
            Saved Call Signs
        </h4>
        <div class="row mt-2 mb-2">
            <div class="col">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>
                            Group
                        </th>
                        <th>
                            Call Sign
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
                                    <?= $call_sign->group_title; ?>
                                </td>
                                <td>
                                    <?= $call_sign->call_sign; ?>
                                </td>
                                <td>
                                    <form action="<?= "index.php"; ?>" method="post">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="call_sign_id"
                                               value="<?= $call_sign->call_sign_id; ?>">
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
        </div>
        <div class="row mt-2">
            <div class="col">
                <div class="card">
                    <form action="<?= "index.php"; ?>" method="post">
                        <div class="card-header">
                            Add New Call Sign
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="group_id">
                                    Select Group:
                                </label>
                                <select class="form-select" name="group_id" id="group_id" required>
                                    <?php if (isset($groups) && is_array($groups) && count($groups) > 0) { ?>
                                        <?php foreach ($groups as $group) { ?>
                                            <option value="<?= $group->group_id; ?>">
                                                <?= $group->title; ?>
                                            </option>
                                        <?php } ?>
                                    <?php } ?>
                                    <option value="">
                                        Add New One
                                    </option>
                                </select>
                            </div>
                            <div id="group_title_box" class="d-none mb-3">
                                <label for="group_title">
                                    New Group:
                                </label>
                                <input type="text" class="form-control" name="group_title" id="group_title" min="1"
                                       value="">
                            </div>
                            <div class="mb-3">
                                <label for="call_sign">
                                    Call Sign:
                                </label>
                                <input type="text" class="form-control" name="call_sign" id="call_sign" min="3" value=""
                                       required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="submit" class="btn btn-success" value="Add">
                        </div>
                    </form>
                </div>
            </div>

            <h4 class="mt-3 text-center">
                Groups
            </h4>
            <div class="row mt-2 mb-2">
                <div class="col">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>
                                Group ID
                            </th>
                            <th>
                                Title
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (isset($groups) && is_array($groups) && count($groups) > 0) { ?>
                            <?php foreach ($groups as $group) { ?>
                                <tr>
                                    <td>
                                        <?= $group->group_id; ?>
                                    </td>
                                    <td>
                                        <?= $group->title; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="2">
                                    No data found in the system.
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h4 class="mt-3 text-center">
                Not used IGate stations, found by backend.
            </h4>
            <div class="row mt-2 mb-2">
                <div class="col">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>
                                Call Sign
                            </th>
                            <th>
                                From>Path
                            </th>
                            <th>
                                Comment
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (isset($proposals) && is_array($proposals) && count($proposals) > 0) { ?>
                            <?php foreach ($proposals as $proposal) { ?>
                                <tr>
                                    <td>
                                        <?= $proposal->call_sign; ?>
                                    </td>
                                    <td>
                                        <?= $proposal->from . ">" . $proposal->path; ?>
                                    </td>
                                    <td>
                                        <?= $proposal->comment; ?>
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
            </div>

            <div class="mt-2 text-end">
                &copy; UR5WKM 2022
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function (event) {
                var group_id = document.getElementById('group_id').value;
                if (group_id === "") {
                    document.getElementById('group_id').removeAttribute('required');
                    document.getElementById('group_title').setAttribute('required', 'required');
                    document.getElementById('group_title_box').classList.remove('d-none');
                } else {
                    document.getElementById('group_id').setAttribute('required', 'required');
                    document.getElementById('group_title').removeAttribute('required');
                    document.getElementById('group_title').value = "";
                    document.getElementById('group_title_box').classList.add('d-none');
                }

                document.getElementById('group_id').onchange = function () {
                    var group_id = document.getElementById('group_id').value;
                    if (group_id === "") {
                        document.getElementById('group_id').removeAttribute('required');
                        document.getElementById('group_title').setAttribute('required', 'required');
                        document.getElementById('group_title_box').classList.remove('d-none');
                    } else {
                        document.getElementById('group_id').setAttribute('required', 'required');
                        document.getElementById('group_title').removeAttribute('required');
                        document.getElementById('group_title').value = "";
                        document.getElementById('group_title_box').classList.add('d-none');
                    }
                };
            });
        </script>
    </div>
<?php } else { ?>
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-4 mb-5 mb-md-0">
                <div class="card">
                    <form action="<?= "index.php"; ?>" method="post">
                        <div class="card-header">
                            Please log:
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
                    APRS Stations Status Monitor
                </h3>
                <div class="mb-3">
                    Simple application to collect APRS stations status from APRS-IS network.
                    <br/>
                    <br/>
                    Inspired by <a
                            href="https://iz7boj.wordpress.com/2019/03/04/aprs-how-to-automatically-monitor-a-group-of-digipeaters-and-i-gate/"
                            target="_blank">IZ7BOJ system</a>.
                </div>
                <div class="mb-3">
                    Frontend source code: <a href="https://github.com/mkbodanu4/aprs-stations-status-monitor-frontend"
                                             target="_blank">https://github.com/mkbodanu4/aprs-stations-status-monitor-frontend</a>
                    <br/>
                    <br/>
                    Backend source code: <a href="https://github.com/mkbodanu4/aprs-stations-status-monitor-backend"
                                            target="_blank">https://github.com/mkbodanu4/aprs-stations-status-monitor-backend</a>
                    <br/>
                    <br/>
                    WordPress plugin: <a href="https://github.com/mkbodanu4/aprs-stations-status-plugin"
                                         target="_blank">https://github.com/mkbodanu4/aprs-stations-status-plugin</a>
                </div>
                <div class="mb-3">
                    73!<br/>
                    Bohdan UR5WKM
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
        crossorigin="anonymous"></script>
</body>
</html>
