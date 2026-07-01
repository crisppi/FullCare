<form action="check_login.php" method="POST" autocomplete="off">
    <div class="inputs">
        <input type="hidden" id="loggedin" name="loggedin" value="loggedin">
        <input type="text" placeholder="Usuario" name="email_login" onfocus="ocultar()" id="email_login" class="input login-form-input"
            required autocomplete="off">
        <br>
        <input type="password" placeholder="Senha" id="senha_login" name="senha_login" class="input login-form-input" required
            autocomplete="off">
    </div>

    <br><br>

    <div class="remember-me--forget-password">
        <p class="login-access-help">Caso o acesso não esteja funcionando entre em contato com a
            administração!</p>
    </div>

    <br>
    <button class="login-submit-button" type="submit">Login</button>
</form>

<script>
document.getElementById('email_login').setAttribute('autocomplete', 'off');
document.getElementById('senha_login').setAttribute('autocomplete', 'off');
</script>
