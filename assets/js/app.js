// Sidebar: di layar lebar digeser (disimpan di localStorage), di HP muncul sebagai panel di atas halaman.
(function () {
  var html = document.documentElement, body = document.body;
  var btnSidebar = document.getElementById('btnSidebar');
  var backdrop = document.getElementById('backdrop');
  var btnTema = document.getElementById('btnTema');
  var lebar = function () { return window.matchMedia('(min-width: 992px)').matches; };

  btnSidebar.addEventListener('click', function () {
    if (lebar()) {
      var tutup = html.classList.toggle('side-closed');
      try { localStorage.setItem('sidebar', tutup ? 'tutup' : 'buka'); } catch (e) {}
    } else {
      body.classList.toggle('sidebar-open');
    }
  });
  backdrop.addEventListener('click', function () { body.classList.remove('sidebar-open'); });

  // Mode gelap: Bootstrap 5.3 membaca atribut data-bs-theme pada <html>.
  function setSwitch() {
    var gelap = html.getAttribute('data-bs-theme') === 'dark';
    btnTema.setAttribute('aria-checked', gelap ? 'true' : 'false');
    btnTema.classList.toggle('aktif', gelap);
  }
  btnTema.addEventListener('click', function () {
    var baru = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', baru);
    try { localStorage.setItem('tema', baru); } catch (e) {}
    setSwitch();
  });
  setSwitch();
})();
