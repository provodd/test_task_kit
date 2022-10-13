<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$result = array();

switch (true) {
    case isset($data['getItems']) and $data['getItems'] == 'userPage':
        $result['forSelect'] .= '<option value="0">Нет</option>';
        $objects = $mysqli->query("SELECT * FROM objects");
        while ($row = mysqli_fetch_assoc($objects)) {
            $obj[$row['parent_id']][$row['id']] = $row;
            $result['forSelect'] .= '<option value="' . $row['id'] . '">' . $row['name_obj'] . '</option>';
        }
        $events = $mysqli->query("SELECT * FROM events ORDER BY id DESC LIMIT 10");
        while ($row = mysqli_fetch_assoc($events)) {
            $result['events'] .= '<p><small>user#' . $row['id_user'] . ' - ' . $row['event'] . ' ' . date('d-m-Y H:i', strtotime($row['datetime'])) . '</small></p>';
        }
        $result['tree'] = tree_for_user($mysqli, $obj, 0);
        break;
    case isset($data['getItems']) and $data['getItems'] == 'adminPage':
        $result['forSelect'] = '<option value="0">Нет</option>';
        $result['forEditSelect'] = '<option value="0">Нет</option>';
        $objects = $mysqli->query("SELECT * FROM objects");

        while ($row = mysqli_fetch_assoc($objects)) {
            $obj[$row['parent_id']][$row['id']] = $row;
            $result['forSelect'] .= '<option value="' . $row['id'] . '">' . $row['name_obj'] . '</option>';
        }
        if (isset($data['id']) AND $data['id']!==false) {

            $current_obj = $mysqli->query("SELECT * FROM objects WHERE id={$data['id']} LIMIT 1");
            $current_row = mysqli_fetch_assoc($current_obj);
            $current_parent_id = $current_row['parent_id'];
            $checked_ids = checkParents($obj, $data['id']);
            if (!empty($checked_ids)) {
                $checked_ids_list = empty($checked_ids) ? $data['getItem'] : implode(',', $checked_ids);
                $objects = $mysqli->query("SELECT * FROM objects WHERE id!={$data['id']} AND id NOT IN({$checked_ids_list})");
            } else {
                $objects = $mysqli->query("SELECT * FROM objects WHERE id!={$data['id']}");
            }
            while ($row = mysqli_fetch_assoc($objects)) {
                if ($row['id'] == $current_parent_id) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }
                $result['forEditSelect'] .= '<option ' . $selected . ' value="' . $row['id'] . '">' . $row['name_obj'] . '</option>';
            }
        } else {
            $result['forEditSelect'] = $result['forSelect'];
        }
        $events = $mysqli->query("SELECT * FROM events ORDER BY id DESC LIMIT 10");
        $result['events'] = '';
        while ($row = mysqli_fetch_assoc($events)) {
            $result['events'] .= '<p><small>user#' . $row['id_user'] . ' - ' . $row['event'] . ' ' . date('d-m-Y H:i', strtotime($row['datetime'])) . '</small></p>';
        }
        $result['tree'] = tree($obj, 0);

        $result['id'] = $obj;
        break;
    case isset($_POST['type']) and $_POST['type'] == 'addItem':
        if (mysqli_query($mysqli, 'INSERT INTO objects (parent_id, name_obj, descr)
    VALUES ("' . $_POST['parent'] . '", "' . $_POST['name'] . '", "' . $_POST['descr'] . '")')) {
            add_event('Добавлен новый обьект <b>' . $_POST['name'] . '</b>', $mysqli);
        }
        break;
    case isset($_POST['type']) and $_POST['type'] == 'editItem':
        $name = $_POST['edited_name'];
        $parent_id = $_POST['edited_parent'];
        $descr = $_POST['edited_descr'];
        if ($parent_id !== $_POST['id']) {
            $sql = "UPDATE objects SET name_obj='$name' WHERE id={$_POST['id']}";
            if (mysqli_query($mysqli, $sql)) {
                add_event('Отредактирован обьект <b>' . $name . '</b>', $mysqli);
            }
            $sql = "UPDATE objects SET descr='$descr' WHERE id={$_POST['id']}";
            mysqli_query($mysqli, $sql);
            $sql = "UPDATE objects SET parent_id='$parent_id' WHERE id={$_POST['id']}";
            mysqli_query($mysqli, $sql);
        }
        $result['id'] = $_POST['id'];
        break;
    case isset($data['deleteItem']):
        function delete($mysqli,$array, $id)
        {
            if (is_array($array) && isset($array[$id])) {

                foreach ($array[$id] as $obj) {
                    $objects = $mysqli->query("SELECT * FROM objects WHERE id = {$obj['id']}");
                    while ($row = mysqli_fetch_assoc($objects)) {
                        $name = $row['name_obj'];
                    }
                    $sql = "DELETE FROM objects WHERE id = {$obj['id']}";
                    if ($mysqli->query($sql)) {
                        add_event('Удален обьект <b>' . $name . '</b>', $mysqli);
                    }
                    delete($mysqli, $array, $obj['id']);
                }
                return true;
            } else {
                return null;
            }
        }
        $all_objects = $mysqli->query("SELECT * FROM objects WHERE id!={$data['deleteItem']}");
        while ($row = mysqli_fetch_assoc($all_objects)) {
            $obj[$row['parent_id']][$row['id']] = $row;
        }
        $objects = $mysqli->query("SELECT * FROM objects WHERE id = {$data['deleteItem']}");
        while ($row = mysqli_fetch_assoc($objects)) {
            $name = $row['name_obj'];
        }
        $sql = "DELETE FROM objects WHERE id = {$data['deleteItem']}";
        if ($mysqli->query($sql)) {
            add_event('Удален обьект <b>' . $name . '</b>', $mysqli);
        }
        $result['info'] = delete($mysqli, $obj, $data['deleteItem']);
        break;
    case isset($data['getItem']):
        $result['parent'] .= '<option value="0">Нет</option>';
        $objects = $mysqli->query("SELECT * FROM objects WHERE id={$data['getItem']}");
        while ($row = mysqli_fetch_assoc($objects)) {
            $obj[$row['parent_id']][$row['id']] = $row;
            $result['descr'] .= '' . $row['descr'] . '';
            $result['name'] .= '' . $row['name_obj'] . '';
            $id = $row['parent_id'];
        }
        $objects = $mysqli->query("SELECT * FROM objects WHERE id!={$data['getItem']}");
        while ($row = mysqli_fetch_assoc($objects)) {
            $obj2[$row['parent_id']][$row['id']] = $row;
        }
        $checked_ids = checkParents($obj2, $data['getItem']);
        $checked_ids_list = empty($checked_ids) ? $data['getItem'] : implode(',', $checked_ids);
        $objects = $mysqli->query("SELECT * FROM objects WHERE id!={$data['getItem']} AND id NOT IN({$checked_ids_list})");
        while ($row = mysqli_fetch_assoc($objects)) {
            if ($row['parent_id'] !== $data['getItem']) {
                if ($row['id'] == $id) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }
                $result['parent'] .= '<option ' . $selected . ' value="' . $row['id'] . '">' . $row['name_obj'] . '</option>';
            }
        }
        $result['id'] = $data['getItem'];
        break;
    default:
        $result['post'] = var_export($_POST, true);
        $result['raw_post'] = var_export(json_decode(file_get_contents('php://input'), true), true);
        break;
}

echo json_encode($result);

//функция для записи логов в бд
function add_event($event, $mysqli)
{
    $event = $event;
    $date = date("Y-m-d H:i:s");
    $id_user = $_SESSION['logged_user']['id'];
    mysqli_query($mysqli, 'INSERT INTO events (event, datetime, id_user)
    VALUES ("' . $event . '", "' . $date . '", "' . $id_user . '")')
    or die(mysqli_error($mysqli));
}

//функции для формирования дерева обьектов
function tree($objects, $parent_id)
{
    if (is_array($objects) && isset($objects[$parent_id])) {
        $tree = '<ul>';
        foreach ($objects[$parent_id] as $obj) {

            $tree .= '<li class="target mt-3 text-md" id="' . $obj['id'] . '">' . $obj['name_obj'] . '
                <a class="delete text-danger">(Удалить</a>/<a class="text-primary edit">Изменить)</a><br>
                <small class="text-muted">' . $obj['descr'] . '</small>
                ';
            $tree .= tree($objects, $obj['id']);
            $tree .= '</li>';
        }
        $tree .= '</ul>';
    } else {
        return null;
    }
    return $tree;
}

//возвращает айдишники обьектов, которые не могут быть родительскими для редактируемого объекта
function checkParents($array, $id)
{
    function checkRecursive($array, $id)
    {
        if (is_array($array) && isset($array[$id])) {
            $arr = array();
            unset($arr);
            foreach ($array[$id] as $obj) {
                $ids[] = $obj['id'];
                $ids[] = checkRecursive($array, $obj['id']);
            }
            return $ids;
        } else {
            return null;
        }
    }

    $checked_ids = checkRecursive($array, $id);
    $res = [];
    array_walk_recursive($checked_ids, function ($v) use (&$res) {
        if ($v) {
            $res[] = $v;
        }
    });
    return $res;
}
//дерево объектов пользователя
function tree_for_user($mysqli, $objects, $parent_id)
{
    if (is_array($objects) && isset($objects[$parent_id])) {
        $tree = '<ul>';
        foreach ($objects[$parent_id] as $obj) {
            $row_cnt = 0;
            if ($result = $mysqli->query("SELECT * FROM objects WHERE parent_id = {$obj['id']}")) {
                $row_cnt = $result->num_rows;
                $result->close();
            }
            if ($obj['parent_id'] != '0') {
                $display = 'display:none;';
            } else {
                $display = '';
            }
            if ($row_cnt > 0) {
                $link = '<a style="cursor:pointer;" class="showChild text-danger">(+)</a>';
            } else {
                $link = '';
            }
            $tree .= '<li style="' . $display . '" class="target mt-3 text-md" id="' . $obj['id'] . '">' . $obj['name_obj'] . '
                ' . $link . '<br>
                <small class="text-muted" style="display:none;">' . $obj['descr'] . '</small>
                ';
            $tree .= tree_for_user($mysqli, $objects, $obj['id']);
            $tree .= '</li>';
        }
        $tree .= '</ul>';
    } else {
        return null;
    }
    return $tree;
}
