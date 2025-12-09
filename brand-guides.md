# MiHi Brand Style Guide & Design System

> **INSTRUCTIONS FOR AI AGENTS:**
> Strict adherence to Section 4 (Unified Heading Logic) is required.
> * **Font Family:** All Headings (H1-H6) must use `Azo Sans Uber`.
> * **Text Transform:** All Headings must be **UPPERCASE**.
> * **Responsiveness:** Use the defined Mobile -> Desktop sizing scales.

---

## 1. Brand Direction
**Essence:** Elegant Urban  
**Visual Style:** High-contrast, neon-on-dark (or neon-on-white), bold typography.  
**Core Logic:** Deep neutrals for structure; Coral and Aqua for energy.

---

## 2. Color Palette & Variables
*Derived from the HTML source code.*

| Role | Color Name | HEX | Usage |
| :--- | :--- | :--- | :--- |
| **Primary Background** | Carbon Black | `#1F1F1F` | Dark sections, body text on light backgrounds. |
| **Primary Accent** | **Vibrant Coral** | `#FF4F4F` | Buttons, H4/H5 Headers, "New" Tags, Hover states. |
| **Secondary Accent** | **Electric Aqua** | `#18F1E1` | H1 Highlights, Secondary Buttons, Icons. |
| **Canvas White** | Pure White | `#FFFFFF` | Text on dark backgrounds, Light section backgrounds. |

---

## 3. Typography Families

### Header Font
* **Name:** `Azo Sans Uber`
* **CSS Class:** `.font-uber`
* **Characteristics:** Uppercase, Regular Weight (400), Letter-spacing `0.02em`, Line-height `1.2`.

### Body Font
* **Name:** `Azo Sans`
* **CSS Class:** `.font-sans`
* **Characteristics:** Standard case, clean readability.

---

## 4. Unified Heading Logic (Strict Implementation)

*Based on `index.html` Tailwind classes (`text-4xl`, `md:text-7xl`, etc.).*

| Tag | Component Role | Mobile Size | Desktop Size | Color Logic |
| :--- | :--- | :--- | :--- | :--- |
| **H1** | **Hero Title** | `4xl` (36px) | `8xl` (96px) | **White** (spans **Aqua** `#18F1E1`) |
| **H2** | **Section Headers** | `3xl` (30px) | `5xl` (48px) | **Neutral** (White on Dark / Black on Light) |
| **H3** | **Card Titles** | `2xl` (24px) | `3xl` (30px) | **Neutral** (White on Dark / Black on Light) |
| **H4** | **Feature Headers** | `xl` (20px) | `xl` (20px) | **Coral** `#FF4F4F` (Accent Color) |
| **H5** | **Labels/Tags** | `lg` (18px) | `lg` (18px) | **Coral** `#FF4F4F` or White |
| **H6** | **Meta/Tiny** | `sm` (14px) | `base` (16px) | Neutral / Muted |

---

## 5. CSS / Tailwind Configuration

If you are using Tailwind, add these to your `tailwind.config.js` or apply via CSS variables to ensure the AI uses the exact sizing.

### CSS Variables (Copy-Paste)
```css
:root {
  /* Colors */
  --mihi-black: #1F1F1F;
  --mihi-coral: #FF4F4F;
  --mihi-aqua:  #18F1E1;
  --mihi-white: #FFFFFF;

  /* Fonts */
  --font-header: 'Azo Sans Uber', sans-serif;
  --font-body:   'Azo Sans', sans-serif;
}

/* Global Typography Reset */
h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-header);
  text-transform: uppercase;
  letter-spacing: 0.02em;
  line-height: 1.2;
  font-weight: 400; /* Font is naturally bold */
}

/* H1: Hero - Mobile 36px / Desktop 96px */
h1 {
  font-size: 2.25rem; /* text-4xl */
  color: var(--mihi-white);
}
@media (min-width: 1024px) {
  h1 { font-size: 6rem; /* text-8xl */ }
}

/* H2: Section Titles - Mobile 30px / Desktop 48px */
h2 {
  font-size: 1.875rem; /* text-3xl */
  margin-bottom: 1.5rem;
}
@media (min-width: 768px) {
  h2 { font-size: 3rem; /* text-5xl */ }
}

/* H3: Card Titles - Mobile 24px / Desktop 30px */
h3 {
  font-size: 1.5rem; /* text-2xl */
  margin-bottom: 1rem;
}
@media (min-width: 768px) {
  h3 { font-size: 1.875rem; /* text-3xl */ }
}

/* H4: Feature Highlights - 20px (Coral) */
h4 {
  font-size: 1.25rem; /* text-xl */
  color: var(--mihi-coral);
  margin-bottom: 0.5rem;
}

/* H5: Labels - 18px (Coral) */
h5 {
  font-size: 1.125rem; /* text-lg */
  color: var(--mihi-coral);
}