import { useState, useContext, useEffect } from 'react';
import { AppContext } from '../context/AppContext';

export default function AuthModal() {
    const { isAuthModalOpen, setIsAuthModalOpen, setAuthToken, setAuthUsuario, setIsCheckoutModalOpen, carrito } = useContext(AppContext);
    const [tab, setTab] = useState('login'); // 'login' | 'registro'
    
    // Estados del formulario
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [nombre, setNombre] = useState('');

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isAuthModalOpen) setIsAuthModalOpen(false);
        };
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isAuthModalOpen, setIsAuthModalOpen]);

    if (!isAuthModalOpen) return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Simulación de autenticación/registro ya que no hay backend conectado para auth en este plan
        const mockUser = { nombre: tab === 'registro' ? nombre : (email.split('@')[0] || 'Usuario') };
        const mockToken = 'mock-jwt-token-12345';
        
        setAuthToken(mockToken);
        setAuthUsuario(mockUser);
        localStorage.setItem('auth_token', mockToken);
        localStorage.setItem('auth_usuario', JSON.stringify(mockUser));
        
        setIsAuthModalOpen(false);
        
        // Si el carrito no está vacío y el usuario inició sesión, podríamos abrir el checkout.
        if (carrito.length > 0) {
            setIsCheckoutModalOpen(true);
        }
    };

    return (
        <div className="modal" role="dialog" aria-modal="true" aria-labelledby="modal-auth-titulo">
            <div className="modal-contenido">
                <header className="modal-header">
                    <h2 id="modal-auth-titulo">Mi cuenta</h2>
                    <button className="btn-cerrar" aria-label="Cerrar" onClick={() => setIsAuthModalOpen(false)}>×</button>
                </header>

                <div className="auth-tabs" role="tablist">
                    <button 
                        className={`auth-tab ${tab === 'login' ? 'auth-tab-activo' : ''}`} 
                        role="tab" 
                        aria-selected={tab === 'login'} 
                        onClick={() => setTab('login')}
                    >
                        Iniciar sesión
                    </button>
                    <button 
                        className={`auth-tab ${tab === 'registro' ? 'auth-tab-activo' : ''}`} 
                        role="tab" 
                        aria-selected={tab === 'registro'} 
                        onClick={() => setTab('registro')}
                    >
                        Crear cuenta
                    </button>
                </div>

                {tab === 'login' ? (
                    <div className="auth-panel" role="tabpanel">
                        <form className="auth-form" onSubmit={handleSubmit}>
                            <label>
                                Correo electrónico
                                <input type="email" required autoComplete="email" placeholder="tu@email.com" value={email} onChange={e => setEmail(e.target.value)} />
                            </label>
                            <label>
                                Contraseña
                                <input type="password" required autoComplete="current-password" placeholder="••••••••" value={password} onChange={e => setPassword(e.target.value)} />
                            </label>
                            <button type="submit" className="btn-checkout">Entrar</button>
                            <p className="auth-switch">¿No tienes cuenta? <button type="button" className="auth-switch-btn" onClick={() => setTab('registro')}>Regístrate aquí</button></p>
                        </form>
                    </div>
                ) : (
                    <div className="auth-panel" role="tabpanel">
                        <form className="auth-form" onSubmit={handleSubmit}>
                            <label>
                                Nombre completo
                                <input type="text" required autoComplete="name" placeholder="Juan Pérez" value={nombre} onChange={e => setNombre(e.target.value)} />
                            </label>
                            <label>
                                Correo electrónico
                                <input type="email" required autoComplete="email" placeholder="tu@email.com" value={email} onChange={e => setEmail(e.target.value)} />
                            </label>
                            <label>
                                Contraseña <span className="auth-hint">(mín. 8 caracteres)</span>
                                <input type="password" required autoComplete="new-password" minLength="8" placeholder="••••••••" value={password} onChange={e => setPassword(e.target.value)} />
                            </label>
                            <button type="submit" className="btn-checkout">Crear cuenta</button>
                            <p className="auth-switch">¿Ya tienes cuenta? <button type="button" className="auth-switch-btn" onClick={() => setTab('login')}>Inicia sesión</button></p>
                        </form>
                    </div>
                )}
            </div>
        </div>
    );
}
