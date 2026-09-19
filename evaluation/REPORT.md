# Evaluation status

## Current result

The project evaluation set currently contains **0 labeled samples**. Precision,
recall, false-positive rate, inconclusive rate, and performance by image type are
therefore **not yet available**. Reporting numeric project metrics at this stage
would be misleading.

The evaluator intentionally fails when the manifest is empty, a referenced file
is missing, a label is invalid, or the backend does not return a validated model
result.

## Required coverage

The initial target is 225 provenance-tracked images, balanced across these
groups:

| Group | Target |
| --- | ---: |
| Photographs | 25 |
| Digital art | 25 |
| Edited images | 25 |
| Screenshots | 25 |
| SDXL outputs | 25 |
| Stable Diffusion 1.5 outputs | 25 |
| Midjourney outputs | 25 |
| GLIDE outputs | 25 |
| BigGAN outputs | 25 |

The exact plan is maintained in `coverage-plan.csv`. Every sample must also have
source and license information in `manifest.csv`.

## How to generate the report

After curating the files and setting `HUGGINGFACE_API_TOKEN`, run:

```powershell
php scripts/evaluate.php --manifest=evaluation/manifest.csv --endpoint=http://ai-image-detector.local/api/analyze.php
```

Save the generated Markdown output in this file only after reviewing manifest
coverage and any failed requests. Model-author benchmark numbers are documented
separately in `docs/MODEL.md`; they are not presented as measurements of this
application.
