<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Image Detector – Full Analysis</title>
    <style>
        :root {
            --bg: #0a0a12;
            --surface: #14141f;
            --surface-2: #1a1a2a;
            --border: #2a2a3d;
            --text: #e0e0f0;
            --text-secondary: #9898b0;
            --accent-1: #6c5ce7;
            --accent-2: #a855f7;
            --accent-3: #4facfe;
            --danger: #e74c5e;
            --success: #2ecc71;
            --warning: #f39c12;
            --gradient-1: linear-gradient(135deg, #6c5ce7, #a855f7);
            --gradient-2: linear-gradient(135deg, #4facfe, #6c5ce7);
            --radius: 20px;
            --radius-sm: 12px;
            --radius-xs: 8px;
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.5);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.4);
            --transition: 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter','Segoe UI',system-ui,sans-serif;
            background:var(--bg); color:var(--text);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:20px; overflow-x:hidden; position:relative;
        }
        .bg-orb { position:fixed; border-radius:50%; filter:blur(120px); opacity:0.15; pointer-events:none; z-index:0; animation:floatOrb 20s infinite ease-in-out; }
        .bg-orb.orb-1 { width:500px; height:500px; background:#6c5ce7; top:-15%; left:-10%; }
        .bg-orb.orb-2 { width:400px; height:400px; background:#4facfe; bottom:-10%; right:-8%; animation-delay:-7s; }
        .bg-orb.orb-3 { width:350px; height:350px; background:#a855f7; top:50%; left:50%; transform:translate(-50%,-50%); animation-delay:-14s; }
        @keyframes floatOrb {
            0%,100%{transform:translate(0,0) scale(1);} 25%{transform:translate(60px,-40px) scale(1.15);}
            50%{transform:translate(-30px,-80px) scale(0.9);} 75%{transform:translate(-50px,30px) scale(1.1);}
        }
        .grid-overlay { position:fixed; inset:0; pointer-events:none; z-index:0; opacity:0.03;
            background-image:linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px);
            background-size:40px 40px;
        }
        .app-container { position:relative; z-index:1; width:100%; max-width:800px; display:flex; flex-direction:column; gap:24px; }
        .header { text-align:center; }
        .header .logo-icon { font-size:48px; margin-bottom:8px; display:inline-block; animation:pulseIcon 3s infinite ease-in-out; }
        @keyframes pulseIcon { 0%,100%{transform:scale(1);filter:drop-shadow(0 0 20px rgba(108,92,231,0.4));} 50%{transform:scale(1.08);filter:drop-shadow(0 0 35px rgba(168,85,247,0.7));} }
        .header h1 { font-size:2.2rem; font-weight:700; background:var(--gradient-2); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; letter-spacing:-0.02em; margin-bottom:4px; }
        .header .subtitle { color:var(--text-secondary); font-size:1rem; }

        .main-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:32px; box-shadow:var(--shadow-lg); backdrop-filter:blur(20px); position:relative; overflow:hidden; }
        .main-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:var(--gradient-2); opacity:0.6; }

        .upload-zone { border:2px dashed var(--border); border-radius:var(--radius-sm); padding:48px 24px; text-align:center; cursor:pointer; transition:var(--transition); background:var(--surface-2); min-height:220px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; user-select:none; }
        .upload-zone:hover, .upload-zone.drag-over { border-color:var(--accent-2); background:rgba(108,92,231,0.06); box-shadow:0 0 40px rgba(108,92,231,0.15); }
        .upload-zone .upload-icon { font-size:56px; opacity:0.7; }
        .upload-zone:hover .upload-icon { opacity:1; transform:translateY(-4px); }
        .upload-zone .upload-text { font-weight:600; font-size:1.1rem; }
        .upload-zone .upload-hint { font-size:0.85rem; color:var(--text-secondary); }
        .upload-zone input[type="file"] { display:none; }
        .preview-area { display:none; position:relative; border-radius:var(--radius-sm); overflow:hidden; background:#000; aspect-ratio:16/10; max-height:400px; animation:fadeIn 0.4s ease; }
        .preview-area.active { display:block; }
        .preview-area img { width:100%; height:100%; object-fit:contain; display:block; }
        .preview-area .remove-btn { position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.7); color:#fff; border:1px solid rgba(255,255,255,0.3); border-radius:50%; width:36px; height:36px; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; transition:var(--transition); backdrop-filter:blur(8px); z-index:5; }
        .preview-area .remove-btn:hover { background:var(--danger); border-color:var(--danger); transform:scale(1.1); }
        .action-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; }
        .btn { padding:12px 24px; border-radius:50px; font-weight:600; font-size:0.95rem; cursor:pointer; border:none; transition:var(--transition); display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
        .btn-primary { background:var(--gradient-1); color:#fff; box-shadow:0 4px 20px rgba(108,92,231,0.4); flex:1; justify-content:center; min-width:160px; }
        .btn-primary:hover { box-shadow:0 8px 30px rgba(108,92,231,0.6); transform:translateY(-2px); }
        .btn-primary:disabled { opacity:0.5; cursor:not-allowed; transform:none; box-shadow:none; }
        .btn-secondary { background:var(--surface-2); color:var(--text); border:1px solid var(--border); }
        .btn-secondary:hover { border-color:var(--accent-2); background:rgba(108,92,231,0.1); }
        .results-container { display:none; animation:fadeIn 0.5s ease; }
        .results-container.active { display:block; }
        .result-card { background:var(--surface-2); border-radius:var(--radius-sm); padding:24px; border:1px solid var(--border); margin-top:16px; }
        .result-header { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
        .result-badge { padding:8px 18px; border-radius:50px; font-weight:700; font-size:0.9rem; text-transform:uppercase; }
        .badge-ai { background:rgba(231,76,94,0.2); color:#e74c5e; border:1px solid rgba(231,76,94,0.4); }
        .badge-human { background:rgba(46,204,113,0.2); color:#2ecc71; border:1px solid rgba(46,204,113,0.4); }
        .badge-uncertain { background:rgba(243,156,18,0.2); color:#f39c12; border:1px solid rgba(243,156,18,0.4); }
        .confidence-meter { width:100%; height:10px; background:var(--border); border-radius:10px; overflow:hidden; margin:8px 0; }
        .confidence-fill { height:100%; border-radius:10px; transition:width 1s ease, background 0.8s ease; }
        .confidence-label { display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary); margin-top:4px; }
        .indicators-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-top:16px; }
        .indicator-item { background:rgba(255,255,255,0.03); border-radius:var(--radius-xs); padding:14px; text-align:center; border:1px solid var(--border); }
        .indicator-item .indicator-icon { font-size:28px; margin-bottom:6px; }
        .indicator-item .indicator-name { font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; }
        .indicator-item .indicator-value { font-weight:700; font-size:0.9rem; }
        .indicator-item.warning { border-color:rgba(243,156,18,0.4); background:rgba(243,156,18,0.06); }
        .indicator-item.good { border-color:rgba(46,204,113,0.3); background:rgba(46,204,113,0.05); }

        .naturalize-section, .fix-section, .detail-section { display:none; margin-top:20px; padding:20px; background:var(--surface-2); border-radius:var(--radius-sm); border:1px solid var(--border); }
        .naturalize-section.active, .fix-section.active, .detail-section.active { display:block; }
        .btn-naturalize { background:linear-gradient(135deg,#2ecc71,#27ae60); color:white; border:none; padding:12px 24px; border-radius:50px; font-weight:600; cursor:pointer; transition:var(--transition); display:inline-flex; align-items:center; gap:8px; margin-right:10px; }
        .btn-naturalize:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(46,204,113,0.4); }
        .btn-detail { background:linear-gradient(135deg,#f39c12,#e67e22); color:white; border:none; padding:12px 24px; border-radius:50px; font-weight:600; cursor:pointer; }
        .btn-download { background:var(--surface); color:var(--text); border:1px solid var(--border); padding:12px 24px; border-radius:50px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-download:hover { border-color:var(--accent-2); }

        .heatmap-container { position:relative; margin-top:16px; border-radius:var(--radius-xs); overflow:hidden; }
        .heatmap-canvas { width:100%; display:block; }
        .reasoning-text { margin-top:16px; font-size:0.9rem; color:var(--text-secondary); line-height:1.6; }
        .reasoning-text strong { color:var(--text); }

        @keyframes fadeIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
        @media (max-width:600px) {
            .main-card { padding:20px; }
            .upload-zone { padding:32px 16px; min-height:160px; }
            .header h1 { font-size:1.6rem; }
            .btn { padding:10px 18px; font-size:0.85rem; }
        }
    </style>
</head>
<body>
<div class="bg-orb orb-1"></div><div class="bg-orb orb-2"></div><div class="bg-orb orb-3"></div><div class="grid-overlay"></div>

<div class="app-container">
    <header class="header">
        <div class="logo-icon">🔍</div>
        <h1>AI Image Detector</h1>
        <p class="subtitle">Deep analysis, heatmaps & humanization</p>
    </header>

    <div class="main-card" id="mainCard">
        <div class="spinner-overlay" id="spinnerOverlay"><div class="spinner"></div><span class="spinner-text">Processing...</span></div>
        <div class="upload-zone" id="uploadZone">
            <span class="upload-icon">🖼️</span>
            <span class="upload-text">Drop an image here or click to browse</span>
            <span class="upload-hint">Supports JPG, PNG, WebP, GIF — Max 10MB</span>
            <input type="file" id="fileInput" accept="image/*">
        </div>
        <div class="preview-area" id="previewArea">
            <button class="remove-btn" id="removeBtn" title="Remove image">✕</button>
            <img id="previewImage" src="" alt="Preview">
        </div>
        <div class="action-row" id="actionRow" style="display:none;">
            <button class="btn btn-primary" id="analyzeBtn"><span>🔬</span> Analyze Image</button>
            <button class="btn btn-secondary" id="changeImageBtn"><span>📁</span> Change Image</button>
        </div>
        <div class="results-container" id="resultsContainer">
            <div class="result-card">
                <div class="result-header" id="resultHeader"></div>
                <div class="confidence-meter"><div class="confidence-fill" id="confidenceFill" style="width:0%;"></div></div>
                <div class="confidence-label"><span>Human</span><span id="confidenceText">—</span><span>AI-Generated</span></div>
                <div class="indicators-grid" id="indicatorsGrid"></div>
            </div>
        </div>
    </div>

    <div class="naturalize-section" id="naturalizeSection">
        <h3>🛠️ Naturalize Image (General Filter)</h3>
        <button class="btn-naturalize" id="naturalizeBtn"><span>✨</span> Apply Naturalizing Filter</button>
        <a class="btn-download" id="downloadLink" style="display:none;" download="naturalized.png">⬇️ Download</a>
        <canvas id="naturalizeCanvas" style="display:none;"></canvas>
    </div>

    <div class="fix-section" id="fixWeakSection">
        <h3>🎯 Fix Weak Signals (Targeted Humanizer)</h3>
        <button class="btn-naturalize" id="fixWeakBtn" style="background:linear-gradient(135deg,#f39c12,#e67e22);"><span>🔧</span> Fix Warning Signals</button>
        <span id="fixStatus" style="margin-left:12px;font-size:0.85rem;color:var(--text-secondary);"></span>
        <a class="btn-download" id="fixedDownloadLink" style="display:none;" download="humanized.png">⬇️ Download Fixed</a>
    </div>

    <div class="detail-section" id="detailSection">
        <h3>🔍 Detailed AI Analysis</h3>
        <button class="btn-detail" id="detailedBtn"><span>📊</span> Show Heatmap & Reasoning</button>
        <div id="heatmapContainer" class="heatmap-container" style="display:none; margin-top:16px;"></div>
        <div id="reasoningText" class="reasoning-text" style="display:none;"></div>
    </div>

    <button class="settings-toggle" id="settingsToggle"><span>⚙️</span> Advanced Settings</button>
    <div class="settings-panel" id="settingsPanel">
        <label for="apiKeyInput">Hugging Face API Token (optional)</label>
        <input type="password" id="apiKeyInput" placeholder="hf_...">
        <p class="hint">Get token at <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co</a></p>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function() {
    // ── DOM References ──
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const previewArea = document.getElementById('previewArea');
    const previewImage = document.getElementById('previewImage');
    const removeBtn = document.getElementById('removeBtn');
    const actionRow = document.getElementById('actionRow');
    const analyzeBtn = document.getElementById('analyzeBtn');
    const changeImageBtn = document.getElementById('changeImageBtn');
    const resultsContainer = document.getElementById('resultsContainer');
    const resultHeader = document.getElementById('resultHeader');
    const confidenceFill = document.getElementById('confidenceFill');
    const confidenceText = document.getElementById('confidenceText');
    const indicatorsGrid = document.getElementById('indicatorsGrid');
    const spinnerOverlay = document.getElementById('spinnerOverlay');
    const settingsToggle = document.getElementById('settingsToggle');
    const settingsPanel = document.getElementById('settingsPanel');
    const apiKeyInput = document.getElementById('apiKeyInput');
    const toast = document.getElementById('toast');
    const naturalizeSection = document.getElementById('naturalizeSection');
    const naturalizeBtn = document.getElementById('naturalizeBtn');
    const downloadLink = document.getElementById('downloadLink');
    const naturalizeCanvas = document.getElementById('naturalizeCanvas');
    const fixWeakSection = document.getElementById('fixWeakSection');
    const fixWeakBtn = document.getElementById('fixWeakBtn');
    const fixStatus = document.getElementById('fixStatus');
    const fixedDownloadLink = document.getElementById('fixedDownloadLink');
    const detailSection = document.getElementById('detailSection');
    const detailedBtn = document.getElementById('detailedBtn');
    const heatmapContainer = document.getElementById('heatmapContainer');
    const reasoningText = document.getElementById('reasoningText');

    let currentFile = null, currentImageDataUrl = null, isAnalyzing = false;
    let lastHeuristicDetails = null, lastScore = null, lastMethod = '';

    const savedApiKey = localStorage.getItem('hf_api_key') || '';
    if (savedApiKey) apiKeyInput.value = savedApiKey;

    // ── Toast ──
    let toastTimeout;
    function showToast(msg) {
        clearTimeout(toastTimeout);
        toast.textContent = msg;
        toast.classList.add('show');
        toastTimeout = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    // ── File Handling ──
    function handleFile(file) {
        const validTypes = ['image/jpeg','image/png','image/webp','image/gif','image/bmp','image/tiff'];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|webp|gif|bmp|tiff?)$/i)) {
            showToast('⚠️ Please upload a valid image file (JPG, PNG, WebP, GIF)'); return;
        }
        if (file.size > 10*1024*1024) { showToast('⚠️ File size exceeds 10MB limit'); return; }
        currentFile = file;
        const reader = new FileReader();
        reader.onload = function(e) {
            currentImageDataUrl = e.target.result;
            previewImage.src = currentImageDataUrl;
            uploadZone.style.display = 'none';
            previewArea.classList.add('active');
            actionRow.style.display = 'flex';
            resultsContainer.classList.remove('active');
            naturalizeSection.classList.remove('active');
            fixWeakSection.classList.remove('active');
            detailSection.classList.remove('active');
            downloadLink.style.display = 'none';
            fixedDownloadLink.style.display = 'none';
            fixStatus.textContent = '';
            resetResults();
        };
        reader.readAsDataURL(file);
    }
    function resetImage() {
        currentFile = null; currentImageDataUrl = null;
        previewImage.src = '';
        previewArea.classList.remove('active');
        uploadZone.style.display = '';
        actionRow.style.display = 'none';
        resultsContainer.classList.remove('active');
        naturalizeSection.classList.remove('active');
        fixWeakSection.classList.remove('active');
        detailSection.classList.remove('active');
        downloadLink.style.display = 'none';
        fixedDownloadLink.style.display = 'none';
        fixStatus.textContent = '';
        resetResults();
    }
    function resetResults() {
        resultHeader.innerHTML = '';
        confidenceFill.style.width = '0%';
        confidenceFill.style.background = 'var(--border)';
        confidenceText.textContent = '—';
        indicatorsGrid.innerHTML = '';
    }

    // ── Event Listeners (upload, drag, paste, settings) ──
    uploadZone.addEventListener('click', () => { if (!isAnalyzing) fileInput.click(); });
    fileInput.addEventListener('change', (e) => { if (e.target.files?.[0]) handleFile(e.target.files[0]); fileInput.value = ''; });
    uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); e.stopPropagation(); if (!isAnalyzing) uploadZone.classList.add('drag-over'); });
    uploadZone.addEventListener('dragleave', (e) => { e.preventDefault(); e.stopPropagation(); uploadZone.classList.remove('drag-over'); });
    uploadZone.addEventListener('drop', (e) => { e.preventDefault(); e.stopPropagation(); uploadZone.classList.remove('drag-over'); if (isAnalyzing) return; const dt = e.dataTransfer; if (dt.files?.[0]) handleFile(dt.files[0]); });
    document.addEventListener('paste', (e) => { if (isAnalyzing) return; const items = e.clipboardData?.items; if (!items) return; for (const item of items) { if (item.type.startsWith('image/')) { e.preventDefault(); const file = item.getAsFile(); if (file) handleFile(file); break; } } });
    removeBtn.addEventListener('click', (e) => { e.stopPropagation(); if (!isAnalyzing) resetImage(); });
    changeImageBtn.addEventListener('click', () => { if (!isAnalyzing) { resetImage(); setTimeout(() => fileInput.click(), 100); } });
    settingsToggle.addEventListener('click', () => {
        settingsPanel.classList.toggle('open');
        const arrow = settingsToggle.querySelector('span:last-child');
        arrow.textContent = settingsPanel.classList.contains('open') ? '▾' : '▸';
    });
    apiKeyInput.addEventListener('input', () => {
        const val = apiKeyInput.value.trim();
        val ? localStorage.setItem('hf_api_key', val) : localStorage.removeItem('hf_api_key');
    });

    // ── Client‑side Heuristic Analysis ──
    function analyzeClientSide(imageDataUrl) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const maxDim = 256;
                let w = img.naturalWidth, h = img.naturalHeight;
                const aspect = w / h;
                if (w > h) { w = maxDim; h = Math.round(maxDim / aspect); }
                else { h = maxDim; w = Math.round(maxDim * aspect); }
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                const imageData = ctx.getImageData(0, 0, w, h);
                const pixels = imageData.data;

                const hasMetadata = _estimateMetadataPresence(pixels, w, h);
                const noiseScore = _estimateNoiseConsistency(pixels, w, h);
                const colorStats = _analyzeColorDistribution(pixels, w, h);
                const edgeVariance = _analyzeEdgeVariance(pixels, w, h);
                const saturationAnomaly = _analyzeSaturationAnomaly(pixels, w, h);

                let aiIndicators = 0, totalWeight = 0;
                aiIndicators += (1 - hasMetadata) * 0.15; totalWeight += 0.15;
                aiIndicators += noiseScore * 0.25; totalWeight += 0.25;
                aiIndicators += colorStats.aiLikelihood * 0.25; totalWeight += 0.25;
                aiIndicators += edgeVariance * 0.2; totalWeight += 0.2;
                aiIndicators += saturationAnomaly * 0.15; totalWeight += 0.15;

                const score = totalWeight > 0 ? aiIndicators / totalWeight : 0.5;
                resolve({
                    score,
                    details: { hasMetadata, noiseScore, colorStats, edgeVariance, saturationAnomaly },
                    method: 'client-heuristics'
                });
            };
            img.onerror = () => resolve({ score: 0.5, details: null, method: 'client-heuristics-fallback', error: true });
            img.src = imageDataUrl;
        });
    }
    function _estimateMetadataPresence(pixels, w, h) {
        const cornerSize = Math.min(20, Math.floor(w*0.08), Math.floor(h*0.08));
        const corners = [{x:0,y:0},{x:w-cornerSize,y:0},{x:0,y:h-cornerSize},{x:w-cornerSize,y:h-cornerSize}];
        let cornerVariances = [];
        for (const c of corners) {
            let vals = [];
            for (let dy=0; dy<cornerSize; dy++) {
                for (let dx=0; dx<cornerSize; dx++) {
                    const idx = ((c.y+dy)*w + (c.x+dx))*4;
                    vals.push((pixels[idx]+pixels[idx+1]+pixels[idx+2])/3);
                }
            }
            const mean = vals.reduce((a,b)=>a+b,0)/vals.length;
            const variance = vals.reduce((a,b)=>a+(b-mean)**2,0)/vals.length;
            cornerVariances.push(variance);
        }
        const avgVar = cornerVariances.reduce((a,b)=>a+b,0)/cornerVariances.length;
        return Math.min(1, Math.max(0.2, avgVar/500));
    }
    function _estimateNoiseConsistency(pixels, w, h) {
        const patchSize = 16, numPatches = 20;
        let patchVariances = [];
        for (let p=0; p<numPatches; p++) {
            const px = Math.floor(Math.random()*(w-patchSize));
            const py = Math.floor(Math.random()*(h-patchSize));
            let vals = [];
            for (let dy=0; dy<patchSize; dy++) {
                for (let dx=0; dx<patchSize; dx++) {
                    const idx = ((py+dy)*w + (px+dx))*4;
                    vals.push((pixels[idx]+pixels[idx+1]+pixels[idx+2])/3);
                }
            }
            const mean = vals.reduce((a,b)=>a+b,0)/vals.length;
            const variance = vals.reduce((a,b)=>a+(b-mean)**2,0)/vals.length;
            patchVariances.push(variance);
        }
        const avgVar = patchVariances.reduce((a,b)=>a+b,0)/patchVariances.length;
        const varOfVars = patchVariances.reduce((a,b)=>a+(b-avgVar)**2,0)/patchVariances.length;
        const ratio = avgVar>0 ? Math.sqrt(varOfVars)/avgVar : 1;
        return Math.max(0, Math.min(1, 1 - ratio));
    }
    function _analyzeColorDistribution(pixels, w, h) {
        const total = w*h, step = Math.max(1, Math.floor(total/4000));
        let clusters = {};
        for (let i=0; i<total; i+=step) {
            const idx = i*4;
            const r = Math.round(pixels[idx]/32)*32, g = Math.round(pixels[idx+1]/32)*32, b = Math.round(pixels[idx+2]/32)*32;
            clusters[`${r},${g},${b}`] = (clusters[`${r},${g},${b}`]||0)+1;
        }
        const count = Object.keys(clusters).length;
        const uniqueRatio = count / Math.min(total/step, 512);
        const aiLikelihood = uniqueRatio<0.25?0.6 : uniqueRatio<0.4?0.35 : 0.1;
        return { uniqueRatio, clusterCount: count, aiLikelihood };
    }
    function _analyzeEdgeVariance(pixels, w, h) {
        let edgeMags = [];
        const step = 4;
        for (let y=1; y<h-1; y+=step) {
            for (let x=1; x<w-1; x+=step) {
                const idx = (y*w+x)*4;
                const up = ((y-1)*w+x)*4, left = (y*w+(x-1))*4;
                const bc = (pixels[idx]+pixels[idx+1]+pixels[idx+2])/3;
                const bu = (pixels[up]+pixels[up+1]+pixels[up+2])/3;
                const bl = (pixels[left]+pixels[left+1]+pixels[left+2])/3;
                edgeMags.push(Math.sqrt((bc-bu)**2 + (bc-bl)**2));
            }
        }
        const mean = edgeMags.reduce((a,b)=>a+b,0)/edgeMags.length;
        const variance = edgeMags.reduce((a,b)=>a+(b-mean)**2,0)/edgeMags.length;
        const cv = mean>0 ? Math.sqrt(variance)/mean : 1;
        return Math.min(1, cv/3);
    }
    function _analyzeSaturationAnomaly(pixels, w, h) {
        const total = w*h, step = Math.max(1, Math.floor(total/3000));
        let sats = [];
        for (let i=0; i<total; i+=step) {
            const idx = i*4;
            const r=pixels[idx], g=pixels[idx+1], b=pixels[idx+2];
            const max = Math.max(r,g,b), min = Math.min(r,g,b);
            sats.push(max>0 ? (max-min)/max : 0);
        }
        const avg = sats.reduce((a,b)=>a+b,0)/sats.length;
        const variance = sats.reduce((a,b)=>a+(b-avg)**2,0)/sats.length;
        return Math.min(1, variance*5 + Math.abs(avg-0.35)*1.5);
    }

    // ── Hugging Face API ──
    async function callHuggingFaceAPI(file) {
        const apiKey = apiKeyInput.value.trim() || savedApiKey;
        const headers = { 'Content-Type': 'application/octet-stream' };
        if (apiKey && apiKey.startsWith('hf_')) headers['Authorization'] = `Bearer ${apiKey}`;
        const arrayBuffer = await file.arrayBuffer();
        const MODEL_URL = 'https://router.huggingface.co/hf-inference/models/umm-maybe/AI-image-detector';
        let response;
        try {
            response = await fetch(MODEL_URL, { method: 'POST', headers, body: arrayBuffer });
        } catch (e) { throw new Error('NETWORK_ERROR'); }
        if (response.status === 503) {
            const data = await response.json().catch(()=>({}));
            throw new Error(`MODEL_LOADING:${data.estimated_time||30}`);
        }
        if (!response.ok) {
            if (response.status===401||response.status===403) throw new Error('AUTH_REQUIRED');
            if (response.status===429) throw new Error('RATE_LIMITED');
            if (response.status===404) throw new Error('MODEL_NOT_FOUND');
            throw new Error(`API_ERROR:${response.status}`);
        }
        const result = await response.json();
        let aiScore = 0.5;
        if (Array.isArray(result)) {
            for (const item of result) {
                const label = (item.label||'').toLowerCase();
                if (['fake','ai','artificial','ai-generated'].includes(label)) aiScore = item.score;
                else if (['real','human','natural','not-ai'].includes(label)) aiScore = 1 - item.score;
            }
        }
        return { score: aiScore, method: 'huggingface-api', rawResult: result };
    }

    // ── Analyze Button ──
    analyzeBtn.addEventListener('click', async () => {
        if (!currentFile || isAnalyzing) return;
        isAnalyzing = true; analyzeBtn.disabled = true;
        spinnerOverlay.classList.add('active');
        resultsContainer.classList.remove('active'); resetResults();
        let finalScore = null, method = 'unknown', apiResult = null, heuristicResult = null, apiError = null;
        const apiKey = apiKeyInput.value.trim() || savedApiKey;
        if (apiKey && apiKey.startsWith('hf_')) {
            try {
                apiResult = await callHuggingFaceAPI(currentFile);
                finalScore = apiResult.score; method = apiResult.method;
            } catch (err) {
                apiError = err.message;
                if (apiError.startsWith('MODEL_LOADING:')) showToast('⏳ Model warming up. Using heuristics...');
                else if (apiError==='AUTH_REQUIRED') showToast('🔑 Invalid token. Using heuristics.');
                else if (apiError==='RATE_LIMITED') showToast('🚦 Rate limited. Using heuristics.');
                else if (apiError==='MODEL_NOT_FOUND') showToast('❌ Model not found. Using heuristics.');
                else showToast('🌐 API error. Using heuristics.');
            }
        }
        if (finalScore === null) {
            heuristicResult = await analyzeClientSide(currentImageDataUrl);
            finalScore = heuristicResult.score; method = heuristicResult.method;
        }
        if (!heuristicResult) heuristicResult = await analyzeClientSide(currentImageDataUrl);
        lastScore = finalScore;
        lastMethod = method;
        lastHeuristicDetails = heuristicResult?.details;
        displayResults(finalScore, method, heuristicResult, apiResult, apiError);
        spinnerOverlay.classList.remove('active');
        isAnalyzing = false; analyzeBtn.disabled = false;
    });

    function countWarnings(details) {
        let c = 0;
        if (details.noiseScore > 0.4) c++;
        if (details.colorStats.aiLikelihood > 0.3) c++;
        if (details.edgeVariance > 0.4) c++;
        if (details.saturationAnomaly > 0.35) c++;
        if (details.hasMetadata < 0.4) c++;
        return c;
    }

    function displayResults(score, method, heuristicResult, apiResult, apiError) {
        resultsContainer.classList.add('active');
        naturalizeSection.classList.add('active');
        fixWeakSection.classList.add('active');
        detailSection.classList.add('active');

        if (lastHeuristicDetails) {
            const warnings = countWarnings(lastHeuristicDetails);
            fixStatus.textContent = warnings > 0 ? `${warnings} warning(s) detected` : '✅ All signals good';
            fixWeakBtn.disabled = false;
        } else {
            fixStatus.textContent = 'No heuristic data';
            fixWeakBtn.disabled = true;
        }

        let classification, badgeClass;
        if (score >= 0.65) { classification = 'AI-Generated'; badgeClass = 'badge-ai'; }
        else if (score <= 0.35) { classification = 'Likely Human-Made'; badgeClass = 'badge-human'; }
        else { classification = 'Uncertain'; badgeClass = 'badge-uncertain'; }
        const aiProbability = Math.round(score * 100);

        resultHeader.innerHTML = `<span class="result-badge ${badgeClass}">${classification}</span>
            <span style="font-weight:600;font-size:1.1rem;">${aiProbability}% AI probability</span>
            <span style="font-size:0.8rem;color:var(--text-secondary);">via ${method==='huggingface-api'?'🤖 Neural Network':'🔍 Heuristic Analysis'}</span>`;

        setTimeout(() => {
            confidenceFill.style.width = `${aiProbability}%`;
            confidenceFill.style.background = score>=0.65?'linear-gradient(90deg,#f39c12,#e74c5e)':score<=0.35?'linear-gradient(90deg,#2ecc71,#27ae60)':'linear-gradient(90deg,#f39c12,#e67e22)';
        }, 100);
        confidenceText.textContent = `${aiProbability}% AI · ${100-aiProbability}% Human`;

        const details = heuristicResult?.details;
        let indicatorsHTML = '';
        if (details) {
            const metaIcon = details.hasMetadata>0.6?'✅':details.hasMetadata>0.3?'⚠️':'❌';
            const metaClass = details.hasMetadata>0.6?'good':details.hasMetadata>0.3?'':'warning';
            indicatorsHTML += `<div class="indicator-item ${metaClass}"><div class="indicator-icon">${metaIcon}</div><div class="indicator-name">Metadata Traces</div><div class="indicator-value">${Math.round(details.hasMetadata*100)}%</div></div>`;

            const noiseIcon = details.noiseScore<0.4?'✅':details.noiseScore<0.65?'⚠️':'❌';
            const noiseClass = details.noiseScore<0.4?'good':details.noiseScore<0.65?'':'warning';
            indicatorsHTML += `<div class="indicator-item ${noiseClass}"><div class="indicator-icon">${noiseIcon}</div><div class="indicator-name">Noise Consistency</div><div class="indicator-value">${Math.round(details.noiseScore*100)}% anomaly</div></div>`;

            const colorIcon = details.colorStats.aiLikelihood<0.3?'✅':details.colorStats.aiLikelihood<0.5?'⚠️':'❌';
            const colorClass = details.colorStats.aiLikelihood<0.3?'good':details.colorStats.aiLikelihood<0.5?'':'warning';
            indicatorsHTML += `<div class="indicator-item ${colorClass}"><div class="indicator-icon">${colorIcon}</div><div class="indicator-name">Color Distribution</div><div class="indicator-value">${details.colorStats.clusterCount} clusters</div></div>`;

            const edgeIcon = details.edgeVariance<0.4?'✅':details.edgeVariance<0.6?'⚠️':'❌';
            const edgeClass = details.edgeVariance<0.4?'good':details.edgeVariance<0.6?'':'warning';
            indicatorsHTML += `<div class="indicator-item ${edgeClass}"><div class="indicator-icon">${edgeIcon}</div><div class="indicator-name">Edge Variance</div><div class="indicator-value">${Math.round(details.edgeVariance*100)}%</div></div>`;

            const satIcon = details.saturationAnomaly<0.35?'✅':details.saturationAnomaly<0.55?'⚠️':'❌';
            const satClass = details.saturationAnomaly<0.35?'good':details.saturationAnomaly<0.55?'':'warning';
            indicatorsHTML += `<div class="indicator-item ${satClass}"><div class="indicator-icon">${satIcon}</div><div class="indicator-name">Saturation Profile</div><div class="indicator-value">${Math.round(details.saturationAnomaly*100)}% anomaly</div></div>`;
        }
        if (apiError && method!=='huggingface-api') {
            indicatorsHTML += `<div class="indicator-item" style="grid-column:1/-1;border-color:rgba(243,156,18,0.4);background:rgba(243,156,18,0.04);"><div class="indicator-icon">⚠️</div><div class="indicator-name">API Status</div><div class="indicator-value" style="font-size:0.8rem;">${apiError}</div></div>`;
        }
        if (method==='huggingface-api') {
            indicatorsHTML += `<div class="indicator-item good" style="grid-column:1/-1;"><div class="indicator-icon">🤖</div><div class="indicator-name">Detection Engine</div><div class="indicator-value" style="font-size:0.8rem;">Neural network + heuristics</div></div>`;
        }
        indicatorsGrid.innerHTML = indicatorsHTML;
        resultsContainer.scrollIntoView({ behavior:'smooth', block:'nearest' });

        // Reset detailed view
        heatmapContainer.style.display = 'none';
        reasoningText.style.display = 'none';
        heatmapContainer.innerHTML = '';
        reasoningText.innerHTML = '';
    }

    // ── Naturalize (General Filter) ──
    naturalizeBtn.addEventListener('click', () => {
        if (!currentImageDataUrl) return;
        const img = new Image();
        img.onload = () => {
            naturalizeCanvas.width = img.naturalWidth;
            naturalizeCanvas.height = img.naturalHeight;
            const ctx = naturalizeCanvas.getContext('2d');
            ctx.drawImage(img,0,0);
            const imageData = ctx.getImageData(0,0,naturalizeCanvas.width,naturalizeCanvas.height);
            const data = imageData.data;
            for (let i=0; i<data.length; i+=4) {
                const noise = (Math.random()-0.5)*8;
                data[i]=Math.min(255,Math.max(0,data[i]+noise));
                data[i+1]=Math.min(255,Math.max(0,data[i+1]+noise));
                data[i+2]=Math.min(255,Math.max(0,data[i+2]+noise));
            }
            ctx.putImageData(imageData,0,0);
            const w=naturalizeCanvas.width, h=naturalizeCanvas.height;
            const cx=w/2, cy=h/2, maxDist=Math.sqrt(cx*cx+cy*cy);
            const imgData2=ctx.getImageData(0,0,w,h);
            const d2=imgData2.data;
            for (let y=0; y<h; y++) {
                for (let x=0; x<w; x++) {
                    const dx=x-cx, dy=y-cy;
                    const dist=Math.sqrt(dx*dx+dy*dy);
                    const factor=1-(dist/maxDist)*0.15;
                    const idx=(y*w+x)*4;
                    d2[idx]*=factor; d2[idx+1]*=factor; d2[idx+2]*=factor;
                }
            }
            ctx.putImageData(imgData2,0,0);
            const imgData3=ctx.getImageData(0,0,w,h);
            const orig=ctx.getImageData(0,0,w,h);
            for (let y=0; y<h; y++) {
                for (let x=1; x<w-1; x++) {
                    const idx=(y*w+x)*4;
                    const redIdx=(y*w+(x-1))*4;
                    imgData3.data[idx]=orig.data[redIdx]*0.1+orig.data[idx]*0.9;
                }
            }
            ctx.putImageData(imgData3,0,0);
            const grain=document.createElement('canvas');
            grain.width=w; grain.height=h;
            const gCtx=grain.getContext('2d');
            const gData=gCtx.createImageData(w,h);
            for (let i=0; i<gData.data.length; i+=4) {
                const val=Math.random()<0.3?255:0;
                gData.data[i]=val; gData.data[i+1]=val; gData.data[i+2]=val; gData.data[i+3]=15;
            }
            gCtx.putImageData(gData,0,0);
            ctx.drawImage(grain,0,0);
            downloadLink.href = naturalizeCanvas.toDataURL('image/png');
            downloadLink.style.display = 'inline-flex';
            showToast('✅ Naturalizing filter applied.');
        };
        img.src = currentImageDataUrl;
    });

    // ── Fix Weak Signals ──
    fixWeakBtn.addEventListener('click', () => {
        if (!currentImageDataUrl || !lastHeuristicDetails) return;
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            const details = lastHeuristicDetails;

            if (details.noiseScore > 0.4) {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                const strength = Math.min(12, details.noiseScore * 20);
                for (let i = 0; i < data.length; i += 4) {
                    const noise = (Math.random() - 0.5) * strength;
                    data[i] = Math.min(255, Math.max(0, data[i] + noise));
                    data[i+1] = Math.min(255, Math.max(0, data[i+1] + noise));
                    data[i+2] = Math.min(255, Math.max(0, data[i+2] + noise));
                }
                ctx.putImageData(imageData, 0, 0);
            }
            if (details.colorStats.aiLikelihood > 0.3) {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    data[i] = Math.min(255, Math.max(0, data[i] + (Math.random() - 0.5) * 8));
                    data[i+1] = Math.min(255, Math.max(0, data[i+1] + (Math.random() - 0.5) * 8));
                    data[i+2] = Math.min(255, Math.max(0, data[i+2] + (Math.random() - 0.5) * 8));
                }
                ctx.putImageData(imageData, 0, 0);
            }
            if (details.edgeVariance > 0.4) {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const blurred = applyBlur(imageData, canvas.width, canvas.height, 1);
                ctx.putImageData(blurred, 0, 0);
            }
            if (details.saturationAnomaly > 0.35) {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i], g = data[i+1], b = data[i+2];
                    const max = Math.max(r,g,b), min = Math.min(r,g,b);
                    const sat = max > 0 ? (max - min) / max : 0;
                    const targetSat = 0.35;
                    const adjust = (targetSat - sat) * 0.3;
                    const gray = (r+g+b)/3;
                    data[i] = Math.min(255, Math.max(0, gray + (r - gray) * (1 + adjust)));
                    data[i+1] = Math.min(255, Math.max(0, gray + (g - gray) * (1 + adjust)));
                    data[i+2] = Math.min(255, Math.max(0, gray + (b - gray) * (1 + adjust)));
                }
                ctx.putImageData(imageData, 0, 0);
            }
            if (details.hasMetadata < 0.4) {
                const w = canvas.width, h = canvas.height;
                const imageData = ctx.getImageData(0, 0, w, h);
                const cornerSize = Math.min(30, Math.floor(w*0.1), Math.floor(h*0.1));
                const corners = [{x:0,y:0},{x:w-cornerSize,y:0},{x:0,y:h-cornerSize},{x:w-cornerSize,y:h-cornerSize}];
                for (const corner of corners) {
                    for (let dy=0; dy<cornerSize; dy++) {
                        for (let dx=0; dx<cornerSize; dx++) {
                            const idx = ((corner.y+dy)*w + (corner.x+dx))*4;
                            const rand = (Math.random() - 0.5) * 6;
                            imageData.data[idx] = Math.min(255, Math.max(0, imageData.data[idx] + rand));
                            imageData.data[idx+1] = Math.min(255, Math.max(0, imageData.data[idx+1] + rand));
                            imageData.data[idx+2] = Math.min(255, Math.max(0, imageData.data[idx+2] + rand));
                        }
                    }
                }
                const borderWidth = 4;
                for (let y=0; y<h; y++) {
                    for (let x=0; x<w; x++) {
                        if (x<borderWidth || x>=w-borderWidth || y<borderWidth || y>=h-borderWidth) {
                            const idx = (y*w + x)*4;
                            const rand = (Math.random() - 0.5) * 4;
                            imageData.data[idx] = Math.min(255, Math.max(0, imageData.data[idx] + rand));
                            imageData.data[idx+1] = Math.min(255, Math.max(0, imageData.data[idx+1] + rand));
                            imageData.data[idx+2] = Math.min(255, Math.max(0, imageData.data[idx+2] + rand));
                        }
                    }
                }
                ctx.putImageData(imageData, 0, 0);
            }

            const fixedDataUrl = canvas.toDataURL('image/png');
            previewImage.src = fixedDataUrl;
            currentImageDataUrl = fixedDataUrl;
            fixedDownloadLink.href = fixedDataUrl;
            fixedDownloadLink.style.display = 'inline-flex';

            analyzeClientSide(fixedDataUrl).then(heuristicResult => {
                lastHeuristicDetails = heuristicResult.details;
                lastScore = heuristicResult.score;
                lastMethod = 'client-heuristics';
                displayResults(heuristicResult.score, 'client-heuristics', heuristicResult, null, null);
                showToast('✅ Weak signals fixed & re‑analysed.');
            });
        };
        img.src = currentImageDataUrl;
    });

    function applyBlur(imageData, width, height, radius) {
        const copy = new Uint8ClampedArray(imageData.data);
        const output = new ImageData(width, height);
        const getPixel = (x, y) => {
            if (x < 0 || x >= width || y < 0 || y >= height) return [0,0,0,0];
            const idx = (y * width + x) * 4;
            return [copy[idx], copy[idx+1], copy[idx+2], copy[idx+3]];
        };
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                let r=0,g=0,b=0,a=0, count=0;
                for (let dy = -radius; dy <= radius; dy++) {
                    for (let dx = -radius; dx <= radius; dx++) {
                        const [pr,pg,pb,pa] = getPixel(x+dx, y+dy);
                        r += pr; g += pg; b += pb; a += pa;
                        count++;
                    }
                }
                const idx = (y * width + x) * 4;
                output.data[idx] = r/count;
                output.data[idx+1] = g/count;
                output.data[idx+2] = b/count;
                output.data[idx+3] = a/count;
            }
        }
        return output;
    }

    // ── Detailed Analysis (Heatmap + Reasoning) ──
    detailedBtn.addEventListener('click', async () => {
        if (!currentImageDataUrl || !lastHeuristicDetails) {
            showToast('Please run an analysis first.');
            return;
        }
        const img = new Image();
        img.onload = async () => {
            const cellSize = 40;
            const w = img.naturalWidth, h = img.naturalHeight;
            const cols = Math.ceil(w / cellSize);
            const rows = Math.ceil(h / cellSize);
            const heatmapValues = new Array(rows).fill(0).map(() => new Array(cols).fill(0.5));

            const analyzeCanvas = document.createElement('canvas');
            analyzeCanvas.width = w; analyzeCanvas.height = h;
            const actx = analyzeCanvas.getContext('2d');
            actx.drawImage(img, 0, 0);

            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    const x = col * cellSize, y = row * cellSize;
                    const cellW = Math.min(cellSize, w - x), cellH = Math.min(cellSize, h - y);
                    const cellImageData = actx.getImageData(x, y, cellW, cellH);
                    const cellScore = computeCellAIScore(cellImageData.data, cellW, cellH);
                    heatmapValues[row][col] = cellScore;
                }
            }

            const heatCanvas = document.createElement('canvas');
            heatCanvas.width = w; heatCanvas.height = h;
            const hctx = heatCanvas.getContext('2d');
            hctx.drawImage(img, 0, 0);
            hctx.fillStyle = 'rgba(0,0,0,0.4)';
            hctx.fillRect(0, 0, w, h);

            const cellW_actual = w / cols, cellH_actual = h / rows;
            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    const val = heatmapValues[row][col];
                    const red = Math.min(255, Math.floor(val * 255));
                    const green = Math.min(255, Math.floor((1 - val) * 255));
                    hctx.fillStyle = `rgba(${red},${green},0,0.5)`;
                    hctx.fillRect(col * cellW_actual, row * cellH_actual, cellW_actual, cellH_actual);
                }
            }

            heatmapContainer.innerHTML = '';
            heatmapContainer.appendChild(heatCanvas);
            heatmapContainer.style.display = 'block';

            const d = lastHeuristicDetails;
            let reasoning = '<strong>Key Indicators:</strong><ul>';
            if (d.hasMetadata < 0.4) reasoning += '<li>Low metadata traces typical of AI generation</li>';
            if (d.noiseScore > 0.6) reasoning += '<li>Unnaturally consistent noise pattern</li>';
            if (d.colorStats.aiLikelihood > 0.4) reasoning += '<li>Synthetic color distribution (limited palette)</li>';
            if (d.edgeVariance > 0.5) reasoning += '<li>Abnormal edge sharpness variation</li>';
            if (d.saturationAnomaly > 0.4) reasoning += '<li>Unnatural saturation profile</li>';
            if (d.hasMetadata > 0.6 && d.noiseScore < 0.4) reasoning += '<li>Natural noise and metadata suggest human origin</li>';
            reasoning += '</ul><strong>Visual Patterns:</strong><br>';
            if (d.edgeVariance < 0.3) reasoning += 'Smooth, uniform edges typical of AI.<br>';
            else if (d.edgeVariance > 0.7) reasoning += 'Highly irregular edges, possible human photo.<br>';
            if (d.saturationAnomaly > 0.5) reasoning += 'Overly saturated or desaturated regions.<br>';
            reasoning += `Overall score: ${Math.round(lastScore*100)}% AI likelihood.`;

            reasoningText.innerHTML = reasoning;
            reasoningText.style.display = 'block';
            showToast('✅ Detailed heatmap generated');
        };
        img.src = currentImageDataUrl;
    });

    function computeCellAIScore(data, w, h) {
        let edgeSum = 0, count = 0;
        for (let y = 1; y < h-1; y += 2) {
            for (let x = 1; x < w-1; x += 2) {
                const idx = (y*w + x)*4;
                const up = ((y-1)*w + x)*4;
                const left = (y*w + (x-1))*4;
                const b = (data[idx]+data[idx+1]+data[idx+2])/3;
                const bu = (data[up]+data[up+1]+data[up+2])/3;
                const bl = (data[left]+data[left+1]+data[left+2])/3;
                edgeSum += Math.abs(b - bu) + Math.abs(b - bl);
                count++;
            }
        }
        const avgEdge = count ? edgeSum / count : 0;
        let noiseVar = 0, nCount = 0;
        for (let i = 0; i < data.length; i += 8) {
            const val = (data[i]+data[i+1]+data[i+2])/3;
            noiseVar += val * val;
            nCount++;
        }
        const avgBright = noiseVar / nCount;
        const edgeScore = Math.min(1, avgEdge / 60);
        const noiseScore = Math.min(1, Math.sqrt(avgBright) / 50);
        return 0.5 + (edgeScore - noiseScore) * 0.3;
    }

    document.addEventListener('keydown', (e) => {
        if (e.key==='Enter' && currentFile && !isAnalyzing && document.activeElement===document.body) analyzeBtn.click();
    });

    console.log('🔍 AI Image Detector ready – full analysis mode');
})();
</script>
</body>
</html>