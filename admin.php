<?php
include 'include/db.php';

//анлогин
if (isset($_POST['logout'])) {
    unset($_SESSION['logged_user']);
    header('Location: /admin.php');
}

//авторизация
if (isset($_POST['login_email']) && isset($_POST['login_password'])) {

    $email = trim($_POST['login_email']);
    $pass = trim($_POST['login_password']);

    $user = $mysqli->query("SELECT * FROM users WHERE email='$email'");
    while ($row = mysqli_fetch_assoc($user)) {
        $user = $row;
    }

    $errors = [];

    if (empty($email)) {
        $errors[] = 'Введите Email';
    }
    if (empty($pass)) {
        $errors[] = 'Введите пароль';
    }

    if (is_null($user)) {
        $errors[] = 'Пользователь не найден';
    }
    if (!password_verify($pass, $user['password'])) {
        $errors[] = 'Неверный пароль';
    }

    if (empty($errors)) {
        $_SESSION['logged_user'] = $user;
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница администратора</title>
    <link rel="stylesheet" href="./src/style.css">
</head>
<body>

<div id="root">
    <div class="sidebar">
        <a href="/admin.php">Страница администратора</a>
        <a href="/">Страница пользователя</a>
        <?php
        if (!empty($_SESSION['logged_user'])) {
            ?>
            <form action="admin.php" method="post">
                <input type="hidden" name="logout" value='logout'>
                <button type="submit" class="btn">Выход
                </button>
            </form>
            <?php
        }
        ?>
    </div>
    <div class="main">
        <h2>Страница администратора</h2>

        <hr>
        <?php
        //если не авторизован
        if (empty($_SESSION['logged_user'])) {
            ?>

            <div class="flex-block">
                <div class="card">
                    <form action="admin.php" method="post">
                        <p>Данные для входа: </p>
                        <p>admin@test.ru - test467</p>
                        <input type="email" class="form-control" placeholder="Email" name="login_email"
                               value="admin@test.ru">
                        <input type="password" class="form-control" placeholder="Password" name="login_password"
                               value="test467">
                        <button type="submit" class="btn">Войти</button>
                    </form>
                </div>
            </div>
            <?php
            //если авторизован
        } else {
            ?>
            <div id="modal" class="modal">
                <div class="modal-dialog">
                    <h4>Редактировать объект</h4>
                    <div class="card">
                        <form id="editForm">
                            <label for="name">Название обьекта</label>
                            <input type="hidden" id="id" name="id">
                            <input type="hidden" name="type" value="editItem">
                            <input type="text" class="form-control" id="edited_name" name="edited_name">
                            <label for="parent">Родительский объект</label>
                            <select class="form-control" id="edited_parent" name="edited_parent"></select>
                            <label for="edited_descr">Описание</label>
                            <textarea id="edited_descr" class="form-control" name="edited_descr"></textarea>
                            <div class="row">
                                <button type="submit" class="btn">Сохранить</button>
                                <div class="btn-info close-modal">Закрыть</div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col" id="left">
                    <h4>Добавить объект</h4>
                    <div class="card">
                        <form id="addForm">
                            <label for="name">Название обьекта</label>
                            <input type="hidden" name="type" value="addItem">
                            <input type="text" class="form-control" id="name" name="name">
                            <label for="parent">Родительский объект</label>
                            <select class="form-control" id="parent" name="parent">
                            </select>
                            <label for="descr">Описание</label>
                            <textarea id="descr" class="form-control" name="descr"></textarea>
                            <div class="row">
                                <button type="submit" class="btn">Сохранить</button>
                            </div>

                        </form>
                    </div>
                </div>
                <div class="col" id="middle">
                    <h4>Дерево объектов</h4>
                    <div id="show-items"></div>
                </div>
                <div class="col">
                    <h4>Логи</h4>
                    <ul class="list-group events" id="events-list"></ul>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>

<script src="src/main.js"></script>
<script>

    window.onload = function () {
        //получение обьектов
        getItems('adminPage');

        //отправка форм добавления и редактирования
        let addForm = document.getElementById("addForm");
        addForm.addEventListener("submit", function (event) {
            event.preventDefault();
            sendData(addForm);
        });
        let editForm = document.getElementById("editForm");
        editForm.addEventListener("submit", function (event) {
            event.preventDefault();
            sendData(editForm);
        });

        //события по клику, редактирование и удаление
        document.onclick = async function (event) {
            let target = event.target;
            let id = target.parentElement.id;
            if (target.classList.contains("edit")) {
                let response = await fetch('/include/handler.php', {
                    method: 'POST',
                    body: JSON.stringify({getItem: id})
                });
                let result = await response.json();
                console.log(result);
                let modal = document.getElementById('modal');
                modal.style.display = 'block';
                document.getElementById("edited_name").value = result.name;
                document.getElementById("edited_parent").innerHTML = result.parent;
                document.getElementById("edited_descr").innerHTML = result.descr;
                document.getElementById("id").value = result.id;
            } else if (target.classList.contains("close-modal")) {
                document.getElementById('modal').style.display = 'none';
            } else if (target.classList.contains("delete")) {
                let id = target.parentElement.id;
                let response = await fetch('/include/handler.php', {
                    method: 'POST',
                    body: JSON.stringify({deleteItem: id})
                });
                if (response.ok) {
                    let result = await response.json();
                    await getItems('adminPage');
                }
            }
        }
    };
</script>
</body>

</html>