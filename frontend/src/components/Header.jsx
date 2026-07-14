import { useContext } from 'react';
import { AppContext } from '../context/AppContext';

export default function Header() {
    const { 
        totalItems, 
        tema, toggleTema, 
        authToken, authUsuario, logout,
        setIsCartOpen, setIsAuthModalOpen
    } = useContext(AppContext);

    return (
        <header>
            <h1 className="titulo-galeria">Franelas Mundial 2026</h1>
            <nav aria-label="Navegación principal">
                <a href="#tienda" className="nav-link">Tienda</a>
                <a href="#datos" className="nav-link">Estadísticas</a>

                <button 
                    className="btn-carrito" 
                    aria-label="Abrir carrito de compras"
                    onClick={() => setIsCartOpen(true)}
                >
                    <span aria-hidden="true">🛒</span>
                    <span className="carrito-label">Carrito</span>
                    <span className="carrito-contador" aria-live="polite" data-vacio={totalItems === 0}>{totalItems}</span>
                </button>

                {!authToken ? (
                    <button 
                        className="btn-auth" 
                        aria-label="Iniciar sesión"
                        onClick={() => setIsAuthModalOpen(true)}
                    >
                        <span aria-hidden="true">👤</span>
                        <span>Iniciar sesión</span>
                    </button>
                ) : (
                    <div className="user-badge">
                        <span className="user-nombre">{authUsuario?.nombre || 'Usuario'}</span>
                        <button className="btn-logout" aria-label="Cerrar sesión" onClick={logout}>Salir</button>
                    </div>
                )}

                <button 
                    className="btn-theme" 
                    aria-label="Cambiar modo de color" 
                    aria-pressed={tema === 'claro'}
                    onClick={toggleTema}
                >
                    <span>{tema === 'claro' ? '☀️' : '🌙'}</span>
                    <span>{tema === 'claro' ? 'Claro' : 'Oscuro'}</span>
                </button>
            </nav>
        </header>
    );
}
