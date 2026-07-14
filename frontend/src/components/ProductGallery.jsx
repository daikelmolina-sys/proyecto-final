import { useContext, useEffect, useState } from 'react';
import { AppContext } from '../context/AppContext';
import ProductCard from './ProductCard';
import apiFetch from '../services/api';

const productosLocales = () => [
    { id: 1, nombre: 'Argentina', precio: 80, imagen: 'argentina.webp' },
    { id: 2, nombre: 'Brasil', precio: 65, imagen: 'brasil.webp' },
    { id: 3, nombre: 'Alemania', precio: 70, imagen: 'alemania.webp' },
    { id: 4, nombre: 'Portugal', precio: 60, imagen: 'portugal.webp' },
    { id: 5, nombre: 'Colombia', precio: 75, imagen: 'colombia.webp' },
    { id: 6, nombre: 'España', precio: 90, imagen: 'españa.webp' },
];

export default function ProductGallery() {
    const { catalogo, setCatalogo } = useContext(AppContext);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchProductos = async () => {
            try {
                const response = await apiFetch('/productos');
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                if (result.success && result.data?.length > 0) {
                    setCatalogo(result.data);
                } else {
                    setCatalogo(productosLocales());
                }
            } catch (error) {
                console.warn('Usando datos locales, error de API:', error);
                setCatalogo(productosLocales());
            } finally {
                setLoading(false);
            }
        };
        fetchProductos();
    }, [setCatalogo]);

    return (
        <section id="tienda" className="galeria" aria-label="Catálogo de Franelas">
            {loading ? (
                <div id="loader-tienda" className="loader" role="status" aria-live="polite">
                    <span className="spinner" aria-hidden="true"></span>
                    <span>Cargando catálogo…</span>
                </div>
            ) : (
                catalogo.map(producto => (
                    <ProductCard key={producto.id} producto={producto} />
                ))
            )}
        </section>
    );
}
