<!DOCTYPE html>
<!-- http://www.thiengo.com.br -->
<!-- Por: Vinícius Thiengo -->
<!-- Em: 17/11/2013 -->
<!-- Versão: 1.0 -->
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-br" xml:lang="pt-br">
	<head>
    <link rel="icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="shortcut icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="apple-touch-icon" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">

		<title>Simples Formulário Ajax - Thiengo [Calopsita]</title>
	</head>
	
	
	
	<body>
		<form id="simples-formulario-ajax">
			<fieldset>
				<input type="text" id="nome" placeholder="Nome" />
				<br />
				<input type="text" id="email" placeholder="Email" />
				<br />
				<input type="password" id="senha" placeholder="Senha" />
				<br />
				<input type="password" id="senha-confirmar" placeholder="Confirmar senha" />
				<br />
				<br />
				<input type="submit" id="enviar" placeholder="Enviar" />
				
				<input type="hidden" id="metodo" value="formulario-ajax" />
			</fieldset>
		</form>
		
		
		<!-- JAVASCRIPT -->
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
			<script src="formulario.js"></script>
		<!-- JAVASCRIPT -->
	</body>
</html>