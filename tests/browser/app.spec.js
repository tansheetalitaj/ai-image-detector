import { expect, test } from "@playwright/test";

function createBmp(width = 16, height = 16) {
  const rowSize = Math.ceil((width * 3) / 4) * 4;
  const pixelBytes = rowSize * height;
  const buffer = Buffer.alloc(54 + pixelBytes);
  buffer.write("BM", 0, 2, "ascii");
  buffer.writeUInt32LE(buffer.length, 2);
  buffer.writeUInt32LE(54, 10);
  buffer.writeUInt32LE(40, 14);
  buffer.writeInt32LE(width, 18);
  buffer.writeInt32LE(height, 22);
  buffer.writeUInt16LE(1, 26);
  buffer.writeUInt16LE(24, 28);
  buffer.writeUInt32LE(pixelBytes, 34);
  for (let offset = 54; offset < buffer.length; offset += 3) {
    buffer[offset] = 80;
    buffer[offset + 1] = 140;
    buffer[offset + 2] = 210;
  }
  return buffer;
}

async function uploadValidImage(page) {
  await page.locator("#fileInput").setInputFiles({
    name: "browser-test.bmp",
    mimeType: "image/bmp",
    buffer: createBmp(),
  });
  await expect(page.locator("#previewArea")).toHaveClass(/active/);
}

test.beforeEach(async ({ page }) => {
  await page.goto("/ai-image-detector.php");
});

test("presents the TraceLens identity and routes users to the workspace", async ({
  page,
}) => {
  await expect(page.locator(".brand").first()).toContainText("TraceLens");
  await expect(page.getByRole("heading", { level: 1 })).toContainText(
    "Read the signals",
  );
  await expect(
    page.getByRole("link", { name: /start an analysis/i }),
  ).toHaveAttribute("href", "#analyze");
  await expect(
    page.getByRole("navigation", { name: "Primary navigation" }),
  ).toBeVisible();
});

test("fits the landing page within a mobile viewport", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.reload();
  const hasHorizontalOverflow = await page.evaluate(
    () =>
      globalThis.document.documentElement.scrollWidth > globalThis.innerWidth,
  );
  expect(hasHorizontalOverflow).toBe(false);
  await expect(page.locator("#uploadZone")).toBeVisible();
});

test("uploads an image and exposes the analysis action", async ({ page }) => {
  await uploadValidImage(page);
  await expect(page.locator("#fileName")).toHaveText("browser-test.bmp");
  await expect(page.locator("#analyzeBtn")).toBeVisible();
});

test("reset returns the interface to its initial state", async ({ page }) => {
  await uploadValidImage(page);
  await page.locator("#removeBtn").click();
  await expect(page.locator("#uploadZone")).toBeVisible();
  await expect(page.locator("#previewArea")).not.toHaveClass(/active/);
  await expect(page.locator("#actionRow")).toBeHidden();
});

test("invalid upload produces a user-facing error", async ({ page }) => {
  await page.locator("#fileInput").setInputFiles({
    name: "not-an-image.txt",
    mimeType: "text/plain",
    buffer: Buffer.from("not an image"),
  });
  await expect(page.locator("#toast")).toContainText("Please upload");
});

test("backend failure unlocks the UI and shows the local fallback", async ({
  page,
}) => {
  await page.route("**/api/analyze.php", (route) =>
    route.fulfill({
      status: 500,
      contentType: "application/json",
      body: JSON.stringify({ ok: false, error: { code: "TEST_FAILURE" } }),
    }),
  );
  await uploadValidImage(page);
  await page.locator("#analyzeBtn").click();
  await expect(page.locator("#resultHeader")).toContainText(
    "experimental heuristic score",
  );
  await expect(page.locator("#indicatorsGrid")).toContainText(
    "local heuristic result shown",
  );
  await expect(page.locator("#analyzeBtn")).toBeEnabled();
});
