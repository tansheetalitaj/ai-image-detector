import assert from "node:assert/strict";
import test from "node:test";
import {
  calculateHeuristicScore,
  validateBackendPayload,
} from "../../../public/assets/js/analysis.js";

test("heuristic scoring is deterministic and weighted", () => {
  const details = {
    cornerTextureVariation: 0.2,
    noiseScore: 0.6,
    colorStats: { aiLikelihood: 0.8 },
    edgeVariance: 0.4,
    saturationAnomaly: 0.3,
  };
  const first = calculateHeuristicScore(details);
  const second = calculateHeuristicScore(details);
  assert.ok(Math.abs(first - 0.595) < Number.EPSILON);
  assert.equal(second, first);
});

test("valid backend model payload is accepted", () => {
  const payload = {
    ok: true,
    metadata: {},
    model: {
      status: "ok",
      model_id: "Organika/sdxl-detector",
      label_schema: { artificial: 0, human: 1 },
      artificial_score: 0.71,
      decision: "ai_like",
      calibrated: false,
    },
  };
  assert.equal(validateBackendPayload(payload), payload);
});

test("invalid model score is rejected", () => {
  assert.throws(
    () =>
      validateBackendPayload({
        ok: true,
        metadata: {},
        model: {
          status: "ok",
          model_id: "Organika/sdxl-detector",
          label_schema: { artificial: 0, human: 1 },
          artificial_score: 1.2,
          decision: "ai_like",
          calibrated: false,
        },
      }),
    /BACKEND_MODEL_SCHEMA_INVALID/,
  );
});
