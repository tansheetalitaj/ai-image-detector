export const MODEL_ID = "Organika/sdxl-detector";

export function calculateHeuristicScore(details) {
  if (!details || !details.colorStats) {
    return 0.5;
  }

  const weightedSignals = [
    [1 - details.cornerTextureVariation, 0.15],
    [details.noiseScore, 0.25],
    [details.colorStats.aiLikelihood, 0.25],
    [details.edgeVariance, 0.2],
    [details.saturationAnomaly, 0.15],
  ];

  const weightedTotal = weightedSignals.reduce(
    (total, [value, weight]) => total + value * weight,
    0,
  );
  const totalWeight = weightedSignals.reduce(
    (total, [, weight]) => total + weight,
    0,
  );
  return Math.max(0, Math.min(1, weightedTotal / totalWeight));
}

export function validateBackendPayload(payload) {
  if (
    !payload ||
    payload.ok !== true ||
    typeof payload.metadata !== "object" ||
    typeof payload.model !== "object"
  ) {
    throw new Error("BACKEND_RESPONSE_INVALID");
  }

  if (payload.model.status !== "ok") {
    return payload;
  }

  const model = payload.model;
  const schemaValid =
    model.model_id === MODEL_ID &&
    model.label_schema?.artificial === 0 &&
    model.label_schema?.human === 1 &&
    Number.isFinite(model.artificial_score) &&
    model.artificial_score >= 0 &&
    model.artificial_score <= 1 &&
    ["ai_like", "human_like", "inconclusive"].includes(model.decision) &&
    model.calibrated === false;

  if (!schemaValid) {
    throw new Error("BACKEND_MODEL_SCHEMA_INVALID");
  }
  return payload;
}

export async function callBackendAPI(
  file,
  endpoint = "/api/analyze.php",
  fetchImpl = fetch,
) {
  const formData = new FormData();
  formData.append("image", file, file.name || "pasted-image");

  let response;
  try {
    response = await fetchImpl(endpoint, {
      method: "POST",
      body: formData,
      headers: { Accept: "application/json" },
    });
  } catch (error) {
    throw new Error("BACKEND_NETWORK_ERROR", { cause: error });
  }

  let payload;
  try {
    payload = await response.json();
  } catch (error) {
    throw new Error("BACKEND_JSON_INVALID", { cause: error });
  }

  if (!response.ok) {
    throw new Error(
      payload?.error?.code || `BACKEND_HTTP_ERROR:${response.status}`,
    );
  }

  return validateBackendPayload(payload);
}
