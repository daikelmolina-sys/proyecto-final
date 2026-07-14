import { AppProvider } from './context/AppContext';
import Header from './components/Header';
import ProductGallery from './components/ProductGallery';
import CartDrawer from './components/CartDrawer';
import AuthModal from './components/AuthModal';
import CheckoutModal from './components/CheckoutModal';
import BudgetChart from './components/BudgetChart';
import './index.css';

function App() {
  return (
    <AppProvider>
      <Header />
      <main>
        <ProductGallery />
        <BudgetChart />
      </main>
      <footer>
        <p>Proyecto educativo · Tienda de Franelas Mundial 2026</p>
      </footer>
      
      <CartDrawer />
      <AuthModal />
      <CheckoutModal />
      <div id="notificaciones" className="notificaciones-region" role="status" aria-live="polite" aria-atomic="true"></div>
    </AppProvider>
  );
}

export default App;
