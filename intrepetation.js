import { GoogleGenerativeAI } from "https://esm.sh/@google/generative-ai";

/**
 * CONFIGURATION & INITIALIZATION
 */
const GEMINI_API_KEY = "";
const genAI = new GoogleGenerativeAI(GEMINI_API_KEY);

const SYSTEM_CONTEXT = `Harraxbot adalah asisten AI ahli evaluasi TEAP (Test of English Academic Proficiency) Universitas Trunojoyo Madura yang dikembangin Sama Martin Harahap.
Gaya bahasa: Santai khas Jakarta (elo-gue) tapi tetep profesional, singkat, padat, dan langsung ke inti.

ATURAN OUTPUT (WAJIB):
1. JANGAN gunakan markdown formatting sama sekali (TIDAK ADA simbol *, #, -, atau lainnya).
2. JANGAN gunakan emoji atau simbol aneh.
3. JANGAN ada basa-basi berlebihan.
4. Gunakan format teks biasa yang bersih.
5. JANGAN gunakan bold, italic, atau list bullet. Pisahkan poin menggunakan baris baru atau angka saja (1, 2, 3).
6. Respons harus ringkas, tidak repetitif, dan tidak dalam paragraf yang terlalu panjang.`;

/**
 * DEBUGGING & LOGGING UTILITY
 */
const Logger = {
    info: (title, data) => {
        console.log(`%c[INFO] ${title}`, 'color: #00d1b2; font-weight: bold;', data);
    },
    warn: (title, data) => {
        console.warn(`%c[WARN] ${title}`, 'color: #ffdd57; font-weight: bold;', data);
    },
    error: (title, error, context = {}) => {
        console.error(`%c[ERROR] ${title}`, 'color: #ff3860; font-weight: bold;', {
            message: error.message || error,
            stack: error.stack,
            context: context
        });
    }
};

/**
 * DATA VALIDATION
 */
const Validator = {
    validateSession: (data) => {
        const required = ['no_tes', 'score', 'listening', 'structure', 'reading'];
        const missing = required.filter(field => data[field] === undefined || data[field] === null || data[field] === '');
        if (missing.length > 0) return { valid: false, missing };
        return { valid: true };
    },
    getFallback: (val, fallback = "N/A") => (val !== undefined && val !== null && val !== '') ? val : fallback
};

/**
 * AI CORE LOGIC
 */
async function getAIResponse(prompt) {
    Logger.info('Sending Prompt to Gemini', { prompt });
    try {
        const model = genAI.getGenerativeModel({ model: "gemini-2.5-flash" });
        const result = await model.generateContent(`${SYSTEM_CONTEXT}\n\n${prompt}`);

        if (!result || !result.response) throw new Error('Empty response');
        const text = await result.response.text();

        // Clean text from common markdown artifacts just in case
        const cleanText = text.replace(/[*#\\-]/g, '').trim();

        Logger.info('Gemini Response Received', { length: cleanText.length });
        return cleanText;
    } catch (error) {
        Logger.error('Gemini API Call Failed', error);
        throw error;
    }
}

function setBtnLoading(loading) {
    const btns = document.querySelectorAll('.ai-interpret-btn');
    btns.forEach(btn => {
        if (loading) {
            btn.setAttribute('disabled', 'true');
            btn.dataset.oldText = btn.innerText;
            btn.innerText = "Processing...";
            btn.style.opacity = "0.6";
        } else {
            btn.removeAttribute('disabled');
            if (btn.dataset.oldText) btn.innerText = btn.dataset.oldText;
            btn.style.opacity = "1";
        }
    });
}

/**
 * PUBLIC API
 */

window.interpretWithAI = async function (no_tes, score, listening, structure, reading, nama_peserta, nama_ruang) {
    const sessionData = {
        no_tes, score, listening, structure, reading,
        nama_peserta: Validator.getFallback(nama_peserta, "Mahasiswa"),
        nama_ruang: Validator.getFallback(nama_ruang, "Ruang Tes")
    };

    if (!Validator.validateSession(sessionData).valid) {
        alert("Data sesi tidak lengkap bray.");
        return;
    }

    showAIModal(`Analisis Sesi: ${sessionData.no_tes}`);
    setBtnLoading(true);

    try {
        const prompt = `Analisis sesi tes ini secara singkat:
        Peserta: ${sessionData.nama_peserta}
        No Tes: ${sessionData.no_tes}
        Total Skor: ${sessionData.score}
        Listening: ${sessionData.listening}
        Structure: ${sessionData.structure}
        Reading: ${sessionData.reading}
        
        Fokus: Analisis tiap subtest, kekuatan, kelemahan, dan saran konkrit. Singkat saja, jangan pakai simbol atau markdown.`;

        const aiText = await getAIResponse(prompt);
        document.getElementById('ai-response-content').innerText = aiText; // Instant display, plain text
    } catch (error) {
        document.getElementById('ai-response-content').innerText = "Gagal ambil interpretasi bray. Coba lagi ya.";
    } finally {
        setBtnLoading(false);
    }
};

window.interpretAllAI = async function (historyData) {
    if (!historyData || historyData.length === 0) {
        alert("Belum ada riwayat tes buat dianalisis bray.");
        return;
    }

    showAIModal("Analisis Seluruh Pencapaian");
    setBtnLoading(true);

    try {
        const historyDetails = historyData.map(d =>
            `Tanggal: ${d.tgl_ujian}, Skor: ${d.score} (L: ${d.listening}, S: ${d.structure}, R: ${d.reading})`
        ).join('\n');

        const prompt = `Gue punya riwayat tes TEAP lengkap dengan detail subtestnya nih bray:
        ${historyDetails}
        
        Tolong rangkum tren perkembangan gue antar sesi yang dimana L itu listening S itu structure dan R itu reading. Fokus ke detail subtest buat tau mana yang beneran naik atau turun. 
        Kasih evaluasi akhir yang singkat dan saran strategi kedepannya. Tanpa markdown, tanpa simbol, tanpa basa-basi.`;

        const aiText = await getAIResponse(prompt);
        document.getElementById('ai-response-content').innerText = aiText; // Instant display, plain text
    } catch (error) {
        document.getElementById('ai-response-content').innerText = "Gagal ngerangkum progres elo bray.";
    } finally {
        setBtnLoading(false);
    }
};

/**
 * MODAL MANAGEMENT
 */
function showAIModal(title) {
    let modal = document.getElementById('aiInterpretationModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'aiInterpretationModal';
        modal.className = 'ai-modal-overlay';
        modal.innerHTML = `
            <div class="ai-modal-card">
                <div class="ai-modal-header">
                    <h3 id="ai-modal-title">${title}</h3>
                    <button onclick="closeAIModal()">&times;</button>
                </div>
                <div class="ai-modal-body">
                    <div id="ai-response-content" class="ai-response-text" style="white-space: pre-wrap;">
                        Processing analysis...
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    } else {
        document.getElementById('ai-modal-title').innerText = title;
        document.getElementById('ai-response-content').innerText = "Processing analysis...";
        modal.style.display = 'flex';
    }
}

window.closeAIModal = function () {
    const modal = document.getElementById('aiInterpretationModal');
    if (modal) modal.style.display = 'none';
};
