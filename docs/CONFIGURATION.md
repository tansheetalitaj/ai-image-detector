# Environment configuration

TraceLens loads runtime configuration from the project-root `.env` file through
`vlucas/phpdotenv`. Copy `.env.example` to `.env` and edit the copy:

```powershell
Copy-Item .env.example .env
```

`.env` is ignored by Git. Never add real credentials to `.env.example`, source
control, frontend JavaScript, HTML attributes, screenshots, or support logs.

## Precedence

Existing process, operating-system, Apache, and deployment-platform variables
take precedence. The local `.env` file supplies only values that are not already
defined. This makes the same configuration layer suitable for local XAMPP and
hosted environments.

## Variables

| Variable | Default | Purpose |
| --- | --- | --- |
| `APP_ENV` | `local` | Runtime environment name. |
| `APP_DEBUG` | `false` | Local debug mode flag. Keep false in production. |
| `APP_URL` | `http://ai-image-detector.local` | Base application URL. |
| `APP_API_PATH` | `/api/analyze.php` | Browser-facing analysis endpoint. |
| `HUGGINGFACE_API_TOKEN` | empty | Server-only Hugging Face access token. |
| `HUGGINGFACE_MODEL_ID` | `Organika/sdxl-detector` | Expected model identifier. |
| `HUGGINGFACE_API_ENDPOINT` | derived from model ID | Server-side inference URL. |
| `MODEL_HUMAN_MAX_SCORE` | `0.35` | Highest score reported as human-like. |
| `MODEL_ARTIFICIAL_MIN_SCORE` | `0.65` | Lowest score reported as AI-like. |
| `MODEL_CONNECT_TIMEOUT_SECONDS` | `5` | Inference connection timeout. |
| `MODEL_REQUEST_TIMEOUT_SECONDS` | `30` | Overall inference timeout. |
| `UPLOAD_MAX_FILE_BYTES` | `10485760` | Maximum image size in bytes. |
| `UPLOAD_MAX_WIDTH` | `8192` | Maximum decoded image width. |
| `UPLOAD_MAX_HEIGHT` | `8192` | Maximum decoded image height. |
| `UPLOAD_MAX_PIXELS` | `40000000` | Maximum total decoded pixels. |
| `EVALUATION_API_ENDPOINT` | app URL + API path | Evaluation runner endpoint. |

Numeric and boolean values are parsed strictly. Invalid values stop startup with
a configuration error instead of silently weakening limits. The human maximum
must remain lower than the artificial minimum so the inconclusive band is valid.

## Configuration boundaries

- `bootstrap.php` loads Composer and `.env` once for web, CLI, and tests.
- `config/app.php` contains application and routing settings.
- `config/model.php` contains public model policy and server request limits.
- `config/services.php` contains server-only credentials.
- PHP renders only the analysis path and expected model ID for the browser.
  Secrets are never included in client configuration.

After changing `.env` under XAMPP, reload the page. If a value is defined at the
Apache or operating-system level, restart Apache after changing that external
value.
