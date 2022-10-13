<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница пользователя</title>
    <link rel="stylesheet" href="./src/style.css">
</head>

<body>

<div id="root">
    <div class="sidebar">
        <a href="/admin.php">Страница администратора</a>
        <a href="/">Страница пользователя</a>
    </div>
    <div class="main">
        <h2>Страница пользователя</h2>
        <hr>
        <div class="row">
            <div class="col">
                <h4>Дерево объектов</h4>
                <div id="items"></div>
            </div>
        </div>

    </div>
</div>
<script src="src/main.js"></script>
<script>
    window.onload = function () {
        //раскрытие дерева и показ описания по клику
        document.onclick = function (event) {
            let target = event.target;
            if (target.classList.contains("showChild")) {
                let ul = target.parentElement.querySelector('ul');
                ul.childNodes.forEach((e)=>{
                    e.style.display = 'block';
                });
                target.parentElement.querySelector('li').style.display = "block";
            } else if (target.classList.contains("target")) {
                let el = target.querySelector("small");
                el.style.display = (el.style.display == 'none') ? 'block' : 'none';
            }
        }
        getItems();
    }
</script>
</body>

</html>