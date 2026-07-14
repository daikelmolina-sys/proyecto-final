import { useContext, useEffect, useRef } from 'react';
import { AppContext } from '../context/AppContext';

export default function CartDrawer() {
    const { 
        carrito, 
        cambiarCantidad, 
        eliminarDelCarrito, 
        totalCarrito, 
        isCartOpen, 
        setIsCartOpen,
        setIsCheckoutModalOpen,
        authToken,
        setIsAuthModalOpen
    } = useContext(AppContext);
    
    const overlayRef = useRef(null);

    // Cerrar con Escape
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isCartOpen) {
                setIsCartOpen(false);
            }
        };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isCartOpen, setIsCartOpen]);

    const handleCheckoutClick = () => {
        if (!authToken) {
            setIsCartOpen(false);
            setIsAuthModalOpen(true);
        } else {
            setIsCartOpen(false);
            setIsCheckoutModalOpen(true);
        }
    };

    return (
        <>
            <div 
                id="carrito-overlay" 
                className={`carrito-overlay ${isCartOpen ? 'visible' : ''}`} 
                hidden={!isCartOpen}
                onClick={() => setIsCartOpen(false)}
                ref={overlayRef}
            ></div>
            
            <aside 
                id="carrito-drawer" 
                className={`carrito-drawer ${isCartOpen ? 'abierto' : ''}`} 
                aria-label="Carrito de compras" 
                role="dialog"
                inert={!isCartOpen ? "" : undefined}
            >
                <header className="carrito-drawer-header">
                    <h2>Tu carrito</h2>
                    <button 
                        className="btn-cerrar" 
                        aria-label="Cerrar carrito"
                        onClick={() => setIsCartOpen(false)}
                    >
                        ×
                    </button>
                </header>

                <ul className="carrito-lista" aria-live="polite">
                    {carrito.map(item => (
                        <li key={item.id} className="carrito-item">
                            <div className="carrito-item-info">
                                <span className="carrito-item-nombre">{item.nombre}</span>
                                <span className="carrito-item-precio">${item.precio} c/u</span>
                            </div>
                            <div className="carrito-item-cantidad">
                                <button type="button" onClick={() => cambiarCantidad(item.id, -1)} aria-label="Quitar una unidad">−</button>
                                <span aria-live="polite">{item.cantidad}</span>
                                <button type="button" onClick={() => cambiarCantidad(item.id, 1)} aria-label="Agregar una unidad">+</button>
                            </div>
                            <span className="carrito-item-subtotal">
                                ${(item.precio * item.cantidad) % 1 === 0 ? (item.precio * item.cantidad) : (item.precio * item.cantidad).toFixed(2)}
                            </span>
                            <button 
                                type="button" 
                                className="carrito-item-eliminar" 
                                onClick={() => eliminarDelCarrito(item.id)}
                                aria-label={`Eliminar ${item.nombre} del carrito`}
                            >
                                🗑
                            </button>
                        </li>
                    ))}
                </ul>

                {carrito.length === 0 && (
                    <div className="carrito-vacio">
                        <p>Tu carrito está vacío.</p>
                        <p className="carrito-vacio-hint">Agrega franelas desde la tienda para empezar.</p>
                    </div>
                )}

                <footer className="carrito-drawer-footer">
                    <div className="carrito-total">
                        <span>Total</span>
                        <span>${totalCarrito % 1 === 0 ? totalCarrito : totalCarrito.toFixed(2)}</span>
                    </div>
                    <button 
                        className="btn-checkout" 
                        disabled={carrito.length === 0}
                        onClick={handleCheckoutClick}
                    >
                        Finalizar compra
                    </button>
                </footer>
            </aside>
        </>
    );
}
