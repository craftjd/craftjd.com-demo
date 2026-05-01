---
title: About
description: Learn more about this site.
---

# About

Pages are flat files. A request to `/about` resolves to `resources/content/about.md`.

## Front matter

Optional YAML front matter at the top of each file sets the page title and meta description:

```yaml
---
title: Page Title
description: Meta description for search engines.
---
```

## Nested pages

Use subdirectories for nested URLs. For example, `resources/content/services/consulting.md` is served at `/services/consulting`.
