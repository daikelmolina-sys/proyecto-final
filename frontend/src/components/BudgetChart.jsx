import { useContext, useEffect, useRef, useState } from 'react';
import { AppContext } from '../context/AppContext';

export default function BudgetChart() {
    const { presupuesto, setPresupuesto, catalogo, tema } = useContext(AppContext);
    const canvasRef = useRef(null);
    const [inputValue, setInputValue] = useState(presupuesto || '');

    const handleSave = (e) => {
        e.preventDefault();
        const monto = Number(inputValue);
        if (monto > 0) setPresupuesto(monto);
    };

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const dpr = window.devicePixelRatio || 1;
        const cssWidth = canvas.clientWidth || 800;
        const cssHeight = canvas.clientHeight || 320;
        canvas.width = cssWidth * dpr;
        canvas.height = cssHeight * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        ctx.clearRect(0, 0, cssWidth, cssHeight);

        const temaClaro = tema === 'claro';
        const colorTexto = temaClaro ? '#0f172a' : '#f1f5f9';
        const colorTextoSuave = temaClaro ? '#475569' : '#cbd5e1';
        const colorBarra = temaClaro ? '#0284c7' : '#38bdf8';
        const colorBarraSobrepasa = '#ef4444';
        const colorLineaPresupuesto = '#f59e0b';
        const colorGrid = temaClaro ? 'rgba(15,23,42,0.08)' : 'rgba(255,255,255,0.08)';

        if (!catalogo || catalogo.length === 0) return;

        const precios = catalogo.map(p => ({ nombre: p.nombre, precio: Number(p.precio) }));
        const maxPrecio = Math.max(...precios.map(p => p.precio), presupuesto, 1);

        const marginLeft = 50, marginRight = 20, marginTop = 30, marginBottom = 50;
        const chartW = cssWidth - marginLeft - marginRight;
        const chartH = cssHeight - marginTop - marginBottom;

        const yMax = Math.ceil(maxPrecio * 1.1 / 10) * 10;
        const yScale = chartH / yMax;

        ctx.font = "11px 'Plus Jakarta Sans', sans-serif";
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        
        for (let i = 0; i <= 5; i++) {
            const valor = (yMax / 5) * i;
            const y = marginTop + chartH - valor * yScale;
            ctx.strokeStyle = colorGrid;
            ctx.beginPath();
            ctx.moveTo(marginLeft, y);
            ctx.lineTo(marginLeft + chartW, y);
            ctx.stroke();
            ctx.fillStyle = colorTextoSuave;
            ctx.fillText(`$${valor}`, marginLeft - 6, y);
        }

        const barW = (chartW / precios.length) * 0.6;
        const gap = (chartW / precios.length) * 0.4;

        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        precios.forEach((p, i) => {
            const barH = p.precio * yScale;
            const x = marginLeft + (chartW / precios.length) * i + gap / 2;
            const y = marginTop + chartH - barH;

            const sobrepasa = presupuesto > 0 && p.precio > presupuesto;
            ctx.fillStyle = sobrepasa ? colorBarraSobrepasa : colorBarra;
            ctx.fillRect(x, y, barW, barH);

            ctx.fillStyle = colorTexto;
            ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
            ctx.fillText(`$${p.precio}`, x + barW / 2, y - 16);

            ctx.fillStyle = colorTextoSuave;
            ctx.font = "11px 'Plus Jakarta Sans', sans-serif";
            ctx.fillText(p.nombre, x + barW / 2, marginTop + chartH + 8);
        });

        if (presupuesto > 0) {
            const yPres = marginTop + chartH - presupuesto * yScale;
            ctx.strokeStyle = colorLineaPresupuesto;
            ctx.lineWidth = 2;
            ctx.setLineDash([6, 4]);
            ctx.beginPath();
            ctx.moveTo(marginLeft, yPres);
            ctx.lineTo(marginLeft + chartW, yPres);
            ctx.stroke();
            ctx.setLineDash([]);

            ctx.fillStyle = colorLineaPresupuesto;
            ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
            ctx.textAlign = 'left';
            ctx.fillText(`Presupuesto: $${presupuesto}`, marginLeft + 10, yPres - 10);
        }
    }, [presupuesto, catalogo, tema]);

    return (
        <section id="datos" aria-label="Sección de analítica y presupuesto">
            <h2>Calculadora de Presupuesto</h2>
            <form id="form-presupuesto" onSubmit={handleSave}>
                <input 
                    type="number" 
                    id="monto" 
                    placeholder="Tu presupuesto ($)" 
                    required 
                    min="1"
                    aria-label="Monto del presupuesto"
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                />
                <button type="submit" id="btn-guardar-presupuesto">Guardar</button>
            </form>

            <canvas 
                id="graficoCanvas" 
                ref={canvasRef} 
                style={{ width: '100%', height: '320px', display: 'block' }} 
                aria-label="Gráfico de presupuesto vs precios de franelas"
            ></canvas>
            
            <table>
                <thead>
                    <tr>
                        <th>Selección</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody id="tabla-precios-body">
                    {catalogo?.map(p => (
                        <tr key={p.id}>
                            <td>{p.nombre}</td>
                            <td>${p.precio}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </section>
    );
}
