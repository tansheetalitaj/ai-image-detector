<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$appConfig = require dirname(__DIR__) . '/config/app.php';
$modelConfig = require dirname(__DIR__) . '/config/model.php';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="TraceLens inspects image metadata, model output, and transparent local signals without pretending uncertainty is certainty.">
    <meta name="theme-color" content="#070b12">
    <title>TraceLens — Image Authenticity Lab</title>
    <link rel="stylesheet" href="/public/assets/css/app.css">
</head>
<body
    data-api-endpoint="<?= $escape($appConfig['api_path']) ?>"
    data-model-id="<?= $escape($modelConfig['id']) ?>"
    data-max-file-bytes="<?= $modelConfig['max_file_bytes'] ?>"
    data-max-width="<?= $modelConfig['max_width'] ?>"
    data-max-height="<?= $modelConfig['max_height'] ?>"
    data-max-pixels="<?= $modelConfig['max_pixels'] ?>"
>
<a class="skip-link" href="#mainContent">Skip to main content</a>
<div class="ambient-grid" aria-hidden="true"></div>
<div class="ambient-glow ambient-glow-one" aria-hidden="true"></div>
<div class="ambient-glow ambient-glow-two" aria-hidden="true"></div>

<header class="site-header">
    <div class="shell nav-shell">
        <a class="brand" href="#top" aria-label="TraceLens home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" role="img">
                    <path d="M7 8.5 16 3l9 5.5v15L16 29l-9-5.5z"></path>
                    <circle cx="16" cy="16" r="4.5"></circle>
                    <path d="M16 7v4M16 21v4M7 16h4M21 16h4"></path>
                </svg>
            </span>
            <span class="brand-copy"><strong>TraceLens</strong><small>Image authenticity lab</small></span>
        </a>
        <nav class="primary-nav" aria-label="Primary navigation">
            <a href="#how-it-works">How it works</a>
            <a href="#method">Method</a>
            <a href="#analyze">Workspace</a>
        </nav>
        <a class="nav-cta" href="#analyze">Analyze an image <span aria-hidden="true">↘</span></a>
    </div>
</header>

<main id="mainContent">
    <section class="hero shell" id="top" aria-labelledby="heroTitle">
        <div class="hero-copy">
            <p class="eyebrow"><span class="pulse-dot" aria-hidden="true"></span> Experimental forensic workspace</p>
            <h1 id="heroTitle">Read the signals.<br><span>Keep the verdict honest.</span></h1>
            <p class="hero-lede">TraceLens brings model output, original-file metadata, and transparent local checks into one careful review—without presenting a score as proof.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="#analyze">Start an analysis <span aria-hidden="true">→</span></a>
                <a class="button button-quiet" href="#how-it-works">See how it works</a>
            </div>
            <ul class="hero-assurances" aria-label="Product assurances">
                <li><span aria-hidden="true">✓</span> Explicit inconclusive range</li>
                <li><span aria-hidden="true">✓</span> Server-side credentials</li>
                <li><span aria-hidden="true">✓</span> Evidence separated by source</li>
            </ul>
        </div>

        <div class="hero-instrument" role="img" aria-label="Abstract image forensic scanner showing model, metadata, and local signal channels">
            <div class="instrument-topline"><span>TRACELENS / OPTICAL NODE</span><span class="instrument-live">LIVE SYSTEM</span></div>
            <div class="scanner-stage">
                <span class="scanner-corner corner-one"></span><span class="scanner-corner corner-two"></span>
                <span class="scanner-corner corner-three"></span><span class="scanner-corner corner-four"></span>
                <div class="scanner-orbit orbit-one"></div>
                <div class="scanner-orbit orbit-two"></div>
                <div class="scanner-core"><span>TL</span></div>
                <div class="scanner-line"></div>
                <span class="coordinate coordinate-one">X 042.18</span>
                <span class="coordinate coordinate-two">Y 771.04</span>
            </div>
            <div class="channel-list">
                <div><span class="channel-icon channel-model"></span><p><strong>Model channel</strong><small>Validated when configured</small></p><b>01</b></div>
                <div><span class="channel-icon channel-meta"></span><p><strong>Metadata channel</strong><small>Read from the original file</small></p><b>02</b></div>
                <div><span class="channel-icon channel-local"></span><p><strong>Local signal channel</strong><small>Explainable visual heuristics</small></p><b>03</b></div>
            </div>
        </div>
    </section>

    <section class="trust-rail" aria-label="Analysis principles">
        <div class="shell trust-rail-inner">
            <p><span>01</span><strong>Original-file checks</strong><small>Format, dimensions, and selected EXIF</small></p>
            <p><span>02</span><strong>Source-aware evidence</strong><small>Model and heuristic signals stay separate</small></p>
            <p><span>03</span><strong>Uncertainty by design</strong><small>No forced binary verdict</small></p>
        </div>
    </section>

    <section class="section shell" id="how-it-works" aria-labelledby="howTitle">
        <div class="section-intro">
            <p class="section-index">01 / Workflow</p>
            <div><h2 id="howTitle">A clear path from file to evidence.</h2><p>Three focused stages, with the origin of every signal made visible.</p></div>
        </div>
        <div class="process-grid">
            <article class="process-card">
                <span class="process-number">01</span><span class="process-glyph" aria-hidden="true">＋</span>
                <h3>Add the original image</h3>
                <p>Drop, browse, or paste a supported file. Original files preserve more useful metadata than screenshots.</p>
            </article>
            <article class="process-card">
                <span class="process-number">02</span><span class="process-glyph" aria-hidden="true">⌁</span>
                <h3>Inspect independent channels</h3>
                <p>The backend validates the file while model output and local visual heuristics remain clearly separated.</p>
            </article>
            <article class="process-card">
                <span class="process-number">03</span><span class="process-glyph" aria-hidden="true">◎</span>
                <h3>Review, don’t overclaim</h3>
                <p>Use the result as one piece of context. An inconclusive result is a valid and intentional outcome.</p>
            </article>
        </div>
    </section>

    <section class="workspace-section" id="analyze" aria-labelledby="workspaceTitle">
        <div class="shell">
            <div class="section-intro workspace-intro">
                <p class="section-index">02 / Workspace</p>
                <div><h2 id="workspaceTitle">Open a new inspection.</h2><p>Nothing is analyzed until you choose a file and start the scan.</p></div>
                <span class="system-chip"><i aria-hidden="true"></i> System ready</span>
            </div>

            <div class="workspace-layout">
                <aside class="workflow-sidebar" aria-label="Analysis progress">
                    <p class="sidebar-label">Analysis sequence</p>
                    <ol class="workflow-list">
                        <li class="workflow-step active" id="workflowStep1"><span class="step-number">01</span><div><strong>Select</strong><small>Choose an image</small></div></li>
                        <li class="workflow-step" id="workflowStep2"><span class="step-number">02</span><div><strong>Inspect</strong><small>Run the channels</small></div></li>
                        <li class="workflow-step" id="workflowStep3"><span class="step-number">03</span><div><strong>Review</strong><small>Read the evidence</small></div></li>
                    </ol>
                    <div class="sidebar-note"><span aria-hidden="true">i</span><p><strong>Use the source file</strong>Resizing, screenshots, and compression can remove useful evidence.</p></div>
                </aside>

                <div class="workspace-main">
                    <div class="main-card" id="mainCard">
                        <div class="spinner-overlay" id="spinnerOverlay" role="status" aria-live="polite" aria-hidden="true">
                            <div class="scan-loader" aria-hidden="true"><span></span></div>
                            <span class="spinner-text">Inspecting image channels…</span>
                            <small>Keep this tab open</small>
                        </div>
                        <div class="console-header">
                            <div><div class="console-kicker" id="consoleStatus">Analysis workspace / ready</div><h3>Image input</h3><p>Select one original image for the strongest available signal.</p></div>
                            <span class="local-badge" id="modeBadge"><i aria-hidden="true"></i> Backend ready</span>
                        </div>

                        <div class="upload-zone" id="uploadZone" role="button" tabindex="0" aria-controls="fileInput" aria-describedby="uploadHint">
                            <span class="upload-target" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 33V13m0 0-7 7m7-7 7 7"></path><path d="M11 31v5a3 3 0 0 0 3 3h20a3 3 0 0 0 3-3v-5"></path></svg></span>
                            <span class="upload-text">Drop an image into the lens</span>
                            <span class="upload-hint">or choose a file from your device</span>
                            <span class="upload-rule" id="uploadHint">JPG, PNG, WebP, GIF, BMP or TIFF <b>·</b> <?= $escape(number_format($modelConfig['max_file_bytes'] / 1048576, 1)) ?> MB maximum</span>
                            <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff" aria-label="Choose an image to analyze">
                        </div>

                        <div class="preview-area" id="previewArea">
                            <button type="button" class="remove-btn" id="removeBtn" title="Remove image" aria-label="Remove selected image">×</button>
                            <img id="previewImage" src="" alt="Selected image preview">
                        </div>
                        <div class="file-meta" id="fileMeta" aria-live="polite"><strong id="fileName"></strong><span id="fileDetails"></span></div>
                        <div class="action-row" id="actionRow" style="display:none">
                            <button type="button" class="button button-primary action-primary" id="analyzeBtn">Run authenticity scan <span aria-hidden="true">→</span></button>
                            <button type="button" class="button button-quiet" id="changeImageBtn">Choose another image</button>
                        </div>

                        <div class="results-container" id="resultsContainer" role="status" aria-live="polite" aria-atomic="true">
                            <div class="result-card">
                                <div class="result-kicker">Inspection result / interpret with care</div>
                                <div class="result-header" id="resultHeader"></div>
                                <div class="confidence-meter"><div class="confidence-fill" id="confidenceFill" style="width:0" role="progressbar" aria-label="AI signal score" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div></div>
                                <div class="confidence-label"><span>Lower AI-like signal</span><span id="confidenceText">—</span><span>Higher AI-like signal</span></div>
                                <div class="indicators-grid" id="indicatorsGrid"></div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="settings-toggle" id="settingsToggle" aria-expanded="false" aria-controls="settingsPanel"><span>Analysis configuration</span><span class="settings-arrow" aria-hidden="true">＋</span></button>
                    <div class="settings-panel" id="settingsPanel" aria-hidden="true">
                        <div><span class="config-label">Selected model</span><code><?= $escape($modelConfig['id']) ?></code></div>
                        <div><span class="config-label">Decision policy</span><p>Scores from <?= $escape((string) $modelConfig['thresholds']['human_max']) ?> to <?= $escape((string) $modelConfig['thresholds']['artificial_min']) ?> are reported as inconclusive.</p></div>
                        <p class="settings-note"><strong>Privacy:</strong> the original file reaches this application’s PHP backend for validation and metadata inspection. It is forwarded to Hugging Face only when the server administrator has configured a token.</p>
                    </div>
                </div>
            </div>

            <div class="analysis-tools">
                <div class="naturalize-section" id="naturalizeSection">
                    <span class="tool-tag">Comparison tool</span><h3>Texture variation</h3>
                    <p class="tool-copy">Create a lightly textured version for visual comparison. This does not prove or change authorship.</p>
                    <button type="button" class="tool-button" id="naturalizeBtn">Create comparison</button>
                    <a class="download-link" id="downloadLink" style="display:none" download="naturalized.png">Download variation</a>
                    <canvas id="naturalizeCanvas" style="display:none"></canvas>
                </div>
                <div class="fix-section" id="fixWeakSection">
                    <span class="tool-tag">Experimental tool</span><h3>Signal variation</h3>
                    <p class="tool-copy">Adjust properties flagged by local heuristics, then compare the experimental score.</p>
                    <button type="button" class="tool-button" id="fixWeakBtn">Create signal variation</button>
                    <span id="fixStatus" class="tool-status"></span>
                    <a class="download-link" id="fixedDownloadLink" style="display:none" download="signal-variation.png">Download variation</a>
                </div>
                <div class="detail-section" id="detailSection">
                    <span class="tool-tag">Evidence detail</span><h3>Heuristic signal map</h3>
                    <p class="tool-copy">See which regions contributed stronger or weaker local signals. This map does not come from the external model.</p>
                    <button type="button" class="tool-button" id="detailedBtn">Open signal map</button>
                    <div id="heatmapContainer" class="heatmap-container" style="display:none"></div>
                    <div id="reasoningText" class="reasoning-text" style="display:none"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section shell method-section" id="method" aria-labelledby="methodTitle">
        <div class="section-intro">
            <p class="section-index">03 / Method</p>
            <div><h2 id="methodTitle">Evidence has a source.</h2><p>TraceLens labels each channel so a polished interface never disguises experimental limitations.</p></div>
        </div>
        <div class="method-grid">
            <article><span class="method-code">M</span><h3>Model output</h3><p>A validated raw class score from the configured detector. It is not presented as a calibrated probability.</p><small>External · when configured</small></article>
            <article><span class="method-code">X</span><h3>File metadata</h3><p>Format, dimensions, size, and selected EXIF fields read from the original uploaded file.</p><small>Server-side · factual</small></article>
            <article><span class="method-code">H</span><h3>Local heuristics</h3><p>Deterministic texture, noise, color, edge, and saturation checks that provide context rather than proof.</p><small>Browser-side · experimental</small></article>
        </div>
        <div class="method-warning"><span aria-hidden="true">!</span><p><strong>No detector can establish authorship on its own.</strong> Editing, resizing, screenshots, and unfamiliar generators can all produce false positives or false negatives. Use this result as one review signal.</p></div>
    </section>
</main>

<footer class="site-footer">
    <div class="shell footer-inner">
        <a class="brand footer-brand" href="#top"><span class="brand-mark" aria-hidden="true">TL</span><span class="brand-copy"><strong>TraceLens</strong><small>Experimental image forensics</small></span></a>
        <p>Designed for careful review—not automatic judgment.</p>
        <nav aria-label="Footer navigation"><a href="#how-it-works">Workflow</a><a href="#method">Method</a><a href="#analyze">Analyze</a></nav>
    </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite" aria-atomic="true"></div>
<script type="module" src="/public/assets/js/app.js"></script>
</body>
</html>
