import './bootstrap';
import Alpine from 'alpinejs';
import { fuzzyTour } from './tour.js';

window.Alpine    = Alpine;
window.fuzzyTour = fuzzyTour;
Alpine.start();
