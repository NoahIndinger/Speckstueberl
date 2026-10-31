/* Gemeinsames Skript für alle Seiten (Header, Menü, Dialoge, Einblenden) */
(function(){
/* ---- Header / Navigation ---- */
const top_ = document.getElementById('top');
const callbar = document.getElementById('callbar');
const onScroll = () => {
  top_.classList.toggle('scrolled', scrollY > 30);
  callbar.classList.toggle('show', scrollY > innerHeight * .6);
};
addEventListener('scroll', onScroll, {passive:true}); onScroll();

const nav = document.getElementById('nav'), burger = document.getElementById('burger');
burger.addEventListener('click', () => {
  const open = nav.classList.toggle('open');
  burger.setAttribute('aria-expanded', open);
});
nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
  nav.classList.remove('open'); burger.setAttribute('aria-expanded', false);
}));

/* ---- Footer / Dialoge ---- */
document.getElementById('yr').textContent = new Date().getFullYear();
document.getElementById('impBtn').onclick = () => document.getElementById('imp').showModal();
document.getElementById('dsBtn').onclick = () => document.getElementById('ds').showModal();

/* ---- Einblenden beim Scrollen ---- */
const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), {threshold:.12});
document.querySelectorAll('.reveal').forEach(el => io.observe(el));
})();
