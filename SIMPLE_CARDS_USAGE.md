# Simple Cards Component Usage Guide

The simple-cards component has been enhanced to support flexible layouts and background color customization.

## Basic Usage (Backward Compatible)

The component works exactly as before for existing implementations:

```twig
{% include '_components/sections/simple-cards.twig' %}
```

## Advanced Usage with Parameters

### 4-Card Layout

To enable the 4-card layout (4 cards in a row on large screens):

```twig
{% include '_components/sections/simple-cards.twig' with {
  cardCount: 4
} %}
```

### Background Color Variants

#### Alternative Background (Primary Color)
```twig
{% include '_components/sections/simple-cards.twig' with {
  backgroundVariant: 'alt'
} %}
```

#### Dark Background
```twig
{% include '_components/sections/simple-cards.twig' with {
  backgroundVariant: 'dark'
} %}
```

#### Accent Background
```twig
{% include '_components/sections/simple-cards.twig' with {
  backgroundVariant: 'accent'
} %}
```

### Combined Usage

4-card layout with alternative background:

```twig
{% include '_components/sections/simple-cards.twig' with {
  cardCount: 4,
  backgroundVariant: 'alt'
} %}
```

### Custom CSS Classes

Add custom CSS classes for page-specific styling:

```twig
{% include '_components/sections/simple-cards.twig' with {
  customClass: 'my-custom-class'
} %}
```

## Responsive Behavior

### Default Layout (3 cards or fewer)
- **Large screens (1200px+)**: Auto-fit grid (typically 3 cards per row)
- **Medium screens (768px-1199px)**: 2 cards per row
- **Small screens (<768px)**: 1 card per row (stacked)

### 4-Card Layout
- **Large screens (1200px+)**: 4 cards in a row
- **Medium screens (992px-1199px)**: 2 cards per row
- **Small screens (769px-991px)**: 2 cards per row
- **Mobile (<768px)**: 1 card per row (stacked)

## Page-Specific Customization

You can also override the background color in page-specific CSS files:

```css
/* In your page-specific CSS file */
.features {
  --features-bg-color: #your-custom-color;
}
```

## CSS Classes Generated

The component automatically generates CSS classes based on parameters:

- `.features` - Base class (always present)
- `.features--four-cards` - Added when cardCount >= 4
- `.features--alt-bg` - Added when backgroundVariant = 'alt'
- `.features--dark-bg` - Added when backgroundVariant = 'dark'
- `.features--accent-bg` - Added when backgroundVariant = 'accent'
- Custom classes from the `customClass` parameter

## Examples for Different Pages

### Homepage (3 cards, default background)
```twig
{% include '_components/sections/simple-cards.twig' %}
```

### Services Page (4 cards, default background)
```twig
{% include '_components/sections/simple-cards.twig' with {
  cardCount: 4
} %}
```

### About Page (3 cards, alternative background)
```twig
{% include '_components/sections/simple-cards.twig' with {
  backgroundVariant: 'alt'
} %}
```

### Contact Page (4 cards, dark background)
```twig
{% include '_components/sections/simple-cards.twig' with {
  cardCount: 4,
  backgroundVariant: 'dark'
} %}
