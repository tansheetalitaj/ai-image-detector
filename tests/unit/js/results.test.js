import assert from "node:assert/strict";
import test from "node:test";
import {
  createScorePresentation,
  decisionFromScore,
  escapeHTML,
} from "../../../public/assets/js/results.js";

test("score boundaries include an inconclusive range", () => {
  assert.equal(decisionFromScore(0.35), "human_like");
  assert.equal(decisionFromScore(0.5), "inconclusive");
  assert.equal(decisionFromScore(0.65), "ai_like");
});

test("score copy never describes a probability", () => {
  assert.equal(
    createScorePresentation(0.72, true).scoreLabel,
    "72% model AI score",
  );
  assert.equal(
    createScorePresentation(0.72, false).scoreLabel,
    "72% experimental heuristic score",
  );
});

test("dynamic error values are escaped", () => {
  assert.equal(escapeHTML("<script>"), "&lt;script&gt;");
});
