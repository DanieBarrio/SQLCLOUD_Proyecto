<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Registro/Login</title>
    <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

a{
    text-decoration: none;
}

body{
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
}

.container{
    position: relative;
    width: 460px;
    height: 640px;
    border-radius: 12px;
    padding: 20px 30px 120px;
    background: #303f9f;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.login-section{
    position: absolute;
    left: 50%;
    bottom: -88%;
    transform: translateX(-50%);
    width: calc(100% + 180px);
    padding: 20px 140px;
    background: #fff;
    border-radius: 290px;
    height: 100%;
    transition: all 0.6s ease;
}

.login-section header,
.signup-section header{
    font-size: 30px;
    text-align: center;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}

.login-section header{
    color: #333;
    opacity: 0.6;
}

.social-buttons{
    margin-top: 40px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.social-buttons button{
    width: 100%;
    padding: 10px;
    background: #fff;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
}

.login-section .social-buttons button{
    border: 1px solid #000;
}

.login-section .social-buttons button i,
.signup-section .social-buttons button i{
    font-size: 25px;
}

.separator{
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.separator .line{
    width: 100%;
    height: 1px;
    background: #ccc;
}

.separator p{
    color: #fff;
}

.login-section .separator p{
    color: #000;
}

.container form{
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 30px;
}

form input{
    outline: none;
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    color: #333;
    font-weight: 400;
    border-radius: 8px;
    background: #fff;
}

.login-section input{
    border: 1px solid #aaa;
}

form a{
    color: #333;
}

.signup-section form a{
    color: #fff;
}

form .btn{
    margin-top: 15px;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 500;
    cursor: pointer;
}

.login-section .btn{
    background: #303f9f;
    color: #fff;
    border: none;
}

.container.active .login-section{
    bottom: -12%;
    border-radius: 220px;
    box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.1);
}

.container.active .login-section header{
    opacity: 1;
}

.container.active .signup-section header{
    opacity: 0.6;
}
    /* NUEVOS ESTILOS */
    .password-container {
        position: relative;
        margin: 12px 0;
    }
    
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
        z-index: 2;
        display: none;
    }
    
    .login-section.active .toggle-password,
    .signup-section.active .toggle-password {
        display: block;
    }
    
    .password-container input {
        padding-right: 40px !important;
        width: 100% !important;
    }
</style>
<body>

    <div class="container">
        <div class="signup-section">
            <header>Registro</header>

            <div class="social-buttons">
                <button><i class='bx bxl-google'></i> Usar Google</button>
                <button><i class='bx bxl-apple'></i> Usar Apple</button>
            </div>

            <div class="separator">
                <div class="line"></div>
                <p>Or</p>
                <div class="line"></div>
            </div>


  <form id="registroForm">
  <input type="text" name="nombre" placeholder="Nombre Completo" required>
  <input type="email" name="correo" placeholder="Correo electrónico" required>
  <div class="password-container">
    <input type="password" name="password" placeholder="Contraseña" required>
    <i class='bx bx-hide toggle-password'></i>
  </div>
  <div id="registroMensaje" style="color: red; text-align: center; margin-top: 1px;"></div>
  <button type="submit" class="btn">Registrarse</button>
</form>


        </div>

        <div class="login-section">
            <header>Acceso</header>

            <div class="social-buttons">
                <button><i class='bx bxl-google'></i> Usar Google</button>
                <button><i class='bx bxl-apple'></i> Usar Apple</button>
            </div>

            <div class="separator">
                <div class="line"></div>
                <p>Or</p>
                <div class="line"></div>
            </div>

     <div id="loginMensaje" style="color: red; text-align: center; margin-top: 10px;"></div>

  <form id="loginForm">
  <input type="email" name="correo" placeholder="Correo electrónico" required>
  <div class="password-container">
    <input type="password" name="password" placeholder="Contraseña" required>
    <i class='bx bx-hide toggle-password'></i>
  </div>
  <a href="recuperar.php">¿Olvidaste la Contraseña?</a>
  <button type="submit" class="btn">Acceder</button>
</form>


        </div>

    </div>


    <script>
    const container = document.querySelector('.container');
    const signupButton = document.querySelector('.signup-section header');
    const loginButton = document.querySelector('.login-section header');

    // Inicializar estado al cargar
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('.signup-section').classList.add('active');
    });

    // Modificar los event listeners
    loginButton.addEventListener('click', () => {
        container.classList.add('active');
        document.querySelector('.login-section').classList.add('active');
        document.querySelector('.signup-section').classList.remove('active');
    });

    signupButton.addEventListener('click', () => {
        container.classList.remove('active');
        document.querySelector('.signup-section').classList.add('active');
        document.querySelector('.login-section').classList.remove('active');
    });

    // Toggle contraseña
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const isVisible = input.type === 'password';
            
            input.type = isVisible ? 'text' : 'password';
            this.classList.toggle('bx-hide', !isVisible);
            this.classList.toggle('bx-show', isVisible);
        });
    });
    </script>

<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append('accion', 'login');

  const response = await fetch('procesar_login.php', {
    method: 'POST',
    body: formData
  });

  const data = await response.json();
  const mensajeDiv = document.getElementById('loginMensaje');

  if (data.exito) {
    mensajeDiv.style.color = 'green';
    mensajeDiv.textContent = data.mensaje;
    setTimeout(() => window.location.href = 'index.php', 1000);
  } else {
    mensajeDiv.style.color = 'red';
    mensajeDiv.textContent = data.mensaje;
  }
});
</script>

<script>
document.getElementById('registroForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append('accion', 'registro');

  const response = await fetch('procesar_login.php', {
    method: 'POST',
    body: formData
  });

  const data = await response.json();
  const mensajeDiv = document.getElementById('registroMensaje');

  if (data.exito) {
    mensajeDiv.style.color = 'green';
    mensajeDiv.textContent = data.mensaje;
    setTimeout(() => window.location.href = 'index.php', 1000);
  } else {
    mensajeDiv.style.color = 'red';
    mensajeDiv.textContent = data.mensaje;
  }
});
</script>


</body>

</html>
