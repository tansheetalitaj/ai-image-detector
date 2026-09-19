import assert from "node:assert/strict";
import test from "node:test";
import {
  validateImageDimensions,
  validateUploadCandidate,
} from "../../../public/assets/js/upload.js";

test("upload candidate rejects non-images and oversized files", () => {
  assert.equal(
    validateUploadCandidate({ name: "notes.txt", type: "text/plain", size: 20 })
      .code,
    "UNSUPPORTED_IMAGE_TYPE",
  );
  assert.equal(
    validateUploadCandidate({
      name: "photo.png",
      type: "image/png",
      size: 11 * 1024 * 1024,
    }).code,
    "INVALID_IMAGE_SIZE",
  );
});

test("dimension limits reject small and decompression-heavy images", () => {
  assert.equal(
    validateImageDimensions({ width: 8, height: 8 }).code,
    "IMAGE_TOO_SMALL",
  );
  assert.equal(
    validateImageDimensions({ width: 8000, height: 8000 }).code,
    "IMAGE_TOO_LARGE",
  );
  assert.deepEqual(validateImageDimensions({ width: 1024, height: 768 }), {
    ok: true,
  });
});

test("upload validation accepts server-provided limits", () => {
  const limits = {
    maxFileBytes: 100,
    maxWidth: 100,
    maxHeight: 80,
    maxPixels: 8_000,
    minDimension: 16,
  };

  assert.equal(
    validateUploadCandidate(
      { name: "photo.png", type: "image/png", size: 101 },
      limits,
    ).code,
    "INVALID_IMAGE_SIZE",
  );
  assert.equal(
    validateImageDimensions({ width: 101, height: 40 }, limits).code,
    "IMAGE_TOO_LARGE",
  );
});
