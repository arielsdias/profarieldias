/* ============================================================
   CARREGAMENTO DO JSON EXTERNO
============================================================ */
let courseData = null;

async function loadCourseData() {
    const response = await fetch("/data/courseData.json");
    courseData = await response.json();

    renderSidebar();
    updateProgress();

    const state = loadState();
    if (state?.lastTopicId) {
        const topic = findTopicById(state.lastTopicId);
        if (topic) loadTopic(topic);
    }
}

function findTopicById(id) {
    for (const module of courseData.modules)
        for (const topic of module.topics)
            if (topic.id === id) return topic;
    return null;
}

/* ============================================================
   LOCAL STORAGE
============================================================ */
const STORAGE_KEY = "curso_python_estado_v1";

let selectedTopic = null;
let completed = new Set();
let practiceCode = {};

function loadState() {
    try {
        const data = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (!data) return null;

        if (Array.isArray(data.completed)) completed = new Set(data.completed);
        if (data.practiceCode) practiceCode = data.practiceCode;
        return data;

    } catch {
        return null;
    }
}

function saveState() {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
            completed: [...completed],
            lastTopicId: selectedTopic?.id || null,
            practiceCode
        })
    );
}

/* ============================================================
   SIDEBAR
============================================================ */
let openModules = new Set();

function renderSidebar() {
    const modulesList = document.getElementById("modulesList");
    modulesList.innerHTML = "";

courseData.modules.forEach(mod => {

    const moduleWrapper = document.createElement("div");

    // Cabeçalho da sanfona
    const header = document.createElement("div");
    header.className = "p-4 bg-slate-100 font-semibold border-b cursor-pointer flex justify-between items-center";
    header.innerHTML = `
        <span>${mod.title}</span>
        <i data-lucide="chevron-down"></i>
    `;

    // Conteúdo da sanfona (lista de tópicos)
    const topicsContainer = document.createElement("div");
    topicsContainer.className = openModules.has(mod.title)
        ? "block"
        : "hidden";

    // Evento do clique ABRIR/FECHAR módulo
    header.onclick = () => {
        if (openModules.has(mod.title)) {
            openModules.delete(mod.title);
            topicsContainer.classList.add("hidden");
        } else {
            openModules.add(mod.title);
            topicsContainer.classList.remove("hidden");
        }
    };

    // Criar tópicos dentro da sanfona
    mod.topics.forEach(topic => {
        const isDone = completed.has(topic.id);

        const topicDiv = document.createElement("div");
        topicDiv.className =
            "p-3 pl-6 flex gap-3 items-center cursor-pointer transition hover:bg-slate-50";

        topicDiv.innerHTML = `
            <i data-lucide="${isDone ? "check-circle" : ""}"
                class="w-5 h-5 ${isDone ? "text-green-600" : ""}"></i>

            <i data-lucide="${topic.type === "theory"
                     ? "book-open"
                     : "code"}"
               class="w-4 h-4"></i>

            <span>${topic.title}</span>
        `;

        // Clique no tópico NÃO mexe na sanfona
        topicDiv.onclick = () => loadTopic(topic);

        topicsContainer.appendChild(topicDiv);
    });

    moduleWrapper.appendChild(header);
    moduleWrapper.appendChild(topicsContainer);

    modulesList.appendChild(moduleWrapper);
});


    lucide.createIcons();
}

/* ============================================================
   TEMA TEÓRICO
============================================================ */
function loadTheory(topic) {
    document.getElementById("contentArea").innerHTML = `
        <div class="border-b border-slate-300 mb-4 flex gap-2">

            <button id="tabVideo"
                class="px-4 py-2 text-sm font-semibold text-blue-600 border-b-2 border-blue-600">
                Vídeo da Aula
            </button>

            <button id="tabGamma"
                class="px-4 py-2 text-sm font-semibold text-slate-500 hover:text-blue-600">
                Material Interativo
            </button>
        </div>

        <div id="contentVideo">
            <iframe src="${topic.video}"
                class="w-full h-[600px] rounded-xl shadow border"
                frameborder="0"></iframe>
        </div>

        <div id="contentGamma" class="hidden">
            <iframe src="${topic.gamma}"
                class="w-full h-[600px] rounded-xl shadow border"
                frameborder="0"></iframe>
        </div>
    `;

    const tabVideo = document.getElementById("tabVideo");
    const tabGamma = document.getElementById("tabGamma");

    const contentVideo = document.getElementById("contentVideo");
    const contentGamma = document.getElementById("contentGamma");

    tabVideo.onclick = () => {
        tabVideo.classList.add("text-blue-600", "border-blue-600");
        contentVideo.classList.remove("hidden");
        tabGamma.classList.remove("text-blue-600");
        contentGamma.classList.add("hidden");
    };

    tabGamma.onclick = () => {
        tabGamma.classList.add("text-blue-600", "border-blue-600");
        contentGamma.classList.remove("hidden");
        tabVideo.classList.remove("text-blue-600");
        contentVideo.classList.add("hidden");
    };
}

/* ============================================================
   EXERCÍCIO
============================================================ */
function renderExercise(topic) {
    document.getElementById("contentArea").innerHTML = `
        <div class="flex gap-4 h-[380px]">

            <div id="exercise"
                 class="w-[45%] bg-white border border-slate-300 rounded-xl p-5 shadow-sm overflow-auto">

                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="list-checks" class="w-5 h-5 text-slate-700"></i>
                    <h2 class="text-lg font-bold text-slate-800">Exercício</h2>
                </div>

                <div id="exerciseContent"
                     class="text-slate-700 leading-relaxed text-[15px]"></div>

                <div class="mt-4 bg-slate-100 border border-slate-300 rounded-md p-3">
                    <p class="font-semibold text-sm text-slate-800 mb-1">Resultado esperado:</p>
                    <pre id="expectedOutput"
                         class="text-slate-900 whitespace-pre-wrap"></pre>
                </div>

            </div>

            <div id="editor"
                 class="w-[55%] bg-[#1e1e1e] border border-slate-300 rounded-xl"></div>
        </div>

        <button id="run"
            class="mt-5 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 text-sm font-semibold">
            Executar Código
        </button>

        <h3 class="mt-4 font-semibold text-slate-800">Saída:</h3>
        <div id="output"
             class="mt-2 p-3 bg-[#272822] text-white rounded-md min-h-[120px] whitespace-pre-wrap">
             Aguardando execução...
        </div>

        <!-- MODAL DE INPUT -->
        <div id="inputModal"
             class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl shadow-xl w-96">

                <h2 class="text-lg font-bold mb-3" id="inputModalLabel">
                    Digite o valor:
                </h2>

                <input id="inputModalValue"
                       type="text"
                       class="w-full p-2 border rounded mb-4">

                <div class="flex justify-end gap-2">
                    <button id="cancelInput"
                        class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>

                    <button id="confirmInput"
                        class="px-4 py-2 bg-blue-600 text-white rounded">Enviar</button>
                </div>
            </div>
        </div>
    `;

    renderExerciseBox(topic);
}

/* ============================================================
   FORMATADOR DO EXERCÍCIO
============================================================ */
function renderExerciseBox(topic) {
    const raw = topic.problemStatement;
    let html = "";

    raw.split("\n").forEach(line => {
        const trimmed = line.trim();

        if (trimmed.startsWith("- "))
            html += `<li class="ml-6 list-disc">${trimmed.slice(2)}</li>`;

        else if (trimmed.startsWith("print") || trimmed.includes("("))
            html += `<pre class="bg-slate-900 text-slate-100 p-2 rounded-md my-2">${line}</pre>`;

        else if (trimmed === "")
            html += `<div class="my-2"></div>`;

        else
            html += `<p>${line}</p>`;
    });

    document.getElementById("exerciseContent").innerHTML = html;
    document.getElementById("expectedOutput").innerText = topic.respostaEsperada;
}

/* ============================================================
   CARREGAR TÓPICO
============================================================ */
function loadTopic(topic) {
    selectedTopic = topic;

    document.getElementById("topicTitle").textContent = topic.title;
    document.getElementById("topicType").textContent =
        topic.type === "theory" ? "📖 Conteúdo Teórico" : "💻 Exercício Prático";

    document.getElementById("markCompleteBtn").classList.remove("hidden");

    if (topic.type === "theory") {
        loadTheory(topic);
    } else {
        renderExercise(topic);
        initMonacoPracticeEditor();
    }

    updateProgress();
    saveState();
}

/* ============================================================
   MONACO EDITOR
============================================================ */
let editorPractice = null;

function initMonacoPracticeEditor() {
    require.config({
        paths: { vs: "https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs" }
    });

    require(["vs/editor/editor.main"], () => {
        const id = selectedTopic.id;

        const initialValue =
            practiceCode[id] ||
            selectedTopic.codigoPreEscrito ||
            "# Escreva seu código Python aqui\n";

        editorPractice = monaco.editor.create(
            document.getElementById("editor"),
            {
                value: initialValue,
                language: "python",
                theme: "vs-dark",
                automaticLayout: true
            }
        );

        editorPractice.onDidChangeModelContent(() => {
            practiceCode[id] = editorPractice.getValue();
            saveState();
        });
    });
}

/* ============================================================
   EXECUÇÃO COM INPUT INTERATIVO — PISTON
============================================================ */
let inputPrompts = [];
let inputIndex = 0;
let collectedInputs = [];
let pendingCode = "";

/* Detecta quantos input() o código possui */
function extractInputs(code) {
    return [...code.matchAll(/input\s*\((.*?)\)/g)].map(m => m[1].replace(/['"]/g, ""));
}

async function runStudentCode() {
    const code = editorPractice.getValue();
    const out = document.getElementById("output");
    
    inputPrompts = extractInputs(code);
    inputIndex = 0;
    collectedInputs = [];
    pendingCode = code.replace(/input\s*\((.*?)\)/g, "input()");

    if (inputPrompts.length === 0) {
        executePiston(code, "");
        return;
    }

    showPrompt(inputPrompts[inputIndex] || "Digite um valor:");
    openInputModal();
}



function showPrompt(text) {
    document.getElementById("inputModalLabel").innerText =
        text.trim() === "" ? "Digite um valor:" : text;
}

/* =============== EXECUTAR NO PISTON =============== */
async function executePiston(code, stdinValue) {
    const out = document.getElementById("output");
    out.innerText = "Executando código via Piston...\n";

    try {
        const response = await fetch("https://emkc.org/api/v2/piston/execute", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                language: "python",
                version: "3.10.0",
                files: [{ content: code }],
                stdin: stdinValue
            })
        });

        const result = await response.json();

        console.log("Resultado: " + result.run.stdout)

        out.innerText = result.run.stdout || "(sem saída)";
        validatePractice(result.run.stdout);

    } catch (err) {
        out.innerText = "Erro ao executar no Piston:\n" + err;
    }
}

/* ============================================================
   MODAL — EVENTOS
============================================================ */
function openInputModal() {
    document.getElementById("inputModal").classList.remove("hidden");
}

function closeInputModal() {
    document.getElementById("inputModal").classList.add("hidden");
}

document.addEventListener("click", (e) => {

    // BOTÃO CANCELAR
    if (e.target.id === "cancelInput") {
        collectedInputs = [];
        pendingCode = "";
        closeInputModal();
        return;
    }

    // BOTÃO CONFIRMAR
    if (e.target.id === "confirmInput") {
        const value = document.getElementById("inputModalValue").value;
        collectedInputs.push(value);
        document.getElementById("inputModalValue").value = "";

        inputIndex++;

        if (inputIndex < inputPrompts.length) {
            showPrompt(inputPrompts[inputIndex]);
            return;
        }

        closeInputModal();
        executePiston(pendingCode, collectedInputs.join("\n"));
    }
});


/* ============================================================
   VALIDAÇÃO
============================================================ */
function similarity(a, b) {
    a = a.trim().toLowerCase();
    b = b.trim().toLowerCase();

    const len = Math.max(a.length, b.length);
    let match = 0;

    for (let i = 0; i < Math.min(a.length, b.length); i++)
        if (a[i] === b[i]) match++;

    return (match / len) * 100;
}

function validatePractice(output) {
    const expected = selectedTopic.respostaEsperada || "";
    const pct = similarity(output, expected);

    const box = document.getElementById("output");

    if (pct >= 80)
        box.innerText += `\n\n✔ Similaridade ${pct.toFixed(1)}% — Solução aceita!`;
    else
        box.innerText += `\n\n✘ Similaridade ${pct.toFixed(1)}%. Ajuste o código.`;
}

/* ============================================================
   PROGRESSO
============================================================ */
function updateProgress() {
    const total = courseData.modules.reduce(
        (acc, mod) => acc + mod.topics.length,
        0
    );
    const pct = Math.round((completed.size / total) * 100);

    document.getElementById("progressText").innerText = pct + "%";
    document.getElementById("progressBar").style.width = pct + "%";
    document.getElementById("progressCount").innerText =
        `${completed.size} de ${total} tópicos`;
}

/* ============================================================
   MARCAR COMO CONCLUÍDO
============================================================ */
document.getElementById("markCompleteBtn").onclick = () => {
    if (!selectedTopic) return;
    completed.add(selectedTopic.id);
    saveState();
    updateProgress();
    renderSidebar();
};

/* ============================================================
   INICIALIZAÇÃO
============================================================ */
window.addEventListener("load", () => {
    const gl = document.getElementById("globalLoader");
    gl.classList.add("opacity-0", "pointer-events-none");
    setTimeout(() => (gl.style.display = "none"), 200);
});

document.addEventListener("click", e => {
    if (e.target.id === "run") runStudentCode();
});


/* ============================================================
   TOGGLE SIDEBAR
============================================================ */
document.getElementById("toggleSidebar").addEventListener("click", () => {
    const sidebar = document.getElementById("sidebar");

    if (sidebar.classList.contains("sidebar-open")) {
        // Fecha
        sidebar.classList.remove("sidebar-open");
        sidebar.classList.add("sidebar-closed");
    } else {
        // Abre
        sidebar.classList.remove("sidebar-closed");
        sidebar.classList.add("sidebar-open");
    }
});


loadCourseData();
lucide.createIcons();
