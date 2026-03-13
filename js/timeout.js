// Timeout.js
var idleTime = 0;

function resolveLoginUrl() {
    if (typeof window.BASE_URL === "string" && window.BASE_URL.length > 0) {
        return window.BASE_URL.replace(/\/?$/, "/") + "index.php";
    }

    var path = window.location.pathname || "/";
    var m = path.match(/^\/(FullCare|FullConex(?:Aud)?)(?:\/|$)/i);
    if (m && m[1]) {
        return "/" + m[1] + "/index.php";
    }

    return "/index.php";
}

function timerIncrement() {
    idleTime += 1;
    if (idleTime >= 60) { // 60 minutos
        window.location.href = resolveLoginUrl();
    }
}

document.onmousemove = resetTimer;
document.onkeydown = resetTimer;

function resetTimer() {
    idleTime = 0;
}

setInterval(timerIncrement, 60000); // ciclo a cada 1 minuto
