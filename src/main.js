//получение объектов
async function getItems(page = 'userPage', id = false) {
    let response = await fetch('/include/handler.php', {
        method: 'POST',
        body: JSON.stringify({getItems: page, id: id})
    });
    let result = await response.json();

    switch (page) {
        case "userPage":
            document.getElementById("items").innerHTML = result.tree;
            break
        case 'adminPage':
            document.getElementById("show-items").innerHTML = result.tree;
            document.getElementById("events-list").innerHTML = result.events;
            document.getElementById('parent').innerHTML = result.forSelect;
            document.getElementById('edited_parent').innerHTML = result.forEditSelect;
            break;
    }
}

//функция для отправки форм добавления или редактирования обьектов
function sendData(form) {
    const XHR = new XMLHttpRequest();
    const FD = new FormData(form);
    XHR.onload = function () {
        let jsonResponse = JSON.parse(XHR.responseText);
        getItems('adminPage', jsonResponse.id || false);
    };
    XHR.open("POST", "/include/handler.php");
    XHR.send(FD);
}