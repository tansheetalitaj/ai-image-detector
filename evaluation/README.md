# Evaluation set

`manifest.csv` is the authoritative labeled evaluation set. It intentionally
starts empty: no image may be added without verified provenance, an explicit
ground-truth label, and a recorded source/license. Empty rows or invented scores
would make the resulting metrics misleading.

## Required coverage

`coverage-plan.csv` defines the minimum initial target: photographs, human-made
digital art, edited images, screenshots, and outputs from several generators.
The initial target is 225 images, balanced across the nine listed segments.

For broader generator evaluation, the official GenImage benchmark contains
real images plus Midjourney, Stable Diffusion 1.4/1.5, ADM, GLIDE, Wukong, VQDM,
and BigGAN output. GenImage is non-commercial and very large; select and record a
small, balanced test-only subset rather than committing the full dataset.

## Manifest rules

- `id`: stable unique identifier.
- `file`: path relative to the repository root.
- `label`: exactly `artificial` or `human`.
- `image_type`: `photograph`, `digital_art`, `edited_image`, `screenshot`, or `generated`.
- `generator`: generator name for synthetic images, otherwise the known source class.
- `edit_type`: `none` or a precise edit such as `jpeg-q65`, `crop`, or `color-grade`.
- `source`: source URL, asset ID, or first-party provenance record.
- `license`: license or permission basis.
- `split`: use `evaluation`; never mix training data into this set.
- `notes`: relevant capture, export, or curation details.

Do not place personal photographs, private screenshots, or unlicensed third-party
content in the repository.

## Running the evaluation

1. Configure `HUGGINGFACE_API_TOKEN` in the project-root `.env` file or the
   PHP/Apache environment.
2. Populate `manifest.csv` and place the referenced images under an ignored local
   evaluation-data directory or another approved path.
3. Start the local site.
4. Run:

```powershell
php scripts/evaluate.php --endpoint=http://ai-image-detector.local/api/analyze.php
```

The `--endpoint` option is optional. Without it, the runner uses
`EVALUATION_API_ENDPOINT`, or derives the endpoint from `APP_URL` and
`APP_API_PATH`.

The command reports coverage, precision, recall, false-positive rate,
inconclusive rate, and the same metrics grouped by `image_type`. It refuses to
report results if the manifest is empty, malformed, or missing files.
