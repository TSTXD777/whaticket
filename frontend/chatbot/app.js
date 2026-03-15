/* app.js - Lógica del frontend
   - Conecta con backend.php mediante fetch/FormData
   - Renderiza lista de artículos, formulario de edición/creación y chatbot simulado
   - No usa IA ni bases de datos: todo se almacena en data.json a través del backend PHP
*/

// URL del endpoint PHP
const apiUrl = '../../backend/chatbot.php';

// función auxiliar para hacer llamadas al backend
async function api(action, data){
  const form = new FormData();
  form.append('action', action);
  if(data){
    for(const k in data) form.append(k, data[k]);
  }
  const res = await fetch(apiUrl, { method: 'POST', body: form });
  return res.json();
}

// Elementos del DOM
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const showAllBtn = document.getElementById('showAllBtn');
const articlesList = document.getElementById('articlesList');
const newArticleBtn = document.getElementById('newArticleBtn');
const formArea = document.getElementById('formArea');
const articleForm = document.getElementById('articleForm');
const cancelBtn = document.getElementById('cancelBtn');
const formTitle = document.getElementById('formTitle');

// Chatbot
const faqList = document.getElementById('faqList');
const chatLog = document.getElementById('chatLog');
const chatInput = document.getElementById('chatInput');
const chatSend = document.getElementById('chatSend');

// Historial de conversación en esta sesión (se envía al backend para contexto)
const chatHistory = [];

// FAQs predeterminadas (simulan respuestas rápidas del bot)
let faqs = [
  {q: '¿Cómo reinicio el servicio X?', a: 'Para reiniciar el servicio X ejecute: systemctl restart x.service'},
  {q: '¿Dónde están los logs?', a: 'Los logs se encuentran en /var/log/miapp/ y se rotan semanalmente.'},
  {q: '¿Cómo restablezco contraseña?', a: 'Siga el procedimiento en el panel de administración -> Usuarios -> Reset.'}
];

function renderFaqs(){
  faqList.innerHTML = '';
  faqs.forEach((f,i)=>{
    const btn = document.createElement('button');
    btn.textContent = f.q;
    btn.onclick = ()=>{
      appendChat('user', f.q);
      setTimeout(()=>appendChat('bot', f.a), 300);
    };
    btn.className = 'faq-btn';
    faqList.appendChild(btn);
  });
}

function appendChat(who, text){
  const div = document.createElement('div');
  div.className = 'chat-message ' + who;
  div.style.whiteSpace = 'pre-wrap';
  div.textContent = text;
  chatLog.appendChild(div);
  chatLog.scrollTop = chatLog.scrollHeight;

  // Guardar en historial de sesión (solo user/bot)
  if(who === 'user' || who === 'bot'){
    chatHistory.push({ role: who, text });
    // Limitar tamaño del historial para no enviar demasiado
    if(chatHistory.length > 20) chatHistory.splice(0, chatHistory.length - 20);
  }
}

// Envío desde la caja de chat: se consulta al backend mediante 'ai-search'
chatSend.addEventListener('click', async ()=>{
  const q = chatInput.value.trim();
  if(!q) return;
  appendChat('user', q);
  chatInput.value = '';
  // Llamada al endpoint híbrido que usa Ollama (incluye historial de la sesión)
  const resp = await api('ai-search', { q, history: JSON.stringify(chatHistory) });
  if(resp && resp.ok && resp.response){
    appendChat('bot', resp.response);
  } else if(resp && resp.context && resp.context.length){
    // fallback: mostramos títulos de contexto
    let msg = 'Lo siento, no pude generar respuesta. Contexto disponible:';
    resp.context.forEach((a,i)=>{
      msg += '\n' + (i+1) + '. ' + a.title;
    });
    appendChat('bot', msg);
  } else {
    appendChat('bot', 'Lo siento, no encontré una respuesta en la base de conocimiento.');
  }
});

// Cargar todos los artículos
async function loadAll(){
  const res = await api('list');
  renderArticles(res.data);
}

// Renderiza la lista de artículos en el panel izquierdo
function renderArticles(items){
  articlesList.innerHTML = '';
  if(!items || !items.length){
    articlesList.innerHTML = '<div class="col-12"><div class="alert alert-info">No hay artículos disponibles.</div></div>';
    return;
  }
  items.forEach(it=>{
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4';
    col.innerHTML = `
      <div class="card article-card h-100">
        <div class="card-body">
          <h6 class="card-title">${escapeHtml(it.title)}</h6>
          <p class="card-text small text-muted">
            <i class="bi bi-tag me-1"></i>${escapeHtml(it.category)}
            ${it.keywords ? `<br><i class="bi bi-hash me-1"></i>${escapeHtml(it.keywords)}` : ''}
          </p>
          <p class="card-text">${escapeHtml(it.content.substring(0, 100))}...</p>
        </div>
        <div class="card-footer bg-transparent">
          <div class="btn-group btn-group-sm w-100">
            <button data-id="${it.id}" class="btn btn-outline-primary viewBtn">Ver</button>
            <button data-id="${it.id}" class="btn btn-outline-warning editBtn">Editar</button>
            <button data-id="${it.id}" class="btn btn-outline-danger deleteBtn">Eliminar</button>
          </div>
        </div>
      </div>
    `;
    articlesList.appendChild(col);
  });
  // handlers dinámicos para botones
  document.querySelectorAll('.viewBtn').forEach(b=>b.onclick=async e=>{
    const id = e.target.dataset.id;
    const resp = await api('get',{id});
    if(resp && resp.item) {
      // Show content in a modal
      const modal = new bootstrap.Modal(document.getElementById('articleModal'));
      document.getElementById('formTitle').textContent = resp.item.title;
      document.getElementById('title').value = resp.item.title;
      document.getElementById('category').value = resp.item.category;
      document.getElementById('keywords').value = resp.item.keywords || '';
      document.getElementById('content').value = resp.item.content;
      document.querySelector('.modal-footer').style.display = 'none'; // Hide form buttons
      modal.show();
    }
  });
  document.querySelectorAll('.editBtn').forEach(b=>b.onclick=async e=>{
    const id = e.target.dataset.id;
    const resp = await api('get',{id});
    if(resp && resp.item){
      showForm(resp.item);
    }
  });
  document.querySelectorAll('.deleteBtn').forEach(b=>b.onclick=async e=>{
    if(!confirm('¿Eliminar artículo?')) return;
    const id = e.target.dataset.id;
    await api('delete',{id});
    loadAll();
  });
}

// Muestra el formulario para nuevo artículo o edición
function showForm(item){
  const modal = new bootstrap.Modal(document.getElementById('articleModal'));
  document.querySelector('.modal-footer').style.display = 'flex'; // Show form buttons

  document.getElementById('articleId').value = item ? item.id : '';
  document.getElementById('title').value = item ? item.title : '';
  document.getElementById('category').value = item ? item.category : '';
  document.getElementById('keywords').value = item ? (item.keywords||'') : '';
  document.getElementById('content').value = item ? item.content : '';
  document.getElementById('formTitle').textContent = item ? 'Editar Artículo' : 'Nuevo Artículo';

  modal.show();
}

newArticleBtn.onclick = ()=> showForm(null);

// Enviar formulario: crea o actualiza artículo mediante el backend
articleForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('articleId').value;
  const payload = {
    id: id || '',
    title: document.getElementById('title').value,
    category: document.getElementById('category').value,
    keywords: document.getElementById('keywords').value,
    content: document.getElementById('content').value
  };

  try {
    const resp = id ? await api('update', payload) : await api('add', payload);
    if (resp && resp.ok) {
      // Close modal and reset form
      const modal = bootstrap.Modal.getInstance(document.getElementById('articleModal'));
      modal.hide();
      articleForm.reset();
      loadAll();
    } else {
      alert('Error al guardar el artículo: ' + (resp.msg || 'Respuesta inválida'));
    }
  } catch (error) {
    alert('Error de conexión: ' + error.message);
  }
});

// Búsqueda por texto
searchBtn.onclick = async ()=>{
  const q = searchInput.value.trim();
  const res = await api('search',{ q });
  renderArticles(res.hits || []);
};

showAllBtn.onclick = loadAll;

// Util
function escapeHtml(s){ if(!s) return ''; return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Inicializacion
renderFaqs();
loadAll();
