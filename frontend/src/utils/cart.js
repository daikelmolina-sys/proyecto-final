// Funciones puras para la lógica del carrito

export const agregarAlCarrito = (carrito, producto, cantidad = 1) => {
    const existente = carrito.find(item => item.id === producto.id);
    if (existente) {
        return carrito.map(item =>
            item.id === producto.id
                ? { ...item, cantidad: Math.min(10, item.cantidad + cantidad) }
                : item
        );
    }
    return [...carrito, { id: producto.id, nombre: producto.nombre, precio: Number(producto.precio), cantidad }];
};

export const cambiarCantidad = (carrito, id, delta) => {
    return carrito.map(item => {
        if (item.id === id) {
            return { ...item, cantidad: Math.max(1, Math.min(10, item.cantidad + delta)) };
        }
        return item;
    });
};

export const eliminarDelCarrito = (carrito, id) => {
    return carrito.filter(item => item.id !== id);
};

export const calcularTotalCarrito = (carrito) => {
    return carrito.reduce((sum, item) => sum + item.precio * item.cantidad, 0);
};
