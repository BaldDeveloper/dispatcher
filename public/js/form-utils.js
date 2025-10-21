// Utility functions for form reset and error display

/**
 * Reset all fields in a form and clear validation states.
 * @param {HTMLFormElement} form
 */
function resetForm(form) {
    form.reset();
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = 'none');
}

/**
 * Show validation error for a field.
 * @param {HTMLElement} field
 * @param {string} message
 */
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    let feedback = field.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = message;
        feedback.style.display = 'block';
    }
}

/**
 * Hide validation error for a field.
 * @param {HTMLElement} field
 */
function hideFieldError(field) {
    field.classList.remove('is-invalid');
    let feedback = field.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.style.display = 'none';
    }
}

// Auto-calc total for transport charges
(function () {
  function parseVal(v) {
    if (v === null || v === undefined || v === '') return 0;
    v = String(v).trim();
    if (v === '') return 0;
    // normalize comma decimal separators to dot
    v = v.replace(/,/g, '.');
    var n = parseFloat(v);
    return isFinite(n) ? n : 0;
  }

  function updateTotal() {
    var inputs = document.querySelectorAll('.charge-input');
    var total = 0;
    inputs.forEach(function (el) {
      total += parseVal(el.value);
    });
    var totalEl = document.getElementById('total_charge');
    if (totalEl) {
      totalEl.value = total.toFixed(2);
    }
  }

  // Wire events (input covers typing/paste; change covers some browsers)
  document.addEventListener('input', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('charge-input')) {
      updateTotal();
    }
  });
  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('charge-input')) {
      updateTotal();
    }
  });

  // initial calc on page load
  document.addEventListener('DOMContentLoaded', updateTotal);

  // export for CommonJS (tests) if present
  if (typeof module !== 'undefined') {
    module.exports = Object.assign(module.exports || {}, { resetForm: typeof resetForm !== 'undefined' ? resetForm : undefined, updateTotal: updateTotal, showFieldError: typeof showFieldError !== 'undefined' ? showFieldError : undefined, hideFieldError: typeof hideFieldError !== 'undefined' ? hideFieldError : undefined });
  }
})();
