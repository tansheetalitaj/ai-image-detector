export const IMAGE_LIMITS = Object.freeze({
  maxFileBytes: 10 * 1024 * 1024,
  maxWidth: 8192,
  maxHeight: 8192,
  maxPixels: 40_000_000,
  minDimension: 16,
});

export const ALLOWED_IMAGE_TYPES = Object.freeze([
  "image/jpeg",
  "image/png",
  "image/webp",
  "image/gif",
  "image/bmp",
  "image/tiff",
]);

export function validateUploadCandidate(file) {
  if (!file) {
    return {
      ok: false,
      code: "IMAGE_REQUIRED",
      message: "Choose an image and try again.",
    };
  }

  const extensionLooksValid = /\.(jpe?g|png|webp|gif|bmp|tiff?)$/i.test(
    file.name || "",
  );
  if (!ALLOWED_IMAGE_TYPES.includes(file.type) && !extensionLooksValid) {
    return {
      ok: false,
      code: "UNSUPPORTED_IMAGE_TYPE",
      message: "Please upload a JPG, PNG, WebP, GIF, BMP, or TIFF image.",
    };
  }

  if (
    !Number.isFinite(file.size) ||
    file.size < 1 ||
    file.size > IMAGE_LIMITS.maxFileBytes
  ) {
    return {
      ok: false,
      code: "INVALID_IMAGE_SIZE",
      message: "Image size must be between 1 byte and 10 MB.",
    };
  }

  return { ok: true };
}

export function validateImageDimensions({ width, height }) {
  if (!Number.isInteger(width) || !Number.isInteger(height)) {
    return {
      ok: false,
      code: "IMAGE_DECODE_FAILED",
      message: "The selected file could not be decoded as an image.",
    };
  }
  if (width < IMAGE_LIMITS.minDimension || height < IMAGE_LIMITS.minDimension) {
    return {
      ok: false,
      code: "IMAGE_TOO_SMALL",
      message: `Image must be at least ${IMAGE_LIMITS.minDimension}px on each side.`,
    };
  }
  if (
    width > IMAGE_LIMITS.maxWidth ||
    height > IMAGE_LIMITS.maxHeight ||
    width * height > IMAGE_LIMITS.maxPixels
  ) {
    return {
      ok: false,
      code: "IMAGE_TOO_LARGE",
      message:
        "Image is too large. Maximum: 8192px per side and 40 megapixels.",
    };
  }
  return { ok: true };
}

export function readImageDimensions(file) {
  return new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      URL.revokeObjectURL(objectUrl);
      resolve({ width: image.naturalWidth, height: image.naturalHeight });
    };
    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error("IMAGE_DECODE_FAILED"));
    };
    image.src = objectUrl;
  });
}

export function formatFileSize(bytes) {
  if (bytes < 1024 * 1024) {
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  }
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
