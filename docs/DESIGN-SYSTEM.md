# TraceLens design system

**Version:** 1.0
**Product:** TraceLens — Image Authenticity Lab
**Design principles:** evidence before certainty, quiet precision, progressive disclosure, accessible motion

## Identity

TraceLens is an experimental image-forensics workspace. Its voice is measured,
direct, and transparent about uncertainty. The interface may feel advanced, but
it must never make experimental output look legally or scientifically conclusive.

### Naming

- Product name: **TraceLens**
- Descriptor: **Image Authenticity Lab**
- Primary action: **Run authenticity scan**
- Process noun: **inspection**
- Output noun: **result** or **evidence**
- Uncertain state: **inconclusive**

Avoid “proof,” “verified AI,” “confidence,” and “probability” unless a future
model has been independently calibrated for that exact use.

## Tokens

Tokens are defined in `public/assets/css/app.css`.

| Category | Core tokens | Use |
| --- | --- | --- |
| Canvas | `--ink-1000` through `--ink-700` | Page, panels, raised surfaces |
| Text | `--white`, `--mist-100`, `--mist-300`, `--mist-500` | Heading, body, secondary, muted |
| Brand | `--cyan-500`, `--cyan-400`, `--violet-500` | Focus, primary actions, model channel |
| Semantic | `--green-500`, `--amber-500`, `--red-500` | Success, inconclusive/warning, error |
| Shape | `--radius-xs` through `--radius-lg` | Controls through major panels |
| Motion | `--transition` | Short interaction feedback |

Semantic colors must always be accompanied by text, an icon, or a shape change.

## Components

### Brand lockup

The geometric lens mark and two-line wordmark identify the product. Use the full
lockup in navigation and the compact `TL` mark where space is constrained.

### Buttons

| Variant | Use | Behavior |
| --- | --- | --- |
| Primary | One main action per region | Cyan fill, dark text |
| Quiet | Secondary or reversible action | Transparent surface, bordered |
| Tool | Optional analysis utility | Compact neutral control |

All buttons expose visible hover, keyboard focus, disabled, and loading states.

### Workflow step

Three states are supported: default, active, and done. The state uses text,
border, and number treatment—not color alone. JavaScript controls state through
the existing `active` and `done` classes.

### Upload zone

The entire dashed region is keyboard-focusable and responds to click, Enter,
Space, drag-and-drop, paste, and the hidden file input. Validation feedback is
announced through the global live-region toast.

### Status chip

Status chips combine a dot and text. They communicate backend readiness and the
active analysis source without implying a model is available when it is not.

### Evidence cards

Model, metadata, and local heuristic evidence remain visually distinct and are
described by their source. Result color is supportive context, never the only
carrier of meaning.

## Layout patterns

- `shell`: shared maximum width and responsive page gutter.
- `section-intro`: numbered section label plus heading and explanatory copy.
- `workspace-layout`: sticky workflow rail and primary analysis surface.
- `process-grid` and `method-grid`: three-column desktop grids collapsing to one
  column on smaller screens.

## Accessibility

- Minimum interactive target height: 44 pixels.
- Keyboard focus uses a high-contrast cyan outline with offset.
- The page includes a skip link and semantic landmark navigation.
- Motion is reduced to near-zero when `prefers-reduced-motion` is active.
- Upload, processing, results, and errors use existing ARIA live regions.
- Decorative scanner graphics are grouped into one descriptive image role.

## Migration notes

The public product identity changes from “AI Image Detector” to “TraceLens.” The
repository name, original local URL, backend endpoint, DOM IDs, and test selectors
remain unchanged for compatibility. Phase 4B may redesign the generated result
markup, but it should continue using these tokens and copy rules.
