/**
 * app.js — Tienda Franelas Mundial 2026 (versión refactorizada)
 * ================================================================
 * Mejoras vs versión anterior:
 * - Init unificado en un solo DOMContentLoaded (no más window.onload + DOMContentLoaded).
 * - Carrito visible con contador, drawer lateral y persistencia en localStorage.
 * - Checkout mediante modal accesible (sin prompt/confirm/alert).
 * - Canvas convertido en gráfico de barras real (presupuesto vs precios).
 * - Tema claro/oscuro con feedback visual en el botón (icono + label).
 * - Notificaciones con role="status" + aria-live (lectores de pantalla).
 * - Anti-doble-click: el botón Comprar se deshabilita durante el procesamiento.
 * - Tarjetas estáticas eliminadas; el catálogo arranca con loader y se renderiza desde JS.
 * - Nombres de imágenes normalizados a minúsculas (case-sensitive safe en Linux).
 * - Selectores CSS huérfanos eliminados.
 *
 * Backend esperado (Laravel API):
 * GET  {API_URL}/productos   → { success, data: [{id, nombre, precio, imagen}] }
 * POST {API_URL}/pedidos     → { success, data: {id, total} }
 *
 * Si la API no responde, se usan productosLocales() como fallback.
 */

// ============================================================
// CONFIGURACIÓN
// ============================================================
// Detectar la URL base de las imágenes según dónde esté el frontend
const IMG_BASE = (() => {
    const { protocol, hostname, port, pathname } = window.location;
    if (protocol === 'file:') {
        // Abierto directamente como archivo: las imágenes están en la misma carpeta
        return '';
    }
    // Calcular la carpeta donde está el index.html
    const base = pathname.endsWith('/') ? pathname : pathname.substring(0, pathname.lastIndexOf('/') + 1);
    return `${protocol}//${hostname}${port ? ':' + port : ''}${base}`;
})();

const API_URL = 'http://localhost:8000/api';
const STORAGE_KEYS = {
    carrito: 'carrito_franelas',
    presupuesto: 'presupuesto',
    tema: 'tema_visual',
    token: 'auth_token',
    usuario: 'auth_usuario',
};

// ============================================================
// ESTADO
// ============================================================
let carrito = cargarCarritoDeStorage();
let catalogo = []; // caché local de productos cargados
let procesandoCompra = false; // anti-doble-click
let authToken = localStorage.getItem(STORAGE_KEYS.token) || null;
let authUsuario = (() => { try { return JSON.parse(localStorage.getItem(STORAGE_KEYS.usuario)) || null; } catch { return null; } })();
let pendienteCheckout = false; // abre checkout tras login exitoso

// ============================================================
// REFERENCIAS AL DOM (se asignan en init)
// ============================================================
const $ = (sel) => document.querySelector(sel);

let galeriaContainer;
let loaderTienda;
let btnToggleTheme;
let themeIcon, themeLabel;
let btnCarrito, btnCerrarCarrito, carritoDrawer, carritoOverlay;
let carritoLista, carritoVacio, carritoContador, carritoTotalEl, btnCheckout;
let modalCheckout, btnCerrarModal, formCheckout, modalTotalEl, btnEnviarPedido;
let notificacionesRegion;
let formularioPresupuesto, montoInput;
let ubicacionEl;
let apiContentEl;
let canvasEl;
let tablaPreciosBody;

// Auth DOM refs
let btnAuth, authLabel, userBadge, userNombreEl, btnLogout;
let modalAuth, btnCerrarAuth;
let tabLoginBtn, tabRegistroBtn, panelLogin, panelRegistro;
let formLogin, formRegistro, irARegistroBtn, irALoginBtn;
let btnLoginSubmit, btnRegistroSubmit;

// ============================================================
// DATOS DE RESPALDO (si la API no responde)
// ============================================================
function productosLocales() {
    return [
        { id: 1, nombre: 'Argentina', precio: 80, imagen: 'argentina.webp' },
        { id: 2, nombre: 'Brasil', precio: 65, imagen: 'brasil.webp' },
        { id: 3, nombre: 'Alemania', precio: 70, imagen: 'alemania.webp' },
        { id: 4, nombre: 'Portugal', precio: 60, imagen: 'portugal.webp' },
        { id: 5, nombre: 'Colombia', precio: 75, imagen: 'colombia.webp' },
        { id: 6, nombre: 'España', precio: 90, imagen: 'españa.webp' },
    ];
}

// ============================================================
// INICIALIZACIÓN ÚNICA
// ============================================================
document.addEventListener('DOMContentLoaded', init);

function init() {
    // Asignar referencias
    galeriaContainer = $('#tienda');
    loaderTienda = $('#loader-tienda');
    btnToggleTheme = $('#toggle-theme');
    themeIcon = $('#theme-icon');
    themeLabel = $('#theme-label');
    btnCarrito = $('#btn-carrito');
    btnCerrarCarrito = $('#btn-cerrar-carrito');
    carritoDrawer = $('#carrito-drawer');
    carritoOverlay = $('#carrito-overlay');
    carritoLista = $('#carrito-lista');
    carritoVacio = $('#carrito-vacio');
    carritoContador = $('#carrito-contador');
    carritoTotalEl = $('#carrito-total');
    btnCheckout = $('#btn-checkout');
    modalCheckout = $('#modal-checkout');
    btnCerrarModal = $('#btn-cerrar-modal');
    formCheckout = $('#form-checkout');
    modalTotalEl = $('#modal-total');
    btnEnviarPedido = $('#btn-enviar-pedido');
    notificacionesRegion = $('#notificaciones');
    formularioPresupuesto = $('#form-presupuesto');
    montoInput = $('#monto');
    ubicacionEl = $('#ubicacion');
    apiContentEl = $('#api-content');
    canvasEl = $('#graficoCanvas');
    tablaPreciosBody = $('#tabla-precios-body');

    // Auth DOM refs
    btnAuth = $('#btn-auth');
    authLabel = $('#auth-label');
    userBadge = $('#user-badge');
    userNombreEl = $('#user-nombre');
    btnLogout = $('#btn-logout');
    modalAuth = $('#modal-auth');
    btnCerrarAuth = $('#btn-cerrar-auth');
    tabLoginBtn = $('#tab-login');
    tabRegistroBtn = $('#tab-registro');
    panelLogin = $('#panel-login');
    panelRegistro = $('#panel-registro');
    formLogin = $('#form-login');
    formRegistro = $('#form-registro');
    irARegistroBtn = $('#ir-a-registro');
    irALoginBtn = $('#ir-a-login');
    btnLoginSubmit = $('#btn-login-submit');
    btnRegistroSubmit = $('#btn-registro-submit');

    // Tema visual
    aplicarTemaGuardado();

    // Auth UI inicial
    renderAuthUI();

    // Geolocalización
    initGeolocalizacion();

    // Presupuesto
    const presupuestoPersistido = localStorage.getItem(STORAGE_KEYS.presupuesto);
    if (presupuestoPersistido) {
        montoInput.value = presupuestoPersistido;
    }
    dibujarGraficoCanvas(presupuestoPersistido ? Number(presupuestoPersistido) : 0);

    // Eventos
    bindEventos();

    // Cargar datos
    cargarProductosDesdeAPI();
    consumirAPI();

    // Render inicial del carrito (por si ya había items en localStorage)
    renderCarrito();

    console.log('✅ DOM listo — init completo.');
}

// ============================================================
// EVENTOS
// ============================================================
// ============================================================
// EVENTOS
// ============================================================
function bindEventos() {
    // Carrito drawer
    btnCarrito.addEventListener('click', abrirCarrito);
    btnCerrarCarrito.addEventListener('click', cerrarCarrito);

    // Controles del carrito (+, -, eliminar)
    carritoLista.addEventListener('click', onInteraccionCarrito);

    // Evento de Comprar en los productos
    galeriaContainer.addEventListener('click', onClickComprar);

    // Evento de Presupuesto
    formularioPresupuesto.addEventListener('submit', onGuardarPresupuesto);

    // Tema
    btnToggleTheme.addEventListener('click', toggleTema);

    // Un solo listener en overlay
    carritoOverlay.addEventListener('click', () => {
        if (!modalCheckout.hidden) {
            cerrarModalCheckout();
        } else {
            cerrarCarrito();
        }
    });

    // Checkout
    btnCheckout.addEventListener('click', abrirModalCheckout);
    btnCerrarModal.addEventListener('click', cerrarModalCheckout);
    formCheckout.addEventListener('submit', onEnviarPedido);

    // ── Auth ──
    btnAuth.addEventListener('click', abrirModalAuth);
    btnLogout.addEventListener('click', onLogout);
    btnCerrarAuth.addEventListener('click', cerrarModalAuth);

    // Tabs de Login / Registro
    tabLoginBtn.addEventListener('click', () => cambiarTabAuth('login'));
    tabRegistroBtn.addEventListener('click', () => cambiarTabAuth('registro'));
    irARegistroBtn.addEventListener('click', () => cambiarTabAuth('registro'));
    irALoginBtn.addEventListener('click', () => cambiarTabAuth('login'));

    // Formularios de auth
    formLogin.addEventListener('submit', onSubmitLogin);
    formRegistro.addEventListener('submit', onSubmitRegistro);

    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (modalAuth && !modalAuth.hidden) cerrarModalAuth();
            else if (modalCheckout && !modalCheckout.hidden) cerrarModalCheckout();
            else if (carritoDrawer.classList.contains('abierto')) cerrarCarrito();
        }
    });
}

// ============================================================
// FETCH INTERCEPTOR (inyecta Bearer token)
// ============================================================
/**
 * Wrapper de fetch que agrega automáticamente el encabezado
 * Authorization: Bearer {token} si hay sesión activa.
 * Todos los llamados a la API deben usar esta función.
 */
async function apiFetch(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers || {}),
    };
    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    return fetch(url, { ...options, headers });
}

// ============================================================
// CARGA DE PRODUCTOS DESDE LA API
// ============================================================
async function cargarProductosDesdeAPI() {
    try {
        const response = await apiFetch(`${API_URL}/productos`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const resultado = await response.json();

        if (resultado.success && Array.isArray(resultado.data) && resultado.data.length > 0) {
            catalogo = resultado.data;
            console.log('✅ Productos cargados desde la API Laravel');
        } else {
            console.warn('⚠️ La API no devolvió productos. Usando datos locales.');
            catalogo = productosLocales();
        }
    } catch (error) {
        console.warn('⚠️ No se pudo conectar con la API. Usando datos locales.');
        console.warn('   Asegúrate de que Laravel esté corriendo: php artisan serve');
        catalogo = productosLocales();
    }

    renderProductos(catalogo);
    renderTablaPrecios(catalogo);
    // Redibujar el gráfico ahora que tenemos los precios
    const presupuesto = Number(localStorage.getItem(STORAGE_KEYS.presupuesto)) || 0;
    dibujarGraficoCanvas(presupuesto);
}

// ============================================================
// RENDER: CATÁLOGO
// ============================================================
function renderProductos(productos) {
    // Limpiar loader y tarjetas previas
    galeriaContainer.innerHTML = '';

    productos.forEach(producto => {
        // Parseamos el precio a número
        const precio = parseFloat(producto.precio);
        // Imagen con ruta base correcta para XAMPP o file://
        const imgSrc = producto.imagen.startsWith('http')
            ? producto.imagen
            : IMG_BASE + producto.imagen;

        const tarjeta = document.createElement('article');
        tarjeta.className = 'tarjeta';
        tarjeta.innerHTML = `
            <img src="${escaparHTML(imgSrc)}" alt="Franela de ${escaparHTML(producto.nombre)}" loading="lazy">
            <h3>${escaparHTML(producto.nombre)}</h3>
            <p>$${precio % 1 === 0 ? precio : precio.toFixed(2)}</p>
            <div class="controles-compra">
                <input type="number" class="input-cantidad" value="1" min="1" max="10"
                    aria-label="Cantidad para ${escaparHTML(producto.nombre)}">
                <button class="btn-comprar"
                    data-id="${producto.id}"
                    data-nombre="${escaparHTML(producto.nombre)}"
                    data-precio="${precio}"
                    aria-label="Agregar ${escaparHTML(producto.nombre)} al carrito">
                    Comprar
                </button>
            </div>
        `;
        galeriaContainer.appendChild(tarjeta);
    });
}

// ============================================================
// RENDER: TABLA DE PRECIOS
// ============================================================
function renderTablaPrecios(productos) {
    tablaPreciosBody.innerHTML = '';
    productos.forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escaparHTML(p.nombre)}</td><td>$${p.precio}</td>`;
        tablaPreciosBody.appendChild(tr);
    });
}

// ============================================================
// CARRITO: PERSISTENCIA
// ============================================================
function cargarCarritoDeStorage() {
    try {
        const raw = localStorage.getItem(STORAGE_KEYS.carrito);
        if (!raw) return [];

        const carritoParseado = JSON.parse(raw);

        // Verificamos si es un arreglo válido antes de devolverlo
        if (Array.isArray(carritoParseado)) {
            return carritoParseado;
        }

        // Si no es un arreglo (está corrupto), lo borramos y devolvemos vacío
        localStorage.removeItem(STORAGE_KEYS.carrito);
        return [];
    } catch (e) {
        // Si hubo error al parsear, borramos la llave dañada
        localStorage.removeItem(STORAGE_KEYS.carrito);
        return [];
    }
}

function guardarCarritoEnStorage() {
    localStorage.setItem(STORAGE_KEYS.carrito, JSON.stringify(carrito));
}

// ============================================================
// CARRITO: LÓGICA
// ============================================================
function agregarAlCarrito(productoId, nombre, precio, cantidad) {
    const existente = carrito.find(item => item.id === productoId);
    if (existente) {
        existente.cantidad = Math.min(10, existente.cantidad + cantidad);
    } else {
        carrito.push({
            id: productoId,
            nombre,
            precio: Number(precio),
            cantidad,
        });
    }
    guardarCarritoEnStorage();
    renderCarrito();
}

function cambiarCantidad(productoId, delta) {
    const item = carrito.find(i => i.id === productoId);
    if (!item) return;
    item.cantidad = Math.max(1, Math.min(10, item.cantidad + delta));
    guardarCarritoEnStorage();
    renderCarrito();
}

function eliminarDelCarrito(productoId) {
    carrito = carrito.filter(i => i.id !== productoId);
    guardarCarritoEnStorage();
    renderCarrito();
}

function vaciarCarrito() {
    carrito = [];
    guardarCarritoEnStorage();
    renderCarrito();
}

function totalItemsCarrito() {
    return carrito.reduce((sum, item) => sum + item.cantidad, 0);
}

function totalCarrito() {
    return carrito.reduce((sum, item) => sum + item.precio * item.cantidad, 0);
}

// ============================================================
// CARRITO: RENDER
// ============================================================
function renderCarrito() {
    // Contador en el botón del header
    const total = totalItemsCarrito();
    carritoContador.textContent = total;
    carritoContador.setAttribute('data-vacio', total === 0 ? 'true' : 'false');

    // Lista
    carritoLista.innerHTML = '';

    if (carrito.length === 0) {
        carritoVacio.style.display = 'flex';
        btnCheckout.disabled = true;
    } else {
        carritoVacio.style.display = 'none';
        btnCheckout.disabled = false;

        carrito.forEach(item => {
            const subtotal = item.precio * item.cantidad;
            const li = document.createElement('li');
            li.className = 'carrito-item';
            li.innerHTML = `
                <div class="carrito-item-info">
                    <span class="carrito-item-nombre">${escaparHTML(item.nombre)}</span>
                    <span class="carrito-item-precio">$${item.precio} c/u</span>
                </div>
                <div class="carrito-item-cantidad">
                    <button type="button" data-accion="restar" data-id="${item.id}" aria-label="Quitar una unidad">−</button>
                    <span aria-live="polite">${item.cantidad}</span>
                    <button type="button" data-accion="sumar" data-id="${item.id}" aria-label="Agregar una unidad">+</button>
                </div>
                <span class="carrito-item-subtotal">$${subtotal % 1 === 0 ? subtotal : subtotal.toFixed(2)}</span>
                <button type="button" class="carrito-item-eliminar" data-accion="eliminar" data-id="${item.id}" aria-label="Eliminar ${escaparHTML(item.nombre)} del carrito">🗑</button>
            `;
            carritoLista.appendChild(li);
        });
    }

    // Formatear totales como número entero cuando corresponda
    carritoTotalEl.textContent = `$${totalCarrito() % 1 === 0 ? totalCarrito() : totalCarrito().toFixed(2)}`;
}

// ============================================================
// CARRITO: UI (abrir/cerrar)
// ============================================================
// ============================================================
// CARRITO: UI (abrir/cerrar)
// ============================================================
function abrirCarrito() {
    carritoOverlay.hidden = false;
    void carritoOverlay.offsetWidth;
    carritoOverlay.classList.add('visible');
    carritoDrawer.classList.add('abierto');
    carritoDrawer.removeAttribute('inert');

    // Re-renderizar el carrito al abrir para garantizar que el layout se
    // calcule en el contexto visible (el drawer puede haber sido renderizado
    // cuando estaba fuera de pantalla con translateX(100%), lo que provoca
    // que el navegador calcule anchos como 0 y recorte texto largo).
    renderCarrito();
}

function cerrarCarrito() {
    carritoOverlay.classList.remove('visible');
    carritoDrawer.classList.remove('abierto');

    // EL CAMBIO: ponemos el inert para ocultarlo totalmente al lector de pantalla
    carritoDrawer.setAttribute('inert', '');

    setTimeout(() => {
        if (!carritoDrawer.classList.contains('abierto')) {
            carritoOverlay.hidden = true;
        }
    }, 300);
}

// ============================================================
// CARRITO: interacciones (delegation)
// ============================================================
function onInteraccionCarrito(e) {
    const btn = e.target.closest('button[data-accion]');
    if (!btn) return;
    const id = Number(btn.dataset.id);
    const accion = btn.dataset.accion;
    if (Number.isNaN(id)) return;

    if (accion === 'sumar') cambiarCantidad(id, +1);
    else if (accion === 'restar') cambiarCantidad(id, -1);
    else if (accion === 'eliminar') eliminarDelCarrito(id);
}

// ============================================================
// CLICK EN COMPRAR
// ============================================================
function onClickComprar(e) {
    const boton = e.target.closest('.btn-comprar');
    if (!boton) return;

    // Anti-doble-click
    if (procesandoCompra || boton.disabled) return;
    procesandoCompra = true;
    boton.disabled = true;

    const productoId = Number(boton.dataset.id);
    const nombre = boton.dataset.nombre;
    const precio = Number(boton.dataset.precio);

    if (Number.isNaN(productoId)) {
        mostrarNotificacion('Error: producto sin id. Recarga la página.', 'error');
        procesandoCompra = false;
        boton.disabled = false;
        return;
    }

    const contenedorTarjeta = boton.closest('.tarjeta');
    const inputCantidad = contenedorTarjeta.querySelector('.input-cantidad');
    let cantidad = parseInt(inputCantidad.value, 10);

    if (Number.isNaN(cantidad) || cantidad < 1 || cantidad > 10) {
        mostrarNotificacion('Cantidad inválida. Selecciona entre 1 y 10 unidades.', 'error');
        procesandoCompra = false;
        boton.disabled = false;
        return;
    }

    agregarAlCarrito(productoId, nombre, precio, cantidad);
    mostrarNotificacion(`${cantidad} franela(s) de ${nombre} agregada(s) al carrito.`, 'success');

    // Rehabilitar tras un breve bloqueo anti-doble-click
    setTimeout(() => {
        procesandoCompra = false;
        boton.disabled = false;
    }, 400);
}

// ============================================================
// MODAL DE CHECKOUT
// ============================================================
function abrirModalCheckout() {
    if (carrito.length === 0) {
        mostrarNotificacion('El carrito está vacío.', 'error');
        return;
    }
    // Protección: requerir sesión activa
    if (!authToken) {
        pendienteCheckout = true;
        mostrarNotificacion('Debes iniciar sesión para finalizar tu compra.', 'info');
        cerrarCarrito();
        abrirModalAuth();
        return;
    }
    pendienteCheckout = false;
    const total = totalCarrito();
    modalTotalEl.textContent = `$${total % 1 === 0 ? total : total.toFixed(2)}`;
    modalCheckout.hidden = false;
    // Focus al primer input para accesibilidad
    setTimeout(() => $('#checkout-nombre').focus(), 50);
}

function cerrarModalCheckout() {
    modalCheckout.hidden = true;
}

async function onEnviarPedido(e) {
    e.preventDefault();
    if (btnEnviarPedido.disabled) return;
    btnEnviarPedido.disabled = true;
    const textoOriginal = btnEnviarPedido.textContent;
    btnEnviarPedido.textContent = 'Enviando…';

    const nombre = $('#checkout-nombre').value.trim();
    const email = $('#checkout-email').value.trim();
    const telefono = $('#checkout-telefono').value.trim() || null;

    if (!nombre || !email) {
        mostrarNotificacion('Nombre y correo son obligatorios.', 'error');
        btnEnviarPedido.disabled = false;
        btnEnviarPedido.textContent = textoOriginal;
        return;
    }

    const body = {
        nombre_cliente: nombre,
        email,
        telefono,
        productos: carrito.map(item => ({
            producto_id: item.id,
            cantidad: item.cantidad,
        })),
    };

    try {
        const response = await apiFetch(`${API_URL}/pedidos`, {
            method: 'POST',
            body: JSON.stringify(body),
        });

        const resultado = await response.json();

        if (response.ok && resultado.success) {
            const total = resultado.data.total;
            const pedidoId = resultado.data.id;
            mostrarNotificacion(`✅ Pedido #${pedidoId} creado. Total: $${total}`, 'success');
            console.log('✅ Pedido creado:', resultado.data);

            vaciarCarrito();
            cerrarModalCheckout();
            cerrarCarrito();
            formCheckout.reset();
        } else {
            const errorMsg = resultado.message || 'Error desconocido en el servidor.';
            mostrarNotificacion(`Error: ${errorMsg}`, 'error');
            console.error('❌ Error al crear pedido:', resultado);
        }
    } catch (error) {
        mostrarNotificacion('No se pudo conectar con el servidor. ¿Laravel está corriendo?', 'error');
        console.error('❌ Error de conexión:', error);
    } finally {
        btnEnviarPedido.disabled = false;
        btnEnviarPedido.textContent = textoOriginal;
    }
}

// ============================================================
// TEMA VISUAL
// ============================================================
function aplicarTemaGuardado() {
    const temaGuardado = localStorage.getItem(STORAGE_KEYS.tema);
    if (temaGuardado === 'claro') {
        document.body.setAttribute('data-theme', 'claro');
        btnToggleTheme.setAttribute('aria-pressed', 'true');
        themeIcon.textContent = '☀️';
        themeLabel.textContent = 'Claro';
    } else {
        document.body.removeAttribute('data-theme');
        btnToggleTheme.setAttribute('aria-pressed', 'false');
        themeIcon.textContent = '🌙';
        themeLabel.textContent = 'Oscuro';
    }
}

function toggleTema() {
    const estaEnClaro = document.body.getAttribute('data-theme') === 'claro';
    if (estaEnClaro) {
        document.body.removeAttribute('data-theme');
        localStorage.setItem(STORAGE_KEYS.tema, 'oscuro');
        btnToggleTheme.setAttribute('aria-pressed', 'false');
        themeIcon.textContent = '🌙';
        themeLabel.textContent = 'Oscuro';
    } else {
        document.body.setAttribute('data-theme', 'claro');
        localStorage.setItem(STORAGE_KEYS.tema, 'claro');
        btnToggleTheme.setAttribute('aria-pressed', 'true');
        themeIcon.textContent = '☀️';
        themeLabel.textContent = 'Claro';
    }
    // Redibujar el canvas con los nuevos colores del tema
    const presupuesto = Number(localStorage.getItem(STORAGE_KEYS.presupuesto)) || 0;
    dibujarGraficoCanvas(presupuesto);
}

// ============================================================
// PRESUPUESTO
// ============================================================
function onGuardarPresupuesto(e) {
    e.preventDefault();
    const monto = Number(montoInput.value);
    if (!monto || monto <= 0) {
        mostrarNotificacion('Ingresa un monto válido.', 'error');
        return;
    }
    localStorage.setItem(STORAGE_KEYS.presupuesto, String(monto));
    dibujarGraficoCanvas(monto);
    mostrarNotificacion(`Presupuesto guardado: $${monto}`, 'success');
}

// ============================================================
// GRÁFICO CANVAS (gráfico de barras real)
// ============================================================
function dibujarGraficoCanvas(presupuesto = 0) {
    const canvas = canvasEl;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // Ajustar resolución para pantallas HiDPI
    const dpr = window.devicePixelRatio || 1;
    const cssWidth = canvas.clientWidth || 800;
    const cssHeight = canvas.clientHeight || 320;
    canvas.width = cssWidth * dpr;
    canvas.height = cssHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    // Limpiar
    ctx.clearRect(0, 0, cssWidth, cssHeight);

    // Colores según tema
    const temaClaro = document.body.getAttribute('data-theme') === 'claro';
    const colorTexto = temaClaro ? '#0f172a' : '#f1f5f9';
    const colorTextoSuave = temaClaro ? '#475569' : '#cbd5e1';
    const colorBarra = temaClaro ? '#0284c7' : '#38bdf8';
    const colorBarraSobrepasa = '#ef4444';
    const colorLineaPresupuesto = '#f59e0b';
    const colorGrid = temaClaro ? 'rgba(15,23,42,0.08)' : 'rgba(255,255,255,0.08)';

    // Datos: precios del catálogo (o fallback local si aún no se cargó)
    const productos = (catalogo && catalogo.length > 0) ? catalogo : productosLocales();
    if (productos.length === 0) return;

    const precios = productos.map(p => ({ nombre: p.nombre, precio: Number(p.precio) }));
    const maxPrecio = Math.max(...precios.map(p => p.precio), presupuesto, 1);

    // Márgenes
    const marginLeft = 50;
    const marginRight = 20;
    const marginTop = 30;
    const marginBottom = 50;
    const chartW = cssWidth - marginLeft - marginRight;
    const chartH = cssHeight - marginTop - marginBottom;

    // Escala Y
    const yMax = Math.ceil(maxPrecio * 1.1 / 10) * 10;
    const yScale = chartH / yMax;

    // Grid horizontal + etiquetas Y
    ctx.font = "11px 'Plus Jakarta Sans', sans-serif";
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    const pasosY = 5;
    for (let i = 0; i <= pasosY; i++) {
        const valor = (yMax / pasosY) * i;
        const y = marginTop + chartH - valor * yScale;
        ctx.strokeStyle = colorGrid;
        ctx.beginPath();
        ctx.moveTo(marginLeft, y);
        ctx.lineTo(marginLeft + chartW, y);
        ctx.stroke();
        ctx.fillStyle = colorTextoSuave;
        ctx.fillText(`$${valor}`, marginLeft - 6, y);
    }

    // Barras
    const barW = chartW / precios.length * 0.6;
    const gap = chartW / precios.length * 0.4;

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    precios.forEach((p, i) => {
        const barH = p.precio * yScale;
        const x = marginLeft + (chartW / precios.length) * i + gap / 2;
        const y = marginTop + chartH - barH;

        // Color: si el presupuesto > 0 y la franela supera el presupuesto, resaltar
        const sobrepasa = presupuesto > 0 && p.precio > presupuesto;
        ctx.fillStyle = sobrepasa ? colorBarraSobrepasa : colorBarra;
        ctx.fillRect(x, y, barW, barH);

        // Etiqueta de precio encima de la barra
        ctx.fillStyle = colorTexto;
        ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
        ctx.fillText(`$${p.precio}`, x + barW / 2, y - 16);

        // Etiqueta de nombre debajo
        ctx.fillStyle = colorTextoSuave;
        ctx.font = "11px 'Plus Jakarta Sans', sans-serif";
        ctx.fillText(p.nombre, x + barW / 2, marginTop + chartH + 8);
    });

    // Línea del presupuesto
    if (presupuesto > 0) {
        const yPres = marginTop + chartH - presupuesto * yScale;
        ctx.strokeStyle = colorLineaPresupuesto;
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
        ctx.beginPath();
        ctx.moveTo(marginLeft, yPres);
        ctx.lineTo(marginLeft + chartW, yPres);
        ctx.stroke();
        ctx.setLineDash([]);

        // Etiqueta "Presupuesto: $X"
        ctx.fillStyle = colorLineaPresupuesto;
        ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
        ctx.textAlign = 'left';
        ctx.textBaseline = 'bottom';
        ctx.fillText(`Presupuesto: $${presupuesto}`, marginLeft + 6, yPres - 4);
    }

    // Título
    ctx.fillStyle = colorTexto;
    ctx.font = "600 13px 'Plus Jakarta Sans', sans-serif";
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.fillText('Precios de franelas vs tu presupuesto', marginLeft, 8);
}

// Redibujar el canvas al redimensionar la ventana
window.addEventListener('resize', () => {
    const presupuesto = Number(localStorage.getItem(STORAGE_KEYS.presupuesto)) || 0;
    dibujarGraficoCanvas(presupuesto);
});

// ============================================================
// AUTENTICACIÓN — Sesión
// ============================================================

/**
 * Guarda el token y datos del usuario en localStorage y actualiza el estado.
 * @param {string} token  - plainTextToken devuelto por Sanctum
 * @param {object} usuario - objeto con al menos { name, email }
 */
function guardarSesion(token, usuario) {
    authToken = token;
    authUsuario = usuario;
    localStorage.setItem(STORAGE_KEYS.token, token);
    localStorage.setItem(STORAGE_KEYS.usuario, JSON.stringify(usuario));
    renderAuthUI();
}

/** Elimina la sesión del estado y del localStorage. */
function limpiarSesion() {
    authToken = null;
    authUsuario = null;
    localStorage.removeItem(STORAGE_KEYS.token);
    localStorage.removeItem(STORAGE_KEYS.usuario);
    renderAuthUI();
}

/**
 * Actualiza el nav: muestra/oculta el botón de Login y el badge de usuario.
 */
function renderAuthUI() {
    if (authToken && authUsuario) {
        // Logueado
        btnAuth.hidden = true;
        userBadge.hidden = false;
        userNombreEl.textContent = authUsuario.name || authUsuario.email;
    } else {
        // No logueado
        btnAuth.hidden = false;
        userBadge.hidden = true;
    }
}

// ── Modal Auth: abrir / cerrar ──────────────────────────────
function abrirModalAuth() {
    modalAuth.hidden = false;
    setTimeout(() => $('#login-email').focus(), 50);
}

function cerrarModalAuth() {
    modalAuth.hidden = true;
    pendienteCheckout = false;
}

// ── Tabs Login / Registro ───────────────────────────────────
function cambiarTabAuth(tab) {
    const esLogin = tab === 'login';
    tabLoginBtn.classList.toggle('auth-tab-activo', esLogin);
    tabLoginBtn.setAttribute('aria-selected', String(esLogin));
    tabRegistroBtn.classList.toggle('auth-tab-activo', !esLogin);
    tabRegistroBtn.setAttribute('aria-selected', String(!esLogin));
    panelLogin.hidden = !esLogin;
    panelRegistro.hidden = esLogin;
    // Focus al primer campo del panel activo
    setTimeout(() => {
        const firstInput = (esLogin ? panelLogin : panelRegistro).querySelector('input');
        if (firstInput) firstInput.focus();
    }, 50);
}

// ── Login ───────────────────────────────────────────────────
async function onSubmitLogin(e) {
    e.preventDefault();
    if (btnLoginSubmit.disabled) return;
    btnLoginSubmit.disabled = true;
    const textoOriginal = btnLoginSubmit.textContent;
    btnLoginSubmit.textContent = 'Entrando…';

    const email = $('#login-email').value.trim();
    const password = $('#login-password').value;

    try {
        const response = await fetch(`${API_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, password }),
        });
        const resultado = await response.json();

        if (response.ok && resultado.token) {
            guardarSesion(resultado.token, resultado.user || { name: email, email });
            mostrarNotificacion(`¡Bienvenido${resultado.user?.name ? ', ' + resultado.user.name : ''}! 🎉`, 'success');
            cerrarModalAuth();
            formLogin.reset();
            // Si el usuario venía desde el checkout, abrirlo
            if (pendienteCheckout) {
                pendienteCheckout = false;
                abrirCarrito();
                setTimeout(abrirModalCheckout, 350);
            }
        } else {
            const msg = resultado.message || 'Credenciales incorrectas.';
            mostrarNotificacion(msg, 'error');
        }
    } catch (err) {
        mostrarNotificacion('No se pudo conectar con el servidor.', 'error');
        console.error('❌ Error login:', err);
    } finally {
        btnLoginSubmit.disabled = false;
        btnLoginSubmit.textContent = textoOriginal;
    }
}

// ── Registro ────────────────────────────────────────────────
async function onSubmitRegistro(e) {
    e.preventDefault();
    if (btnRegistroSubmit.disabled) return;
    btnRegistroSubmit.disabled = true;
    const textoOriginal = btnRegistroSubmit.textContent;
    btnRegistroSubmit.textContent = 'Creando cuenta…';

    const name = $('#registro-nombre').value.trim();
    const email = $('#registro-email').value.trim();
    const password = $('#registro-password').value;

    try {
        const response = await fetch(`${API_URL}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name, email, password }),
        });
        const resultado = await response.json();

        if (response.ok && resultado.token) {
            guardarSesion(resultado.token, resultado.user || { name, email });
            mostrarNotificacion(`¡Cuenta creada! Bienvenido, ${name} 🎉`, 'success');
            cerrarModalAuth();
            formRegistro.reset();
            if (pendienteCheckout) {
                pendienteCheckout = false;
                abrirCarrito();
                setTimeout(abrirModalCheckout, 350);
            }
        } else {
            // Laravel devuelve errores de validación en resultado.errors
            const msg = resultado.message
                || Object.values(resultado.errors || {}).flat()[0]
                || 'Error al crear la cuenta.';
            mostrarNotificacion(msg, 'error');
        }
    } catch (err) {
        mostrarNotificacion('No se pudo conectar con el servidor.', 'error');
        console.error('❌ Error registro:', err);
    } finally {
        btnRegistroSubmit.disabled = false;
        btnRegistroSubmit.textContent = textoOriginal;
    }
}

// ── Logout ──────────────────────────────────────────────────
async function onLogout() {
    // Llamamos al endpoint de logout en el backend (revoca el token)
    if (authToken) {
        try {
            await apiFetch(`${API_URL}/logout`, { method: 'POST' });
        } catch (err) {
            console.warn('⚠️ Logout en el servidor falló (se limpia localmente de todas formas):', err);
        }
    }
    limpiarSesion();
    mostrarNotificacion('Sesión cerrada correctamente.', 'success');
}

// ============================================================
// API EXTERNA (noticias)
// ============================================================
async function consumirAPI() {
    if (!apiContentEl) return;
    try {
        // Simulamos una carga de noticias en español
        setTimeout(() => {
            apiContentEl.textContent = '¡Última hora! El Mundial 2026 se prepara para recibir a las mejores selecciones. ¡Asegura tu franela oficial hoy mismo!';
        }, 1000);
    } catch (err) {
        apiContentEl.textContent = 'No se pudieron cargar las noticias en este momento.';
        console.warn('⚠️ Falló consumirAPI:', err);
    }
}

// ============================================================
// GEOLOCALIZACIÓN
// ============================================================
function initGeolocalizacion() {
    if (!('geolocation' in navigator)) {
        ubicacionEl.textContent = 'No soportada en este dispositivo.';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude.toFixed(2);
            const lng = pos.coords.longitude.toFixed(2);
            ubicacionEl.textContent = `Lat ${lat} · Lng ${lng}`;
        },
        (err) => {
            console.warn('⚠️ Geolocalización denegada:', err.message);
            ubicacionEl.textContent = 'No disponible (permiso denegado).';
        },
        { timeout: 8000, enableHighAccuracy: false }
    );
}

// ============================================================
// NOTIFICACIONES (toasts accesibles)
// ============================================================
function mostrarNotificacion(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `notif-toast${tipo === 'error' ? ' notif-error' : tipo === 'success' ? ' notif-success' : ''}`;
    toast.textContent = mensaje;
    notificacionesRegion.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('saliendo');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================================
// UTILIDADES
// ============================================================
function escaparHTML(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ============================================================
// EXPORTS PARA PRUEBAS UNITARIAS (Jest / Node.js)
// ============================================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        agregarAlCarrito,
        totalCarrito,
        vaciarCarrito,
        totalItemsCarrito,
        cambiarCantidad,
        eliminarDelCarrito,
        productosLocales,
        escaparHTML,
    };
}