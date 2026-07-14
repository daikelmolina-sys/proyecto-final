import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ProductCard from '../components/ProductCard';
import { AppContext } from '../context/AppContext';

describe('Componente ProductCard', () => {
    const mockProducto = {
        id: 1,
        nombre: 'Franela Argentina',
        precio: 85.50,
        imagen: 'argentina.webp'
    };

    const mockAgregarAlCarrito = vi.fn();

    const renderConContexto = (producto) => {
        return render(
            <AppContext.Provider value={{ agregarAlCarrito: mockAgregarAlCarrito }}>
                <ProductCard producto={producto} />
            </AppContext.Provider>
        );
    };

    it('renderiza el nombre y precio del producto correctamente', () => {
        renderConContexto(mockProducto);
        
        expect(screen.getByText('Franela Argentina')).toBeInTheDocument();
        expect(screen.getByText('$85.50')).toBeInTheDocument();
        // Verificar imagen
        const img = screen.getByAltText('Franela de Franela Argentina');
        expect(img).toBeInTheDocument();
        expect(img.src).toContain('/legacy/argentina.webp');
    });

    it('llama a agregarAlCarrito con la cantidad correcta al hacer click en Comprar', () => {
        renderConContexto(mockProducto);
        
        // Cambiamos la cantidad a 3
        const input = screen.getByRole('spinbutton');
        fireEvent.change(input, { target: { value: '3' } });
        
        // Clic en comprar
        const btn = screen.getByRole('button', { name: /Agregar Franela Argentina al carrito/i });
        fireEvent.click(btn);
        
        expect(mockAgregarAlCarrito).toHaveBeenCalledWith(mockProducto, 3);
    });
});
