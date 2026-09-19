import {
  formatFileSize,
  readImageDimensions,
  validateImageDimensions,
  validateUploadCandidate,
} from "./upload.js";
import { calculateHeuristicScore, callBackendAPI } from "./analysis.js";
import {
  countWarnings,
  createScorePresentation,
  decisionFromScore,
  escapeHTML,
} from "./results.js";

(function () {
  // ── DOM References ──
  const uploadZone = document.getElementById("uploadZone");
  const fileInput = document.getElementById("fileInput");
  const previewArea = document.getElementById("previewArea");
  const previewImage = document.getElementById("previewImage");
  const removeBtn = document.getElementById("removeBtn");
  const actionRow = document.getElementById("actionRow");
  const analyzeBtn = document.getElementById("analyzeBtn");
  const changeImageBtn = document.getElementById("changeImageBtn");
  const resultsContainer = document.getElementById("resultsContainer");
  const resultHeader = document.getElementById("resultHeader");
  const confidenceFill = document.getElementById("confidenceFill");
  const confidenceText = document.getElementById("confidenceText");
  const indicatorsGrid = document.getElementById("indicatorsGrid");
  const spinnerOverlay = document.getElementById("spinnerOverlay");
  const settingsToggle = document.getElementById("settingsToggle");
  const settingsPanel = document.getElementById("settingsPanel");
  const toast = document.getElementById("toast");
  const naturalizeSection = document.getElementById("naturalizeSection");
  const naturalizeBtn = document.getElementById("naturalizeBtn");
  const downloadLink = document.getElementById("downloadLink");
  const naturalizeCanvas = document.getElementById("naturalizeCanvas");
  const fixWeakSection = document.getElementById("fixWeakSection");
  const fixWeakBtn = document.getElementById("fixWeakBtn");
  const fixStatus = document.getElementById("fixStatus");
  const fixedDownloadLink = document.getElementById("fixedDownloadLink");
  const detailSection = document.getElementById("detailSection");
  const detailedBtn = document.getElementById("detailedBtn");
  const heatmapContainer = document.getElementById("heatmapContainer");
  const reasoningText = document.getElementById("reasoningText");
  const fileMeta = document.getElementById("fileMeta");
  const fileName = document.getElementById("fileName");
  const fileDetails = document.getElementById("fileDetails");
  const consoleStatus = document.getElementById("consoleStatus");
  const modeBadge = document.getElementById("modeBadge");
  const workflowSteps = [1, 2, 3].map((number) =>
    document.getElementById(`workflowStep${number}`),
  );

  let currentFile = null,
    currentImageDataUrl = null,
    isAnalyzing = false;
  let lastHeuristicDetails = null,
    lastScore = null;
  function setWorkflowStage(stage) {
    workflowSteps.forEach((step, index) => {
      step.classList.toggle("done", index + 1 < stage);
      step.classList.toggle("active", index + 1 === stage);
    });
  }

  // ── Toast ──
  let toastTimeout;
  function showToast(msg) {
    clearTimeout(toastTimeout);
    toast.textContent = msg;
    toast.classList.add("show");
    toastTimeout = setTimeout(() => toast.classList.remove("show"), 3500);
  }

  // ── File Handling ──
  async function handleFile(file) {
    const uploadValidation = validateUploadCandidate(file);
    if (!uploadValidation.ok) {
      showToast(`⚠️ ${uploadValidation.message}`);
      return;
    }
    let dimensions;
    try {
      dimensions = await readImageDimensions(file);
    } catch {
      showToast("⚠️ The selected file could not be decoded as an image.");
      return;
    }
    const dimensionValidation = validateImageDimensions(dimensions);
    if (!dimensionValidation.ok) {
      showToast(`⚠️ ${dimensionValidation.message}`);
      return;
    }
    const { width, height } = dimensions;
    currentFile = file;
    const reader = new FileReader();
    reader.onload = function (e) {
      currentImageDataUrl = e.target.result;
      previewImage.src = currentImageDataUrl;
      fileName.textContent = file.name || "Pasted image";
      fileDetails.textContent = `${width} × ${height}px · ${formatFileSize(file.size)}`;
      fileMeta.classList.add("active");
      uploadZone.style.display = "none";
      previewArea.classList.add("active");
      actionRow.style.display = "flex";
      resultsContainer.classList.remove("active");
      naturalizeSection.classList.remove("active");
      fixWeakSection.classList.remove("active");
      detailSection.classList.remove("active");
      downloadLink.style.display = "none";
      fixedDownloadLink.style.display = "none";
      fixStatus.textContent = "";
      resetResults();
      setWorkflowStage(2);
      consoleStatus.textContent = "Analysis workspace / image ready";
    };
    reader.onerror = function () {
      currentFile = null;
      showToast("⚠️ The selected image could not be read.");
    };
    reader.readAsDataURL(file);
  }
  function resetImage() {
    currentFile = null;
    currentImageDataUrl = null;
    previewImage.src = "";
    previewArea.classList.remove("active");
    fileMeta.classList.remove("active");
    fileName.textContent = "";
    fileDetails.textContent = "";
    uploadZone.style.display = "";
    actionRow.style.display = "none";
    resultsContainer.classList.remove("active");
    naturalizeSection.classList.remove("active");
    fixWeakSection.classList.remove("active");
    detailSection.classList.remove("active");
    downloadLink.style.display = "none";
    fixedDownloadLink.style.display = "none";
    fixStatus.textContent = "";
    resetResults();
    setWorkflowStage(1);
    consoleStatus.textContent = "Analysis workspace / ready";
  }
  function resetResults() {
    resultHeader.innerHTML = "";
    confidenceFill.style.width = "0%";
    confidenceFill.style.background = "var(--border)";
    confidenceFill.setAttribute("aria-valuenow", "0");
    confidenceText.textContent = "—";
    indicatorsGrid.innerHTML = "";
  }

  // ── Event Listeners (upload, drag, paste, settings) ──
  uploadZone.addEventListener("click", () => {
    if (!isAnalyzing) fileInput.click();
  });
  uploadZone.addEventListener("keydown", (e) => {
    if (!isAnalyzing && (e.key === "Enter" || e.key === " ")) {
      e.preventDefault();
      fileInput.click();
    }
  });
  fileInput.addEventListener("change", (e) => {
    if (e.target.files?.[0]) handleFile(e.target.files[0]);
    fileInput.value = "";
  });
  uploadZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!isAnalyzing) uploadZone.classList.add("drag-over");
  });
  uploadZone.addEventListener("dragleave", (e) => {
    e.preventDefault();
    e.stopPropagation();
    uploadZone.classList.remove("drag-over");
  });
  uploadZone.addEventListener("drop", (e) => {
    e.preventDefault();
    e.stopPropagation();
    uploadZone.classList.remove("drag-over");
    if (isAnalyzing) return;
    const dt = e.dataTransfer;
    if (dt.files?.[0]) handleFile(dt.files[0]);
  });
  document.addEventListener("paste", (e) => {
    if (isAnalyzing) return;
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
      if (item.type.startsWith("image/")) {
        e.preventDefault();
        const file = item.getAsFile();
        if (file) handleFile(file);
        break;
      }
    }
  });
  removeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!isAnalyzing) resetImage();
  });
  changeImageBtn.addEventListener("click", () => {
    if (!isAnalyzing) {
      resetImage();
      setTimeout(() => fileInput.click(), 100);
    }
  });
  settingsToggle.addEventListener("click", () => {
    const isOpen = settingsPanel.classList.toggle("open");
    settingsToggle.setAttribute("aria-expanded", String(isOpen));
    settingsPanel.setAttribute("aria-hidden", String(!isOpen));
    settingsToggle.querySelector(".settings-arrow").textContent = isOpen
      ? "−"
      : "+";
  });
  // ── Client‑side Heuristic Analysis ──
  function analyzeClientSide(imageDataUrl) {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement("canvas");
        const maxDim = 256;
        let w = img.naturalWidth,
          h = img.naturalHeight;
        const scale = Math.min(1, maxDim / Math.max(w, h));
        w = Math.max(3, Math.round(w * scale));
        h = Math.max(3, Math.round(h * scale));
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, w, h);
        const imageData = ctx.getImageData(0, 0, w, h);
        const pixels = imageData.data;

        const cornerTextureVariation = _estimateCornerTextureVariation(
          pixels,
          w,
          h,
        );
        const noiseScore = _estimateNoiseConsistency(pixels, w, h);
        const colorStats = _analyzeColorDistribution(pixels, w, h);
        const edgeVariance = _analyzeEdgeVariance(pixels, w, h);
        const saturationAnomaly = _analyzeSaturationAnomaly(pixels, w, h);

        const details = {
          cornerTextureVariation,
          noiseScore,
          colorStats,
          edgeVariance,
          saturationAnomaly,
        };
        const score = calculateHeuristicScore(details);
        resolve({
          score,
          details,
          method: "client-heuristics",
        });
      };
      img.onerror = () =>
        resolve({
          score: 0.5,
          details: null,
          method: "client-heuristics-fallback",
          error: true,
        });
      img.src = imageDataUrl;
    });
  }
  function _estimateCornerTextureVariation(pixels, w, h) {
    const cornerSize = Math.max(
      1,
      Math.min(20, Math.floor(w * 0.08), Math.floor(h * 0.08)),
    );
    const corners = [
      { x: 0, y: 0 },
      { x: w - cornerSize, y: 0 },
      { x: 0, y: h - cornerSize },
      { x: w - cornerSize, y: h - cornerSize },
    ];
    let cornerVariances = [];
    for (const c of corners) {
      let vals = [];
      for (let dy = 0; dy < cornerSize; dy++) {
        for (let dx = 0; dx < cornerSize; dx++) {
          const idx = ((c.y + dy) * w + (c.x + dx)) * 4;
          vals.push((pixels[idx] + pixels[idx + 1] + pixels[idx + 2]) / 3);
        }
      }
      const mean = vals.reduce((a, b) => a + b, 0) / vals.length;
      const variance =
        vals.reduce((a, b) => a + (b - mean) ** 2, 0) / vals.length;
      cornerVariances.push(variance);
    }
    const avgVar =
      cornerVariances.reduce((a, b) => a + b, 0) / cornerVariances.length;
    return Math.min(1, Math.max(0.2, avgVar / 500));
  }
  function _estimateNoiseConsistency(pixels, w, h) {
    const patchSize = Math.max(2, Math.min(16, w, h));
    const gridColumns = 5,
      gridRows = 4;
    let patchVariances = [];
    for (let row = 0; row < gridRows; row++) {
      for (let col = 0; col < gridColumns; col++) {
        const px = Math.round((w - patchSize) * (col / (gridColumns - 1)));
        const py = Math.round((h - patchSize) * (row / (gridRows - 1)));
        let vals = [];
        for (let dy = 0; dy < patchSize; dy++) {
          for (let dx = 0; dx < patchSize; dx++) {
            const idx = ((py + dy) * w + (px + dx)) * 4;
            vals.push((pixels[idx] + pixels[idx + 1] + pixels[idx + 2]) / 3);
          }
        }
        const mean = vals.reduce((a, b) => a + b, 0) / vals.length;
        const variance =
          vals.reduce((a, b) => a + (b - mean) ** 2, 0) / vals.length;
        patchVariances.push(variance);
      }
    }
    const avgVar =
      patchVariances.reduce((a, b) => a + b, 0) / patchVariances.length;
    const varOfVars =
      patchVariances.reduce((a, b) => a + (b - avgVar) ** 2, 0) /
      patchVariances.length;
    const ratio = avgVar > 0 ? Math.sqrt(varOfVars) / avgVar : 1;
    return Math.max(0, Math.min(1, 1 - ratio));
  }
  function _analyzeColorDistribution(pixels, w, h) {
    const total = w * h,
      step = Math.max(1, Math.floor(total / 4000));
    let clusters = {};
    for (let i = 0; i < total; i += step) {
      const idx = i * 4;
      const r = Math.round(pixels[idx] / 32) * 32,
        g = Math.round(pixels[idx + 1] / 32) * 32,
        b = Math.round(pixels[idx + 2] / 32) * 32;
      clusters[`${r},${g},${b}`] = (clusters[`${r},${g},${b}`] || 0) + 1;
    }
    const count = Object.keys(clusters).length;
    const uniqueRatio = count / Math.min(total / step, 512);
    const aiLikelihood =
      uniqueRatio < 0.25 ? 0.6 : uniqueRatio < 0.4 ? 0.35 : 0.1;
    return { uniqueRatio, clusterCount: count, aiLikelihood };
  }
  function _analyzeEdgeVariance(pixels, w, h) {
    let edgeMags = [];
    const step = 4;
    for (let y = 1; y < h - 1; y += step) {
      for (let x = 1; x < w - 1; x += step) {
        const idx = (y * w + x) * 4;
        const up = ((y - 1) * w + x) * 4,
          left = (y * w + (x - 1)) * 4;
        const bc = (pixels[idx] + pixels[idx + 1] + pixels[idx + 2]) / 3;
        const bu = (pixels[up] + pixels[up + 1] + pixels[up + 2]) / 3;
        const bl = (pixels[left] + pixels[left + 1] + pixels[left + 2]) / 3;
        edgeMags.push(Math.sqrt((bc - bu) ** 2 + (bc - bl) ** 2));
      }
    }
    const mean = edgeMags.reduce((a, b) => a + b, 0) / edgeMags.length;
    const variance =
      edgeMags.reduce((a, b) => a + (b - mean) ** 2, 0) / edgeMags.length;
    const cv = mean > 0 ? Math.sqrt(variance) / mean : 1;
    return Math.min(1, cv / 3);
  }
  function _analyzeSaturationAnomaly(pixels, w, h) {
    const total = w * h,
      step = Math.max(1, Math.floor(total / 3000));
    let sats = [];
    for (let i = 0; i < total; i += step) {
      const idx = i * 4;
      const r = pixels[idx],
        g = pixels[idx + 1],
        b = pixels[idx + 2];
      const max = Math.max(r, g, b),
        min = Math.min(r, g, b);
      sats.push(max > 0 ? (max - min) / max : 0);
    }
    const avg = sats.reduce((a, b) => a + b, 0) / sats.length;
    const variance = sats.reduce((a, b) => a + (b - avg) ** 2, 0) / sats.length;
    return Math.min(1, variance * 5 + Math.abs(avg - 0.35) * 1.5);
  }

  // ── Same-origin backend API ──
  // ── Analyze Button ──
  analyzeBtn.addEventListener("click", async () => {
    if (!currentFile || isAnalyzing) return;
    isAnalyzing = true;
    analyzeBtn.disabled = true;
    spinnerOverlay.classList.add("active");
    spinnerOverlay.setAttribute("aria-hidden", "false");
    spinnerOverlay.querySelector(".spinner-text").textContent =
      "Inspecting metadata and detector signals...";
    consoleStatus.textContent = "Analysis workspace / inspection running";
    setWorkflowStage(2);
    resultsContainer.classList.remove("active");
    resetResults();
    try {
      let finalScore = null,
        method = "unknown",
        backendResult = null,
        heuristicResult = null,
        apiError = null;
      try {
        backendResult = await callBackendAPI(currentFile);
        if (backendResult.model.status === "ok") {
          finalScore = backendResult.model.artificial_score;
          method = "backend-model";
          modeBadge.innerHTML = '<i aria-hidden="true"></i> Model + metadata';
          modeBadge.style.color = "var(--cyan-400)";
        } else {
          apiError =
            backendResult.model.error_code || backendResult.model.status;
          modeBadge.innerHTML = '<i aria-hidden="true"></i> Local + metadata';
          showToast(
            "The model is unavailable. Showing the local experimental score instead.",
          );
        }
      } catch (error) {
        apiError = error.message;
        modeBadge.innerHTML = '<i aria-hidden="true"></i> Local fallback';
        showToast(
          "The analysis service is unavailable. Showing the local experimental score instead.",
        );
      }
      if (finalScore === null) {
        heuristicResult = await analyzeClientSide(currentImageDataUrl);
        finalScore = heuristicResult.score;
        method = heuristicResult.method;
      }
      if (!heuristicResult)
        heuristicResult = await analyzeClientSide(currentImageDataUrl);
      lastScore = finalScore;
      lastHeuristicDetails = heuristicResult?.details;
      displayResults(
        finalScore,
        method,
        heuristicResult,
        backendResult,
        apiError,
      );
    } catch (error) {
      console.error("Image analysis failed", error);
      showToast("Analysis stopped. Try a different image or check the file.");
      consoleStatus.textContent = "Analysis workspace / inspection interrupted";
    } finally {
      spinnerOverlay.classList.remove("active");
      spinnerOverlay.setAttribute("aria-hidden", "true");
      isAnalyzing = false;
      analyzeBtn.disabled = false;
    }
  });

  function displayResults(
    score,
    method,
    heuristicResult,
    backendResult,
    apiError,
  ) {
    resultsContainer.classList.add("active");
    naturalizeSection.classList.add("active");
    fixWeakSection.classList.add("active");
    detailSection.classList.add("active");

    if (lastHeuristicDetails) {
      const warnings = countWarnings(lastHeuristicDetails);
      fixStatus.textContent =
        warnings > 0
          ? `${warnings} local signal(s) flagged`
          : "Signals within the expected range";
      fixWeakBtn.disabled = false;
    } else {
      fixStatus.textContent = "No heuristic data";
      fixWeakBtn.disabled = true;
    }

    const isModelResult = method === "backend-model";
    let classification, badgeClass;
    const decision = decisionFromScore(
      score,
      isModelResult ? backendResult.model.decision : null,
    );
    if (decision === "ai_like") {
      classification = isModelResult
        ? "Model: AI-Like"
        : "High AI-Like Signals";
      badgeClass = "badge-ai";
    } else if (decision === "human_like") {
      classification = isModelResult
        ? "Model: Human-Like"
        : "Low AI-Like Signals";
      badgeClass = "badge-human";
    } else {
      classification = "Inconclusive";
      badgeClass = "badge-uncertain";
    }
    const { scorePercent, scoreLabel, methodLabel } = createScorePresentation(
      score,
      isModelResult,
    );

    resultHeader.innerHTML = `<span class="result-badge ${badgeClass}">${classification}</span>
            <span style="font-weight:600;font-size:1.1rem;">${scoreLabel}</span>
            <span style="font-size:0.8rem;color:var(--text-secondary);">${isModelResult ? "🤖" : "🔍"} ${methodLabel}</span>`;

    setTimeout(() => {
      confidenceFill.style.width = `${scorePercent}%`;
      confidenceFill.style.background =
        score >= 0.65
          ? "linear-gradient(90deg,#f39c12,#e74c5e)"
          : score <= 0.35
            ? "linear-gradient(90deg,#2ecc71,#27ae60)"
            : "linear-gradient(90deg,#f39c12,#e67e22)";
      confidenceFill.setAttribute("aria-valuenow", String(scorePercent));
    }, 100);
    confidenceText.textContent = scoreLabel;

    const details = heuristicResult?.details;
    let indicatorsHTML = "";
    const metadata = backendResult?.metadata;
    if (metadata?.format) {
      const format = metadata.format;
      indicatorsHTML += `<div class="indicator-item good"><div class="indicator-icon">🗂️</div><div class="indicator-name">Verified file format</div><div class="indicator-value">${format.mime.replace("image/", "").toUpperCase()} · ${format.width}×${format.height}</div></div>`;
      indicatorsHTML += `<div class="indicator-item ${metadata.exif_present ? "good" : ""}"><div class="indicator-icon">📷</div><div class="indicator-name">Genuine EXIF</div><div class="indicator-value">${metadata.exif_present ? "Present" : "Not present"}</div></div>`;
    }
    if (isModelResult) {
      indicatorsHTML += `<div class="indicator-item" style="grid-column:1/-1;"><div class="indicator-icon">ℹ️</div><div class="indicator-name">Local heuristic context</div><div class="indicator-value" style="font-size:0.8rem;">The signals below are explanatory local checks and are not combined with the model score.</div></div>`;
    }
    if (details) {
      const cornerIcon =
        details.cornerTextureVariation > 0.6
          ? "✅"
          : details.cornerTextureVariation > 0.3
            ? "⚠️"
            : "❌";
      const cornerClass =
        details.cornerTextureVariation > 0.6
          ? "good"
          : details.cornerTextureVariation > 0.3
            ? ""
            : "warning";
      indicatorsHTML += `<div class="indicator-item ${cornerClass}"><div class="indicator-icon">${cornerIcon}</div><div class="indicator-name">Corner Texture Variation</div><div class="indicator-value">${Math.round(details.cornerTextureVariation * 100)}%</div></div>`;

      const noiseIcon =
        details.noiseScore < 0.4
          ? "✅"
          : details.noiseScore < 0.65
            ? "⚠️"
            : "❌";
      const noiseClass =
        details.noiseScore < 0.4
          ? "good"
          : details.noiseScore < 0.65
            ? ""
            : "warning";
      indicatorsHTML += `<div class="indicator-item ${noiseClass}"><div class="indicator-icon">${noiseIcon}</div><div class="indicator-name">Noise Consistency</div><div class="indicator-value">${Math.round(details.noiseScore * 100)}% anomaly</div></div>`;

      const colorIcon =
        details.colorStats.aiLikelihood < 0.3
          ? "✅"
          : details.colorStats.aiLikelihood < 0.5
            ? "⚠️"
            : "❌";
      const colorClass =
        details.colorStats.aiLikelihood < 0.3
          ? "good"
          : details.colorStats.aiLikelihood < 0.5
            ? ""
            : "warning";
      indicatorsHTML += `<div class="indicator-item ${colorClass}"><div class="indicator-icon">${colorIcon}</div><div class="indicator-name">Color Distribution</div><div class="indicator-value">${details.colorStats.clusterCount} clusters</div></div>`;

      const edgeIcon =
        details.edgeVariance < 0.4
          ? "✅"
          : details.edgeVariance < 0.6
            ? "⚠️"
            : "❌";
      const edgeClass =
        details.edgeVariance < 0.4
          ? "good"
          : details.edgeVariance < 0.6
            ? ""
            : "warning";
      indicatorsHTML += `<div class="indicator-item ${edgeClass}"><div class="indicator-icon">${edgeIcon}</div><div class="indicator-name">Edge Variance</div><div class="indicator-value">${Math.round(details.edgeVariance * 100)}%</div></div>`;

      const satIcon =
        details.saturationAnomaly < 0.35
          ? "✅"
          : details.saturationAnomaly < 0.55
            ? "⚠️"
            : "❌";
      const satClass =
        details.saturationAnomaly < 0.35
          ? "good"
          : details.saturationAnomaly < 0.55
            ? ""
            : "warning";
      indicatorsHTML += `<div class="indicator-item ${satClass}"><div class="indicator-icon">${satIcon}</div><div class="indicator-name">Saturation Profile</div><div class="indicator-value">${Math.round(details.saturationAnomaly * 100)}% anomaly</div></div>`;
    }
    if (apiError && method !== "backend-model") {
      indicatorsHTML += `<div class="indicator-item" style="grid-column:1/-1;border-color:rgba(243,156,18,0.4);background:rgba(243,156,18,0.04);"><div class="indicator-icon">⚠️</div><div class="indicator-name">Model status</div><div class="indicator-value" style="font-size:0.8rem;">${escapeHTML(apiError)}; local heuristic result shown</div></div>`;
    }
    if (method === "backend-model") {
      indicatorsHTML += `<div class="indicator-item good" style="grid-column:1/-1;"><div class="indicator-icon">🤖</div><div class="indicator-name">Primary detection result</div><div class="indicator-value" style="font-size:0.8rem;">Organika/sdxl-detector raw class score; not a calibrated probability. Local heuristics are shown separately.</div></div>`;
    }
    indicatorsGrid.innerHTML = indicatorsHTML;
    setWorkflowStage(3);
    consoleStatus.textContent = "Analysis workspace / inspection complete";
    resultsContainer.scrollIntoView({ behavior: "smooth", block: "nearest" });

    // Reset detailed view
    heatmapContainer.style.display = "none";
    reasoningText.style.display = "none";
    heatmapContainer.innerHTML = "";
    reasoningText.innerHTML = "";
  }

  // ── Naturalize (General Filter) ──
  naturalizeBtn.addEventListener("click", () => {
    if (!currentImageDataUrl) return;
    const img = new Image();
    img.onload = () => {
      naturalizeCanvas.width = img.naturalWidth;
      naturalizeCanvas.height = img.naturalHeight;
      const ctx = naturalizeCanvas.getContext("2d");
      ctx.drawImage(img, 0, 0);
      const imageData = ctx.getImageData(
        0,
        0,
        naturalizeCanvas.width,
        naturalizeCanvas.height,
      );
      const data = imageData.data;
      for (let i = 0; i < data.length; i += 4) {
        const noise = (Math.random() - 0.5) * 8;
        data[i] = Math.min(255, Math.max(0, data[i] + noise));
        data[i + 1] = Math.min(255, Math.max(0, data[i + 1] + noise));
        data[i + 2] = Math.min(255, Math.max(0, data[i + 2] + noise));
      }
      ctx.putImageData(imageData, 0, 0);
      const w = naturalizeCanvas.width,
        h = naturalizeCanvas.height;
      const cx = w / 2,
        cy = h / 2,
        maxDist = Math.sqrt(cx * cx + cy * cy);
      const imgData2 = ctx.getImageData(0, 0, w, h);
      const d2 = imgData2.data;
      for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
          const dx = x - cx,
            dy = y - cy;
          const dist = Math.sqrt(dx * dx + dy * dy);
          const factor = 1 - (dist / maxDist) * 0.15;
          const idx = (y * w + x) * 4;
          d2[idx] *= factor;
          d2[idx + 1] *= factor;
          d2[idx + 2] *= factor;
        }
      }
      ctx.putImageData(imgData2, 0, 0);
      const imgData3 = ctx.getImageData(0, 0, w, h);
      const orig = ctx.getImageData(0, 0, w, h);
      for (let y = 0; y < h; y++) {
        for (let x = 1; x < w - 1; x++) {
          const idx = (y * w + x) * 4;
          const redIdx = (y * w + (x - 1)) * 4;
          imgData3.data[idx] = orig.data[redIdx] * 0.1 + orig.data[idx] * 0.9;
        }
      }
      ctx.putImageData(imgData3, 0, 0);
      const grain = document.createElement("canvas");
      grain.width = w;
      grain.height = h;
      const gCtx = grain.getContext("2d");
      const gData = gCtx.createImageData(w, h);
      for (let i = 0; i < gData.data.length; i += 4) {
        const val = Math.random() < 0.3 ? 255 : 0;
        gData.data[i] = val;
        gData.data[i + 1] = val;
        gData.data[i + 2] = val;
        gData.data[i + 3] = 15;
      }
      gCtx.putImageData(gData, 0, 0);
      ctx.drawImage(grain, 0, 0);
      downloadLink.href = naturalizeCanvas.toDataURL("image/png");
      downloadLink.style.display = "inline-flex";
      showToast("Comparison variation is ready.");
    };
    img.src = currentImageDataUrl;
  });

  // ── Fix Weak Signals ──
  fixWeakBtn.addEventListener("click", () => {
    if (!currentImageDataUrl || !lastHeuristicDetails) return;
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement("canvas");
      canvas.width = img.naturalWidth;
      canvas.height = img.naturalHeight;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);
      const details = lastHeuristicDetails;

      if (details.noiseScore > 0.4) {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        const strength = Math.min(12, details.noiseScore * 20);
        for (let i = 0; i < data.length; i += 4) {
          const noise = (Math.random() - 0.5) * strength;
          data[i] = Math.min(255, Math.max(0, data[i] + noise));
          data[i + 1] = Math.min(255, Math.max(0, data[i + 1] + noise));
          data[i + 2] = Math.min(255, Math.max(0, data[i + 2] + noise));
        }
        ctx.putImageData(imageData, 0, 0);
      }
      if (details.colorStats.aiLikelihood > 0.3) {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
          data[i] = Math.min(
            255,
            Math.max(0, data[i] + (Math.random() - 0.5) * 8),
          );
          data[i + 1] = Math.min(
            255,
            Math.max(0, data[i + 1] + (Math.random() - 0.5) * 8),
          );
          data[i + 2] = Math.min(
            255,
            Math.max(0, data[i + 2] + (Math.random() - 0.5) * 8),
          );
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
          const r = data[i],
            g = data[i + 1],
            b = data[i + 2];
          const max = Math.max(r, g, b),
            min = Math.min(r, g, b);
          const sat = max > 0 ? (max - min) / max : 0;
          const targetSat = 0.35;
          const adjust = (targetSat - sat) * 0.3;
          const gray = (r + g + b) / 3;
          data[i] = Math.min(
            255,
            Math.max(0, gray + (r - gray) * (1 + adjust)),
          );
          data[i + 1] = Math.min(
            255,
            Math.max(0, gray + (g - gray) * (1 + adjust)),
          );
          data[i + 2] = Math.min(
            255,
            Math.max(0, gray + (b - gray) * (1 + adjust)),
          );
        }
        ctx.putImageData(imageData, 0, 0);
      }
      if (details.cornerTextureVariation < 0.4) {
        const w = canvas.width,
          h = canvas.height;
        const imageData = ctx.getImageData(0, 0, w, h);
        const cornerSize = Math.min(
          30,
          Math.floor(w * 0.1),
          Math.floor(h * 0.1),
        );
        const corners = [
          { x: 0, y: 0 },
          { x: w - cornerSize, y: 0 },
          { x: 0, y: h - cornerSize },
          { x: w - cornerSize, y: h - cornerSize },
        ];
        for (const corner of corners) {
          for (let dy = 0; dy < cornerSize; dy++) {
            for (let dx = 0; dx < cornerSize; dx++) {
              const idx = ((corner.y + dy) * w + (corner.x + dx)) * 4;
              const rand = (Math.random() - 0.5) * 6;
              imageData.data[idx] = Math.min(
                255,
                Math.max(0, imageData.data[idx] + rand),
              );
              imageData.data[idx + 1] = Math.min(
                255,
                Math.max(0, imageData.data[idx + 1] + rand),
              );
              imageData.data[idx + 2] = Math.min(
                255,
                Math.max(0, imageData.data[idx + 2] + rand),
              );
            }
          }
        }
        const borderWidth = 4;
        for (let y = 0; y < h; y++) {
          for (let x = 0; x < w; x++) {
            if (
              x < borderWidth ||
              x >= w - borderWidth ||
              y < borderWidth ||
              y >= h - borderWidth
            ) {
              const idx = (y * w + x) * 4;
              const rand = (Math.random() - 0.5) * 4;
              imageData.data[idx] = Math.min(
                255,
                Math.max(0, imageData.data[idx] + rand),
              );
              imageData.data[idx + 1] = Math.min(
                255,
                Math.max(0, imageData.data[idx + 1] + rand),
              );
              imageData.data[idx + 2] = Math.min(
                255,
                Math.max(0, imageData.data[idx + 2] + rand),
              );
            }
          }
        }
        ctx.putImageData(imageData, 0, 0);
      }

      const fixedDataUrl = canvas.toDataURL("image/png");
      previewImage.src = fixedDataUrl;
      currentImageDataUrl = fixedDataUrl;
      fixedDownloadLink.href = fixedDataUrl;
      fixedDownloadLink.style.display = "inline-flex";

      analyzeClientSide(fixedDataUrl).then((heuristicResult) => {
        lastHeuristicDetails = heuristicResult.details;
        lastScore = heuristicResult.score;
        displayResults(
          heuristicResult.score,
          "client-heuristics",
          heuristicResult,
          null,
          null,
        );
        showToast("Signal variation is ready and has been re-analyzed.");
      });
    };
    img.src = currentImageDataUrl;
  });

  function applyBlur(imageData, width, height, radius) {
    const copy = new Uint8ClampedArray(imageData.data);
    const output = new ImageData(width, height);
    const getPixel = (x, y) => {
      if (x < 0 || x >= width || y < 0 || y >= height) return [0, 0, 0, 0];
      const idx = (y * width + x) * 4;
      return [copy[idx], copy[idx + 1], copy[idx + 2], copy[idx + 3]];
    };
    for (let y = 0; y < height; y++) {
      for (let x = 0; x < width; x++) {
        let r = 0,
          g = 0,
          b = 0,
          a = 0,
          count = 0;
        for (let dy = -radius; dy <= radius; dy++) {
          for (let dx = -radius; dx <= radius; dx++) {
            const [pr, pg, pb, pa] = getPixel(x + dx, y + dy);
            r += pr;
            g += pg;
            b += pb;
            a += pa;
            count++;
          }
        }
        const idx = (y * width + x) * 4;
        output.data[idx] = r / count;
        output.data[idx + 1] = g / count;
        output.data[idx + 2] = b / count;
        output.data[idx + 3] = a / count;
      }
    }
    return output;
  }

  // ── Detailed Analysis (Heuristic Signal Map + Reasoning) ──
  detailedBtn.addEventListener("click", async () => {
    if (!currentImageDataUrl || !lastHeuristicDetails) {
      showToast("Please run an analysis first.");
      return;
    }
    const img = new Image();
    img.onload = async () => {
      const cellSize = 40;
      const w = img.naturalWidth,
        h = img.naturalHeight;
      const cols = Math.ceil(w / cellSize);
      const rows = Math.ceil(h / cellSize);
      const heatmapValues = new Array(rows)
        .fill(0)
        .map(() => new Array(cols).fill(0.5));

      const analyzeCanvas = document.createElement("canvas");
      analyzeCanvas.width = w;
      analyzeCanvas.height = h;
      const actx = analyzeCanvas.getContext("2d");
      actx.drawImage(img, 0, 0);

      for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
          const x = col * cellSize,
            y = row * cellSize;
          const cellW = Math.min(cellSize, w - x),
            cellH = Math.min(cellSize, h - y);
          const cellImageData = actx.getImageData(x, y, cellW, cellH);
          const cellScore = computeCellAIScore(
            cellImageData.data,
            cellW,
            cellH,
          );
          heatmapValues[row][col] = cellScore;
        }
      }

      const heatCanvas = document.createElement("canvas");
      heatCanvas.width = w;
      heatCanvas.height = h;
      const hctx = heatCanvas.getContext("2d");
      hctx.drawImage(img, 0, 0);
      hctx.fillStyle = "rgba(0,0,0,0.4)";
      hctx.fillRect(0, 0, w, h);

      const cellW_actual = w / cols,
        cellH_actual = h / rows;
      for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
          const val = heatmapValues[row][col];
          const red = Math.min(255, Math.floor(val * 255));
          const green = Math.min(255, Math.floor((1 - val) * 255));
          hctx.fillStyle = `rgba(${red},${green},0,0.5)`;
          hctx.fillRect(
            col * cellW_actual,
            row * cellH_actual,
            cellW_actual,
            cellH_actual,
          );
        }
      }

      heatmapContainer.innerHTML = "";
      heatCanvas.setAttribute("role", "img");
      heatCanvas.setAttribute(
        "aria-label",
        "Heuristic signal map. Red areas have higher local AI-like signals; green areas have lower signals.",
      );
      heatmapContainer.appendChild(heatCanvas);
      heatmapContainer.style.display = "block";

      const d = lastHeuristicDetails;
      let reasoning = "<strong>Key Indicators:</strong><ul>";
      if (d.cornerTextureVariation < 0.4)
        reasoning += "<li>Low texture variation in image corners</li>";
      if (d.noiseScore > 0.6)
        reasoning += "<li>Unnaturally consistent noise pattern</li>";
      if (d.colorStats.aiLikelihood > 0.4)
        reasoning += "<li>Synthetic color distribution (limited palette)</li>";
      if (d.edgeVariance > 0.5)
        reasoning += "<li>Abnormal edge sharpness variation</li>";
      if (d.saturationAnomaly > 0.4)
        reasoning += "<li>Unnatural saturation profile</li>";
      if (d.cornerTextureVariation > 0.6 && d.noiseScore < 0.4)
        reasoning +=
          "<li>Varied corner texture and less-uniform noise lower the local heuristic signal</li>";
      reasoning += "</ul><strong>Visual Patterns:</strong><br>";
      if (d.edgeVariance < 0.3)
        reasoning += "Smooth, uniform edges typical of AI.<br>";
      else if (d.edgeVariance > 0.7)
        reasoning += "Highly irregular edges, possible human photo.<br>";
      if (d.saturationAnomaly > 0.5)
        reasoning += "Overly saturated or desaturated regions.<br>";
      reasoning += `Overall score: ${Math.round(lastScore * 100)}% AI likelihood.`;

      reasoningText.innerHTML = reasoning;
      reasoningText.style.display = "block";
      showToast("Heuristic signal map is ready.");
    };
    img.src = currentImageDataUrl;
  });

  function computeCellAIScore(data, w, h) {
    let edgeSum = 0,
      count = 0;
    for (let y = 1; y < h - 1; y += 2) {
      for (let x = 1; x < w - 1; x += 2) {
        const idx = (y * w + x) * 4;
        const up = ((y - 1) * w + x) * 4;
        const left = (y * w + (x - 1)) * 4;
        const b = (data[idx] + data[idx + 1] + data[idx + 2]) / 3;
        const bu = (data[up] + data[up + 1] + data[up + 2]) / 3;
        const bl = (data[left] + data[left + 1] + data[left + 2]) / 3;
        edgeSum += Math.abs(b - bu) + Math.abs(b - bl);
        count++;
      }
    }
    const avgEdge = count ? edgeSum / count : 0;
    let noiseVar = 0,
      nCount = 0;
    for (let i = 0; i < data.length; i += 8) {
      const val = (data[i] + data[i + 1] + data[i + 2]) / 3;
      noiseVar += val * val;
      nCount++;
    }
    const avgBright = noiseVar / nCount;
    const edgeScore = Math.min(1, avgEdge / 60);
    const noiseScore = Math.min(1, Math.sqrt(avgBright) / 50);
    return 0.5 + (edgeScore - noiseScore) * 0.3;
  }

  document.addEventListener("keydown", (e) => {
    if (
      e.key === "Enter" &&
      currentFile &&
      !isAnalyzing &&
      document.activeElement === document.body
    )
      analyzeBtn.click();
  });

  console.log("🔍 AI Image Detector ready – full analysis mode");
})();
