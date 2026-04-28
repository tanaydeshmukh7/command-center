---
name: Cyber-Medical Interface
colors:
  surface: '#131315'
  surface-dim: '#131315'
  surface-bright: '#39393b'
  surface-container-lowest: '#0e0e10'
  surface-container-low: '#1c1b1d'
  surface-container: '#201f21'
  surface-container-high: '#2a2a2c'
  surface-container-highest: '#353437'
  on-surface: '#e5e1e4'
  on-surface-variant: '#b9cacb'
  inverse-surface: '#e5e1e4'
  inverse-on-surface: '#313032'
  outline: '#849495'
  outline-variant: '#3a494b'
  surface-tint: '#00dbe7'
  primary: '#e1fdff'
  on-primary: '#00363a'
  primary-container: '#00f2ff'
  on-primary-container: '#006a71'
  inverse-primary: '#00696f'
  secondary: '#fface8'
  on-secondary: '#5e0053'
  secondary-container: '#ff24e4'
  on-secondary-container: '#520049'
  tertiary: '#fef5ff'
  on-tertiary: '#480081'
  tertiary-container: '#ead2ff'
  on-tertiary-container: '#8624de'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#74f5ff'
  primary-fixed-dim: '#00dbe7'
  on-primary-fixed: '#002022'
  on-primary-fixed-variant: '#004f54'
  secondary-fixed: '#ffd7f0'
  secondary-fixed-dim: '#fface8'
  on-secondary-fixed: '#3a0033'
  on-secondary-fixed-variant: '#840076'
  tertiary-fixed: '#efdbff'
  tertiary-fixed-dim: '#dcb8ff'
  on-tertiary-fixed: '#2c0051'
  on-tertiary-fixed-variant: '#6700b5'
  background: '#131315'
  on-background: '#e5e1e4'
  surface-variant: '#353437'
typography:
  h1:
    fontFamily: Space Grotesk
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  h2:
    fontFamily: Space Grotesk
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
  h3:
    fontFamily: Space Grotesk
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-caps:
    fontFamily: Space Grotesk
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1'
    letterSpacing: 0.1em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  xs: 4px
  sm: 12px
  md: 24px
  lg: 48px
  xl: 80px
  grid-margin: 32px
  grid-gutter: 24px
---

## Brand & Style

This design system reimagines healthcare administration as a high-performance, futuristic interface. It targets a Gen Z audience by blending the high-stakes precision of medical technology with a vibrant cyberpunk aesthetic. The brand personality is "Command Center"—it is energetic yet reliable, replacing the sterile, cold atmosphere of traditional hospitals with a dynamic, dark-mode environment that feels like a premium command center.

The visual style is a hybrid of **Glassmorphism** and **Cyberpunk Futurism**. It utilizes deep obsidian surfaces layered with translucent glass panels, accented by "light-pipe" borders and radioactive neon hues. The emotional response is one of empowerment and speed, turning resource management into a gamified, high-fidelity experience.

## Colors

The palette is anchored in a "Deep Space" black (`#0a0a0c`), providing a high-contrast foundation that allows neon accents to vibrate. 

- **Electric Blue (#00f2ff):** Used for primary actions, navigation, and "Stable" medical statuses.
- **Neon Pink (#ff00e5):** Reserved for urgent alerts, critical resource shortages, and "Emergency" indicators.
- **Deep Purple (#8a2be2):** Utilized for secondary information, data visualization overlays, and specialized department tagging.
- **Surface Treatment:** Backgrounds must incorporate a subtle 5% opacity grid overlay or a monochromatic noise texture to prevent "flatness" and enhance the futuristic hardware feel.

## Typography

This design system utilizes a dual-font strategy to balance character with readability. **Space Grotesk** is used for all headlines and labels to reinforce the "technical/futuristic" narrative; its geometric apertures feel like digital readouts. **Inter** is used for all body text and data-heavy tables to ensure medical information is processed without fatigue. 

Headlines should occasionally use "Glow" effects (a subtle text-shadow in the primary color) when used as primary page titles. All labels should be uppercase with increased tracking for a modular, tabulated appearance.

## Layout & Spacing

The layout follows a **fluid 12-column grid** with generous 32px external margins to ensure the "Glass" panels never feel cramped. We use an 8px base unit for all internal component spacing. 

Information density should be high but organized through clear containment. Group related medical resources into distinct glass modules. Use negative space strategically to ensure that neon glowing elements do not cause visual "vibration" or eye strain for the user.

## Elevation & Depth

Depth is achieved through **Glassmorphism tiers** rather than traditional drop shadows. 

1.  **Level 0 (Background):** Deep black with a 2px grid pattern.
2.  **Level 1 (Standard Card):** Background blur (20px), 3% white fill, and a 1px border at 10% white.
3.  **Level 2 (Active/Hover):** Background blur (30px), 1px border using a primary or secondary neon gradient, and a soft outer glow (`box-shadow: 0 0 15px rgba(primary, 0.3)`).
4.  **Level 3 (Modals/Popovers):** Higher saturation fill (8% white) and a persistent neon outer glow to indicate immediate priority.

All glass elements must use `backdrop-filter: blur()` to ensure content behind them remains illegible but color-contributory.

## Shapes

The shape language is defined by **large, friendly radii** contrasted with **precise, sharp internal elements**. 

Main containers and resource cards use a signature **24px radius** to soften the cyberpunk edge and make the app feel accessible (Gen Z "Soft-Tech"). Smaller components like buttons and inputs use tighter radii (12px and 8px respectively) to maintain a sense of structural integrity. Interactive elements should feel "squishy" and organic through the use of these rounded forms, even while their colors signal high-tech utility.

## Components

- **Neon Buttons:** High-saturation fills with a constant 5px outer glow. On hover, the button should scale by 1.05x and the glow should "pulse" using a CSS keyframe animation.
- **Glass Cards:** Semi-transparent containers with a "light-leak" effect—a subtle gradient on the border that suggests a light source from the top-left.
- **Glowing Charts:** Line charts should use 3px thick neon strokes with a "shadow" that mimics the line's path but with a blurred, glowing finish.
- **Status Tables:** Modern, borderless rows. Status indicators (e.g., "Available", "Full", "Critical") are displayed as "Neon Pills"—small capsules with high-contrast text and a glow that matches the status color.
- **Animated Inputs:** When focused, the input border should transition from a subtle grey to a full Electric Blue neon glow, with the label floating upwards and changing to the primary neon color.
- **Department Chips:** Small, dark-base chips with Deep Purple neon borders, used for filtering resources by medical specialty.