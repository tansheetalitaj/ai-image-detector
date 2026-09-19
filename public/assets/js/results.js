export function escapeHTML(value) {
  return String(value).replace(
    /[&<>'"]/g,
    (character) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "'": "&#39;",
        '"': "&quot;",
      })[character],
  );
}

export function decisionFromScore(score, modelDecision = null) {
  if (["ai_like", "human_like", "inconclusive"].includes(modelDecision)) {
    return modelDecision;
  }
  if (score >= 0.65) return "ai_like";
  if (score <= 0.35) return "human_like";
  return "inconclusive";
}

export function createScorePresentation(score, isModelResult) {
  const scorePercent = Math.round(score * 100);
  return {
    scorePercent,
    scoreLabel: isModelResult
      ? `${scorePercent}% model AI score`
      : `${scorePercent}% experimental heuristic score`,
    methodLabel: isModelResult
      ? "Validated backend model output"
      : "Local heuristics only",
  };
}

export function countWarnings(details) {
  if (!details) return 0;
  let count = 0;
  if (details.noiseScore > 0.4) count++;
  if (details.colorStats.aiLikelihood > 0.3) count++;
  if (details.edgeVariance > 0.4) count++;
  if (details.saturationAnomaly > 0.35) count++;
  if (details.cornerTextureVariation < 0.4) count++;
  return count;
}
