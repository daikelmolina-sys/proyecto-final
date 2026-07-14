import { useState, useContext } from 'react';
import { AppContext } from '../context/AppContext';

export default function ProductCard({ producto }) {
    const { agregarAlCarrito } = useContext(AppContext);
    const [cantidad, setCantidad] = useState(1);
    const [isProcessing, setIsProcessing] = useState(false);

    const precio = parseFloat(producto.precio);
    const imgSrc = producto.imagen.startsWith('http') ? producto.imagen : `/${producto.imagen}`;

    const handleComprar = () => {
        if (isProcessing) return;

        setIsProcessing(true);
        agregarAlCarrito(producto, cantidad);

        setTimeout(() => {
            setIsProcessing(false);
        }, 400);
    };

    return (
        <article className="tarjeta">
            <img src={imgSrc} alt={`Franela de ${producto.nombre}`} loading="lazy" />
            <h3>{producto.nombre}</h3>
            <p>${precio % 1 === 0 ? precio : precio.toFixed(2)}</p>
            <div className="controles-compra">
                <input
                    type="number"
                    className="input-cantidad"
                    value={cantidad}
                    min="1"
                    max="10"
                    onChange={(e) => setCantidad(parseInt(e.target.value) || 1)}
                    aria-label={`Cantidad para ${producto.nombre}`}
                />
                <button
                    className="btn-comprar"
                    onClick={handleComprar}
                    disabled={isProcessing}
                    aria-label={`Agregar ${producto.nombre} al carrito`}
                >
                    Comprar
                </button>
            </div>
        </article>
    );
}
