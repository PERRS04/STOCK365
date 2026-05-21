import './bootstrap';
import Alpine from 'alpinejs';
import { formatCurrency } from './utils/currency';

window.Alpine = Alpine;
window.formatCurrency = formatCurrency;
Alpine.start();
