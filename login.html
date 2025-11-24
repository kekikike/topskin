<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | TopSkin</title>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Baumans&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family: 'Baumans', cursive;
    background: linear-gradient( rgba(14, 27, 43, 0.568)),
              url('media/fondosi.jpg') center -0% / cover no-repeat;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    color: rgb(19, 15, 34);
  }

  .login-container {
    position: relative;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(5px);
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    width: 450px;
    padding: 3.5rem 2.2rem 2.5rem;
    box-shadow: 0 20px 45px rgba(0,0,0,0.5);
    text-align: center;
    overflow: hidden;
    justify-content: center;
  }

  .switch-container {
    position: absolute;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10;
  }
  .switch-container input[type="checkbox"] {
    width: 48px; height: 24px;
    -webkit-appearance: none;
    background: #555;
    border-radius: 30px;
    position: relative;
    cursor: pointer;
    transition: background 0.4s ease;
    box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.048);
    justify-content: center;
  }
  .switch-container input[type="checkbox"]:checked { background: #6c8599; }
  .switch-container input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 20px; height: 20px;
    border-radius: 50%;
    top: 2px; left: 2px;
    background: white;
    transition: transform 0.35s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    justify-content: center;
  }
  .switch-container input[type="checkbox"]:checked::before {
    transform: translateX(24px);
  }

  /* ==== CONTENEDOR DE FORMULARIOS ==== */
  .form-wrapper {
    position: relative;
    height: 420px;
    margin-top: 20px;
  }

  .form-cliente, .form-empleado {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 0 10px;
    transition: all 0.55s cubic-bezier(0.68, -0.55, 0.27, 1.55);
  }

  /* Animación Cliente → Empleado */
  .slide-right-enter-active, .slide-right-leave-active,
  .slide-left-enter-active, .slide-left-leave-active {
    transition: all 0.55s cubic-bezier(0.68, -0.55, 0.27, 1.55);
  }
  .slide-right-enter-from { transform: translateX(-100%); opacity: 0; }
  .slide-right-leave-to   { transform: translateX(100%); opacity: 0; }

  .slide-left-enter-from  { transform: translateX(100%); opacity: 0; }
  .slide-left-leave-to    { transform: translateX(-100%); opacity: 0; }

  .icon { 
    width: 90px; height: 90px; border-radius: 50%; overflow: hidden; 
    border: 3px solid rgba(255,255,255,0.25); margin: 0 auto 1rem;
    background: rgba(255,255,255,0.1); display: flex; justify-content: center; align-items: center;
  }
  .icon img { width: 60px; height: 60px; object-fit: cover; }

  .profile-img {
    width: 110px; height: 110px; border-radius: 50%;
    border: 5px solid #6c8599;
    margin: 0 auto 1.5rem;
    object-fit: cover;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
  }

  h2 { font-size: 28px; margin-bottom: 1.8rem; letter-spacing: 1px; justify-content: center; }

  input {
    width: 100%; padding: 10px 20px;margin-bottom:10px ;
    border: none; border-radius: 14px; background: rgba(255,255,255,0.22);
    color: white; font-size: 1.05rem;
    transition: all 0.3s ease;
    font-family: 'Baumans', cursive;
    font-size: 20px;
  }
  input::placeholder { color: rgba(255,255,255,0.75); }
  input:focus {
    background: rgba(255,255,255,0.35);
    box-shadow: 0 0 0 4px rgba(20,184,166,0.4);
    transform: scale(1.02);
  }

  .btn-ingresar {
    background: linear-gradient(135deg, #95a8b8, #416d69);
    color: white; border: none; padding: 2px;
    width: 100%; border-radius: 14px; font-size: 1.15rem; font-weight: 600;
    height: 50px;
    cursor: pointer; margin-top: 0.5rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    transition: all 0.3s ease;
    font-family: 'Baumans', cursive;
    margin-top: -10px;
    margin-bottom: 20px;
  }
  .btn-ingresar:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
  }

  .error { color: #4e1010; min-height: 1.4rem; margin: 0.4rem 0; font-weight: 500; font-size: large;}

  .letra{
    font-size: 20px;
  }
</style>
</head>
<body>
<div id="app">
  <div class="login-container">
    <!-- SWITCH -->
    <div class="letra">
      Ingresar como Administrador
      <div class="switch-container">
        <input type="checkbox" v-model="isEmpleado">
      </div>
    </div>
     

    <!-- FORMULARIOS CON ANIMACIÓN -->
    <div class="form-wrapper">

      <!-- CLIENTE -->
      <transition name="slide-right">
        <div v-if="!isEmpleado" class="form-cliente" key="cliente">
          <div class="icon">
            <img src="media/icon.png" alt="Cliente">
          </div>
          <h2>Cliente</h2>
          <form @submit.prevent="loginCliente">
            <input type="email" v-model="correoCliente" placeholder="tu@correo.com" required>
            <input type="password" v-model="contrasenaCliente" placeholder="••••••••" required>
            <p class="error">{{ mensajeErrorCliente }}</p>
            <button type="submit" class="btn-ingresar">Ingresar</button>
            <button type="button" class="btn-ingresar" @click="regresar">Regresar</button>
          </form>
        </div>
      </transition>

      <!-- EMPLEADO -->
      <transition name="slide-left">
        <div v-if="isEmpleado" class="form-empleado" key="empleado">
          <img src="media/icon.png" alt="Empleado" class="profile-img">
          <h2>Administrador</h2>
          <form @submit.prevent="loginEmpleado">
            <input type="email" v-model="correoEmpleado" placeholder="tu@correo.com" required>
            <input type="password" v-model="contrasenaEmpleado" placeholder="••••••••" required>
            <p class="error">{{ mensajeErrorEmpleado }}</p>
            <button type="submit" class="btn-ingresar">Ingresar</button>
            <button type="button" class="btn-ingresar" @click="regresar">Regresar</button>
          </form>
        </div>
      </transition>

    </div>
  </div>
</div>

<script>
const { createApp, ref } = Vue;

createApp({
  setup() {
    const isEmpleado = ref(false);

    // === CLIENTE ===
    const correoCliente = ref('');
    const contrasenaCliente = ref('');
    const mensajeErrorCliente = ref('');

    // === EMPLEADO / ADMIN ===
    const correoEmpleado = ref('');
    const contrasenaEmpleado = ref('');
    const mensajeErrorEmpleado = ref('');

    // LOGIN CLIENTE
    const loginCliente = async () => {
      mensajeErrorCliente.value = '';
      try {
        const res = await fetch('backend/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            correo: correoCliente.value.trim(),
            contrasena: contrasenaCliente.value
          })
        });

        const data = await res.json();

        if (data.success) {
          localStorage.setItem('cliente', JSON.stringify(data.usuario));
          window.location.href = 'index.html';
        } else {
          mensajeErrorCliente.value = data.message || 'Credenciales incorrectas';
        }
      } catch (err) {
        mensajeErrorCliente.value = 'Error de conexión';
        console.error(err);
      }
    };

    // LOGIN EMPLEADO / ADMIN
    const loginEmpleado = async () => {
      mensajeErrorEmpleado.value = '';
      try {
        const res = await fetch('backend/login_empleado.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            correo: correoEmpleado.value.trim(),
            contrasena: contrasenaEmpleado.value
          })
        });

        const data = await res.json();

        if (data.success) {
          const u = data.usuario;

          localStorage.setItem('usuarioEmpleado', JSON.stringify(u));
          localStorage.setItem('ciEmpleado', u.ciEmpleado || '');
          localStorage.setItem('idSucursal', u.idSucursal || '');
          localStorage.setItem('idRol', u.idRol || '');
          localStorage.setItem('nombreCompleto', u.nombre || '');

          if (u.idRol === 'ADM') {
            window.location.href = 'src/menu_admin.html';
          } else if (u.idRol === 'VND') {
            window.location.href = 'src/menu_empleado.html';
          } else {
            mensajeErrorEmpleado.value = 'Rol no autorizado';
          }
        } else {
          mensajeErrorEmpleado.value = data.message || 'Credenciales incorrectas';
        }
      } catch (err) {
        mensajeErrorEmpleado.value = 'Error de conexión';
        console.error(err);
      }
    };
    const regresar = () => {
      window.location.href = 'index.html';
    };

    return {
      isEmpleado,
      correoCliente, contrasenaCliente, mensajeErrorCliente, loginCliente,
      correoEmpleado, contrasenaEmpleado, mensajeErrorEmpleado, loginEmpleado, regresar
    };
  }
}).mount('#app');
</script>

</body>
</html>
