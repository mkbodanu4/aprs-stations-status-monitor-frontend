<?php
/*
    APRS Stations Status Monitor Frontend
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
        case 'add_call_sign':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $group_id = trim(filter_var($_POST['group_id'], FILTER_SANITIZE_NUMBER_INT));
                    $value = trim(filter_var($_POST['value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($group_id && $value) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `call_signs` WHERE `value` = ? LIMIT 1;");
                        $stmt->bind_param("s", $value);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `call_signs`(`group_id`,`value`) VALUES (?, ?);");
                            $stmt->bind_param("is", $group_id, $value);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?tab=call_sings_table&result=success");
                        } else {
                            header("Location: index.php?tab=call_sings_table&result=exists");
                        }
                    } else {
                        header("Location: index.php?tab=call_sings_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=call_sings_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=call_sings_table");
            }

            exit;
            break;
        case 'edit_call_sign':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $call_sign_id = trim(filter_var($_POST['call_sign_id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
                    $group_id = trim(filter_var($_POST['group_id'], FILTER_SANITIZE_NUMBER_INT));
                    $value = trim(filter_var($_POST['value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($call_sign_id && $group_id && $value) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `call_signs` WHERE `call_sign_id` = ? LIMIT 1;");
                        $stmt->bind_param("i", $call_sign_id);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if ($exists) {
                            $stmt = $db->prepare("UPDATE `call_signs` SET `group_id` = ?,`value` = ? WHERE `call_sign_id` = ? LIMIT 1;");
                            $stmt->bind_param("isi", $group_id, $value, $call_sign_id);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?tab=call_sings_table&result=success");
                        } else {
                            header("Location: index.php?tab=call_sings_table&result=invalid_request");
                        }
                    } else {
                        header("Location: index.php?tab=call_sings_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=call_sings_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=call_sings_table");
            }

            exit;
            break;
        case 'delete_call_sign':
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
                            foreach (array("positions", "telemetry", "weather", "objects", "routing", "status") as $table_name) {
                                $stmt = $db->prepare("DELETE FROM `" . $table_name . "` WHERE `call_sign_id` = ? LIMIT 1;");
                                $stmt->bind_param("i", $call_sign->call_sign_id);
                                $stmt->execute();
                                $stmt->close();
                            }

                            $stmt = $db->prepare("DELETE FROM `call_signs` WHERE `call_sign_id` = ? LIMIT 1;");
                            $stmt->bind_param("i", $call_sign->call_sign_id);
                            $stmt->execute();
                            $stmt->close();

                            header("Location: index.php?tab=call_sings_table&result=success");
                        } else {
                            header("Location: index.php?tab=call_sings_table&result=invalid_request");
                        }
                    } else {
                        header("Location: index.php?tab=call_sings_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=call_sings_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=call_sings_table");
            }

            exit;
            break;
        case 'add_group':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $title = trim(filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($title) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `groups` WHERE `title` = ? LIMIT 1;");
                        $stmt->bind_param("s", $title);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if (!$exists) {
                            $stmt = $db->prepare("INSERT INTO `groups`(`title`) VALUES (?);");
                            $stmt->bind_param("s", $title);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?tab=groups_table&result=success");
                        } else {
                            header("Location: index.php?tab=groups_table&result=exists");
                        }
                    } else {
                        header("Location: index.php?tab=groups_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=groups_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=groups_table");
            }

            exit;
            break;
        case 'edit_group':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $group_id = trim(filter_var($_POST['group_id'], FILTER_SANITIZE_NUMBER_INT));
                    $title = trim(filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                    if ($group_id && $title) {
                        $stmt = $db->prepare("SELECT COUNT(*) AS `exists` FROM `groups` WHERE `group_id` = ? LIMIT 1;");
                        $stmt->bind_param("i", $group_id);
                        $stmt->execute();
                        $stmt->bind_result($exists);
                        $stmt->fetch();
                        $stmt->close();

                        if ($exists) {
                            $stmt = $db->prepare("UPDATE `groups` SET `title` = ? WHERE `group_id` = ? LIMIT 1;");
                            $stmt->bind_param("si", $title, $group_id);
                            $stmt->execute();
                            $stmt->close();
                            header("Location: index.php?tab=groups_table&result=success");
                        } else {
                            header("Location: index.php?tab=groups_table&result=invalid_request");
                        }
                    } else {
                        header("Location: index.php?tab=groups_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=groups_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=groups_table");
            }

            exit;
            break;
        case 'delete_group':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $group_id = trim(filter_var($_POST['group_id'], FILTER_SANITIZE_NUMBER_INT));

                    if ($group_id) {
                        $stmt = $db->prepare("SELECT * FROM `groups` WHERE `group_id` = ? LIMIT 1;");
                        $stmt->bind_param("i", $group_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $group = $result->fetch_object();
                        $stmt->close();

                        if ($group) {
                            $stmt = $db->prepare("DELETE FROM `groups` WHERE `group_id` = ? LIMIT 1;");
                            $stmt->bind_param("i", $group->group_id);
                            $stmt->execute();
                            $stmt->close();

                            header("Location: index.php?tab=groups_table&result=success");
                        } else {
                            header("Location: index.php?tab=groups_table&result=invalid_request");
                        }
                    } else {
                        header("Location: index.php?tab=groups_table&result=invalid_request");
                    }
                } else {
                    header("Location: index.php?tab=groups_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=groups_table");
            }

            exit;
            break;
        case 'clean_proposals':
            try {
                if (isset($_SESSION['logged']) && $_SESSION['logged'] === TRUE) {
                    $stmt = $db->prepare("TRUNCATE `proposals`;");
                    $stmt->execute();
                    $stmt->close();

                    header("Location: index.php?tab=proposals_table&result=success");
                } else {
                    header("Location: index.php?tab=proposals_table");
                }
            } catch (Exception $e) {
                header("Location: index.php?tab=proposals_table");
            }

            exit;
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
        `c`.`value` as `value`
    FROM
        `call_signs` `c`
    LEFT JOIN `groups` `r` ON `r`.`group_id` = `c`.`group_id`
    ORDER BY `c`.`call_sign_id` DESC
    ;");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_object()) {
        $call_signs[] = $row;
    }
    $stmt->close();

    $groups = array();
    $stmt = $db->prepare("SELECT * FROM `groups` ORDER BY `group_id` DESC;");
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

    $tab = isset($_GET['tab']) ? trim(filter_var($_GET['tab'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : NULL;
    if (!in_array($tab, array('call_sings_table', 'groups_table', 'proposals_table')))
        $tab = "call_sings_table";
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
                    <div class="alert alert-success my-2 text-center">
                        Operation performed successfully.
                    </div>
                    <?php
                    break;
                case 'exists':
                    ?>
                    <div class="alert alert-danger my-2 text-center">
                        Item already found in the system.
                    </div>
                    <?php
                    break;
                case 'group_error':
                    ?>
                    <div class="alert alert-danger my-2 text-center">
                        Please use valid group.
                    </div>
                    <?php
                    break;
                case 'invalid_request':
                    ?>
                    <div class="alert alert-danger my-2 text-center">
                        Invalid request.
                    </div>
                    <?php
                    break;
            }
        }

        ?>

        <div class="d-flex align-items-start mt-2 mb-2">
            <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist"
                 aria-orientation="vertical">
                <button class="nav-link<?= isset($tab) && $tab === 'call_sings_table' ? ' active' : ''; ?>"
                        id="v-pills-call_sings_table-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-call_sings_table" type="button" role="tab"
                        aria-controls="v-pills-call_sings_table"
                        aria-selected="<?= isset($tab) && $tab === 'call_sings_table' ? 'true' : 'false'; ?>">
                    Call Signs
                </button>
                <button class="nav-link<?= isset($tab) && $tab === 'groups_table' ? ' active' : ''; ?>"
                        id="v-pills-groups_table-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-groups_table" type="button" role="tab"
                        aria-controls="v-pills-groups_table"
                        aria-selected="<?= isset($tab) && $tab === 'groups_table' ? 'true' : 'false'; ?>">
                    Groups
                </button>
                <button class="nav-link<?= isset($tab) && $tab === 'proposals_table' ? ' active' : ''; ?>"
                        id="v-pills-proposals_table-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-proposals_table" type="button" role="tab"
                        aria-controls="v-pills-proposals_table"
                        aria-selected="<?= isset($tab) && $tab === 'proposals_table' ? 'true' : 'false'; ?>">
                    Proposals
                </button>
            </div>

            <div class="tab-content w-100" id="v-pills-tabContent">
                <div class="tab-pane fade<?= isset($tab) && $tab === 'call_sings_table' ? ' show active' : ''; ?>"
                     id="v-pills-call_sings_table" role="tabpanel"
                     aria-labelledby="v-pills-call_sings_table-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col">
                            <h4 class="text-start">
                                Saved Call Signs
                            </h4>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#add_call_sign_modal">
                                Add new
                            </button>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>
                                ID
                            </th>
                            <th>
                                Group ID
                            </th>
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
                                        <?= $call_sign->call_sign_id; ?>
                                    </td>
                                    <td>
                                        <?= $call_sign->group_id; ?>
                                    </td>
                                    <td>
                                        <?= $call_sign->group_title; ?>
                                    </td>
                                    <td>
                                        <?= $call_sign->value; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                    onclick="edit_call_sign_modal(this);"
                                                    data-call_sign_id="<?= $call_sign->call_sign_id; ?>"
                                                    data-group_id="<?= $call_sign->group_id; ?>"
                                                    data-value="<?= $call_sign->value; ?>">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="delete_call_sign_modal(this);"
                                                    data-call_sign_id="<?= $call_sign->call_sign_id; ?>">
                                                Delete
                                            </button>
                                        </div>
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

                    <div class="modal" tabindex="-1" id="add_call_sign_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add New Call Sign</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="add_call_sign">
                                        <div class="mb-3">
                                            <label for="add_call_sign_modal_group_id">
                                                Select Group:
                                            </label>
                                            <select class="form-select" name="group_id"
                                                    id="add_call_sign_modal_group_id" required>
                                                <?php if (isset($groups) && is_array($groups) && count($groups) > 0) { ?>
                                                    <?php foreach ($groups as $group) { ?>
                                                        <option value="<?= $group->group_id; ?>">
                                                            <?= $group->title; ?>
                                                        </option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="add_call_sign_modal_value">
                                                Call Sign:
                                            </label>
                                            <input type="text" class="form-control" name="value"
                                                   id="add_call_sign_modal_value" min="3" value=""
                                                   required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-primary" value="Add">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal" tabindex="-1" id="edit_call_sign_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Call Sign</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit_call_sign">
                                        <input type="hidden" name="call_sign_id" id="edit_call_sign_modal_call_sign_id"
                                               value="">
                                        <div class="mb-3">
                                            <label for="edit_call_sign_modal_group_id">
                                                Select Group:
                                            </label>
                                            <select class="form-select" name="group_id"
                                                    id="edit_call_sign_modal_group_id" required>
                                                <?php if (isset($groups) && is_array($groups) && count($groups) > 0) { ?>
                                                    <?php foreach ($groups as $group) { ?>
                                                        <option value="<?= $group->group_id; ?>">
                                                            <?= $group->title; ?>
                                                        </option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_call_sign_modal_value">
                                                Call Sign:
                                            </label>
                                            <input type="text" class="form-control" name="value"
                                                   id="edit_call_sign_modal_value" min="3" value=""
                                                   required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-primary" value="Save">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal" tabindex="-1" id="delete_call_sign_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Call Sign</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="delete_call_sign">
                                        <input type="hidden" name="call_sign_id"
                                               id="delete_call_sign_modal_call_sign_id" value="">
                                        Are you sure that want to delete this call sign?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-danger" value="Delete">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                        function edit_call_sign_modal(el) {
                            document.getElementById('edit_call_sign_modal_call_sign_id').value = el.dataset['call_sign_id'];
                            document.getElementById('edit_call_sign_modal_group_id').value = el.dataset['group_id'];
                            document.getElementById('edit_call_sign_modal_value').value = el.dataset['value'];

                            var edit_call_sign_modal = new bootstrap.Modal(document.getElementById("edit_call_sign_modal"), {});
                            edit_call_sign_modal.show();
                        }

                        function delete_call_sign_modal(el) {
                            document.getElementById('delete_call_sign_modal_call_sign_id').value = el.dataset['call_sign_id'];

                            var delete_call_sign_modal = new bootstrap.Modal(document.getElementById("delete_call_sign_modal"), {});
                            delete_call_sign_modal.show();
                        }
                    </script>
                </div>

                <div class="tab-pane fade<?= isset($tab) && $tab === 'groups_table' ? ' show active' : ''; ?>"
                     id="v-pills-groups_table" role="tabpanel"
                     aria-labelledby="v-pills-groups_table-tab" tabindex="0">
                    <div class="row mb-3">
                        <div class="col">
                            <h4 class="text-start">
                                Groups
                            </h4>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#add_group_modal">
                                Add new
                            </button>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>
                                Group ID
                            </th>
                            <th>
                                Title
                            </th>
                            <th>
                                Action
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
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                    onclick="edit_group_modal(this);"
                                                    data-group_id="<?= $group->group_id; ?>"
                                                    data-title="<?= $group->title; ?>">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="delete_group_modal(this);"
                                                    data-group_id="<?= $group->group_id; ?>">
                                                Delete
                                            </button>
                                        </div>
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

                    <div class="modal" tabindex="-1" id="add_group_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add New Group</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="add_group">
                                        <div class="mb-3">
                                            <label for="add_group_modal_title">
                                                Group Title:
                                            </label>
                                            <input type="text" class="form-control" name="title"
                                                   id="add_group_modal_title" min="1" value=""
                                                   required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-primary" value="Add">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal" tabindex="-1" id="edit_group_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Group</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit_group">
                                        <input type="hidden" name="group_id" id="edit_group_modal_group_id" value="">
                                        <div class="mb-3">
                                            <label for="edit_group_modal_title">
                                                Group Title:
                                            </label>
                                            <input type="text" class="form-control" name="title"
                                                   id="edit_group_modal_title" min="1" value=""
                                                   required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-primary" value="Save">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal" tabindex="-1" id="delete_group_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete Group</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="delete_group">
                                        <input type="hidden" name="group_id" id="delete_group_modal_group_id" value="">
                                        Are you sure that want to delete this group?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-danger" value="Delete">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                        function edit_group_modal(el) {
                            document.getElementById('edit_group_modal_group_id').value = el.dataset['group_id'];
                            document.getElementById('edit_group_modal_title').value = el.dataset['title'];

                            var edit_group_modal = new bootstrap.Modal(document.getElementById("edit_group_modal"), {});
                            edit_group_modal.show();
                        }

                        function delete_group_modal(el) {
                            document.getElementById('delete_group_modal_group_id').value = el.dataset['group_id'];

                            var delete_group_modal = new bootstrap.Modal(document.getElementById("delete_group_modal"), {});
                            delete_group_modal.show();
                        }
                    </script>
                </div>

                <div class="tab-pane fade<?= isset($tab) && $tab === 'proposals_table' ? ' show active' : ''; ?>"
                     id="v-pills-proposals_table" role="tabpanel"
                     aria-labelledby="v-pills-proposals_table-tab" tabindex="0">

                    <div class="row mb-3">
                        <div class="col">
                            <h4 class="text-start">
                                Not used IGate stations, found by backend.
                            </h4>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#clean_proposals_modal">
                                Clean up all proposals
                            </button>
                        </div>
                    </div>

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

                    <div class="modal" tabindex="-1" id="clean_proposals_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= "index.php"; ?>" method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Clean up proposals</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="clean_proposals">
                                        Please confirm this action.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                        <input type="submit" class="btn btn-primary" value="Clear table">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-2 text-end">
            &copy; UR5WKM 2022
        </div>
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
