import { createContext, useState, useEffect } from 'react';

export const AppContext = createContext();

const STORAGE_KEYS = {
    carrito: 'carrito_franelas',
    presupuesto: 'presupuesto',
    tema: 'tema_visual',
    token: 'auth_token',
    usuario: 'auth_usuario',
};

export const AppProvider = ({ children }) => {
    // Estado inicial
    const [carrito, setCarrito] = useState(() => {
        try {
            const local = localStorage.getItem(STORAGE_KEYS.carrito);
            return local ? JSON.parse(local) : [];
        } catch { return []; }
    });
    const [catalogo, setCatalogo] = useState([]);
    const [presupuesto, setPresupuesto] = useState(() => Number(localStorage.getItem(STORAGE_KEYS.presupuesto)) || 0);
    const [tema, setTema] = useState(() => localStorage.getItem(STORAGE_KEYS.tema) || 'oscuro');
    const [authToken, setAuthToken] = useState(() => localStorage.getItem(STORAGE_KEYS.token) || null);
    const [authUsuario, setAuthUsuario] = useState(() => {
        try {
            const local = localStorage.getItem(STORAGE_KEYS.usuario);
            return local ? JSON.parse(local) : null;
        } catch { return null; }
    });

    const [isCartOpen, setIsCartOpen] = useState(false);
    const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
    const [isCheckoutModalOpen, setIsCheckoutModalOpen] = useState(false);

    // Persistencia del carrito
    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.carrito, JSON.stringify(carrito));
    }, [carrito]);

    // Persistencia del presupuesto
    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.presupuesto, presupuesto.toString());
    }, [presupuesto]);

    // Persistencia del tema
    useEffect(() => {
        localStorage.setItem(STORAGE_KEYS.tema, tema);
        document.body.setAttribute('data-theme', tema);
    }, [tema]);

    // Funciones del carrito
    const agregarAlCarrito = (producto, cantidad = 1) => {
        setCarrito(prev => {
            const existente = prev.find(item => item.id === producto.id);
            if (existente) {
                return prev.map(item =>
                    item.id === producto.id
                        ? { ...item, cantidad: Math.min(10, item.cantidad + cantidad) }
                        : item
                );
            }
            return [...prev, { id: producto.id, nombre: producto.nombre, precio: Number(producto.precio), cantidad }];
        });
    };

    const cambiarCantidad = (id, delta) => {
        setCarrito(prev => prev.map(item => {
            if (item.id === id) {
                return { ...item, cantidad: Math.max(1, Math.min(10, item.cantidad + delta)) };
            }
            return item;
        }));
    };

    const eliminarDelCarrito = (id) => {
        setCarrito(prev => prev.filter(item => item.id !== id));
    };

    const vaciarCarrito = () => setCarrito([]);

    const totalItems = carrito.reduce((sum, item) => sum + item.cantidad, 0);
    const totalCarrito = carrito.reduce((sum, item) => sum + item.precio * item.cantidad, 0);

    const toggleTema = () => {
        setTema(prev => prev === 'claro' ? 'oscuro' : 'claro');
    };

    const logout = () => {
        setAuthToken(null);
        setAuthUsuario(null);
        localStorage.removeItem(STORAGE_KEYS.token);
        localStorage.removeItem(STORAGE_KEYS.usuario);
    };

    const contextValue = {
        carrito, agregarAlCarrito, cambiarCantidad, eliminarDelCarrito, vaciarCarrito,
        totalItems, totalCarrito,
        catalogo, setCatalogo,
        presupuesto, setPresupuesto,
        tema, toggleTema,
        authToken, setAuthToken,
        authUsuario, setAuthUsuario,
        logout,
        isCartOpen, setIsCartOpen,
        isAuthModalOpen, setIsAuthModalOpen,
        isCheckoutModalOpen, setIsCheckoutModalOpen
    };

    return (
        <AppContext.Provider value={contextValue}>
            {children}
        </AppContext.Provider>
    );
};
