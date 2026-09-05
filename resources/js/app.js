import './bootstrap';

import Alpine from 'alpinejs';
import assessmentTimer from './assessment-timer';

window.Alpine = Alpine;
Alpine.data('assessmentTimer', assessmentTimer);

Alpine.start();
