# Product Manuscript Gallery Design

**Context**

The product extra area previously exposed a single `Iconografia` item backed by `iconography_image`, `iconography_caption`, and `iconography_link`. The ACF group now exposes a `Manuscrito` tab with a gallery field named `manuscript_gallery` that returns an array of image arrays.

**Goal**

Update the product theme integration so product pages and product loop extra content render the manuscript gallery instead of the old single iconography item.

**Design**

- Keep the existing product extra structure, but rename the helper and template variables from iconography to manuscript to match the current content model.
- Read `manuscript_gallery` from ACF and normalize it into a view model that only includes entries with a valid attachment ID.
- Render the manuscript area as a gallery of figures in both the single-product extra section and the loop slider extra content.
- Reuse existing product extra styling where it still applies, and adapt the manuscript block styles to support multiple images.

**Testing**

- Add a focused PHP test for the new helper return shape.
- Verify the helper returns `false` for empty galleries and a normalized image list for valid galleries.
- Run the new targeted test plus the existing PHP lint test command for the touched PHP files.
