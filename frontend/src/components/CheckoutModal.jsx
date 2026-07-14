import { useState, useContext, useEffect, useRef } from 'react';
import { AppContext } from '../context/AppContext';
import apiFetch from '../services/api';

export default function CheckoutModal() {
    const { isCheckoutModalOpen, setIsCheckoutModalOpen, totalCarrito, carrito, vaciarCarrito, authUsuario } = useContext(AppContext);
    
    const [nombre, setNombre] = useState('');
    const [email, setEmail] = useState('');
    const [telefono, setTelefono] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [mensaje, setMensaje] = useState(null);
    
    const nombreRef = useRef(null);

    useEffect(() => {
        if (isCheckoutModalOpen) {
            setNombre(authUsuario?.nombre || '');
            if (nombreRef.current) setTimeout(() => nombreRef.current.focus(), 50);
        }
    }, [isCheckoutModalOpen, authUsuario]);

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isCheckoutModalOpen) setIsCheckoutModalOpen(false);
        };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isCheckoutModalOpen, setIsCheckoutModalOpen]);

    if (!isCheckoutModalOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (isSubmitting) return;
        setIsSubmitting(true);
        setMensaje(null);

        const body = {
            nombre_cliente: nombre,
            email,
            telefono: telefono || null,
            productos: carrito.map(item => ({
                producto_id: item.id,
                cantidad: item.cantidad,
            })),
        };

        try {
            const response = await apiFetch('/pedidos', {
                method: 'POST',
                body: JSON.stringify(body),
            });

            const resultado = await response.json();

            if (response.ok && resultado.success) {
                alert(`✅ Pedido #${resultado.data.id} creado. Total: $${resultado.data.total}`);
                vaciarCarrito();
                setIsCheckoutModalOpen(false);
                setNombre('');
                setEmail('');
                setTelefono('');
            } else {
                setMensaje({ tipo: 'error', texto: resultado.message || 'Error desconocido en el servidor.' });
            }
        } catch (error) {
            setMensaje({ tipo: 'error', texto: 'No se pudo conectar con el servidor.' });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="modal" role="dialog" aria-modal="true" aria-labelledby="modal-checkout-titulo">
            <div className="modal-contenido">
                <header className="modal-header">
                    <h2 id="modal-checkout-titulo">Finalizar compra</h2>
                    <button className="btn-cerrar" aria-label="Cerrar formulario" onClick={() => setIsCheckoutModalOpen(false)}>×</button>
                </header>
                
                {mensaje && (
                    <div style={{ color: mensaje.tipo === 'error' ? 'red' : 'green', marginBottom: '1rem' }}>
                        {mensaje.texto}
                    </div>
                )}
                
                <form id="form-checkout" onSubmit={handleSubmit}>
                    <label>
                        Nombre completo
                        <input type="text" required autoComplete="name" value={nombre} onChange={e => setNombre(e.target.value)} ref={nombreRef} />
                    </label>
                    <label>
                        Correo electrónico
                        <input type="email" required autoComplete="email" value={email} onChange={e => setEmail(e.target.value)} />
                    </label>
                    <label>
                        Teléfono (opcional)
                        <input type="tel" autoComplete="tel" value={telefono} onChange={e => setTelefono(e.target.value)} />
                    </label>
                    <div className="modal-resumen" aria-live="polite">
                        <span>Total a pagar</span>
                        <strong>${totalCarrito % 1 === 0 ? totalCarrito : totalCarrito.toFixed(2)}</strong>
                    </div>
                    <button type="submit" className="btn-checkout" disabled={isSubmitting}>
                        {isSubmitting ? 'Enviando…' : 'Confirmar pedido'}
                    </button>
                </form>
            </div>
        </div>
    );
}
