/****** Estado y utilidades ******/
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));
const money = n => Number(n).toLocaleString('es-MX',{style:'currency',currency:'MXN'});

let baseProductos = [];          // cargado de productos.json
let overrides = null;            // edición local (localStorage)
let productos = [];              // resultado de combinar base + overrides
let cart = JSON.parse(localStorage.getItem('cart_biscocho')||'{}'); // {id: qty}

const OVK = 'inv_biscocho_overrides_v1';

/****** Tabs ******/
function setTab(id){
  $$('.tab').forEach(b=>b.classList.remove('active'));
  $$('.view').forEach(v=>v.classList.add('hidden'));
  if(id==='catalogo'){ $('#tabCatalogo').classList.add('active'); $('#vistaCatalogo').classList.remove('hidden'); }
  if(id==='inventario'){ $('#tabInventario').classList.add('active'); $('#vistaInventario').classList.remove('hidden'); }
  if(id==='ticket'){ $('#tabTicket').classList.add('active'); $('#vistaTicket').classList.remove('hidden'); renderTicket(); }
}
$('#tabCatalogo').onclick = ()=>setTab('catalogo');
$('#tabInventario').onclick = ()=>setTab('inventario');
$('#tabTicket').onclick = ()=>setTab('ticket');

/****** Carga productos ******/
async function loadProductos(){
  try{
    const r = await fetch('productos.json', {cache:'no-store'});
    const data = await r.json();
    baseProductos = data.productos || [];
  }catch(e){
    console.error('No se pudo leer productos.json', e);
    baseProductos = [];
  }
  overrides = JSON.parse(localStorage.getItem(OVK)||'{}'); // {id:{...p editado}} y __new:[] para nuevos
  mergeProductos();
  renderCatalogo();
  renderInventario();
}

function mergeProductos(){
  const map = new Map(baseProductos.map(p=>[p.id, {...p}]));
  // aplica overrides existentes
  if(overrides){
    for(const [id,ov] of Object.entries(overrides)){
      if(id==='__new') continue;
      if(map.has(id)) map.set(id, {...map.get(id), ...ov});
    }
    // agrega nuevos
    if(Array.isArray(overrides.__new)){
      for(const p of overrides.__new){ map.set(p.id, {...p}); }
    }
  }
  productos = Array.from(map.values());
}

/****** Catálogo ******/
function filteredProductos(){
  const s = $('#buscador').value.trim().toLowerCase();
  const cat = $('#filtroCategoria').value || 'Todas';
  return productos.filter(p=>{
    const okCat = cat==='Todas' || p.categoria===cat;
    const okTxt = p.nombre.toLowerCase().includes(s);
    return okCat && okTxt;
  });
}

function renderCatalogo(){
  // opciones de categoría
  const cats = ['Todas', ...new Set(productos.map(p=>p.categoria))];
  $('#filtroCategoria').innerHTML = cats.map(c=>`<option>${c}</option>`).join('');

  const root = $('#grid');
  root.innerHTML = '';
  for(const p of filteredProductos()){
    const el = document.createElement('div');
    el.className = 'card';
    el.innerHTML = `
      <div class="meta"><span class="badge">${p.categoria||'-'}</span><span class="price">${money(p.precio)}</span></div>
      <h3>${p.nombre}</h3>
      <div class="meta"><span>Stock: ${p.stock ?? 0}</span><span>ID: ${p.id}</span></div>
      <button class="btn" data-id="${p.id}" ${!p.stock || p.stock<=0 ? 'disabled':''}>Agregar</button>
    `;
    root.appendChild(el);
  }
  root.querySelectorAll('button[data-id]').forEach(b=>b.onclick = ()=>addToCart(b.dataset.id));
}

$('#buscador').oninput = renderCatalogo;
$('#filtroCategoria').onchange = renderCatalogo;

/****** Ticket (carrito) ******/
function addToCart(id){
  const p = productos.find(x=>x.id===id);
  if(!p || (p.stock|0) <= 0) return;
  cart[id] = (cart[id]||0)+1;
  // descuenta stock en vista (override temporal)
  writeOverride(id, {stock:(p.stock|0)-1});
  saveCart();
  renderCatalogo();
}

function decFromCart(id){
  if(!cart[id]) return;
  cart[id] -= 1;
  if(cart[id]<=0) delete cart[id];
  const p = productos.find(x=>x.id===id);
  if(p) writeOverride(id, {stock:(p.stock|0)+1});
  saveCart();
  renderTicket();
  renderCatalogo();
}

function removeFromCart(id){
  if(!cart[id]) return;
  const qty = cart[id];
  delete cart[id];
  const p = productos.find(x=>x.id===id);
  if(p) writeOverride(id, {stock:(p.stock|0)+qty});
  saveCart();
  renderTicket();
  renderCatalogo();
}

function saveCart(){ localStorage.setItem('cart_biscocho', JSON.stringify(cart)); }

function cartItems(){
  const items = Object.entries(cart).map(([id,qty])=>{
    const p = productos.find(x=>x.id===id);
    const price = Number(p?.precio||0);
    return { id, nombre:p?.nombre||id, precio:price, qty, subtotal: price*qty };
  });
  const total = items.reduce((a,b)=>a+b.subtotal,0);
  return {items, total};
}

function renderTicket(){
  const {items,total} = cartItems();
  const root = $('#ticketList');
  root.innerHTML = items.length ? '' : '<p>No hay productos en el ticket.</p>';
  for(const it of items){
    const row = document.createElement('div');
    row.className = 'ticket-item';
    row.innerHTML = `
      <div>${it.nombre}</div>
      <div class="price">${money(it.precio)}</div>
      <div class="qtyctl">
        <button class="btn ghost" data-k="-" data-id="${it.id}">−</button>
        <span>${it.qty}</span>
        <button class="btn ghost" data-k="+" data-id="${it.id}">+</button>
      </div>
      <div class="price">${money(it.subtotal)}</div>
    `;
    root.appendChild(row);
  }
  // listeners
  root.querySelectorAll('button').forEach(b=>{
    const id=b.dataset.id,k=b.dataset.k;
    b.onclick = ()=>{
      if(k==='+') addToCart(id);
      if(k==='-') decFromCart(id);
    };
  });

  $('#ticketTotal').textContent = money(total);
}
$('#btnClearTicket').onclick = ()=>{
  // reponer stock
  for(const [id,qty] of Object.entries(cart)){
    const p = productos.find(x=>x.id===id);
    if(p) writeOverride(id,{stock:(p.stock|0)+qty});
  }
  cart = {};
  saveCart(); renderTicket(); renderCatalogo();
};

/****** PDF ******/
$('#btnPDF').onclick = ()=>{
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const now = new Date();
  const fecha = now.toLocaleString('es-MX');

  doc.setFontSize(16);
  doc.text('El Biscocho Sabroso', 14, 16);
  doc.setFontSize(11);
  doc.text('Ticket de compra', 14, 24);
  doc.text(`Fecha: ${fecha}`, 14, 30);

  let y = 40;
  doc.setFont(undefined,'bold');
  doc.text('Producto', 14, y);
  doc.text('Cant', 110, y);
  doc.text('Precio', 140, y);
  doc.text('Subtotal', 170, y);
  doc.setFont(undefined,'normal');

  const {items,total} = cartItems();
  y += 6;
  for(const it of items){
    doc.text(it.nombre, 14, y);
    doc.text(String(it.qty), 110, y, {align:'right'});
    doc.text(money(it.precio), 140, y, {align:'right'});
    doc.text(money(it.subtotal), 190, y, {align:'right'});
    y += 6;
    if(y>270){ doc.addPage(); y=20; }
  }

  y += 8;
  doc.setFont(undefined,'bold');
  doc.text(`TOTAL: ${money(total)}`, 190, y, {align:'right'});
  doc.save('ticket_biscocho.pdf');
};

/****** Inventario editable ******/
function writeOverride(id, patch){
  overrides = overrides || {};
  if(!overrides[id]) overrides[id] = {};
  Object.assign(overrides[id], patch);
  localStorage.setItem(OVK, JSON.stringify(overrides));
  mergeProductos();
}

function addNewProduct({nombre,categoria,precio,stock,imagen}){
  overrides = overrides || {};
  if(!Array.isArray(overrides.__new)) overrides.__new = [];
  // genera ID único simple
  const nid = 'pan' + String(Math.floor(Math.random()*1e6)).padStart(6,'0');
  const p = {id:nid, nombre, categoria, precio:Number(precio), stock:Number(stock), unidad:'pz', imagen: imagen||''};
  overrides.__new.push(p);
  localStorage.setItem(OVK, JSON.stringify(overrides));
  mergeProductos();
}

function renderInventario(){
  const body = $('#invBody');
  body.innerHTML = '';
  for(const p of productos){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${p.id}</td>
      <td><input class="input" data-f="nombre" data-id="${p.id}" value="${p.nombre??''}"></td>
      <td><input class="input" data-f="categoria" data-id="${p.id}" value="${p.categoria??''}"></td>
      <td><input class="input" data-f="precio" data-id="${p.id}" type="number" min="0" step="0.01" value="${Number(p.precio)||0}"></td>
      <td>
        <div style="display:flex; gap:6px; align-items:center">
          <button class="btn ghost" data-k="-" data-id="${p.id}">−</button>
          <input class="input" data-f="stock" data-id="${p.id}" type="number" min="0" step="1" value="${Number(p.stock)||0}" style="max-width:90px">
          <button class="btn ghost" data-k="+" data-id="${p.id}">+</button>
        </div>
      </td>
      <td>
        <button class="btn" data-act="save" data-id="${p.id}">Guardar</button>
        <button class="btn ghost" data-act="del" data-id="${p.id}">Borrar</button>
      </td>
    `;
    body.appendChild(tr);
  }

  // +/- stock
  body.querySelectorAll('button[data-k]').forEach(b=>{
    b.onclick = ()=>{
      const id=b.dataset.id, k=b.dataset.k;
      const p = productos.find(x=>x.id===id);
      const s = Number(p?.stock||0) + (k==='+'?1:-1);
      writeOverride(id,{stock: Math.max(0,s)});
      renderInventario(); renderCatalogo();
    };
  });

  // Guardar fila
  body.querySelectorAll('button[data-act="save"]').forEach(b=>{
    b.onclick = ()=>{
      const id=b.dataset.id;
      const vals = {};
      body.querySelectorAll(`[data-id="${id}"][data-f]`).forEach(inp=>{
        const f=inp.dataset.f, v=inp.value;
        vals[f] = (f==='precio'||f==='stock') ? Number(v) : v;
      });
      writeOverride(id, vals);
      renderInventario(); renderCatalogo();
      alert('Producto actualizado.');
    };
  });

  // Borrar (solo de overrides / nuevos)
  body.querySelectorAll('button[data-act="del"]').forEach(b=>{
    b.onclick = ()=>{
      const id=b.dataset.id;
      // Si es nuevo, elimínalo de __new; si es base, “anula” stock a 0 y marca oculto opcional.
      if(overrides?.__new){
        const i = overrides.__new.findIndex(x=>x.id===id);
        if(i>=0){ overrides.__new.splice(i,1); localStorage.setItem(OVK, JSON.stringify(overrides)); mergeProductos(); renderInventario(); renderCatalogo(); return; }
      }
      // Para base: poner stock 0
      writeOverride(id, {stock:0});
      renderInventario(); renderCatalogo();
    };
  });
}

// Reset local (volver a productos.json original)
$('#btnResetLocal').onclick = ()=>{
  if(confirm('¿Deseas descartar todos los cambios locales de inventario?')){
    localStorage.removeItem(OVK);
    overrides = {};
    mergeProductos(); renderInventario(); renderCatalogo();
  }
};

// Export JSON (descarga un productos.json con cambios locales)
$('#btnExportJSON').onclick = ()=>{
  // Combina y baja
  const data = { productos };
  const blob = new Blob([JSON.stringify(data,null,2)], {type:'application/json'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'productos.json';
  a.click();
  URL.revokeObjectURL(a.href);
  alert('Se descargó productos.json con tus cambios.');
};

// Añadir nuevo producto
$('#formAgregar').onsubmit = (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const obj = Object.fromEntries(fd.entries());
  addNewProduct(obj);
  e.target.reset();
  renderInventario(); renderCatalogo();
  alert('Producto agregado.');
};

/****** Init ******/
window.addEventListener('DOMContentLoaded', loadProductos);
