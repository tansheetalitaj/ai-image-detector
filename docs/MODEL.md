# Detection model contract

## Selected model

- Model: [`Organika/sdxl-detector`](https://huggingface.co/Organika/sdxl-detector)
- Task: binary image classification
- Architecture: Swin image classifier, 224 × 224 model input
- License: CC BY-NC 3.0
- Intended use in this project: experimental, personal, educational, and non-commercial review

This model is the updated successor recommended by the original
`umm-maybe/AI-image-detector` model card. Its author reports improved performance
on recent diffusion output and broader non-artistic imagery. The model card also
warns that performance may be lower for generators other than SDXL and for older
generators.

The selected model is not evidence of authorship and is not suitable for
commercial deployment without a separate licensing review.

## Label schema

The model's published `config.json` defines exactly two labels:

| Model index | Label | Application meaning |
| --- | --- | --- |
| `0` | `artificial` | AI-like model class |
| `1` | `human` | Human-like model class |

The backend rejects responses that:

- are not a two-item array;
- omit `label` or `score`;
- contain unknown or duplicate labels;
- contain non-numeric, non-finite, or out-of-range scores;
- do not contain both required labels; or
- have scores whose sum differs from 1 by more than 0.05.

Hugging Face documents image-classification responses as an array of objects
with `label` and `score` fields. Validation occurs again in the browser before a
model result is displayed.

## Decision policy

The raw `artificial` class score is not treated as a calibrated probability.

| Artificial score | Decision |
| --- | --- |
| `0.00`–`0.35` | `human_like` |
| above `0.35` and below `0.65` | `inconclusive` |
| `0.65`–`1.00` | `ai_like` |

These operating thresholds are application policy, not thresholds published or
calibrated by the model author. They must be revisited after the local evaluation
set is populated and measured.

## Published metrics versus project metrics

The model card reports accuracy `0.9813`, precision `0.9945`, recall `0.9529`,
F1 `0.9733`, and AUC `0.9980` on its own validation data. Those figures are
author-reported and must not be presented as this application's measured
performance. The card does not provide the confusion matrix needed to calculate
its false-positive rate, nor does it provide performance by this project's image
types.

Project metrics are generated only from `evaluation/manifest.csv` by
`scripts/evaluate.php`.

## Data flow

```text
Browser
  └─ multipart image → api/analyze.php
       ├─ validates bytes, MIME type, dimensions, and decodeability
       ├─ reads original-file EXIF and format metadata
       └─ when HUGGINGFACE_API_TOKEN is configured
            └─ raw bytes → Hugging Face inference router
                 └─ strict label/score validation → browser
```

The token remains server-side. GPS and embedded thumbnail EXIF data are
deliberately excluded from the response.

## Primary references

- Model card: https://huggingface.co/Organika/sdxl-detector
- Model configuration: https://huggingface.co/Organika/sdxl-detector/blob/main/config.json
- Preprocessor configuration: https://huggingface.co/Organika/sdxl-detector/blob/main/preprocessor_config.json
- Hugging Face image-classification API schema: https://huggingface.co/docs/inference-providers/tasks/image-classification
- GenImage benchmark: https://github.com/GenImage-Dataset/GenImage
