function openReport(){const m=document.getElementById('reportModal');if(m)m.classList.add('show')}
function closeReport(){const m=document.getElementById('reportModal');if(m)m.classList.remove('show')}
document.addEventListener('click',e=>{const m=document.getElementById('reportModal');if(m&&e.target===m)closeReport();});
setTimeout(()=>document.querySelectorAll('.flash').forEach(x=>x.classList.add('hide')),5000);


// Password visibility controls
document.querySelectorAll('[data-password-toggle]').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.passwordToggle);
    if (!input) return;
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    btn.setAttribute('aria-pressed', String(!visible));
    btn.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
    btn.innerHTML = visible
      ? '<svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>'
      : '<svg viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.4 10.4 0 0 1 12 6c6 0 9.5 6 9.5 6a18.6 18.6 0 0 1-3.2 3.7M6.3 6.9C3.8 8.5 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
  });
});
