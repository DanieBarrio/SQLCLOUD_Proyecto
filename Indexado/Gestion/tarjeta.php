<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['user'])) {
    header("Location: logister.php");
    exit;
}

if (strtolower($_SESSION['PLAN']) === 'premium') {
    header("Location: index.php");
    exit;
}

$conn = conectar();
$stmt = $conn->prepare("SELECT p.T_PLAN AS PLAN FROM usuarios u JOIN plan p ON u.ID = p.ID WHERE u.CORREO = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$res = $stmt->get_result();
$datos = $res->fetch_assoc();

//$plan = $datos['PLAN'] ?? 'gratuito';

//if ($plan != 'gratuito') {
    // Ya tiene premium y aún no expira
//    header("Location: index.php");
//    exit;
//}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <title>SQLCLOUD Pago</title>
    <link rel="icon" type="image/png" href="../Recursos/favicon.png?v=2">
<style>
    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      min-height: 100vh;
      background: linear-gradient(45deg, #0f0c29, #302b63, #24243e) fixed;
      position: relative;
      overflow: hidden;
      margin: 0;
    }
        /* Scroll Básico */
    html {
        overflow-y: scroll;
    }

    body::before,
    body::after {
      content: '';
      position: absolute;
      width: 100vmax;
      height: 100vmax;
      animation: float 30s infinite linear;
      opacity: 0.1;
      z-index: 0;
      filter: blur(30px);
      background-repeat: no-repeat;
      background-position: center;
    }

    body::before {
      background: radial-gradient(circle at 30% 30%, #00ff88 0%, transparent 70%);
      left: -20vmax;
      top: -20vmax;
    }

    body::after {
      background: radial-gradient(circle at 70% 70%, #ff0088 0%, transparent 70%);
      right: -20vmax;
      bottom: -20vmax;
    }

    @keyframes float {
      0% { transform: translate(0, 0) scale(1) rotate(0deg); }
      25% { transform: translate(-10%, 5%) scale(1.1) rotate(90deg); }
      50% { transform: translate(5%, -10%) scale(0.9) rotate(180deg); }
      75% { transform: translate(-5%, 15%) scale(1.2) rotate(270deg); }
      100% { transform: translate(0, 0) scale(1) rotate(360deg); }
    }

    .card-preview {
      color: white;
      width: 360px;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      margin-bottom: 30px;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.15);
      transition: transform 0.3s ease;
      background: linear-gradient(135deg, rgba(55,71,79,0.9), rgba(38,50,56,0.9));
    }

    .card-preview.visa {
      background: linear-gradient(135deg, #1a1f71, #3d5b99);
    }
    
    .card-preview.mastercard {
      background: linear-gradient(135deg, #eb001b, #f79e1b);
    }
    
    .card-preview.amex {
      background: linear-gradient(135deg, #016fd0, #7ac5e7);
    }
    
    .card-preview.discover {
      background: linear-gradient(135deg, #ff6000, #ff8d00);
    }
    
    .card-preview.default {
      background: linear-gradient(135deg, rgba(55,71,79,0.9), rgba(38,50,56,0.9));
    }

    .card-preview:hover {
      transform: translateY(-5px);
    }

    .card-preview h3 {
      margin: 0 0 15px 0;
      font-weight: 500;
      letter-spacing: 1px;
      opacity: 0.8;
    }

    .card-number,
    .card-name,
    .card-exp,
    .card-type {
      margin: 8px 0;
      font-size: 18px;
      letter-spacing: 2px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .card-number {
      font-size: 24px;
      margin: 15px 0;
      letter-spacing: 3px;
    }

    .card-type {
      text-align: right;
      font-size: 16px;
      opacity: 0.9;
    }

    form {
      background: rgba(255,255,255,0.95);
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      width: 360px;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.2);
      transition: transform 0.3s ease;
    }

    form:hover {
      transform: translateY(-3px);
    }

    form input {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      font-size: 16px;
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 8px;
      background: rgba(255,255,255,0.9);
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    form input:focus {
      outline: none;
      border-color: #007BFF;
      box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }

    button {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #007BFF, #0056b3);
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 15px;
      letter-spacing: 1px;
      font-weight: 600;
    }

    button:hover {
      opacity: 0.9;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,123,255,0.3);
    }

    .success-message {
      margin-top: 25px;
      color: #28a745;
      font-weight: bold;
      font-size: 18px;
      text-align: center;
      padding: 15px;
      background: rgba(255,255,255,0.9);
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(40,167,69,0.2);
      position: relative;
      z-index: 1;
    }

    .plan-info {
      margin-top: 15px;
      font-weight: bold;
      color: #495057;
      background: rgba(255,255,255,0.9);
      padding: 10px 20px;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
      backdrop-filter: blur(5px);
    }

    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      display: none;
      z-index: 1000;
    }

    .modal-content {
      background: rgba(255,255,255,0.95);
      padding: 30px;
      border-radius: 15px;
      text-align: center;
      width: 320px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
      transform: scale(0.95);
      animation: modalEnter 0.3s ease forwards;
    }

    .error-message {
      color: #dc3545;
      font-size: 14px;
      margin-top: -10px;
      margin-bottom: 10px;
      display: none;
    }

    .error-input {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 3px rgba(220,53,69,0.1) !important;
    }

    @keyframes modalEnter {
      to { transform: scale(1); }
    }

    @media (max-width: 768px) {
      .card-preview {
        width: 90%;
        padding: 15px;
        margin-bottom: 20px;
      }

      form {
        width: 90%;
        padding: 15px;
      }

      .card-number {
        font-size: 20px;
      }

      form input {
        padding: 10px;
        font-size: 14px;
      }

      button {
        padding: 12px;
        font-size: 14px;
      }

      .modal-content {
        width: 85%;
        padding: 20px;
      }

      .card-preview:hover,
      form:hover {
        transform: none;
      }

      @keyframes float {
        0% { transform: translate(-5%, 2%) scale(1.05) rotate(45deg); }
        50% { transform: translate(2%, -5%) scale(0.95) rotate(90deg); }
        100% { transform: translate(-2%, 5%) scale(1.1) rotate(135deg); }
      }
    }

    @media (max-width: 480px) {
      body::before,
      body::after {
        display: none;
      }

      .card-preview {
        border-radius: 10px;
      }

      form input {
        margin: 8px 0;
      }

      .modal-content button {
        width: 48%;
        padding: 10px;
      }
    }
    @media (max-width: 768px) {
    .card-preview {
        margin-top: 20px !important; /* Espacio reducido en móviles */
    }
}
/* Añade esto al final del CSS existente */
@media (max-width: 480px) {
    body {
        overflow-y: auto; /* Habilita scroll vertical */
        min-height: 100vh;
        height: auto;
    }

    /* Opcional: Personalizar barra de scroll */
    ::-webkit-scrollbar {
        width: 6px;
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: #FFD700;
        border-radius: 3px;
    }

    /* Ajustar elementos principales */
    .container {
        padding: 15px;
        height: auto;
        min-height: 100vh;
    }

    .card-preview, form {
        margin-bottom: 15px;
    }
}
</style>
</head>
<body>

<div class="card-preview default" style="margin-top: 25px;">
    <h3>Vista previa</h3>
    <div class="card-number" id="preview-number">#### #### #### ####</div>
    <div class="card-name" id="preview-name">Nombre Apellido</div>
    <div class="card-exp" id="preview-exp">MM/AA</div>
    <div class="card-type" id="preview-type">Tipo: ???</div>
</div>

<!-- Contenedor principal para tarjeta y plan -->
<div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin: 30px 0;">

    <!-- Plan Premium -->
    <div style="background: linear-gradient(135deg, #FFD700, #D4AF37); 
              border: 2px solid #FFE55C;
              color: #2d2d2d;
              padding: 25px;
              border-radius: 15px;
              box-shadow: 0 10px 30px rgba(0,0,0,0.2);
              max-width: 360px;
              width: 100%;
              position: relative;
              overflow: hidden;
              z-index: 1;">
        
        <div style="position: absolute; top: -20px; right: -20px; 
                 width: 60px; height: 60px; 
                 background: rgba(255,255,255,0.2); 
                 transform: rotate(45deg);"></div>
        
        <h2 style="font-size: 26px; margin-bottom: 20px; border-bottom: 2px solid #2d2d2d30; 
                padding-bottom: 15px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-crown" style="color: #2d2d2d;"></i>
            Plan De Por Vida
        </h2>

        <ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">
            <li style="padding: 12px 0; border-bottom: 1px solid rgba(45,45,45,0.1); 
                    display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-database" style="width: 25px;"></i>
                Cuota Total: <strong>100 MB</strong>
            </li>
            <li style="padding: 12px 0; border-bottom: 1px solid rgba(45,45,45,0.1);
                    display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-layer-group" style="width: 25px;"></i>
                Bases de datos: <strong>3 activas</strong>
            </li>
            <li style="padding: 12px 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-tag" style="width: 25px;"></i>
                Precio: <strong style="font-size: 24px;">900 €</strong>
            </li>
        </ul>

        <div style="background: rgba(255,255,255,0.3); padding: 15px; border-radius: 10px;
                 border: 1px solid rgba(45,45,45,0.1);">
            <strong>Oferta Única Especial</strong>
            <div style="font-size: 14px; margin-top: 8px;">
                Esta disponible hasta 12/12/2025<br>
                -Antes de la oferta- 1200 €
            </div>
        </div>
    </div>
  <form id="card-form">
    <div class="input-group">
      <input type="text" id="card-number" placeholder="Número de tarjeta" maxlength="19" required>
      <div class="error-message" id="card-number-error"></div>
    </div>
    
    <div class="input-group">
      <input type="text" id="card-name" placeholder="Nombre del titular" required>
      <div class="error-message" id="card-name-error"></div>
    </div>
    
    <div class="input-group">
      <input type="text" id="card-exp" placeholder="MM/AA" maxlength="5" required>
      <div class="error-message" id="card-exp-error"></div>
    </div>
    
    <div class="input-group">
      <input type="text" id="card-cvc" placeholder="CVC" maxlength="3" required>
      <div class="error-message" id="card-cvc-error"></div>
    </div>

    <button type="submit">Validar tarjeta</button>
  </form>

  <!-- Botón para volver al panel -->
  <div style="text-align: center; width: 100%; margin-top: 30px; padding: 0 20px;">
    <a href="index.php"
       style="display: inline-block;
              background: #FFD700;
              color: #2d2d2d !important;
              padding: 12px 30px;
              border-radius: 8px;
              font-weight: 600;
              text-decoration: none;
              transition: all 0.3s ease;
              border: 2px solid #D4AF37;
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);
              cursor: pointer;
	      position: relative;
	      z-index: 10;">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
        Volver al panel
    </a>
  </div>
</div>

  <!-- Modal de confirmación -->
  <div class="modal" id="modal-confirm">
    <div class="modal-content">
      <h2>Confirmar pago</h2>
      <p>¿Desea continuar? Se realizará un cargo de <strong>900 €</strong> por el plan Premium.</p>
      <button id="confirm-yes" style="background: #28a745;">Aceptar</button>
      <button id="confirm-no" style="background: #dc3545;">Cancelar</button>
    </div>
  </div>
<!-- Modal de éxito -->
<div class="modal" id="modal-success">
  <div class="modal-content">
    <h2>✅ Éxito</h2>
    <p>Su plan ha sido actualizado a <strong>Premium</strong>.</p>
    <button id="continue-button" style="background: #28a745;">Continuar</button>
  </div>
</div>
 <script>
    const elements = {
      numberInput: document.getElementById('card-number'),
      nameInput: document.getElementById('card-name'),
      expInput: document.getElementById('card-exp'),
      cvcInput: document.getElementById('card-cvc'),
      previews: {
        number: document.getElementById('preview-number'),
        name: document.getElementById('preview-name'),
        exp: document.getElementById('preview-exp'),
        type: document.getElementById('preview-type')
      },
      form: document.getElementById('card-form'),
      cardPreview: document.querySelector('.card-preview'),
      errors: {
        number: document.getElementById('card-number-error'),
        name: document.getElementById('card-name-error'),
        exp: document.getElementById('card-exp-error'),
        cvc: document.getElementById('card-cvc-error')
      }
    };


function isValidCardNumber(number) {
    const digits = number.replace(/\D/g, '').split('').map(Number);
    let sum = 0;
    for (let i = 0; i < digits.length; i++) {
        let digit = digits[digits.length - 1 - i];
        if (i % 2 === 1) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        sum += digit;
    }
    return sum % 10 === 0;
}
//Funcion validar fecha introducida / fecha actual
function isFutureDate(mm, yy) {
    const currentYear = new Date().getFullYear() % 100; // Últimos 2 dígitos del año actual
    const currentMonth = new Date().getMonth() + 1; // Mes actual (empieza en 0)

    if (yy < currentYear) return false;
    if (yy === currentYear && mm < currentMonth) return false;
    return true;
}

    let currentCardType = 'default';

  const validations = {
  cardNumber: value => {
    const cleaned = value.replace(/\D/g, '');
    if (!cleaned) return 'El número de tarjeta es requerido';

    // Validación por tipo de tarjeta
    const length = cleaned.length;

    switch (currentCardType) {
      case 'visa':
        if (length !== 13 && length !== 16) return 'Visa debe tener 13 o 16 dígitos';
        break;
      case 'mastercard':
        if (length !== 16) return 'MasterCard debe tener 16 dígitos';
        break;
      case 'amex':
        if (length !== 15) return 'American Express debe tener 15 dígitos';
        break;
      case 'discover':
        if (length !== 16) return 'Discover debe tener 16 dígitos';
        break;
      case 'default':
      default:
        return 'Solo aceptamos Visa, Mastercard, American Express y Discover';
    }

    return '';
  },

  cardName: value => {
    if (!value) return 'El nombre es requerido';
    if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{5,}$/.test(value)) return 'Nombre no válido';
    return '';
  },

  cardExp: value => {
    if (!value) return 'La fecha de expiración es requerida';

    const match = /^(0[1-9]|1[0-2])\/?(\d{2})$/.exec(value);
    if (!match) return 'Formato MM/AA requerido';

    const month = parseInt(match[1], 10);
    const year = parseInt(match[2], 10);

    if (!isFutureDate(month, year)) {
        return 'La tarjeta ha expirado';
    }

    return '';
  },

  cardCvc: value => {
    const length = currentCardType === 'amex' ? 4 : 3;
    if (!value) return 'El código de seguridad es requerido';
    if (value.length !== length) return `El código debe tener ${length} dígitos`;
    return '';
  }
};


    // Manejo de eventos
    elements.numberInput.addEventListener('input', function(e) {
      const value = e.target.value.replace(/\D/g, '');
      const formatted = value.replace(/(.{4})/g, '$1 ').trim();
      e.target.value = formatted.substring(0, 19);
      
      elements.previews.number.textContent = formatted.padEnd(19, '#');
      detectCardType(value);
      updateCardStyle();
    });

    elements.nameInput.addEventListener('input', function(e) {
      elements.previews.name.textContent = e.target.value || "Nombre Apellido";
    });

    elements.expInput.addEventListener('input', function(e) {
      const value = e.target.value.replace(/\D/g, '');
      let formatted = value;
      if (value.length > 2) formatted = `${value.slice(0,2)}/${value.slice(2,4)}`;
      e.target.value = formatted.substring(0,5);
      elements.previews.exp.textContent = formatted || "MM/AA";
    });
// Solo permitir números en el campo CVC
        elements.cvcInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        });

    elements.form.addEventListener('submit', function(e) {
      e.preventDefault();
      clearErrors();

      const errors = {
        number: validations.cardNumber(elements.numberInput.value),
        name: validations.cardName(elements.nameInput.value),
        exp: validations.cardExp(elements.expInput.value),
        cvc: validations.cardCvc(elements.cvcInput.value)
      };

      const hasErrors = Object.values(errors).some(error => error);
      
      if (!hasErrors) {
        document.getElementById('modal-confirm').style.display = 'flex';
      } else {
        Object.keys(errors).forEach(field => {
          if (errors[field]) showError(field, errors[field]);
        });
      }
    });


    // Funciones auxiliares
        function detectCardType(value) {
        const firstTwo = value.slice(0,2);
        const firstFour = value.slice(0,4);
        
        elements.cvcInput.maxLength = 3;
        elements.cvcInput.placeholder = "CVC";
        currentCardType = 'default'; // Resetear a default

        if (/^4/.test(value)) {
            currentCardType = 'visa';
        } else if (/^5[1-5]/.test(firstTwo)) {
            currentCardType = 'mastercard';
        } else if (/(^34|^37)/.test(firstTwo)) {
            currentCardType = 'amex';
            elements.cvcInput.maxLength = 4;
            elements.cvcInput.placeholder = "CID";
        } else if (/^(6011|65)/.test(firstFour)) {
            currentCardType = 'discover';
        }
        }

    function updateCardStyle() {
      elements.cardPreview.className = `card-preview ${currentCardType}`;
      elements.previews.type.textContent = `Tipo: ${currentCardType === 'amex' ? 'American Express' : currentCardType}`;
    }

    function showError(field, message) {
      elements[`${field}Input`].classList.add('error-input');
      elements.errors[field].textContent = message;
      elements.errors[field].style.display = 'block';
    }

    function clearErrors() {
      Object.keys(elements.errors).forEach(field => {
        elements[`${field}Input`].classList.remove('error-input');
        elements.errors[field].style.display = 'none';
      });
    }

    // Configuración de eventos del modal
    document.getElementById('confirm-yes').addEventListener('click', () => {
    fetch('actualizar_plan.php')
        .then(res => res.text())
        .then(response => {
       if (response.trim() === "ok") {
    document.getElementById('modal-confirm').style.display = 'none';
    elements.form.style.display = 'none';

    // Mostrar el modal de éxito
    document.getElementById('modal-success').style.display = 'flex';
}  else {
            alert("Error al actualizar el plan: " + response);
        }
        })
        .catch(err => {
        alert("Error de red: " + err);
        });
    });

    document.getElementById('confirm-no').addEventListener('click', () => {
      document.getElementById('modal-confirm').style.display = 'none';
    });

// Redirección al hacer clic en "Continuar"
document.getElementById('continue-button').addEventListener('click', () => {
    window.location.href = 'index.php';
});

  </script>
</body>
</html>
