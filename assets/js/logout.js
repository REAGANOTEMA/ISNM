function logout() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../assets/logout.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);

                if (data.status === 'success') {
                    window.location.href = '../staff-login.php';
                    return;
                } else {
                    console.error('Logout failed:', data.message);
                }
            }
        }
    };

    xhr.send();
}

