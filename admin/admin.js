/* Admin UI helpers (no inline JS — CSP allows only this file) */
document.addEventListener('DOMContentLoaded', () => {
  // whole table row clickable
  document.querySelectorAll('tr[data-href]').forEach(tr => tr.addEventListener('click', e => {
    if (e.target.closest('a,input,button,label')) return;
    location.href = tr.dataset.href;
  }));

  // bulk selection
  const bulk = document.getElementById('bulk');
  if (bulk) {
    const boxes = [...bulk.querySelectorAll('input[name="ids[]"]')], all = bulk.querySelector('[data-all]'), bar = bulk.querySelector('.bulkbar'), n = bulk.querySelector('[data-count]');
    const sync = () => { const c = boxes.filter(b => b.checked).length; bar.hidden = !c; n.textContent = c; all.checked = c && c === boxes.length; all.indeterminate = c > 0 && c < boxes.length; };
    boxes.forEach(b => b.addEventListener('change', sync));
    all.addEventListener('change', () => { boxes.forEach(b => b.checked = all.checked); sync(); });
  }

  // confirmations
  document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  // copy to clipboard
  document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'zkopírováno'; setTimeout(() => b.textContent = 'kopírovat', 1500); } catch (e) {}
  }));

  // flash messages fade out
  setTimeout(() => document.querySelectorAll('.app .flash:not(.warn):not(.err)').forEach(f => { f.style.transition = 'opacity .6s'; f.style.opacity = '0'; setTimeout(() => f.remove(), 700); }), 4000);
});
