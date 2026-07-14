import { describe, it, expect } from 'vitest';
import { agregarAlCarrito, cambiarCantidad, eliminarDelCarrito, calcularTotalCarrito } from '../utils/cart';

describe('Lógica del Carrito', () => {
    it('agrega un producto nuevo al carrito', () => {
        const carritoInicial = [];
        const producto = { id: 1, nombre: 'Franela A', precio: 100 };
        const nuevoCarrito = agregarAlCarrito(carritoInicial, producto, 2);
        
        expect(nuevoCarrito).toHaveLength(1);
        expect(nuevoCarrito[0].id).toBe(1);
        expect(nuevoCarrito[0].cantidad).toBe(2);
    });

    it('no duplica IDs, suma la cantidad si el producto ya existe', () => {
        const carritoInicial = [{ id: 1, nombre: 'Franela A', precio: 100, cantidad: 2 }];
        const producto = { id: 1, nombre: 'Franela A', precio: 100 };
        const nuevoCarrito = agregarAlCarrito(carritoInicial, producto, 3);
        
        expect(nuevoCarrito).toHaveLength(1);
        expect(nuevoCarrito[0].cantidad).toBe(5); // 2 + 3
    });

    it('respeta el límite máximo de 10 unidades al agregar', () => {
        const carritoInicial = [{ id: 1, nombre: 'Franela A', precio: 100, cantidad: 8 }];
        const producto = { id: 1, nombre: 'Franela A', precio: 100 };
        const nuevoCarrito = agregarAlCarrito(carritoInicial, producto, 5);
        
        expect(nuevoCarrito[0].cantidad).toBe(10);
    });

    it('cambia la cantidad respetando límites (min 1, max 10)', () => {
        const carritoInicial = [{ id: 1, nombre: 'Franela A', precio: 100, cantidad: 5 }];
        
        let modificado = cambiarCantidad(carritoInicial, 1, 2);
        expect(modificado[0].cantidad).toBe(7);
        
        modificado = cambiarCantidad(modificado, 1, 5);
        expect(modificado[0].cantidad).toBe(10); // tope
        
        modificado = cambiarCantidad(modificado, 1, -15);
        expect(modificado[0].cantidad).toBe(1); // mínimo
    });

    it('elimina un producto del carrito', () => {
        const carritoInicial = [
            { id: 1, nombre: 'Franela A', precio: 100, cantidad: 1 },
            { id: 2, nombre: 'Franela B', precio: 200, cantidad: 1 }
        ];
        const nuevoCarrito = eliminarDelCarrito(carritoInicial, 1);
        
        expect(nuevoCarrito).toHaveLength(1);
        expect(nuevoCarrito[0].id).toBe(2);
    });

    it('calcula el total exacto del carrito', () => {
        const carrito = [
            { id: 1, nombre: 'Franela A', precio: 10.5, cantidad: 2 }, // 21
            { id: 2, nombre: 'Franela B', precio: 20, cantidad: 3 }    // 60
        ];
        const total = calcularTotalCarrito(carrito);
        
        expect(total).toBe(81);
    });
});
