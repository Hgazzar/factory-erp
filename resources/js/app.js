import './bootstrap';
import './journalEntryForm';
import Swal from 'sweetalert2';

window.Swal = Swal;

/**
 * Do not import alpinejs or call Alpine.start() here.
 * Livewire 3 (with Filament on the same layout) already loads Alpine once.
 * A second copy from this bundle triggers "multiple instances of Alpine" and
 * breaks x-data scope (e.g. addLine / balanced undefined on journal create).
 */
